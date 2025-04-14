<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Repository\TrajetRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/trajet", name: "app_api_trajet_")]
final class TrajetController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private TrajetRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route(methods: "POST")]
    public function new(Request $request): JsonResponse
    {
        $trajet = $this->serializer->deserialize(
            $request->getContent(),
            Trajet::class,
            'json',
        );

        $trajet->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($trajet);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $trajet,
            'json',
        );

        $location = $this->urlGenerator->generate(
            'app_api_trajet_show',
            ['id' => $trajet->getId()],
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
        $trajet = $this->repository->findOneBy(['id' => $id]);

        if ($trajet) {
            $responseData = $this->serializer->serialize(
                $trajet,
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
        $trajet = $this->repository->findOneBy(['id' => $id]);

        if ($trajet) {
            $trajet = $this->serializer->deserialize(
                $request->getContent(),
                Trajet::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $trajet]
            );

            $trajet->setUpdatedAt(new \DateTimeImmutable());

            $this->manager->flush();

            $responseData = $this->serializer->serialize(
                $trajet,
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
        $trajet = $this->repository->findOneBy(['id' => $id]);

        if ($trajet) {
            $this->manager->remove($trajet);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Trajet supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
