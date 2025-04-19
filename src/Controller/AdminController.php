<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\User;
use App\Repository\AdminRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/admin", name: "app_api_admin_")]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AdminRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route(methods: "POST")]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $admin = $this->serializer->deserialize(
            $request->getContent(),
            Admin::class,
            'json',
        );

        // Assigner le user
        if ($data['user']) {
            $user = $this->manager
                ->getRepository(User::class)
                ->find($data['user']);
            if ($user) {
                $admin->setUser($user);
            } else {
                return new JsonResponse(
                    ['error' => 'user non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $admin->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($admin);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $admin,
            'json',
            ['groups' => 'admin:read']
        );

        $location = $this->urlGenerator->generate(
            'app_api_admin_show',
            ['id' => $admin->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_CREATED,
            ['Location' => $location],
            true,
        );
    }

    #[Route("/{id}", name: "show", methods: "GET")]
    public function show(int $id): JsonResponse
    {
        $admin = $this->repository->findOneBy(['id' => $id]);

        if ($admin) {
            $responseData = $this->serializer->serialize(
                $admin,
                'json',
                ['groups' => 'admin:read']
            );

            return new JsonResponse(
                $responseData,
                Response::HTTP_OK,
                [],
                true
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route("/{id}", name: "edit", methods: "PUT")]
    public function edit(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $admin = $this->repository->findOneBy(['id' => $id]);

        if ($admin) {
            $admin = $this->serializer->deserialize(
                $request->getContent(),
                Admin::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $admin]
            );

            // Mettre à jour le user si fourni
            if ($data['user']) {
                $user = $this->manager
                    ->getRepository(
                        User::class
                    )
                    ->find(
                        $data['user']
                    );
                if (!$user) {
                    return new JsonResponse(
                        ['error' => 'User non trouvé'],
                        Response::HTTP_BAD_REQUEST
                    );
                }
                $admin->setUser($user);
            }

            $admin->setUpdatedAt(new \DateTimeImmutable());

            $this->manager->flush();

            $responseData = $this->serializer->serialize(
                $admin,
                'json',
                ['groups' => 'admin:read']
            );

            return new JsonResponse(
                $responseData,
                Response::HTTP_OK,
                [],
                true
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route("/{id}", name: "delete", methods: "DELETE")]
    public function delete(int $id): JsonResponse
    {
        $admin = $this->repository->findOneBy(['id' => $id]);

        if ($admin) {
            $this->manager->remove($admin);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Admin supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
