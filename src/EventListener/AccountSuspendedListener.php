<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use App\Entity\User;

class AccountSuspendedListener
{
    private Security $security;
    private RouterInterface $router;

    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($user->isCompteSuspendu()) {
            // Récupérer le chemin actuel de la requête
            $request = $event->getRequest();
            $currentPath = $request->getPathInfo();

            // On exclut certaines routes (connexion, logout, assets, etc.) de la vérification
            $excludedPaths = [
                '/connexion',        // page de connexion
                '/deconnexion',           // déconnexion
                '/pageSuspensionCompte', // page de suspension du compte
                '/build',
                '/uploads',
                '/assets',

            ];

            // Si la route actuelle est dans les exclusions, on ne bloque pas
            foreach ($excludedPaths as $excluded) {
                if (str_starts_with($currentPath, $excluded)) {
                    return;
                }
            }

            // Si la requête est une API (ex: /api/...), on renvoie une réponse JSON
            if (str_starts_with($request->getPathInfo(), '/api')) {
                $event->setResponse(new JsonResponse([
                    'error' => 'Compte suspendu. Accès refusé.'
                ], JsonResponse::HTTP_FORBIDDEN));
            } else {
                // Sinon, redirection vers une page pour compte suspendu
                $event->setResponse(new RedirectResponse(
                    $this->router->generate('suspended_account')
                ));
            }
        }
    }
}
