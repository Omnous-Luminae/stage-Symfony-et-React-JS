<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $firstName = $data['firstName'] ?? '';
        $lastName = $data['lastName'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email invalide'], 400);
        }
        if (!$this->isStrongPassword($password)) {
            return $this->json(['error' => 'Mot de passe trop faible'], 400);
        }
        if (empty($firstName) || empty($lastName)) {
            return $this->json(['error' => 'Prénom et nom requis'], 400);
        }
        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Email déjà utilisé'], 400);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $request->getSession()->set('user_id', $user->getId());

        return $this->json($this->userPayload($user), 201);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Identifiants invalides'], 401);
        }

        $request->getSession()->set('user_id', $user->getId());

        return $this->json(['user' => $this->userPayload($user)]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(Request $request, UserRepository $userRepository): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }
        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }
        return $this->json($this->userPayload($user));
    }

    #[Route('/me', name: 'update_profile', methods: ['PUT', 'PATCH'])]
    public function updateProfile(Request $request, UserRepository $userRepository): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }
        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['firstName']) && !empty(trim($data['firstName']))) {
            $user->setFirstName(trim($data['firstName']));
        }
        if (isset($data['lastName']) && !empty(trim($data['lastName']))) {
            $user->setLastName(trim($data['lastName']));
        }
        if (isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            // Vérifier si l'email n'est pas déjà pris par un autre utilisateur
            $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json(['error' => 'Cet email est déjà utilisé'], 400);
            }
            $user->setEmail($data['email']);
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $this->userPayload($user)
        ]);
    }

    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }
        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        // Vérifier le mot de passe actuel
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['error' => 'Mot de passe actuel incorrect'], 400);
        }

        // Vérifier la force du nouveau mot de passe
        if (!$this->isStrongPassword($newPassword)) {
            return $this->json(['error' => 'Le nouveau mot de passe doit contenir au moins 12 caractères, avec majuscules, minuscules, chiffres et caractères spéciaux'], 400);
        }

        // Mettre à jour le mot de passe
        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $this->em->flush();

        return $this->json(['message' => 'Mot de passe modifié avec succès']);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $request->getSession()->invalidate();
        return $this->json(['message' => 'Déconnecté']);
    }

    #[Route('/check-email', name: 'check_email', methods: ['GET'])]
    public function checkEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $email = (string) $request->query->get('email', '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['available' => false, 'error' => 'Email invalide'], 400);
        }
        $exists = (bool) $userRepository->findOneBy(['email' => $email]);
        return $this->json(['available' => !$exists]);
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = $data['email'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email invalide'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        
        // Toujours retourner succès pour éviter l'énumération des emails
        if (!$user) {
            return $this->json([
                'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.'
            ]);
        }

        // TODO: Générer un token de réinitialisation et envoyer l'email
        // Pour l'instant, on simule l'envoi
        
        return $this->json([
            'message' => 'Un email avec les instructions de réinitialisation a été envoyé.',
            'debug' => 'En développement : Le token serait envoyé à ' . $email
        ]);
    }

    #[Route('/reset-password-dev', name: 'reset_password_dev', methods: ['POST'])]
    public function resetPasswordDev(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = $data['email'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email invalide'], 400);
        }

        if (!$this->isStrongPassword($newPassword)) {
            return $this->json(['error' => 'Mot de passe trop faible (12+ caractères, majuscules, minuscules, chiffres, spéciaux)'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $this->em->flush();

        return $this->json([
            'message' => 'Mot de passe modifié avec succès (mode développement)'
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'role' => $user->getRole(),
            'roles' => $user->getRoles(),
            'isAdmin' => $user->isAdmin(),
        ];
    }

    private function isStrongPassword(string $password): bool
    {
        $length = strlen($password) >= 12;
        $upper = preg_match('/[A-Z]/', $password);
        $lower = preg_match('/[a-z]/', $password);
        $digit = preg_match('/[0-9]/', $password);
        $special = preg_match('/[^A-Za-z0-9]/', $password);
        return $length && $upper && $lower && $digit && $special;
    }
}
