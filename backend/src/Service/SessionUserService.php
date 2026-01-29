<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to get the current user from the session.
 * This bridges the gap between our manual session-based auth and the controllers.
 */
class SessionUserService
{
    public function __construct(
        private RequestStack $requestStack,
        private UserRepository $userRepository
    ) {
    }

    /**
     * Get the currently authenticated user from the session.
     */
    public function getCurrentUser(): ?User
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return null;
        }

        return $this->userRepository->find($userId);
    }
}
