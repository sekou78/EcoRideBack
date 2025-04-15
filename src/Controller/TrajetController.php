<?php

namespace App\Controller;

use App\Entity\Historique;
use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\TrajetRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
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
        $data = json_decode(
            $request->getContent(),
            true
        );

        $trajet = $this->serializer->deserialize(
            $request->getContent(),
            Trajet::class,
            'json',
        );

        // Assigner le user
        if ($data['user']) {
            $user = $this->manager
                ->getRepository(User::class)
                ->find($data['user']);
            if ($user) {
                $trajet->setUser($user);
            } else {
                return new JsonResponse(
                    ['error' => 'user non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $trajet->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($trajet);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $trajet,
            'json',
            ['groups' => 'trajet:read']
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
                ['groups' => 'trajet:read']
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
        $data = json_decode(
            $request->getContent(),
            true
        );

        // Récupérer le trajet existant
        $trajet = $this->manager
            ->getRepository(
                Trajet::class
            )
            ->findOneBy(
                ['id' => $id]
            );

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Mettre à jour le statut si présent
        if ($data['statut']) {
            $trajet->setStatut($data['statut']);
        }

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
            $trajet->setUser($user);
        }

        $trajet->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $trajet,
            'json',
            ['groups' => ['trajet:read']]
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_OK,
            [],
            true
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
