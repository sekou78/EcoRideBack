<?php

namespace App\Controller;

use App\Entity\User;
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
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: "/api/reservation",
        summary: "Créer une nouvelle réservation",
        description: "Permet à un utilisateur de créer réservation pour un trajet.",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de la réservation à créer",
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    required: ["statut", "trajet"],
                    properties: [
                        new OA\Property(
                            property: "statut",
                            type: "string",
                            example: "CONFIRMEE"
                        ),
                        new OA\Property(
                            property: "trajet",
                            type: "integer",
                            example: 1
                        )
                    ],
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Réservation créée avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: 'id',
                                type: 'integer',
                                example: 1
                            ),
                            new OA\Property(
                                property: 'statut',
                                type: 'string',
                                example: 'CONFIRMEE'
                            ),
                            new OA\Property(
                                property: 'trajet',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        example: 11
                                    ),
                                    new OA\Property(
                                        property: 'statut',
                                        type: 'string',
                                        example: 'ANNULEE'
                                    )
                                ]
                            ),
                            new OA\Property(
                                property: 'user',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        example: 15
                                    ),
                                    new OA\Property(
                                        property: 'pseudo',
                                        type: 'string',
                                        example: 'testuser'
                                    )
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Données invalides",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Données invalides ou erreurs de validation."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Trajet déjà réservé ou statut du trajet interdit",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Vous avez déjà réservé un trajet."
                            )
                        ]
                    )
                )
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

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

            // Récupérer l'utilisateur authentifié
            $user = $this->security->getUser();
            if (!$user instanceof User) {
                return new JsonResponse(
                    ['error' => 'Utilisateur non connu'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            // Vérifier que l'utilisateur a le bon rôle
            $roles = $user->getRoles();
            if (
                !in_array('ROLE_PASSAGER', $roles) &&
                !in_array('ROLE_PASSAGER_CHAUFFEUR', $roles)
            ) {
                return new JsonResponse(
                    [
                        'error' => "Verifier que vous avez le rôle 'PASSAGER' ou 'PASSAGER_CHAUFFEUR'."
                    ],
                    Response::HTTP_FORBIDDEN
                );
            }

            // Empêcher le chauffeur de réserver son propre trajet
            if ($trajet->getChauffeur() === $user) {
                return new JsonResponse(
                    ['error' => "Vous ne pouvez pas réserver votre propre trajet."],
                    Response::HTTP_FORBIDDEN
                );
            }

            // Vérification des places disponibles
            $placesDisponibles = $trajet->getNombrePlacesDisponible();
            $reservationsCount = count($trajet->getReservations());

            if ($reservationsCount >= $placesDisponibles) {
                return new JsonResponse(
                    ['error' => "Il n'y a plus de places disponibles pour ce trajet."],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérification d'une ancienne réservation déjà existante
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
                    !in_array($statutReservation, ['ANNULEE']) &&
                    $statutTrajet !== 'TERMINEE'
                ) {
                    return new JsonResponse(
                        ['error' => "Vous avez déjà réservé ce trajet."],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            // Création de la réservation
            $reservation->setUser($user);
            $reservation->setTrajet($trajet);
            $reservation->setCreatedAt(new \DateTimeImmutable());

            // Ajout de l'utilisateur au trajet s'il n'y est pas déjà
            if (!$trajet->getUsers()->contains($user)) {
                $trajet->addUser($user);
            }

            $prixTrajet = (int) round(floatval($trajet->getPrix()));
            $creditsUtilisateur = $user->getCredits();

            if ($creditsUtilisateur < $prixTrajet) {
                return new JsonResponse(
                    ['error' => "Vous n'avez pas assez de crédits pour réserver ce trajet."],
                    Response::HTTP_PAYMENT_REQUIRED
                );
            }

            // Déduction des crédits — toujours, quel que soit le statut
            $user->setCredits($creditsUtilisateur - $prixTrajet);


            $this->manager->persist($reservation);
            $this->manager->persist($trajet);
            $this->manager->flush();

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
    #[OA\Get(
        path: '/api/reservation/{id}',
        summary: 'Afficher une réservation',
        description: 'Retourne une réservation qui appartient à l’utilisateur connecté.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la réservation',
                schema: new OA\Schema(
                    type: 'integer'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Réservation trouvée',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'id',
                                type: 'integer',
                                example: 1
                            ),
                            new OA\Property(
                                property: 'statut',
                                type: 'string',
                                example: 'CONFIRMEE'
                            ),
                            new OA\Property(
                                property: 'trajet',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        example: 11
                                    ),
                                    new OA\Property(
                                        property: 'statut',
                                        type: 'string',
                                        example: 'ANNULEE'
                                    )
                                ]
                            ),
                            new OA\Property(
                                property: 'user',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        example: 15
                                    ),
                                    new OA\Property(
                                        property: 'pseudo',
                                        type: 'string',
                                        example: 'testuser'
                                    )
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: 'L’utilisateur n’est pas autorisé à accéder à cette réservation'
            ),
            new OA\Response(
                response: 404,
                description: 'Réservation non trouvée'
            )
        ]
    )]
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
    #[OA\Put(
        path: '/api/reservation/{id}',
        summary: 'Modifier une réservation',
        description: 'Permet à l’utilisateur de modifier sa propre réservation.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la réservation à modifier',
                schema: new OA\Schema(
                    type: 'integer'
                )
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    required: ["statut"],
                    properties: [
                        new OA\Property(
                            property: "statut",
                            type: "string",
                            example: "EN_ATTENTE"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Réservation mise à jour avec succès',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'id',
                                type: 'integer',
                                example: 1
                            ),
                            new OA\Property(
                                property: 'statut',
                                type: 'string',
                                example: 'EN_ATTENTE'
                            ),
                            new OA\Property(
                                property: 'trajet',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        example: 11
                                    ),
                                    new OA\Property(
                                        property: 'statut',
                                        type: 'string',
                                        example: 'CONFIRMEE'
                                    )
                                ]
                            ),
                            new OA\Property(
                                property: 'user',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        example: 15
                                    ),
                                    new OA\Property(
                                        property: 'pseudo',
                                        type: 'string',
                                        example: 'testuser'
                                    )
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Accès interdit - utilisateur non propriétaire de la réservation'
            ),
            new OA\Response(
                response: 404,
                description: 'Réservation non trouvée'
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides ou erreurs de validation'
            )
        ]
    )]
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
                [
                    'error' => "Vous n'êtes pas autorisé à modifier cette réservation."
                ],
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

        $reservation->setUpdatedAt(new DateTimeImmutable());

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
    #[OA\Delete(
        path: '/api/reservation/{id}',
        summary: 'Supprimer une réservation',
        description: 'Supprimer sa réservation.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la réservation à supprimer',
                schema: new OA\Schema(
                    type: 'integer'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Réservation supprimée avec succès',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'message',
                                type: 'string',
                                example: 'Réservation supprimée avec succès'
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Accès interdit - utilisateur non propriétaire de la réservation'
            ),
            new OA\Response(
                response: 404,
                description: 'Réservation non trouvée'
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        // Récupérer la réservation via son ID
        $reservation = $this->repository
            ->findOneBy(
                ['id' => $id]
            );

        // Si pas trouvée, erreur 404
        if (!$reservation) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        // Vérifier qu'il est bien connecté
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connu'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier que l'utilisateur est bien le propriétaire de la réservation
        if ($reservation->getUser() !== $user) {
            return new JsonResponse(
                [
                    'error' => "Vous n'êtes pas autorisé à supprimer cette réservation"
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer le trajet lié à la réservation
        $trajet = $reservation->getTrajet();
        if ($trajet === null) {
            return new JsonResponse(
                ['error' => 'Trajet associé introuvable'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Calculer les crédits à rembourser en convertissant le prix string en int arrondi
        $creditsARembourser = (int) round(
            floatval($trajet->getPrix())
        );

        // Si la réservation n'a pas encore été remboursée, appliquer la logique de remboursement
        if (!$reservation->isRembourse()) {
            $dateDepart = $trajet->getDateDepart();
            $heureDepart = $trajet->getHeureDepart();
            $conducteur = $trajet->getChauffeur();

            // Fusionner la date et l'heure pour avoir un DateTimeImmutable complet
            if (
                $dateDepart instanceof \DateTimeInterface
                &&
                $heureDepart instanceof \DateTimeInterface
            ) {
                $heure = (int) $heureDepart->format('H');
                $minute = (int) $heureDepart->format('i');

                if ($dateDepart instanceof \DateTimeImmutable) {
                    // Si c’est déjà immutable, on peut setTime directement
                    $dateDepart = $dateDepart->setTime($heure, $minute);
                } else {
                    // Sinon convertir en immutable avant de setTime
                    $dateDepart = \DateTimeImmutable::createFromMutable($dateDepart)->setTime($heure, $minute);
                }
            }

            $now = new \DateTimeImmutable();
            // Calculer la différence en heures entre le départ et maintenant
            $diffHeures = ($dateDepart->getTimestamp() - $now->getTimestamp()) / 3600;

            if ($diffHeures <= 12 && $diffHeures > 0) {
                // Si annulation dans les 12h avant départ : partage 50/50 des crédits
                $moitie = (int) round($creditsARembourser / 2);

                // Rembourser moitié au passager
                $user->setCredits($user->getCredits() + $moitie);

                $this->manager->persist($user);

                // Donner moitié au chauffeur s'il est bien un User
                if ($conducteur instanceof User) {
                    $conducteur->setCredits($conducteur->getCredits() + $moitie);
                    $this->manager->persist($conducteur);
                }
            } else {
                // Sinon remboursement complet au passager
                $user->setCredits($user->getCredits() + $creditsARembourser);
                $this->manager->persist($user);
            }

            // Marquer la réservation comme remboursée
            $reservation->setIsRembourse(true);
        }

        // Modifier le statut en "ANNULEE" 
        $reservation->setStatut("ANNULEE");

        $this->manager->flush();

        return new JsonResponse(
            ["message" => "Réservation supprimée avec succès"],
            Response::HTTP_OK
        );
    }

    #[Route('/', name: 'index', methods: 'GET')]
    #[IsGranted('ROLE_USER')]
    public function index(): JsonResponse
    {
        $user = $this->security->getUser();

        // On sélectionne toutes les réservations de l'utilisateur connecté
        // mais uniquement celles sur lesquelles il N’A PAS encore laissé d’avis
        $qb = $this->repository->createQueryBuilder('r')
            ->leftJoin('r.avis', 'a', 'WITH', 'a.user = :user')
            ->where('r.user = :user')
            // Exclut les réservations avec un avis existant pour cet utilisateur
            ->andWhere('a.id IS NULL')
            ->setParameter('user', $user)
            ->orderBy("CASE 
                WHEN r.statut = 'CONFIRMEE' THEN 1
                WHEN r.statut = 'EN_ATTENTE' THEN 2
                ELSE 3
            END", 'ASC');

        $reservations = $qb->getQuery()->getResult();

        $responseData = $this->serializer
            ->serialize(
                $reservations,
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
}
