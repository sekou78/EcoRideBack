<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/admin", name: "app_api_admin_")]
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
        $admin = $this->serializer->deserialize(
            $request->getContent(),
            Admin::class,
            'json',
        );

        $admin->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($admin);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $admin,
            'json',
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
        $admin = $this->repository->findOneBy(['id' => $id]);

        if ($admin) {
            $admin = $this->serializer->deserialize(
                $request->getContent(),
                Admin::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $admin]
            );

            $admin->setUpdatedAt(new \DateTimeImmutable());

            $this->manager->flush();

            $responseData = $this->serializer->serialize(
                $admin,
                'json',
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
