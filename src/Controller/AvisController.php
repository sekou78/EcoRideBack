<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\AvisRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/avis", name: "app_api_avis_")]
#[IsGranted('ROLE_PASSAGER')]
final class AvisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AvisRepository $repository,
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

        $avis = $this->serializer->deserialize(
            $request->getContent(),
            Avis::class,
            'json',
        );

        // Assigner le reservation à la réservation
        if ($data['reservation']) {
            $reservation = $this->manager
                ->getRepository(Reservation::class)
                ->find($data['reservation']);
            if ($reservation) {
                $avis->setReservation($reservation);
            } else {
                return new JsonResponse(
                    ['error' => 'reservation non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        // Assigner le user
        if ($data['user']) {
            $user = $this->manager
                ->getRepository(User::class)
                ->find($data['user']);
            if ($user) {
                $avis->setUser($user);
            } else {
                return new JsonResponse(
                    ['error' => 'user non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $avis->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($avis);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $avis,
            'json',
            ['groups' => ['avis:read']]
        );

        $location = $this->urlGenerator->generate(
            'app_api_avis_show',
            ['id' => $avis->getId()],
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
        $avis = $this->repository->findOneBy(['id' => $id]);

        if ($avis) {
            $responseData = $this->serializer->serialize(
                $avis,
                'json',
                ['groups' => ['avis:read']]
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
        $avis = $this->manager
            ->getRepository(
                Avis::class
            )
            ->findOneBy(
                ['id' => $id]
            );

        if (!$avis) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Mettre à jour le note si présent
        if ($data['note']) {
            $avis->setNote($data['note']);
        }

        // Mettre à jour le commentaire si présent
        if ($data['commentaire']) {
            $avis->setCommentaire($data['commentaire']);
        }

        // Mettre à jour la validation par employes si présent
        if ($data['valideParEmployee']) {
            $avis->setValideParEmployee($data['valideParEmployee']);
        }

        // Mettre à jour le reservation si fourni
        if ($data['reservation']) {
            $reservation = $this->manager
                ->getRepository(
                    Reservation::class
                )
                ->find(
                    $data['reservation']
                );
            if (!$reservation) {
                return new JsonResponse(
                    ['error' => 'Reservation non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $avis->setReservation($reservation);
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
            $avis->setUser($user);
        }

        $avis->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $avis,
            'json',
            ['groups' => ['avis:read']]
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
        $avis = $this->repository->findOneBy(['id' => $id]);

        if ($avis) {
            $this->manager->remove($avis);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Avis supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
