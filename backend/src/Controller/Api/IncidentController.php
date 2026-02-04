<?php

namespace App\Controller\Api;

use App\Entity\Incident;
use App\Entity\IncidentCategory;
use App\Entity\IncidentComment;
use App\Entity\User;
use App\Repository\IncidentRepository;
use App\Repository\IncidentCategoryRepository;
use App\Repository\IncidentCommentRepository;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use App\Service\IncidentCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/incidents')]
class IncidentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private IncidentRepository $incidentRepository,
        private IncidentCategoryRepository $categoryRepository,
        private IncidentCommentRepository $commentRepository,
        private UserRepository $userRepository,
        private AuditLogService $auditLogService,
        private IncidentCalendarService $incidentCalendarService
    ) {}

    /**
     * Liste les incidents visibles par l'utilisateur
     */
    #[Route('', name: 'api_incidents_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur n'est pas un élève
        if ($user->getRole() === 'Élève') {
            return $this->json([
                'error' => 'Les élèves n\'ont pas accès au système de signalement'
            ], Response::HTTP_FORBIDDEN);
        }

        $filters = [
            'status' => $request->query->get('status'),
            'priority' => $request->query->get('priority'),
            'category' => $request->query->get('category'),
            'search' => $request->query->get('search'),
        ];
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(1, $request->query->getInt('limit', 20)));

        // Si l'utilisateur est admin, il voit tout
        $isAdmin = $user->isAdmin();
        
        if ($isAdmin) {
            $incidents = $this->incidentRepository->findAllPaginated($filters, $page, $limit);
            $total = $this->incidentRepository->countAll($filters);
        } else {
            $incidents = $this->incidentRepository->findVisibleBy($user, $filters, $page, $limit);
            $total = $this->incidentRepository->countVisibleBy($user, $filters);
        }

        return $this->json([
            'incidents' => array_map(fn($i) => $this->serializeIncident($i), $incidents),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Récupère un incident par son ID
     */
    #[Route('/{id}', name: 'api_incidents_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function get(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRole() === 'Élève') {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $incident = $this->incidentRepository->find($id);
        
        if (!$incident) {
            return $this->json(['error' => 'Incident non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier l'accès
        if (!$user->isAdmin() && !$incident->isVisibleTo($user)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer les commentaires
        $comments = $this->commentRepository->findByIncident($incident, $user);

        return $this->json([
            'incident' => $this->serializeIncident($incident, true),
            'comments' => array_map(fn($c) => $this->serializeComment($c), $comments)
        ]);
    }

    /**
     * Crée un nouvel incident
     */
    #[Route('', name: 'api_incidents_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRole() === 'Élève') {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['title']) || empty($data['description']) || empty($data['categoryId'])) {
            return $this->json([
                'error' => 'Le titre, la description et la catégorie sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = $this->categoryRepository->find($data['categoryId']);
        if (!$category) {
            return $this->json(['error' => 'Catégorie non trouvée'], Response::HTTP_BAD_REQUEST);
        }

        $incident = new Incident();
        $incident->setTitle(trim($data['title']));
        $incident->setDescription(trim($data['description']));
        $incident->setCategory($category);
        $incident->setReporter($user);

        if (!empty($data['location'])) {
            $incident->setLocation(trim($data['location']));
        }

        if (!empty($data['priority']) && in_array($data['priority'], [
            Incident::PRIORITY_LOW, Incident::PRIORITY_MEDIUM, 
            Incident::PRIORITY_HIGH, Incident::PRIORITY_URGENT
        ])) {
            $incident->setPriority($data['priority']);
        }

        // Assignation
        if (!empty($data['assigneeId'])) {
            $assignee = $this->userRepository->find($data['assigneeId']);
            if ($assignee) {
                $incident->setAssignee($assignee);
            }
        } elseif (!empty($data['assigneeRole'])) {
            $incident->setAssigneeRole($data['assigneeRole']);
        } elseif ($category->getDefaultAssigneeRole()) {
            // Utiliser le rôle par défaut de la catégorie
            $incident->setAssigneeRole($category->getDefaultAssigneeRole());
        }

        $this->em->persist($incident);
        $this->em->flush();

        // Créer un événement dans le calendrier des incidents
        try {
            $this->incidentCalendarService->createEventForIncident($incident, $user);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer la création de l'incident
            error_log('Erreur création événement calendrier incident: ' . $e->getMessage());
        }

        // Log de création
        $this->auditLogService->logIncidentCreated(
            $incident->getId(),
            [
                'title' => $incident->getTitle(),
                'category' => $category->getName(),
                'priority' => $incident->getPriority(),
                'reporter' => $user->getFullName(),
                'assignee' => $incident->getAssignee()?->getFullName(),
                'assigneeRole' => $incident->getAssigneeRole()
            ],
            $user
        );

        return $this->json([
            'message' => 'Incident créé avec succès',
            'incident' => $this->serializeIncident($incident)
        ], Response::HTTP_CREATED);
    }

    /**
     * Met à jour un incident
     */
    #[Route('/{id}', name: 'api_incidents_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRole() === 'Élève') {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $incident = $this->incidentRepository->find($id);
        
        if (!$incident) {
            return $this->json(['error' => 'Incident non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les droits de modification
        if (!$user->isAdmin() && !$incident->isEditableBy($user)) {
            return $this->json(['error' => 'Vous ne pouvez pas modifier cet incident'], Response::HTTP_FORBIDDEN);
        }

        // Capturer l'état avant modification pour le log
        $oldData = [
            'title' => $incident->getTitle(),
            'description' => $incident->getDescription(),
            'location' => $incident->getLocation(),
            'category' => $incident->getCategory()->getName(),
            'priority' => $incident->getPriority(),
            'status' => $incident->getStatus(),
            'assignee' => $incident->getAssignee()?->getFullName(),
            'assigneeRole' => $incident->getAssigneeRole()
        ];

        $data = json_decode($request->getContent(), true);

        // Mise à jour des champs
        if (isset($data['title'])) {
            $incident->setTitle(trim($data['title']));
        }

        if (isset($data['description'])) {
            $incident->setDescription(trim($data['description']));
        }

        if (isset($data['location'])) {
            $incident->setLocation(trim($data['location']));
        }

        if (!empty($data['categoryId'])) {
            $category = $this->categoryRepository->find($data['categoryId']);
            if ($category) {
                $incident->setCategory($category);
            }
        }

        if (!empty($data['priority']) && in_array($data['priority'], [
            Incident::PRIORITY_LOW, Incident::PRIORITY_MEDIUM, 
            Incident::PRIORITY_HIGH, Incident::PRIORITY_URGENT
        ])) {
            $incident->setPriority($data['priority']);
        }

        // Seuls les assignés ou admins peuvent changer le statut
        if (!empty($data['status']) && ($user->isAdmin() || $incident->isEditableBy($user))) {
            $incident->setStatus($data['status']);
        }

        if (isset($data['resolution'])) {
            $incident->setResolution(trim($data['resolution']));
        }

        // Réassignation (uniquement pour admins ou assignés actuels)
        if ($user->isAdmin() || $incident->isEditableBy($user)) {
            if (isset($data['assigneeId'])) {
                if ($data['assigneeId']) {
                    $assignee = $this->userRepository->find($data['assigneeId']);
                    if ($assignee) {
                        $incident->setAssignee($assignee);
                        $incident->setAssigneeRole(null);
                    }
                } else {
                    $incident->setAssignee(null);
                }
            }

            if (isset($data['assigneeRole'])) {
                $incident->setAssigneeRole($data['assigneeRole'] ?: null);
                if ($data['assigneeRole']) {
                    $incident->setAssignee(null);
                }
            }
        }

        $incident->updateTimestamp();
        $this->em->flush();

        // Mettre à jour l'événement dans le calendrier des incidents
        try {
            $this->incidentCalendarService->updateEventForIncident($incident);
        } catch (\Exception $e) {
            error_log('Erreur mise à jour événement calendrier incident: ' . $e->getMessage());
        }

        // Log de mise à jour
        $newData = [
            'title' => $incident->getTitle(),
            'description' => $incident->getDescription(),
            'location' => $incident->getLocation(),
            'category' => $incident->getCategory()->getName(),
            'priority' => $incident->getPriority(),
            'status' => $incident->getStatus(),
            'assignee' => $incident->getAssignee()?->getFullName(),
            'assigneeRole' => $incident->getAssigneeRole()
        ];
        $this->auditLogService->logIncidentUpdated($incident->getId(), $oldData, $newData, $user);

        return $this->json([
            'message' => 'Incident mis à jour',
            'incident' => $this->serializeIncident($incident)
        ]);
    }

    /**
     * Ajoute un commentaire à un incident
     */
    #[Route('/{id}/comments', name: 'api_incidents_add_comment', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addComment(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRole() === 'Élève') {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $incident = $this->incidentRepository->find($id);
        
        if (!$incident) {
            return $this->json(['error' => 'Incident non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier l'accès
        if (!$user->isAdmin() && !$incident->isVisibleTo($user)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['content'])) {
            return $this->json(['error' => 'Le contenu est requis'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new IncidentComment();
        $comment->setIncident($incident);
        $comment->setAuthor($user);
        $comment->setContent(trim($data['content']));

        // Les commentaires internes ne sont visibles que par les assignés
        if (!empty($data['isInternal']) && $incident->isEditableBy($user)) {
            $comment->setIsInternal(true);
        }

        $incident->updateTimestamp();

        $this->em->persist($comment);
        $this->em->flush();

        // Log du commentaire ajouté
        $this->auditLogService->logIncidentCommentAdded(
            $incident->getId(),
            $comment->getId(),
            [
                'content' => substr($comment->getContent(), 0, 100) . (strlen($comment->getContent()) > 100 ? '...' : ''),
                'isInternal' => $comment->isInternal(),
                'author' => $user->getFullName()
            ],
            $user
        );

        return $this->json([
            'message' => 'Commentaire ajouté',
            'comment' => $this->serializeComment($comment)
        ], Response::HTTP_CREATED);
    }

    /**
     * Change le statut d'un incident
     */
    #[Route('/{id}/status', name: 'api_incidents_change_status', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changeStatus(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRole() === 'Élève') {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $incident = $this->incidentRepository->find($id);
        
        if (!$incident) {
            return $this->json(['error' => 'Incident non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Seuls les assignés ou admins peuvent changer le statut
        if (!$user->isAdmin() && !$incident->isEditableBy($user)) {
            return $this->json(['error' => 'Vous ne pouvez pas modifier le statut'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['status']) || !in_array($data['status'], [
            Incident::STATUS_OPEN, Incident::STATUS_IN_PROGRESS,
            Incident::STATUS_RESOLVED, Incident::STATUS_CLOSED
        ])) {
            return $this->json(['error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Capturer l'ancien statut pour le log
        $oldStatus = $incident->getStatus();

        $incident->setStatus($data['status']);
        
        if (isset($data['resolution'])) {
            $incident->setResolution(trim($data['resolution']));
        }

        $incident->updateTimestamp();
        $this->em->flush();

        // Log du changement de statut
        $this->auditLogService->logIncidentStatusChanged(
            $incident->getId(),
            $oldStatus,
            $data['status'],
            $user
        );

        return $this->json([
            'message' => 'Statut mis à jour',
            'incident' => $this->serializeIncident($incident)
        ]);
    }

    /**
     * Récupère les statistiques des incidents (admin only)
     * IMPORTANT: Cette route doit être AVANT /{id} sinon 'statistics' serait interprété comme un ID
     */
    #[Route('/statistics', name: 'api_incidents_statistics', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function statistics(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isAdmin()) {
            return $this->json(['error' => 'Réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $stats = $this->incidentRepository->getStatistics();

        return $this->json($stats);
    }

    /**
     * Supprime un incident (admin only)
     */
    #[Route('/{id}', name: 'api_incidents_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isAdmin()) {
            return $this->json(['error' => 'Réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $incident = $this->incidentRepository->find($id);
        
        if (!$incident) {
            return $this->json(['error' => 'Incident non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Capturer les données pour le log avant suppression
        $incidentData = [
            'id' => $incident->getId(),
            'title' => $incident->getTitle(),
            'category' => $incident->getCategory()->getName(),
            'priority' => $incident->getPriority(),
            'status' => $incident->getStatus(),
            'reporter' => $incident->getReporter()->getFullName()
        ];

        // Supprimer l'événement du calendrier des incidents avant de supprimer l'incident
        try {
            $this->incidentCalendarService->deleteEventForIncident($incident);
        } catch (\Exception $e) {
            error_log('Erreur suppression événement calendrier incident: ' . $e->getMessage());
        }

        $this->em->remove($incident);
        $this->em->flush();

        // Log de suppression
        $this->auditLogService->logIncidentDeleted($id, $incidentData, $user);

        return $this->json(['message' => 'Incident supprimé']);
    }

    /**
     * Sérialise un incident pour la réponse JSON
     */
    private function serializeIncident(Incident $incident, bool $detailed = false): array
    {
        $data = [
            'id' => $incident->getId(),
            'title' => $incident->getTitle(),
            'description' => $incident->getDescription(),
            'location' => $incident->getLocation(),
            'category' => [
                'id' => $incident->getCategory()->getId(),
                'name' => $incident->getCategory()->getName(),
                'color' => $incident->getCategory()->getColor(),
                'icon' => $incident->getCategory()->getIcon()
            ],
            'priority' => $incident->getPriority(),
            'priorityLabel' => $incident->getPriorityLabel(),
            'status' => $incident->getStatus(),
            'statusLabel' => $incident->getStatusLabel(),
            'reporter' => [
                'id' => $incident->getReporter()->getId(),
                'name' => $incident->getReporter()->getFullName(),
                'email' => $incident->getReporter()->getEmail()
            ],
            'assignee' => $incident->getAssignee() ? [
                'id' => $incident->getAssignee()->getId(),
                'name' => $incident->getAssignee()->getFullName(),
                'email' => $incident->getAssignee()->getEmail()
            ] : null,
            'assigneeRole' => $incident->getAssigneeRole(),
            'createdAt' => $incident->getCreatedAt()->format('c'),
            'updatedAt' => $incident->getUpdatedAt()->format('c'),
            'resolvedAt' => $incident->getResolvedAt()?->format('c')
        ];

        if ($detailed) {
            $data['resolution'] = $incident->getResolution();
            $data['commentsCount'] = $this->commentRepository->countByIncident($incident);
        }

        return $data;
    }

    /**
     * Sérialise un commentaire pour la réponse JSON
     */
    private function serializeComment(IncidentComment $comment): array
    {
        return [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'isInternal' => $comment->isInternal(),
            'author' => [
                'id' => $comment->getAuthor()->getId(),
                'name' => $comment->getAuthor()->getFullName()
            ],
            'createdAt' => $comment->getCreatedAt()->format('c'),
            'updatedAt' => $comment->getUpdatedAt()?->format('c')
        ];
    }
}
