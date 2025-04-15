<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Repository\ReservationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/reservation", name: "app_api_reservation_")]
final class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ReservationRepository $repository,
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

        $reservation = $this->serializer->deserialize(
            $request->getContent(),
            Reservation::class,
            'json',
        );

        // Assigner le trajet à la réservation
        if ($data['trajet']) {
            $trajet = $this->manager
                ->getRepository(Trajet::class)
                ->find($data['trajet']);
            if ($trajet) {
                $reservation->setTrajet($trajet);
            } else {
                return new JsonResponse(
                    ['error' => 'trajet non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $reservation->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($reservation);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $reservation,
            'json',
            ['groups' => ['reservation:read']]
        );

        $location = $this->urlGenerator->generate(
            'app_api_reservation_show',
            ['id' => $reservation->getId()],
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
        $reservation = $this->repository->findOneBy(['id' => $id]);

        if ($reservation) {
            $responseData = $this->serializer->serialize(
                $reservation,
                'json',
                ['groups' => ['reservation:read']]
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

        // Récupérer la réservation existante
        $reservation = $this->manager
            ->getRepository(
                Reservation::class
            )
            ->findOneBy(
                ['id' => $id]
            );

        if (!$reservation) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Mettre à jour le statut si présent
        if ($data['statut']) {
            $reservation->setStatut($data['statut']);
        }

        // Mettre à jour le trajet si fourni
        if ($data['trajet']) {
            $trajet = $this->manager
                ->getRepository(
                    Trajet::class
                )
                ->find(
                    $data['trajet']
                );
            if (!$trajet) {
                return new JsonResponse(
                    ['error' => 'Trajet non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $reservation->setTrajet($trajet);
        }

        $reservation->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $reservation,
            'json',
            ['groups' => ['reservation:read']]
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
        $reservation = $this->repository->findOneBy(['id' => $id]);

        if ($reservation) {
            $this->manager->remove($reservation);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Reservation supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
