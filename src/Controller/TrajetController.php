<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\TrajetRepository;
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

#[Route("api/trajet", name: "app_api_trajet_")]
final class TrajetController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private TrajetRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private ValidatorInterface $validator
    ) {}

    #[Route(methods: "POST")]
    #[OA\Post(
        path: '/api/trajet',
        summary: 'Créer un nouveau trajet',
        description: 'Permet à un CHAUFFEUR ou PASSAGER_CHAUFFEUR de créer un nouveau trajet',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    required: [
                        "adresseDepart",
                        "adresseArrivee",
                        "dateDepart",
                        "dateArrivee",
                        "heureDepart",
                        "dureeVoyage",
                        "peage",
                        "prix",
                        "estEcologique",
                        "nombrePlacesDisponible",
                        "statut"
                    ],
                    properties: [
                        new OA\Property(
                            property: "adresseDepart",
                            type: "string",
                            example: "45 rue de la ville XXXXXX La Ville"
                        ),
                        new OA\Property(
                            property: "adresseArrivee",
                            type: "string",
                            example: "Parking de la ville XXXXXX La Ville"
                        ),
                        new OA\Property(
                            property: "dateDepart",
                            type: "string",
                            format: "date-time",
                            example: "10/10/2025"
                        ),
                        new OA\Property(
                            property: "dateArrivee",
                            type: "string",
                            format: "date-time",
                            example: "10/10/2025"
                        ),
                        new OA\Property(
                            property: "heureDepart",
                            type: "string",
                            example: "09:00"
                        ),
                        new OA\Property(
                            property: "dureeVoyage",
                            type: "string",
                            example: "01:00"
                        ),
                        new OA\Property(
                            property: "peage",
                            type: "boolean",
                            example: true
                        ),
                        new OA\Property(
                            property: "prix",
                            type: "string",
                            example: "30"
                        ),
                        new OA\Property(
                            property: "estEcologique",
                            type: "boolean",
                            example: false
                        ),
                        new OA\Property(
                            property: "nombrePlacesDisponible",
                            type: "integer",
                            example: 5
                        ),
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
                response: 201,
                description: "Trajet créée avec succès",
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
                                property: "adresseDepart",
                                type: "string",
                                example: "45 rue de la ville XXXXXX La Ville"
                            ),
                            new OA\Property(
                                property: "adresseArrivee",
                                type: "string",
                                example: "Parking de la ville XXXXXX La Ville"
                            ),
                            new OA\Property(
                                property: "dateDepart",
                                type: "string",
                                format: "date-time",
                            ),
                            new OA\Property(
                                property: "dateArrivee",
                                type: "string",
                                format: "date-time",
                                example: "2025-04-14T09:00:00+02:00"
                            ),
                            new OA\Property(
                                property: "heureDepart",
                                type: "string",
                                example: "09:00"
                            ),
                            new OA\Property(
                                property: "dureeVoyage",
                                type: "string",
                                example: "01:00"
                            ),
                            new OA\Property(
                                property: "peage",
                                type: "boolean",
                                example: true
                            ),
                            new OA\Property(
                                property: "prix",
                                type: "string",
                                example: "30"
                            ),
                            new OA\Property(
                                property: "estEcologique",
                                type: "boolean",
                                example: false
                            ),
                            new OA\Property(
                                property: "nombrePlacesDisponible",
                                type: "integer",
                                example: 5
                            ),
                            new OA\Property(
                                property: "statut",
                                type: "string",
                                example: "EN_ATTENTE"
                            ),
                            new OA\Property(
                                property: "chauffeur",
                                type: "object",
                                properties: [
                                    new OA\Property(
                                        property: "id",
                                        type: "integer",
                                        example: 18
                                    ),
                                    new OA\Property(
                                        property: "pseudo",
                                        type: "string",
                                        example: "testuser"
                                    )
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erreur de validation ou données invalides'
            ),
            new OA\Response(
                response: 403,
                description: 'L’utilisateur ne possède pas le rôle requis ou un passager n’est pas autorisé'
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $trajet = $this->serializer->deserialize(
            $request->getContent(),
            Trajet::class,
            'json'
        );

        $errors = $this->validator->validate($trajet);
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

        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        // Vérifier si l'utilisateur possède l'un des rôles requis
        if (!$user || !in_array(
            'ROLE_CHAUFFEUR',
            $user->getRoles()
        ) && !in_array(
            'ROLE_PASSAGER_CHAUFFEUR',
            $user->getRoles()
        )) {
            return new JsonResponse(
                [
                    'error' => "L'utilisateur doit être un chauffeur ou un passager_chauffeur."
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Ajouter l'utilisateur au trajet
        $trajet->addUser($user);

        // Récupérer les utilisateurs passagers
        if (
            !empty($data['user'])
            &&
            is_array($data['user'])
        ) {
            foreach ($data['user'] as $userId) {
                $userEntity = $this->manager
                    ->getRepository(User::class)
                    ->find($userId);

                if ($userEntity) {
                    // Vérifier les rôles autorisés
                    $roles = $userEntity->getRoles();
                    if (
                        in_array('ROLE_PASSAGER', $roles) ||
                        in_array('ROLE_PASSAGER_CHAUFFEUR', $roles)
                    ) {
                        // Vérifier que l'utilisateur n'est pas déjà ajouté
                        if (!$trajet->getUsers()->contains($userEntity)) {
                            $trajet->addUser($userEntity);
                        } else {
                            return new JsonResponse(
                                [
                                    'error' => "L'utilisateur avec l'ID $userId est déjà ajouté au trajet."
                                ],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                    } else {
                        return new JsonResponse(
                            [
                                'error' => "L'utilisateur avec l'ID $userId n'a pas le rôle requis."
                            ],
                            Response::HTTP_FORBIDDEN
                        );
                    }
                }
            }
        }

        // Vérifier que le nombre de passagers ne dépasse pas le nombre de places disponibles
        $passagers = array_filter(
            $trajet->getUsers()->toArray(),
            function ($user) {
                return in_array(
                    'ROLE_PASSAGER',
                    $user->getRoles()
                );
            }
        );

        if (count($passagers) > $trajet->getNombrePlacesDisponible()) {
            return new JsonResponse(
                [
                    'error' => "Le nombre de passagers ne peut pas dépasser le nombre de places disponibles."
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Assigner l'utilisateur authentifié comme chauffeur du trajet
        $trajet->setChauffeur($user);

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
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_CREATED,
            ['Location' => $location],
            true
        );
    }

    #[Route("/{id}", name: "show", methods: "GET")]
    #[OA\Get(
        path: '/api/trajet/{id}',
        summary: 'Afficher un trajet spécifique',
        description: 'Récupère les détails d’un trajet via son ID.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Identifiant du trajet',
                schema: new OA\Schema(
                    type: 'integer'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détails du trajet trouvés',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "adresseDepart",
                                type: "string",
                                example: "45 rue de la ville XXXXXX La Ville"
                            ),
                            new OA\Property(
                                property: "adresseArrivee",
                                type: "string",
                                example: "Parking de la ville XXXXXX La Ville"
                            ),
                            new OA\Property(
                                property: "dateDepart",
                                type: "string",
                                format: "date-time",
                            ),
                            new OA\Property(
                                property: "dateArrivee",
                                type: "string",
                                format: "date-time",
                                example: "2025-04-14T09:00:00+02:00"
                            ),
                            new OA\Property(
                                property: "heureDepart",
                                type: "string",
                                example: "09:00"
                            ),
                            new OA\Property(
                                property: "dureeVoyage",
                                type: "string",
                                example: "01:00"
                            ),
                            new OA\Property(
                                property: "peage",
                                type: "boolean",
                                example: true
                            ),
                            new OA\Property(
                                property: "prix",
                                type: "string",
                                example: "30"
                            ),
                            new OA\Property(
                                property: "estEcologique",
                                type: "boolean",
                                example: false
                            ),
                            new OA\Property(
                                property: "nombrePlacesDisponible",
                                type: "integer",
                                example: 5
                            ),
                            new OA\Property(
                                property: "statut",
                                type: "string",
                                example: "EN_ATTENTE"
                            ),
                            new OA\Property(
                                property: "chauffeur",
                                type: "object",
                                properties: [
                                    new OA\Property(
                                        property: "id",
                                        type: "integer",
                                        example: 18
                                    ),
                                    new OA\Property(
                                        property: "pseudo",
                                        type: "string",
                                        example: "testuser"
                                    )
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Trajet non trouvé'
            )
        ]
    )]
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
    #[OA\Put(
        path: '/api/trajet/{id}',
        summary: 'Modifier un trajet',
        description: 'Permet à un chauffeur ou passager_chauffeur authentifié de modifier son trajet.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Identifiant du trajet à modifier',
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
                    properties: [
                        new OA\Property(
                            property: "adresseDepart",
                            type: "string",
                            example: "45 rue de la ville XXXXXX La Ville"
                        ),
                        new OA\Property(
                            property: "adresseArrivee",
                            type: "string",
                            example: "Parking de la ville XXXXXX La Ville"
                        ),
                        new OA\Property(
                            property: "dateDepart",
                            type: "string",
                            format: "date-time",
                            example: "11/10/2025"
                        ),
                        new OA\Property(
                            property: "dateArrivee",
                            type: "string",
                            format: "date-time",
                            example: "11/10/2025"
                        ),
                        new OA\Property(
                            property: "heureDepart",
                            type: "string",
                            example: "10:00"
                        ),
                        new OA\Property(
                            property: "dureeVoyage",
                            type: "string",
                            example: "01:30"
                        ),
                        new OA\Property(
                            property: "peage",
                            type: "boolean",
                            example: false
                        ),
                        new OA\Property(
                            property: "prix",
                            type: "string",
                            example: "20"
                        ),
                        new OA\Property(
                            property: "estEcologique",
                            type: "boolean",
                            example: true
                        ),
                        new OA\Property(
                            property: "nombrePlacesDisponible",
                            type: "integer",
                            example: 3
                        ),
                        new OA\Property(
                            property: "statut",
                            type: "string",
                            example: "EN_COURS"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trajet modifié avec succès',
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
                                property: "adresseDepart",
                                type: "string",
                                example: "45 rue de la ville XXXXXX La Ville"
                            ),
                            new OA\Property(
                                property: "adresseArrivee",
                                type: "string",
                                example: "Parking de la ville XXXXXX La Ville"
                            ),
                            new OA\Property(
                                property: "dateDepart",
                                type: "string",
                                format: "date-time",
                                example: "11/10/2025"
                            ),
                            new OA\Property(
                                property: "dateArrivee",
                                type: "string",
                                format: "date-time",
                                example: "11/10/2025"
                            ),
                            new OA\Property(
                                property: "heureDepart",
                                type: "string",
                                example: "10:00"
                            ),
                            new OA\Property(
                                property: "dureeVoyage",
                                type: "string",
                                example: "01:30"
                            ),
                            new OA\Property(
                                property: "peage",
                                type: "boolean",
                                example: false
                            ),
                            new OA\Property(
                                property: "prix",
                                type: "string",
                                example: "20"
                            ),
                            new OA\Property(
                                property: "estEcologique",
                                type: "boolean",
                                example: true
                            ),
                            new OA\Property(
                                property: "nombrePlacesDisponible",
                                type: "integer",
                                example: 3
                            ),
                            new OA\Property(
                                property: "statut",
                                type: "string",
                                example: "EN_COURS"
                            ),
                            new OA\Property(
                                property: 'chauffeur',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'id',
                                        type: 'integer',
                                        example: 18
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
                description: 'Erreur de validation dans les données'
            ),
            new OA\Response(
                response: 403,
                description: "Vous n'êtes pas autorisé à modifier ce trajet"
            ),
            new OA\Response(
                response: 404,
                description: 'Trajet ou passager introuvable'
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $trajet = $this->manager
            ->getRepository(Trajet::class)
            ->findOneBy(['id' => $id]);

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé.'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier que l'utilisateur connecté est le créateur de la réservation
        $user = $this->security->getUser();

        if ($trajet->getChauffeur() !== $user) {
            return new JsonResponse(
                ['error' => "Vous n'êtes pas autorisé à modifier ce trajet."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Vérifier si l'utilisateur est un chauffeur ou un passager_chauffeur
        if (!$user || !in_array(
            'ROLE_CHAUFFEUR',
            $user->getRoles()
        ) && !in_array(
            'ROLE_PASSAGER_CHAUFFEUR',
            $user->getRoles()
        )) {
            return new JsonResponse(
                [
                    'error' => "L'utilisateur doit être un chauffeur ou un passager_chauffeur."
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Si des nouvelles données sont envoyées, on les utilise pour mettre à jour le trajet
        if ($data['adresseDepart']) {
            $trajet->setAdresseDepart($data['adresseDepart']);
        }

        if ($data['adresseArrivee']) {
            $trajet->setAdresseArrivee($data['adresseArrivee']);
        }

        if ($data['dateDepart']) {
            $trajet->setDateDepart(new \DateTimeImmutable($data['dateDepart']));
        }

        if ($data['dateArrivee']) {
            $trajet->setDateArrivee(new \DateTimeImmutable($data['dateArrivee']));
        }

        if ($data['prix']) {
            $trajet->setPrix($data['prix']);
        }

        if (array_key_exists('estEcologique', $data)) {
            $trajet->setEstEcologique((bool) $data['estEcologique']);
        }

        if ($data['nombrePlacesDisponible']) {
            $trajet->setNombrePlacesDisponible($data['nombrePlacesDisponible']);
        }

        if (!empty($data['heureDepart'])) {
            $trajet->setHeureDepart(new \DateTime($data['heureDepart']));
        }

        if (!empty($data['dureeVoyage'])) {
            $trajet->setDureeVoyage(new \DateTime($data['dureeVoyage']));
        }

        if (array_key_exists('peage', $data)) {
            $trajet->setPeage((bool) $data['peage']);
        }

        if ($data['statut']) {
            $trajet->setStatut($data['statut']);
        }

        $errors = $this->validator->validate($data);
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

        // Réinitialiser les utilisateurs passagers existants avant d'ajouter les nouveaux
        if (
            !empty($data['user'])
            &&
            is_array($data['user'])
        ) {
            foreach ($trajet->getUsers() as $existingUser) {
                $trajet->removeUser($existingUser);
            }

            foreach ($data['user'] as $userId) {
                $userEntity = $this->manager
                    ->getRepository(User::class)
                    ->find($userId);

                if ($userEntity) {
                    $roles = $userEntity->getRoles();
                    if (
                        in_array('ROLE_PASSAGER', $roles) ||
                        in_array('ROLE_PASSAGER_CHAUFFEUR', $roles)
                    ) {
                        $trajet->addUser($userEntity);
                    } else {
                        return new JsonResponse([
                            'error' => "L'utilisateur avec l'ID $userId n'a pas un rôle valide."
                        ], Response::HTTP_FORBIDDEN);
                    }
                } else {
                    return new JsonResponse([
                        'error' => "Utilisateur avec l'ID $userId introuvable."
                    ], Response::HTTP_NOT_FOUND);
                }
            }
        }

        // Vérifier que le nombre de passagers ne dépasse pas le nombre de places disponibles
        $passagers = array_filter(
            $trajet->getUsers()->toArray(),
            function ($user) {
                return in_array(
                    'ROLE_PASSAGER',
                    $user->getRoles()
                );
            }
        );

        $totalPassagers = count($passagers);

        if ($totalPassagers > $trajet->getNombrePlacesDisponible()) {
            return new JsonResponse(
                [
                    'error' => "Le nombre de passagers ne peut pas dépasser le nombre de places disponibles."
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $trajet->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

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

    #[Route("/{id}", name: "delete", methods: "DELETE")]
    #[OA\Delete(
        path: '/api/trajet/{id}',
        summary: 'Supprimer un trajet',
        description: 'Supprimer un trajet si le trajet est à l’utilisateur connecté.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Identifiant du trajet à supprimer',
                schema: new OA\Schema(
                    type: 'integer'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trajet supprimé',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'message',
                                type: 'string',
                                example: 'Trajet supprimé avec succès'
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Utilisateur non autorisé à supprimer ce trajet'
            ),
            new OA\Response(
                response: 404,
                description: 'Trajet non trouvé'
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $trajet = $this->repository->findOneBy(['id' => $id]);

        if (!$trajet) {
            return new JsonResponse(
                null,
                Response::HTTP_NOT_FOUND
            );
        }

        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        // Vérifier si l'utilisateur est bien le créateur du trajet
        if ($trajet->getChauffeur() !== $user) {
            return new JsonResponse(
                ['error' => "Vous n'êtes pas autorisé à supprimer ce trajet."],
                Response::HTTP_FORBIDDEN
            );
        }

        $this->manager->remove($trajet);

        $this->manager->flush();

        return new JsonResponse(
            [
                "message" => "Trajet supprimé avec succès"
            ],
            Response::HTTP_OK
        );
    }
}
