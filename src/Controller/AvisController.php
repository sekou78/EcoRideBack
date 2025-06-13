<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\AvisRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route("api/avis", name: "app_api_avis_")]
final class AvisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AvisRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security
    ) {}

    #[Route(methods: "POST")]
    #[OA\Post(
        path: "/api/avis",
        summary: "Créer un avis pour une réservation",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    required: ["reservation", "note", "commentaire"],
                    properties: [
                        new OA\Property(
                            property: "reservation",
                            type: "integer",
                            example: 1,
                            description: "ID de la réservation concernée"
                        ),
                        new OA\Property(
                            property: "note",
                            type: "integer",
                            example: 4,
                            description: "Note de l'avis, entre 1 et 5"
                        ),
                        new OA\Property(
                            property: "commentaire",
                            type: "string",
                            example: "Chauffeur très ponctuel et courtois.",
                            description: "Commentaire optionnel de l'avis"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Avis créé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "note",
                                type: "integer",
                                example: 5
                            ),
                            new OA\Property(
                                property: "commentaire",
                                type: "string",
                                example: "Très bon chauffeur"
                            ),
                            new OA\Property(
                                property: "isVisible",
                                type: "boolean",
                                example: false
                            ),
                            new OA\Property(
                                property: "createdAt",
                                type: "string",
                                format: "date-time"
                            ),
                            new OA\Property(
                                property: "user",
                                type: "object"
                            ),
                            new OA\Property(
                                property: "reservation",
                                type: "object"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Données invalides ou incomplètes"
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé"
            ),
            new OA\Response(
                response: 404,
                description: "Réservation non trouvée"
            ),
            new OA\Response(
                response: 409,
                description: "Avis déjà existant pour cette réservation"
            )
        ]
    )]
    public function new(Request $request): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                [
                    'error' => 'Utilisateur non authentifié'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a le rôle "passager" ou "passager_chauffeur"
        if (
            !in_array('ROLE_PASSAGER', $user->getRoles())
            &&
            !in_array('ROLE_PASSAGER_CHAUFFEUR', $user->getRoles())
        ) {
            return new JsonResponse(
                [
                    'error' => "Seuls les passagers ou passager_chauffeurs peuvent poster un avis."
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode(
            $request->getContent(),
            true
        );

        if (empty($data['reservation'])) {
            return new JsonResponse(
                [
                    'error' => 'ID de réservation requis.'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Récupération de la réservation
        $reservation = $this->manager
            ->getRepository(Reservation::class)
            ->find($data['reservation']);

        if (!$reservation) {
            return new JsonResponse(
                [
                    'error' => 'Réservation non trouvée'
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier que la réservation appartient à l'utilisateur connecté
        if ($reservation->getUser() !== $user) {
            return new JsonResponse(
                [
                    'error' => "Vous n'avez pas de réservation sur ce trajet"
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Vérifier qu'il n'y a pas déjà un avis pour cette réservation
        $existingAvis = $this->manager
            ->getRepository(Avis::class)
            ->findOneBy(
                [
                    'reservation' => $reservation
                ]
            );

        if ($existingAvis) {
            return new JsonResponse(
                [
                    'error' => 'Vous avez déjà soumis un avis pour ce trajet.'
                ],
                Response::HTTP_CONFLICT
            );
        }

        // Créer et enregistrer l'avis
        $avis = $this->serializer
            ->deserialize(
                $request->getContent(),
                Avis::class,
                'json'
            );
        $avis->setIsVisible(false);
        $avis->setIsRefused(false);
        $avis->setUser($user);
        $avis->setReservation($reservation);
        $avis->setCreatedAt(new \DateTimeImmutable());

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
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_CREATED,
            ['Location' => $location],
            true
        );
    }

    #[Route("/", name: "show", methods: "GET")]
    #[OA\Get(
        path: "/api/avis/",
        summary: "Lister tous les avis",
        description: "Accessible uniquement aux employées",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des avis",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    type: "integer",
                                    example: 1
                                ),
                                new OA\Property(
                                    property: "note",
                                    type: "integer",
                                    example: 5
                                ),
                                new OA\Property(
                                    property: "commentaire",
                                    type: "string",
                                    example: "Service excellent, merci !"
                                ),
                                new OA\Property(
                                    property: "reservation",
                                    type: "object",
                                    properties: [
                                        new OA\Property(
                                            property: "id",
                                            type: "integer",
                                            example: 1
                                        ),
                                        new OA\Property(
                                            property: "statut",
                                            type: "string",
                                            example: "CONFIRMEE"
                                        )
                                    ]
                                ),
                                new OA\Property(
                                    property: "user",
                                    type: "object",
                                    properties: [
                                        new OA\Property(
                                            property: "id",
                                            type: "integer",
                                            example: 10
                                        ),
                                        new OA\Property(
                                            property: "pseudo",
                                            type: "string",
                                            example: "testuser"
                                        )
                                    ]
                                ),
                                new OA\Property(
                                    property: "isVisible",
                                    type: "boolean",
                                    example: true
                                )
                            ]
                        )
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Aucun avis trouvé"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé - rôle manquant"
            )
        ]
    )]
    #[IsGranted('ROLE_EMPLOYE')]
    public function show(): JsonResponse
    {
        $avis = $this->manager->getRepository(Avis::class)->findAll();

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

    #[Route("/avisVisible", name: "avisVisible", methods: "GET")]
    #[OA\Get(
        path: '/api/avis/avisVisible',
        summary: 'Liste des avis visibles',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des avis visibles',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    type: "integer",
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'note',
                                    type: 'integer',
                                    example: 5
                                ),
                                new OA\Property(
                                    property: 'commentaire',
                                    type: 'string',
                                    example: 'Service excellent !'
                                ),
                                new OA\Property(
                                    property: 'date de reservation',
                                    type: 'string',
                                    example: '01-05-2025'
                                ),
                                new OA\Property(
                                    property: 'reservation',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(
                                            property: 'id',
                                            type: 'integer',
                                            example: 12
                                        ),
                                        new OA\Property(
                                            property: 'statut',
                                            type: 'string',
                                            example: 'terminée'
                                        ),
                                        new OA\Property(
                                            property: 'date',
                                            type: 'string',
                                            example: '30-04-2025'
                                        )
                                    ]
                                )
                            ]
                        )
                    )
                )
            )
        ]
    )]
    public function avisVisible(): JsonResponse
    {
        $avisVisible = $this->repository
            ->findBy(
                [
                    'isVisible' => true
                ]
            );

        $data = array_map(function (Avis $avis) {
            $reservation = $avis->getReservation();

            return [
                'note' => $avis->getNote(),
                'commentaire' => $avis->getCommentaire(),
                'date de reservation' => $avis->getCreatedAt()->format("d-m-Y"),
                'reservation' => [
                    'id' => $reservation->getId(),
                    'statut' => $reservation->getStatut(),
                    'date' => $reservation->getCreatedAt()->format('d-m-Y'),
                ],
            ];
        }, $avisVisible);

        return new JsonResponse(
            $data,
            JsonResponse::HTTP_OK
        );
    }

    #[Route(
        '/employee/validate-avis/{id}',
        name: 'employee_validate_avis',
        methods: 'PUT'
    )]
    #[OA\Put(
        path: "/api/avis/employee/validate-avis/{id}",
        summary: "Valider un avis client",
        description: "Validation d'un avis client par un employé.",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de l'avis à valider",
                schema: new OA\Schema(
                    type: "integer",
                    example: 3
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Avis validé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Avis validé avec succès"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Accès réfusé"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Avis non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Avis non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    #[IsGranted('ROLE_EMPLOYE')]
    public function validateAvis(
        int $id,
        EntityManagerInterface $manager
    ): JsonResponse {
        $avis = $manager
            ->getRepository(Avis::class)
            ->findOneBy(['id' => $id]);

        // Vérification si l'utilisateur a le rôle requis
        if (
            !$this->isGranted('ROLE_EMPLOYE')
        ) {
            return new JsonResponse(
                ['message' => 'Accès réfusé'],
                Response::HTTP_FORBIDDEN
            );
        }

        if (!$avis) {
            return new JsonResponse(
                ['error' => 'Avis non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Si l'avis a déjà été refusé, on empêche sa validation
        if ($avis->isRefused()) {
            return new JsonResponse(
                [
                    'error' => 'Impossible de valider un avis déjà refusé'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Valider l'avis
        $avis->setIsVisible(true);
        $avis->setIsRefused(false);

        $avis->setUpdatedAt(new \DateTimeImmutable());
        $manager->flush();

        return new JsonResponse(
            [
                'message' => 'Avis validé avec succès'
            ],
            Response::HTTP_OK
        );
    }

    #[Route(
        '/employee/refuse-avis/{id}',
        name: 'employee_refuse_avis',
        methods: 'PUT'
    )]
    #[OA\Put(
        path: "/api/avis/employee/refuse-avis/{id}",
        summary: "Refuser un avis client",
        description: "Refus de l'avis client par un employé.",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de l'avis à refuser",
                schema: new OA\Schema(
                    type: "integer",
                    example: 3
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Avis refusé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Avis refusé avec succès"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Accès réfusé"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Avis non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Avis non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    #[IsGranted('ROLE_EMPLOYE')]
    public function refuseAvis(
        int $id,
        EntityManagerInterface $manager
    ): JsonResponse {
        $avis = $manager
            ->getRepository(Avis::class)
            ->findOneBy(['id' => $id]);

        // Vérification si l'utilisateur a le rôle requis
        if (
            !$this->isGranted('ROLE_EMPLOYE')
        ) {
            return new JsonResponse(
                ['message' => 'Accès réfusé'],
                Response::HTTP_FORBIDDEN
            );
        }

        if (!$avis) {
            return new JsonResponse(
                ['error' => 'Avis non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Si l'avis a déjà été validé, on empêche son refus
        if ($avis->isVisible()) {
            return new JsonResponse(
                [
                    'error' => 'Impossible de refuser un avis déjà validé'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Refuser l'avis
        $avis->setIsRefused(true);
        $avis->setIsVisible(false);

        $avis->setUpdatedAt(new \DateTimeImmutable());
        $manager->flush();

        return new JsonResponse(
            [
                'message' => 'Avis refusé avec succès'
            ],
            Response::HTTP_OK
        );
    }

    #[Route("/{id}", name: "delete", methods: "DELETE")]
    #[OA\Delete(
        path: "/api/avis/{id}",
        summary: "Supprimer un avis",
        description: "Permet à un employé de supprimer un avis.",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de l'avis à supprimer",
                schema: new OA\Schema(
                    type: "integer",
                    example: 5
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Suppression d'un avis par un employé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Avis supprimé avec succès"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Avis non trouvé"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé - rôle manquant"
            )
        ]
    )]
    #[IsGranted('ROLE_EMPLOYE')]
    public function delete(int $id): JsonResponse
    {
        $avis = $this->repository
            ->findOneBy(
                ['id' => $id]
            );

        if ($avis) {
            $this->manager->remove($avis);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Avis supprimé avec succès"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
