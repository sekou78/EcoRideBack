<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Repository\ReservationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("api/reservation", name: "app_api_reservation_")]
final class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ReservationRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private ValidatorInterface $validator
    ) {}

    #[Route(methods: "POST")]
    #[IsGranted('ROLE_USER')]
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

        $errors = $this->validator->validate($reservation);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(
                ['errors' => $errorMessages],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Récupérer le trajet
        if (isset($data['trajet'])) {
            $trajet = $this->manager
                ->getRepository(Trajet::class)
                ->find($data['trajet']);
            if (!$trajet) {
                return new JsonResponse(
                    ['error' => 'Trajet non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérifier si le nombre de places disponibles est suffisant
            $placesDisponibles = $trajet->getNombrePlacesDisponible();
            $reservationsCount = count($trajet->getReservations());

            if ($reservationsCount >= $placesDisponibles) {
                return new JsonResponse(
                    [
                        'error' => "Il n'y a plus de places disponibles pour ce trajet."
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Récupérer l'utilisateur authentifié
            $user = $this->security->getUser();

            // Vérifier les anciennes réservations de cet utilisateur pour ce trajet
            $ancienneReservation = $this->manager
                ->getRepository(Reservation::class)
                ->findOneBy([
                    'trajet' => $trajet,
                    'user' => $user
                ]);

            if ($ancienneReservation) {
                $statutReservation = $ancienneReservation->getStatut();
                $statutTrajet = $trajet->getStatut();

                if (
                    !in_array(
                        $statutReservation,
                        [
                            'ANNULEE'
                        ]
                    )
                    &&
                    $statutTrajet !== 'TERMINEE'
                ) {
                    return new JsonResponse(
                        [
                            'error' => "Vous avez déjà réservé un trajet."
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            // Assigner l'utilisateur et le trajet à la nouvelle réservation
            $reservation->setUser($user);
            $reservation->setTrajet($trajet);
            $reservation->setCreatedAt(new \DateTimeImmutable());

            // Ajouter l'utilisateur aux passagers du trajet s'il n'y est pas encore
            if (!$trajet->getUsers()->contains($user)) {
                $trajet->addUser($user);
            }

            // Persister la réservation et le trajet
            $this->manager->persist($reservation);
            $this->manager->persist($trajet);
            $this->manager->flush();

            // Préparer la réponse
            $responseData = $this->serializer->serialize(
                $reservation,
                'json',
                ['groups' => ['reservation:read']]
            );

            $location = $this->urlGenerator->generate(
                'app_api_reservation_show',
                ['id' => $reservation->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            return new JsonResponse(
                $responseData,
                Response::HTTP_CREATED,
                ['Location' => $location],
                true
            );
        }

        return new JsonResponse(
            ['error' => 'Données invalides'],
            Response::HTTP_BAD_REQUEST
        );
    }

    #[Route("/{id}", name: "show", methods: "GET")]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        $reservation = $this->repository->findOneBy(['id' => $id]);

        if (!$reservation) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        // Vérifier si l'utilisateur authentifié est celui qui a créé la réservation
        if ($reservation->getUser() !== $user) {
            return new JsonResponse(
                ['error' => "Vous n'êtes pas autorisé à voir cette réservation"],
                Response::HTTP_FORBIDDEN
            );
        }

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
    #[IsGranted('ROLE_USER')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $reservation = $this->manager
            ->getRepository(Reservation::class)
            ->findOneBy(['id' => $id]);

        if (!$reservation) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée.'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier que l'utilisateur connecté est le créateur de la réservation
        $user = $this->security->getUser();

        if ($reservation->getUser() !== $user) {
            return new JsonResponse(
                ['error' => "Vous n'êtes pas autorisé à modifier cette réservation."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Mettre à jour les données
        $data = json_decode($request->getContent(), true);

        if (isset($data['statut'])) {
            $reservation->setStatut($data['statut']);
        }

        $errors = $this->validator->validate($reservation);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(
                ['errors' => $errorMessages],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->manager->persist($reservation);
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
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $reservation = $this->repository->findOneBy(['id' => $id]);

        if (!$reservation) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        $user = $this->security->getUser();

        // Vérifier si l'utilisateur authentifié est celui qui a créé la réservation
        if ($reservation->getUser() !== $user) {
            return new JsonResponse(
                [
                    'error' => "Vous n'êtes pas autorisé à supprimer cette réservation"
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $this->manager->remove($reservation);
        $this->manager->flush();

        return new JsonResponse(
            [
                "message" => "Réservation supprimée avec succès"
            ],
            Response::HTTP_OK
        );
    }
}
