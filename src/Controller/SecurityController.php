<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'app_api_')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $manager
    ) {}
    #[Route('/registration', name: 'registration', methods: 'POST')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json'
        );

        // Hachage du mot de passe
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            )
        );

        $user->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            [
                "id" => $user->getId(),
                "user" => $user->getUserIdentifier(),
                "apiToken" => $user->getApiToken(),
                "roles" => $user->getRoles()
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/login', name: 'login', methods: 'POST')]
    public function login(#[CurrentUser()] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(
                [
                    'message' => 'missing credentials',
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return new JsonResponse(
            // ['message' => 'User registered successfully'],
            //Pour le test à supprimer avant production (mise en ligne)
            [
                'id' => $user->getId(),
                'user'  => $user->getUserIdentifier(),
                'apiToken' => $user->getApiToken(),
                'roles' => $user->getRoles()
            ],
        );
    }
}
