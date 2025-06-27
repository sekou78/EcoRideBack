<?php

namespace App\Controller;

use App\Entity\ProfilConducteur;
use App\Entity\Reservation;
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
use Knp\Component\Pager\PaginatorInterface;

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
                        "statut",
                        "vehiculeId"
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
                        ),
                        new OA\Property(
                            property: "vehiculeId",
                            type: "integer",
                            example: 3
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
                                property: "vehicule",
                                type: "object",
                                properties: [
                                    new OA\Property(
                                        property: "id",
                                        type: "integer",
                                        example: 3
                                    ),
                                    new OA\Property(
                                        property: "plaqueImmatriculation",
                                        type: "string",
                                        example: "AB-123-CD"
                                    ),
                                    new OA\Property(
                                        property: "dateImmatriculation",
                                        type: "string",
                                        format: "date-time",
                                        example: "2010-10-10T00:00:00+02:00"
                                    ),
                                    new OA\Property(
                                        property: "modele",
                                        type: "string",
                                        example: "Clio"
                                    ),
                                    new OA\Property(
                                        property: "marque",
                                        type: "string",
                                        example: "Renault"
                                    ),
                                    new OA\Property(
                                        property: "couleur",
                                        type: "string",
                                        example: "Rouge"
                                    ),
                                    new OA\Property(
                                        property: "nombrePlaces",
                                        type: "integer",
                                        example: 5
                                    ),
                                    new OA\Property(
                                        property: "electrique",
                                        type: "boolean",
                                        example: true
                                    ),
                                ],
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

        // Assigner un vehicule du chauffeur au trajet
        // Récupération de l'ID du véhicule envoyé dans le JSON
        if (empty($data['vehiculeId'])) {
            return new JsonResponse(
                ['error' => 'Aucun identifiant de véhicule fourni.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $profilConducteur = $this->manager
            ->getRepository(ProfilConducteur::class)
            ->find(
                $data['vehiculeId']
            );

        if (!$profilConducteur) {
            return new JsonResponse(
                ['error' => 'Véhicule non trouvé.'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérification que le véhicule appartient bien à l'utilisateur connecté
        if ($profilConducteur->getUser() !== $user) {
            return new JsonResponse(
                ['error' => 'Ce véhicule ne vous appartient pas.'],
                Response::HTTP_FORBIDDEN
            );
        }

        // Vérifier que le véhicule n’est pas déjà affecté à un trajet non terminé
        $trajetEnCours = $this->manager
            ->getRepository(Trajet::class)
            ->findTrajetNonFiniParVehicule($profilConducteur);

        if ($trajetEnCours) {
            return new JsonResponse(
                ['error' => 'Ce véhicule est déjà affecté à un trajet en cours.'],
                Response::HTTP_CONFLICT
            );
        }

        // Affecter le statut "en attente" au trajet
        $trajet->setStatut('EN_ATTENTE');

        // Affectation du véhicule au trajet
        $trajet->setVehicule($profilConducteur);

        $trajet->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($trajet);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $trajet,
            'json',
            ['groups' => 'trajet:read', 'trajetChoisi:read']
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
                ['groups' => 'trajet:read', 'trajetChoisi:read']
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
        if (isset($data['adresseDepart'])) {
            $trajet->setAdresseDepart($data['adresseDepart']);
        }

        if (isset($data['adresseArrivee'])) {
            $trajet->setAdresseArrivee($data['adresseArrivee']);
        }

        if (isset($data['dateDepart'])) {
            $trajet->setDateDepart(new \DateTimeImmutable($data['dateDepart']));
        }

        if (isset($data['dateArrivee'])) {
            $trajet->setDateArrivee(new \DateTimeImmutable($data['dateArrivee']));
        }

        if (isset($data['prix'])) {
            $trajet->setPrix($data['prix']);
        }

        if (array_key_exists('estEcologique', $data)) {
            $trajet->setEstEcologique((bool) $data['estEcologique']);
        }

        if (isset($data['nombrePlacesDisponible'])) {
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

        // Gestion du véhicule (optionnel)
        if (!empty($data['vehiculeId'])) {
            $profilConducteur = $this->manager
                ->getRepository(ProfilConducteur::class)
                ->find(
                    $data['vehiculeId']
                );
            if (!$profilConducteur) {
                return new JsonResponse(
                    ['error' => 'Véhicule non trouvé.'],
                    Response::HTTP_NOT_FOUND
                );
            }
            if ($profilConducteur->getUser() !== $user) {
                return new JsonResponse(
                    ['error' => 'Ce véhicule ne vous appartient pas.'],
                    Response::HTTP_FORBIDDEN
                );
            }
            $trajet->setVehicule($profilConducteur);
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
            ['groups' => 'trajet:read', 'trajetChoisi:read']
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

        // Récupérer le prix du trajet une seule fois
        $montantARembourser = (int) round(floatval($trajet->getPrix()));

        foreach ($trajet->getReservations() as $reservation) {
            if (!$reservation->isRembourse()) {
                $passager = $reservation->getUser();

                // Rembourser les crédits au passager
                $passager->setCredits($passager->getCredits() + $montantARembourser);

                // Marquer la réservation comme remboursée
                $reservation->setIsRembourse(true);

                // Supprimer la réservation
                $this->manager->remove($reservation);
            }
        }

        // Le statut du trajet devient "TERMINEE"
        $trajet->setStatut('TERMINEE');

        $this->manager->flush();

        return new JsonResponse(
            [
                "message" => "Trajet supprimé avec succès"
            ],
            Response::HTTP_OK
        );
    }

    #[Route("/api/listeTrajets", name: "list", methods: "GET")]
    public function list(
        Request $request,
        PaginatorInterface $paginator
    ): JsonResponse {
        // Récupérer les paramètres de filtre
        $adresseDepartFilter = $request->query->get('adresseDepart');
        $adresseArriveeFilter = $request->query->get('adresseArrivee');
        $dateDepartInput = $request->query->get('dateDepart');

        // Création de la requête pour récupérer tous les Trajets
        $queryBuilder = $this->manager
            ->getRepository(Trajet::class)
            ->createQueryBuilder('a')
            ->innerJoin('a.chauffeur', 'c')
            ->leftJoin('c.avis', 'r')
            ->addSelect('c', 'r');

        // Filtre sur l’adresse de départ
        if ($adresseDepartFilter) {
            $queryBuilder->andWhere('a.adresseDepart LIKE :adresseDepart')
                ->setParameter('adresseDepart', '%' . $adresseDepartFilter . '%');
        }

        // Filtre sur l’adresse d’arrivée
        if ($adresseArriveeFilter) {
            $queryBuilder->andWhere('a.adresseArrivee LIKE :adresseArrivee')
                ->setParameter('adresseArrivee', '%' . $adresseArriveeFilter . '%');
        }

        // Filtre sur la date de départ (acceptant plusieurs formats)
        if ($dateDepartInput) {
            // Formats de date acceptés (à étendre si nécessaire)
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'];
            $dateDepartFilter = null;

            // On essaie de parser l'entrée avec chaque format
            foreach ($formats as $format) {
                $parsedDate = \DateTime::createFromFormat($format, $dateDepartInput);
                if ($parsedDate && $parsedDate->format($format) === $dateDepartInput) {
                    $dateDepartFilter = $parsedDate;
                    break; // stoppe dès qu’un format est valide
                }
            }

            // Si une date valide est trouvée, on filtre sur toute la journée
            if ($dateDepartFilter) {
                // Début et fin de la journée pour ignorer les heures
                $startOfDay = (clone $dateDepartFilter)->setTime(0, 0, 0);
                $endOfDay = (clone $dateDepartFilter)->setTime(23, 59, 59);

                $queryBuilder
                    ->andWhere('a.dateDepart BETWEEN :startOfDay AND :endOfDay')
                    ->setParameter('startOfDay', $startOfDay)
                    ->setParameter('endOfDay', $endOfDay);
            }
        }

        // Tri personnalisé : EN_COURS puis EN_ATTENTE puis les autres
        $queryBuilder->addSelect("
            CASE 
                WHEN a.statut = 'EN_COURS' THEN 1
                WHEN a.statut = 'EN_ATTENTE' THEN 2
                WHEN a.statut = 'TERMINEE' THEN 3
                ELSE 4
            END AS HIDDEN statutOrdre
        ");

        $queryBuilder->orderBy('statutOrdre', 'ASC');
        $queryBuilder->addOrderBy('a.dateDepart', 'DESC');

        // Application de la pagination (page et nombre d’éléments par page)
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1), // Numéro de page par défaut = 1
            5 // Nombre d’éléments par page
        );

        // Formatage des résultats pour l’API (JSON)
        $items = array_map(function ($trajet) {
            // $avis = array_map(
            //     function ($avis) {
            //         return [
            //             'id' => $avis->getId(),
            //             'note' => $avis->getNote(),
            //             'commentaire' => $avis->getCommentaire(),
            //         ];
            //     },
            //     $trajet->getChauffeur()->getAvis()->toArray(),
            // );
            $chauffeur = $trajet->getChauffeur();
            $avisChauffeur = [];
            $totalNotes = 0;
            $nombreAvis = 0;

            // Parcours des trajets du chauffeur
            foreach ($chauffeur->getTrajet() as $trajetChauffeur) {
                foreach ($trajetChauffeur->getReservations() as $reservation) {
                    foreach ($reservation->getAvis() as $avis) {
                        if ($avis->isVisible() && !$avis->isRefused()) {
                            $note = $avis->getNote();
                            $avisChauffeur[] = [
                                'id' => $avis->getId(),
                                'note' => $note,
                                'commentaire' => $avis->getCommentaire(),
                            ];
                            $totalNotes += $note;
                            $nombreAvis++;
                        }
                    }
                }
            }

            // Calcul de la note moyenne
            $moyenneNoteChauffeur = $nombreAvis > 0 ? round($totalNotes / $nombreAvis, 2) : null;

            return [
                'id' => $trajet->getId(),
                'adresseDepart' => $trajet->getAdresseDepart(),
                'adresseArrivee' => $trajet->getAdresseArrivee(),
                'placesDisponibles' => $trajet->getNombrePlacesDisponible(),
                'prix' => $trajet->getPrix(),
                'dateDepart' => $trajet->getDateDepart()?->format("d-m-Y"),
                'heureDepart' => $trajet->getHeureDepart()?->format("H:i"),
                'dateArrivee' => $trajet->getDateArrivee()?->format("d-m-Y"),
                'dureeVoyage' => $trajet->getDureeVoyage()?->format("H:i"),
                'peage' => $trajet->isPeage() ? 'oui' : 'non',
                'estEcologique' => $trajet->isEstEcologique() ? 'oui' : 'non',
                'chauffeur' => $chauffeur->getPseudo(),
                'avisChauffeur' => $avisChauffeur,
                'moyenneNoteChauffeur' => $moyenneNoteChauffeur,
                'image' => $chauffeur->getImage()
                    ? $this->generateUrl('app_api_image_show', ['id' => $chauffeur->getImage()->getId()])
                    : null,
                'statut' => $trajet->getStatut(),
                'createdAt' => $trajet->getCreatedAt()?->format("d-m-Y"),
            ];
        }, (array) $pagination->getItems());

        // Structure complète de la réponse avec pagination
        $data = [
            'currentPage' => $pagination->getCurrentPageNumber(),
            'totalItems' => $pagination->getTotalItemCount(),
            'itemsPerPage' => $pagination->getItemNumberPerPage(),
            'totalPages' => ceil(
                $pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()
            ),
            'items' => $items,
        ];

        // Retourner la réponse JSON
        return new JsonResponse(
            $data,
            JsonResponse::HTTP_OK
        );
    }

    #[Route('/', name: 'index', methods: 'GET')]
    public function index(): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connu'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (!$user) {
            return new JsonResponse(
                ['message' => 'Utilisateur non connecté.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $trajetRepo = $this->manager->getRepository(Trajet::class);
        $reservationRepo = $this->manager->getRepository(Reservation::class);

        // Trajets où l'utilisateur est chauffeur
        $trajetsChauffeur = $trajetRepo->createQueryBuilder('t')
            ->where('t.chauffeur = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        // Trajets où l'utilisateur est passager
        $reservations = $reservationRepo->createQueryBuilder('r')
            ->leftJoin('r.trajet', 't')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $trajetsPassager = array_map(function (Reservation $res) {
            return $res->getTrajet();
        }, $reservations);

        // Fusionner les deux listes et supprimer les doublons
        $allTrajets = array_unique(array_merge($trajetsChauffeur, $trajetsPassager), SORT_REGULAR);

        // Filtrer uniquement les trajets en cours
        $allTrajets = array_filter($allTrajets, function (Trajet $t) {
            return $t->getStatut() === 'EN_COURS';
        });

        // Trier par date décroissante
        usort($allTrajets, function ($a, $b) {
            return $b->getDateDepart() <=> $a->getDateDepart();
        });

        // Mapper les trajets au format JSON
        $items = array_map(function (Trajet $trajet) use ($user) {
            return [
                'id' => $trajet->getId(),
                'adresseDepart' => $trajet->getAdresseDepart(),
                'adresseArrivee' => $trajet->getAdresseArrivee(),
                'prix' => $trajet->getPrix(),
                'dateDepart' => $trajet->getDateDepart()?->format("d-m-Y"),
                'statut' => $trajet->getStatut(),
                'estChauffeur' => $trajet->getChauffeur() === $user,
            ];
        }, $allTrajets);

        return new JsonResponse(
            ['items' => $items],
            JsonResponse::HTTP_OK
        );
    }

    // #[Route('/api/historique', name: 'historique', methods: 'GET')]
    // public function historique(): JsonResponse
    // {
    //     $user = $this->getUser();

    //     if (!$user) {
    //         return new JsonResponse(
    //             ['message' => 'Utilisateur non connecté.'],
    //             JsonResponse::HTTP_UNAUTHORIZED
    //         );
    //     }

    //     $trajetRepo = $this->manager->getRepository(Trajet::class);
    //     $reservationRepo = $this->manager->getRepository(Reservation::class);

    //     // Trajets où l'utilisateur est chauffeur
    //     $trajetsChauffeur = $trajetRepo->createQueryBuilder('t')
    //         ->where('t.chauffeur = :user')
    //         ->setParameter('user', $user)
    //         ->getQuery()
    //         ->getResult();

    //     // Trajets où l'utilisateur est passager
    //     $reservations = $reservationRepo->createQueryBuilder('r')
    //         ->leftJoin('r.trajet', 't')
    //         ->where('r.user = :user')
    //         ->setParameter('user', $user)
    //         ->getQuery()
    //         ->getResult();

    //     $trajetsPassager = array_map(function (Reservation $res) {
    //         return $res->getTrajet();
    //     }, $reservations);

    //     // Fusionner les deux listes et supprimer les doublons
    //     $allTrajets = array_unique(array_merge($trajetsChauffeur, $trajetsPassager), SORT_REGULAR);

    //     // Optionnel : trier les trajets par date (desc)
    //     usort($allTrajets, function ($a, $b) {
    //         return $b->getDateDepart() <=> $a->getDateDepart();
    //     });

    //     // Mapper les trajets au format JSON
    //     $items = array_map(function (Trajet $trajet) use ($user) {
    //         return [
    //             'id' => $trajet->getId(),
    //             'adresseDepart' => $trajet->getAdresseDepart(),
    //             'adresseArrivee' => $trajet->getAdresseArrivee(),
    //             'prix' => $trajet->getPrix(),
    //             'dateDepart' => $trajet->getDateDepart()?->format("d-m-Y"),
    //             'statut' => $trajet->getStatut(),
    //             'estChauffeur' => $trajet->getChauffeur() === $user,
    //         ];
    //     }, $allTrajets);

    //     return new JsonResponse(
    //         ['items' => $items],
    //         JsonResponse::HTTP_OK
    //     );
    // }

    //  #[Route('/', name: 'index', methods: 'GET')]
    // public function index(): JsonResponse
    // {
    //     $user = $this->getUser();

    //     if (!$user) {
    //         return new JsonResponse(['message' => 'Utilisateur non connecté.'], JsonResponse::HTTP_UNAUTHORIZED);
    //     }

    //     $trajetRepo = $this->manager->getRepository(Trajet::class);
    //     $reservationRepo = $this->manager->getRepository(Reservation::class);

    //     // Trajets où l'utilisateur est chauffeur
    //     $trajetsChauffeur = $trajetRepo->createQueryBuilder('t')
    //         ->where('t.chauffeur = :user')
    //         ->setParameter('user', $user)
    //         ->getQuery()
    //         ->getResult();

    //     // Trajets où l'utilisateur est passager
    //     $reservations = $reservationRepo->createQueryBuilder('r')
    //         ->leftJoin('r.trajet', 't')
    //         ->where('r.user = :user')
    //         ->setParameter('user', $user)
    //         ->getQuery()
    //         ->getResult();

    //     $trajetsPassager = array_map(function (Reservation $res) {
    //         return $res->getTrajet();
    //     }, $reservations);

    //     // Fusionner les deux listes et supprimer les doublons
    //     $allTrajets = array_unique(array_merge($trajetsChauffeur, $trajetsPassager), SORT_REGULAR);

    //     // Optionnel : trier les trajets par date (desc)
    //     usort($allTrajets, function ($a, $b) {
    //         return $b->getDateDepart() <=> $a->getDateDepart();
    //     });

    //     // Mapper les trajets au format JSON
    //     $items = array_map(function (Trajet $trajet) use ($user) {
    //         return [
    //             'id' => $trajet->getId(),
    //             'adresseDepart' => $trajet->getAdresseDepart(),
    //             'adresseArrivee' => $trajet->getAdresseArrivee(),
    //             'prix' => $trajet->getPrix(),
    //             'dateDepart' => $trajet->getDateDepart()?->format("d-m-Y"),
    //             'statut' => $trajet->getStatut(),
    //             'estChauffeur' => $trajet->getChauffeur() === $user,
    //         ];
    //     }, $allTrajets);

    //     return new JsonResponse(['items' => $items], JsonResponse::HTTP_OK);
    // }
}
