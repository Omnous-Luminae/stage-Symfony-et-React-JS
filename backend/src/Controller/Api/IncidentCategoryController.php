<?php

namespace App\Controller\Api;

use App\Entity\IncidentCategory;
use App\Entity\User;
use App\Repository\IncidentCategoryRepository;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/incident-categories')]
class IncidentCategoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private IncidentCategoryRepository $categoryRepository,
        private AuditLogService $auditLogService
    ) {}

    /**
     * Liste toutes les catégories actives
     */
    #[Route('', name: 'api_incident_categories_list', methods: ['GET'])]
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

        // Les admins voient toutes les catégories, les autres seulement les actives
        if ($user->isAdmin() && $request->query->get('all')) {
            $categories = $this->categoryRepository->findAllOrdered();
        } else {
            $categories = $this->categoryRepository->findAllActive();
        }

        return $this->json([
            'categories' => array_map(fn($c) => $this->serializeCategory($c), $categories)
        ]);
    }

    /**
     * Récupère une catégorie par son ID
     */
    #[Route('/{id}', name: 'api_incident_categories_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function get(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRole() === 'Élève') {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $category = $this->categoryRepository->find($id);
        
        if (!$category) {
            return $this->json(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['category' => $this->serializeCategory($category)]);
    }

    /**
     * Crée une nouvelle catégorie (admin only)
     */
    #[Route('', name: 'api_incident_categories_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isAdmin()) {
            return $this->json(['error' => 'Réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['name']) || empty($data['code'])) {
            return $this->json([
                'error' => 'Le nom et le code sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier l'unicité du code
        $existing = $this->categoryRepository->findByCode($data['code']);
        if ($existing) {
            return $this->json([
                'error' => 'Une catégorie avec ce code existe déjà'
            ], Response::HTTP_CONFLICT);
        }

        $category = new IncidentCategory();
        $category->setName(trim($data['name']));
        $category->setCode(trim($data['code']));

        if (isset($data['description'])) {
            $category->setDescription(trim($data['description']));
        }

        if (isset($data['color'])) {
            $category->setColor($data['color']);
        }

        if (isset($data['icon'])) {
            $category->setIcon($data['icon']);
        }

        if (isset($data['isActive'])) {
            $category->setIsActive((bool) $data['isActive']);
        }

        if (isset($data['displayOrder'])) {
            $category->setDisplayOrder((int) $data['displayOrder']);
        }

        if (isset($data['defaultAssigneeRole'])) {
            $category->setDefaultAssigneeRole($data['defaultAssigneeRole']);
        }

        $this->em->persist($category);
        $this->em->flush();

        // Log de création
        $this->auditLogService->logIncidentCategoryCreated(
            $category->getId(),
            [
                'name' => $category->getName(),
                'code' => $category->getCode(),
                'color' => $category->getColor(),
                'isActive' => $category->isActive()
            ],
            $user
        );

        return $this->json([
            'message' => 'Catégorie créée avec succès',
            'category' => $this->serializeCategory($category)
        ], Response::HTTP_CREATED);
    }

    /**
     * Met à jour une catégorie (admin only)
     */
    #[Route('/{id}', name: 'api_incident_categories_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isAdmin()) {
            return $this->json(['error' => 'Réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $category = $this->categoryRepository->find($id);
        
        if (!$category) {
            return $this->json(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Capturer l'état avant modification pour le log
        $oldData = [
            'name' => $category->getName(),
            'code' => $category->getCode(),
            'color' => $category->getColor(),
            'isActive' => $category->isActive()
        ];

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $category->setName(trim($data['name']));
        }

        if (isset($data['code'])) {
            // Vérifier l'unicité du code
            $existing = $this->categoryRepository->findByCode($data['code']);
            if ($existing && $existing->getId() !== $category->getId()) {
                return $this->json([
                    'error' => 'Une catégorie avec ce code existe déjà'
                ], Response::HTTP_CONFLICT);
            }
            $category->setCode(trim($data['code']));
        }

        if (isset($data['description'])) {
            $category->setDescription(trim($data['description']));
        }

        if (isset($data['color'])) {
            $category->setColor($data['color']);
        }

        if (isset($data['icon'])) {
            $category->setIcon($data['icon']);
        }

        if (isset($data['isActive'])) {
            $category->setIsActive((bool) $data['isActive']);
        }

        if (isset($data['displayOrder'])) {
            $category->setDisplayOrder((int) $data['displayOrder']);
        }

        if (array_key_exists('defaultAssigneeRole', $data)) {
            $category->setDefaultAssigneeRole($data['defaultAssigneeRole']);
        }

        $this->em->flush();

        // Log de mise à jour
        $newData = [
            'name' => $category->getName(),
            'code' => $category->getCode(),
            'color' => $category->getColor(),
            'isActive' => $category->isActive()
        ];
        $this->auditLogService->logIncidentCategoryUpdated($category->getId(), $oldData, $newData, $user);

        return $this->json([
            'message' => 'Catégorie mise à jour',
            'category' => $this->serializeCategory($category)
        ]);
    }

    /**
     * Supprime une catégorie (admin only)
     */
    #[Route('/{id}', name: 'api_incident_categories_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isAdmin()) {
            return $this->json(['error' => 'Réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $category = $this->categoryRepository->find($id);
        
        if (!$category) {
            return $this->json(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si la catégorie est utilisée
        $incidentCount = $this->em->getRepository(\App\Entity\Incident::class)
            ->count(['category' => $category]);

        if ($incidentCount > 0) {
            return $this->json([
                'error' => "Cette catégorie est utilisée par {$incidentCount} incident(s). Désactivez-la plutôt que de la supprimer."
            ], Response::HTTP_CONFLICT);
        }

        // Capturer les données pour le log avant suppression
        $categoryData = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'code' => $category->getCode()
        ];

        $this->em->remove($category);
        $this->em->flush();

        // Log de suppression
        $this->auditLogService->logIncidentCategoryDeleted($id, $categoryData, $user);

        return $this->json(['message' => 'Catégorie supprimée']);
    }

    /**
     * Sérialise une catégorie pour la réponse JSON
     */
    private function serializeCategory(IncidentCategory $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'code' => $category->getCode(),
            'description' => $category->getDescription(),
            'color' => $category->getColor(),
            'icon' => $category->getIcon(),
            'isActive' => $category->isActive(),
            'displayOrder' => $category->getDisplayOrder(),
            'defaultAssigneeRole' => $category->getDefaultAssigneeRole(),
            'createdAt' => $category->getCreatedAt()->format('c')
        ];
    }
}
