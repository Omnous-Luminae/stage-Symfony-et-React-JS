<?php

namespace App\EventSubscriber;

use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * This subscriber loads the user from the session and sets it in the security context.
 * This bridges the gap between our manual session-based auth and Symfony's security system.
 */
class SessionAuthSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private UserRepository $userRepository,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 6], // Run AFTER firewall (8) to set our custom token
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Skip auth routes - they handle their own authentication
        if (str_starts_with($request->getPathInfo(), '/api/auth')) {
            return;
        }

        // Debug: Log cookies received
        $cookies = $request->cookies->all();
        error_log("🍪 SessionAuthSubscriber - Cookies received: " . json_encode(array_keys($cookies)));
        
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        error_log("🔑 SessionAuthSubscriber - Session ID: " . $session->getId() . ", user_id: " . ($userId ?? 'NULL'));

        if (!$userId) {
            error_log("❌ SessionAuthSubscriber - No user_id in session");
            return;
        }

        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            // User not found - clear the invalid session
            error_log("❌ SessionAuthSubscriber - User not found for ID: " . $userId);
            $session->remove('user_id');
            return;
        }

        // Create a security token for the user
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        
        // Set the token in the security context
        $this->tokenStorage->setToken($token);
        
        error_log("✅ SessionAuthSubscriber - User authenticated: " . $user->getEmail());
    }
}
