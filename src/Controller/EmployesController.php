<?php

namespace App\Controller;

use App\Entity\Employes;
use App\Entity\User;
use App\Repository\EmployesRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/employes", name: "app_api_employes_")]
final class EmployesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private EmployesRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route(methods: "POST")]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $employes = $this->serializer->deserialize(
            $request->getContent(),
            Employes::class,
            'json',
            ['groups' => 'employes:read']
        );

        // Assigner le user
        if ($data['user']) {
            $user = $this->manager
                ->getRepository(User::class)
                ->find($data['user']);
            if ($user) {
                $employes->setUser($user);
            } else {
                return new JsonResponse(
                    ['error' => 'user non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $employes->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($employes);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $employes,
            'json',
            ['groups' => 'employes:read']
        );

        $location = $this->urlGenerator->generate(
            'app_api_employes_show',
            ['id' => $employes->getId()],
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
        $employes = $this->repository->findOneBy(['id' => $id]);

        if ($employes) {
            $responseData = $this->serializer->serialize(
                $employes,
                'json',
                ['groups' => 'employes:read']
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

        $employes = $this->repository->findOneBy(['id' => $id]);

        if ($employes) {
            $employes = $this->serializer->deserialize(
                $request->getContent(),
                Employes::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $employes]
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
                $employes->setUser($user);
            }

            $employes->setUpdatedAt(new \DateTimeImmutable());

            $this->manager->flush();

            $responseData = $this->serializer->serialize(
                $employes,
                'json',
                ['groups' => 'employes:read']
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
        $employes = $this->repository->findOneBy(['id' => $id]);

        if ($employes) {
            $this->manager->remove($employes);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Employes supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
