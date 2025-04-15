<?php

namespace App\Controller;

use App\Entity\ProfilConducteur;
use App\Repository\ProfilConducteurRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/profilConducteur", name: "app_api_profilConducteur_")]
final class ProfilConducteurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ProfilConducteurRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route(methods: "POST")]
    public function new(Request $request): JsonResponse
    {
        $profilConducteur = $this->serializer->deserialize(
            $request->getContent(),
            ProfilConducteur::class,
            'json',
        );

        $profilConducteur->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($profilConducteur);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $profilConducteur,
            'json',
        );

        $location = $this->urlGenerator->generate(
            'app_api_profilConducteur_show',
            ['id' => $profilConducteur->getId()],
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
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if ($profilConducteur) {
            $responseData = $this->serializer->serialize(
                $profilConducteur,
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
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if ($profilConducteur) {
            $profilConducteur = $this->serializer->deserialize(
                $request->getContent(),
                ProfilConducteur::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $profilConducteur]
            );

            $profilConducteur->setUpdatedAt(new \DateTimeImmutable());

            $this->manager->flush();

            $responseData = $this->serializer->serialize(
                $profilConducteur,
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
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if ($profilConducteur) {
            $this->manager->remove($profilConducteur);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "ProfilConducteur supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
