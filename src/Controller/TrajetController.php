<?php

namespace App\Controller;

use App\Entity\ProfilConducteur;
use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Repository\TrajetRepository;
use App\Service\ArchivageService;
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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route("api/trajet", name: "app_api_trajet_")]
final class TrajetController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private TrajetRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private ValidatorInterface $validator,
        private ReservationRepository $reservationRepository,
        private ArchivageService $archivageService,
    ) {}

    #[Route(methods: ["POST"])]
    #[OA\Post(
        path: '/api/trajet',
        summary: 'Créer un nouveau trajet',
        description: 'Permet à un CHAUFFEUR ou PASSAGER_CHAUFFEUR de créer un nouveau trajet',
        tags: ["Trajets"],
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

        // AJOUT : comparer les places du trajet et celles du véhicule
        // ex. 4
        $placesVehicule = $profilConducteur->getNombrePlaces();
        // ex. 5          
        $placesTrajet   = $trajet->getNombrePlacesDisponible();

        if ($placesTrajet > $placesVehicule) {
            return new JsonResponse(
                ['error' => "Le trajet demande $placesTrajet place(s) alors que "
                    . "le véhicule n'en possède que $placesVehicule."],
                Response::HTTP_BAD_REQUEST
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

    #[Route("/{id}", name: "show", methods: ["GET"])]
    #[OA\Get(
        path: '/api/trajet/{id}',
        summary: 'Afficher un trajet spécifique',
        description: 'Récupère les détails d’un trajet via son ID.',
        tags: ["Trajets"],
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

    #[Route("/{id}", name: "edit", methods: ["PUT"])]
    #[OA\Put(
        path: '/api/trajet/{id}',
        summary: 'Modifier un trajet',
        description: 'Permet à un chauffeur ou passager_chauffeur authentifié de modifier son trajet.',
        tags: ["Trajets"],
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

        // Gestion du véhicule
        $profilConducteur = null;

        // 1. Véhicule déjà affecté
        if ($trajet->getVehicule()) {
            $profilConducteur = $trajet->getVehicule();
        }

        // 2. Éventuel nouveau véhicule passé dans la requête
        if (!empty($data['vehiculeId'])) {
            $place = $this->manager
                ->getRepository(ProfilConducteur::class)
                ->find($data['vehiculeId']);

            if (!$place) {
                return new JsonResponse(
                    ['error' => 'Véhicule non trouvé.'],
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($place->getUser() !== $user) {
                return new JsonResponse(
                    ['error' => 'Ce véhicule ne vous appartient pas.'],
                    Response::HTTP_FORBIDDEN
                );
            }

            // On le remplace uniquement si valide
            $profilConducteur = $place;
            $trajet->setVehicule($place);
        }

        // AJOUT de la vérification places trajet vs places véhicule
        if ($profilConducteur) {
            $placesVehicule = $profilConducteur->getNombrePlaces();
            $placesTrajet = $trajet->getNombrePlacesDisponible();

            if ($placesTrajet > $placesVehicule) {
                return new JsonResponse(
                    [
                        'error' => "Le trajet demande $placesTrajet place(s) alors que le véhicule n'en possède que $placesVehicule."
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
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

        if ($trajet->getStatut() === 'TERMINEE') {
            $this->archivageService->archiverTrajet($trajet);
        }

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

    #[Route("/{id}", name: "delete", methods: ["DELETE"])]
    #[OA\Delete(
        path: '/api/trajet/{id}',
        summary: 'Supprimer un trajet',
        description: 'Supprimer un trajet si le trajet est à l’utilisateur connecté.',
        tags: ["Trajets"],
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

    #[Route("/sendMailPassengers/{id}", name: "sendMailPassengers", methods: ["POST"])]
    #[IsGranted('ROLE_USER')]
    public function sendMailPassengers(
        int $id,
        Request $request,
        MailerInterface $mailer
    ): JsonResponse {
        $trajet = $this->manager
            ->getRepository(Trajet::class)
            ->findOneBy(['id' => $id]);

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                [
                    'error' => 'Utilisateur non authentifié'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier que seul le chauffeur du trajet peut envoyer les notifications
        if ($trajet->getChauffeur() !== $user) {
            return new JsonResponse(
                [
                    'error' => "Vous n'êtes pas autorisé à envoyer des messages pour ce trajet."
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer les passagers
        $passagers = array_filter(
            $trajet->getUsers()->toArray(),
            function ($user) {
                return in_array('ROLE_PASSAGER', $user->getRoles())
                    || in_array('ROLE_PASSAGER_CHAUFFEUR', $user->getRoles());
            }
        );

        if (empty($passagers)) {
            return new JsonResponse(
                ['message' => 'Aucun passager trouvé pour ce trajet.'],
                Response::HTTP_OK
            );
        }

        // Message du chauffeur
        $data = json_decode($request->getContent(), true) ?? [];
        $messageChauffeur = $data['message'] ?? "votre trajet est terminé.";

        // Envoi du mail à chaque passager
        foreach ($passagers as $passager) {
            $email = (new Email())
                ->from('ecoride_studi@dinga223.fr')
                ->replyTo($user->getEmail()) // L'adresse email du chauffeur
                ->to($passager->getEmail()) // L'adresse email du passager
                ->subject('Validation de votre trajet')
                ->text("
                    Bonjour {$passager->getPrenom()},

                    Le chauffeur {$user->getPrenom()} {$user->getNom()} vous informe :
                    {$messageChauffeur}

                    Merci de vous rendre sur votre espace afin d’indiquer si tout s’est bien passé avec ce trajet.
                    
                    À bientôt,
                    L’équipe Covoiturage EcoRide.
                ");

            $mailer->send($email);
        }

        return new JsonResponse(
            ['message' => 'Les passagers ont été notifiés par email.'],
            Response::HTTP_OK
        );
    }

    #[Route("/api/listeTrajets", name: "list", methods: ["GET"])]
    #[OA\Get(
        path: "/api/trajet/api/listeTrajets",
        summary: "Liste des trajets avec filtres et pagination",
        description: "Retourne les trajets disponibles avec filtres 
                        sur adresse de départ, adresse d'arrivée et date 
                        de départ. Tri personnalisé et pagination appliqués.",
        tags: ["Trajets"],
        parameters: [
            new OA\Parameter(
                name: "adresseDepart",
                in: "query",
                description: "Filtrer les trajets par adresse de départ",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    example: "Paris"
                )
            ),
            new OA\Parameter(
                name: "adresseArrivee",
                in: "query",
                description: "Filtrer les trajets par adresse d'arrivée",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    example: "Lyon"
                )
            ),
            new OA\Parameter(
                name: "dateDepart",
                in: "query",
                description: "Filtrer les trajets par date de départ 
                                (formats acceptés : Y-m-d, d/m/Y, d-m-Y, m/d/Y)",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    example: "23-08-2025"
                )
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Numéro de page pour la pagination",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste paginée des trajets",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "currentPage",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "totalItems",
                                type: "integer",
                                example: 42
                            ),
                            new OA\Property(
                                property: "itemsPerPage",
                                type: "integer",
                                example: 5
                            ),
                            new OA\Property(
                                property: "totalPages",
                                type: "integer",
                                example: 9
                            ),
                            new OA\Property(
                                property: "items",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(
                                            property: "id",
                                            type: "integer",
                                            example: 15
                                        ),
                                        new OA\Property(
                                            property: "adresseDepart",
                                            type: "string",
                                            example: "Paris"
                                        ),
                                        new OA\Property(
                                            property: "adresseArrivee",
                                            type: "string",
                                            example: "Lyon"
                                        ),
                                        new OA\Property(
                                            property: "placesDisponibles",
                                            type: "integer",
                                            example: 3
                                        ),
                                        new OA\Property(
                                            property: "prix",
                                            type: "number",
                                            format: "float",
                                            example: 45.5
                                        ),
                                        new OA\Property(
                                            property: "dateDepart",
                                            type: "string",
                                            example: "23-08-2025"
                                        ),
                                        new OA\Property(
                                            property: "heureDepart",
                                            type: "string",
                                            example: "08:30"
                                        ),
                                        new OA\Property(
                                            property: "dateArrivee",
                                            type: "string",
                                            example: "23-08-2025"
                                        ),
                                        new OA\Property(
                                            property: "dureeVoyage",
                                            type: "string",
                                            example: "02:30"
                                        ),
                                        new OA\Property(
                                            property: "peage",
                                            type: "string",
                                            example: "oui"
                                        ),
                                        new OA\Property(
                                            property: "estEcologique",
                                            type: "string",
                                            example: "non"
                                        ),
                                        new OA\Property(
                                            property: "chauffeur",
                                            type: "string",
                                            example: "Dinga223"
                                        ),
                                        new OA\Property(
                                            property: "avisChauffeur",
                                            type: "array",
                                            items: new OA\Items(
                                                type: "object",
                                                properties: [
                                                    new OA\Property(
                                                        property: "id",
                                                        type: "integer",
                                                        example: 7
                                                    ),
                                                    new OA\Property(
                                                        property: "note",
                                                        type: "integer",
                                                        example: 5
                                                    ),
                                                    new OA\Property(
                                                        property: "commentaire",
                                                        type: "string",
                                                        example: "Parfait !"
                                                    )
                                                ]
                                            )
                                        ),
                                        new OA\Property(
                                            property: "moyenneNoteChauffeur",
                                            type: "number",
                                            format: "float",
                                            example: 4.5
                                        ),
                                        new OA\Property(
                                            property: "image",
                                            type: "string",
                                            nullable: true,
                                            example: "/api/image/23"
                                        ),
                                        new OA\Property(
                                            property: "statut",
                                            type: "string",
                                            example: "EN_COURS"
                                        ),
                                        new OA\Property(
                                            property: "createdAt",
                                            type: "string",
                                            example: "22-08-2025"
                                        )
                                    ]
                                )
                            )
                        ]
                    )
                )
            )
        ]
    )]
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

    #[Route("/api/trajetsFiltres", name: "filtre", methods: ["GET"])]
    #[OA\Get(
        path: "/api/trajet/api/trajetsFiltres",
        summary: "Trajets filtrés avec pagination",
        description: "Retourne les trajets filtrés par 
                        prix, durée, statut et si le trajet est écologique.",
        tags: ["Trajets"],
        parameters: [
            new OA\Parameter(
                name: "estEcologique",
                in: "query",
                description: "Filtrer les trajets écologiques (true/false)",
                required: false,
                schema: new OA\Schema(
                    type: "boolean"
                )
            ),
            new OA\Parameter(
                name: "prix",
                in: "query",
                description: "Filtrer les trajets dont le prix est inférieur ou égal",
                required: false,
                schema: new OA\Schema(
                    type: "number",
                    format: "float"
                )
            ),
            new OA\Parameter(
                name: "dureeVoyage",
                in: "query",
                description: "Filtrer les trajets dont la durée 
                                est inférieure ou égale (format HH:MM)",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    example: "02:30"
                )
            ),
            new OA\Parameter(
                name: "statut",
                in: "query",
                description: "Filtrer par statut (ex: EN_ATTENTE, EN_COURS, etc.)",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    example: "EN_ATTENTE"
                )
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Numéro de page pour la pagination",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 1
                )
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                description: "Nombre d'éléments par page",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 5
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste paginée des trajets filtrés",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "currentPage",
                                type: "integer",
                                example: 1
                            ),
                            new OA\Property(
                                property: "totalItems",
                                type: "integer",
                                example: 50
                            ),
                            new OA\Property(
                                property: "itemsPerPage",
                                type: "integer",
                                example: 5
                            ),
                            new OA\Property(
                                property: "totalPages",
                                type: "integer",
                                example: 10
                            ),
                            new OA\Property(
                                property: "items",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(
                                            property: "id",
                                            type: "integer",
                                            example: 12
                                        ),
                                        new OA\Property(
                                            property: "adresseDepart",
                                            type: "string",
                                            example: "Paris"
                                        ),
                                        new OA\Property(
                                            property: "adresseArrivee",
                                            type: "string",
                                            example: "Lyon"
                                        ),
                                        new OA\Property(
                                            property: "placesDisponibles",
                                            type: "integer",
                                            example: 3
                                        ),
                                        new OA\Property(
                                            property: "prix",
                                            type: "number",
                                            format: "float",
                                            example: 45.5
                                        ),
                                        new OA\Property(
                                            property: "dateDepart",
                                            type: "string",
                                            example: "23-08-2025"
                                        ),
                                        new OA\Property(
                                            property: "heureDepart",
                                            type: "string",
                                            example: "08:30"
                                        ),
                                        new OA\Property(
                                            property: "dateArrivee",
                                            type: "string",
                                            example: "23-08-2025"
                                        ),
                                        new OA\Property(
                                            property: "dureeVoyage",
                                            type: "string",
                                            example: "02:30"
                                        ),
                                        new OA\Property(
                                            property: "peage",
                                            type: "string",
                                            example: "oui"
                                        ),
                                        new OA\Property(
                                            property: "estEcologique",
                                            type: "string",
                                            example: "non"
                                        ),
                                        new OA\Property(
                                            property: "chauffeur",
                                            type: "string",
                                            example: "JeanDupont"
                                        ),
                                        new OA\Property(
                                            property: "avisChauffeur",
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
                                                        type: "number",
                                                        format: "float",
                                                        example: 4.5
                                                    ),
                                                    new OA\Property(
                                                        property: "commentaire",
                                                        type: "string",
                                                        example: "Très bon trajet"
                                                    )
                                                ]
                                            )
                                        ),
                                        new OA\Property(
                                            property: "moyenneNoteChauffeur",
                                            type: "number",
                                            format: "float",
                                            example: 4.2
                                        ),
                                        new OA\Property(
                                            property: "image",
                                            type: "string",
                                            example: "/api/image/12"
                                        ),
                                        new OA\Property(
                                            property: "statut",
                                            type: "string",
                                            example: "EN_ATTENTE"
                                        ),
                                        new OA\Property(
                                            property: "createdAt",
                                            type: "string",
                                            example: "23-08-2025"
                                        )
                                    ]
                                )
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function filtre(
        Request $request,
        PaginatorInterface $paginator
    ): JsonResponse {
        // Récupérer les paramètres de filtre
        $ecologiqueFilter = $request->query->get('estEcologique');
        $prixFilter = $request->query->get('prix');
        $dureeVoyageFilter = $request->query->get('dureeVoyage');

        // Ajout récupération du filtre statut
        $statutFilter = $request->query->get('statut') ?? ['EN_ATTENTE'];

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5); // par défaut 5 items par page

        // Création de la requête pour récupérer tous les Trajets
        $queryBuilder = $this->manager
            ->getRepository(Trajet::class)
            ->createQueryBuilder('a')
            ->innerJoin('a.chauffeur', 'c')
            ->leftJoin('c.avis', 'r')
            ->addSelect('c', 'r');

        // Filtre est écologique
        if ($ecologiqueFilter !== null) {
            $boolValue = filter_var($ecologiqueFilter, FILTER_VALIDATE_BOOLEAN);
            $queryBuilder->andWhere('a.estEcologique = :eco')
                ->setParameter('eco', $boolValue);
        }

        // Filtre sur le prix
        if ($prixFilter !== null) {
            $queryBuilder->andWhere('a.prix <= :prix')
                ->setParameter('prix', $prixFilter);
        }

        // Filtre sur la durée du voyage
        if ($dureeVoyageFilter) {
            $queryBuilder->andWhere('a.dureeVoyage <= :duree')
                ->setParameter('duree', new \DateTime($dureeVoyageFilter));
        }

        // Filtre sur le statut (EN_ATTENTE)
        if (!is_array($statutFilter)) {
            $statutFilter = array_map('trim', explode(',', $statutFilter));
        }

        $queryBuilder->andWhere('a.statut IN (:statuts)')
            ->setParameter('statuts', $statutFilter);

        // Tri personnalisé : EN_COURS puis EN_ATTENTE puis les autres
        $queryBuilder->addSelect("
            CASE 
                WHEN a.statut = 'EN_ATTENTE' THEN 1
                ELSE 3
            END AS HIDDEN statutOrdre
        ");

        $queryBuilder->orderBy('statutOrdre', 'ASC');
        $queryBuilder->addOrderBy('a.dateDepart', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );

        // Formatage des résultats pour l’API (JSON)
        $items = array_map(function ($trajet) {
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

    #[Route('/', name: 'index', methods: ['GET'])]
    #[OA\Get(
        path: "/api/trajet/",
        summary: "Trajets en cours pour l'utilisateur connecté",
        description: "Retourne les trajets où l'utilisateur est 
                        soit chauffeur, soit passager, uniquement ceux en cours.",
        tags: ["Trajets"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des trajets en cours",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "items",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(
                                            property: "id",
                                            type: "integer",
                                            example: 12
                                        ),
                                        new OA\Property(
                                            property: "adresseDepart",
                                            type: "string",
                                            example: "Paris"
                                        ),
                                        new OA\Property(
                                            property: "adresseArrivee",
                                            type: "string",
                                            example: "Lyon"
                                        ),
                                        new OA\Property(
                                            property: "prix",
                                            type: "number",
                                            format: "float",
                                            example: 45.5
                                        ),
                                        new OA\Property(
                                            property: "dateDepart",
                                            type: "string",
                                            example: "23-08-2025"
                                        ),
                                        new OA\Property(
                                            property: "statut",
                                            type: "string",
                                            example: "EN_COURS"
                                        ),
                                        new OA\Property(
                                            property: "estChauffeur",
                                            type: "boolean",
                                            example: true
                                        )
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Utilisateur non connu"
                            )
                        ]
                    )
                )
            )
        ]
    )]
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

        // Trajets où l'utilisateur est passager (toutes réservations, peu importe le rôle)
        $trajetsPassager = array_map(function (Reservation $res) use ($user) {
            $trajet = $res->getTrajet();
            // Ne garder que les trajets où l'utilisateur est passager (pas chauffeur)
            if ($trajet->getChauffeur() !== $user) {
                return $trajet;
            }
            return null;
        }, $reservations);

        // Retirer les nulls éventuels
        $trajetsPassager = array_filter($trajetsPassager);

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

    #[Route('/passagers/{id}', name: 'passagers', methods: ['GET'])]
    #[OA\Get(
        path: "/api/trajet/passagers/{id}",
        summary: "Liste des passagers pour un trajet",
        description: "Retourne les passagers d'un trajet spécifique. 
                        L'accès est réservé au chauffeur du trajet possédant 
                        le rôle approprié.",
        tags: ["Trajets"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID du trajet",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des passagers du trajet",
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
                                    example: 5
                                ),
                                new OA\Property(
                                    property: "prenom",
                                    type: "string",
                                    example: "Jean"
                                ),
                                new OA\Property(
                                    property: "telephone",
                                    type: "string",
                                    example: "0612345678"
                                ),
                                new OA\Property(
                                    property: "email",
                                    type: "string",
                                    example: "jean@example.com"
                                ),
                                new OA\Property(
                                    property: "pseudo",
                                    type: "string",
                                    example: "JeanD"
                                ),
                                new OA\Property(
                                    property: "image",
                                    type: "string",
                                    example: "https://example.com/uploads/user1.jpg"
                                ),
                                new OA\Property(
                                    property: "roles",
                                    type: "array",
                                    items: new OA\Items(type: "string"),
                                    example: ["ROLE_PASSAGER"]
                                ),
                                new OA\Property(
                                    property: "trajetId",
                                    type: "integer",
                                    example: 12
                                ),
                                new OA\Property(
                                    property: "reservationId",
                                    type: "integer",
                                    example: 34
                                ),
                                new OA\Property(
                                    property: "statutReservation",
                                    type: "string",
                                    example: "CONFIRMEE"
                                )
                            ]
                        )
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Utilisateur non authentifié"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès interdit",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Accès interdit"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Trajet non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Trajet non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function passagers(int $id, Request $request): JsonResponse
    {

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                [
                    'error' => 'Utilisateur non authentifié'
                ],
                401
            );
        }

        $trajet = $this->repository->find($id);

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé'],
                404
            );
        }

        // Vérifie que l'utilisateur est le chauffeur de ce trajet
        $chauffeur = $trajet->getChauffeur();
        $userId = $user->getId();

        $roles = $user->getRoles();
        $isChauffeur = $chauffeur && $chauffeur->getId() === $userId;
        $hasRightRole = in_array(
            'ROLE_CHAUFFEUR',
            $roles
        )
            ||
            in_array(
                'ROLE_PASSAGER_CHAUFFEUR',
                $roles
            );

        if (!($isChauffeur && $hasRightRole)) {
            return new JsonResponse(
                ['error' => 'Accès interdit'],
                403
            );
        }

        //récupérer les passagers via les réservations
        $passagers = [];
        foreach ($trajet->getReservations() as $reservation) {
            $passager = $reservation->getUser();
            // On exclut le chauffeur du trajet
            if ($passager && $passager->getId() !== $chauffeur->getId()) {
                $passagers[] = [
                    'id' => $passager->getId(),
                    'prenom' => $passager->getPrenom(),
                    'telephone' => $passager->getTelephone(),
                    'email' => $passager->getEmail(),
                    'pseudo' => $passager->getPseudo(),
                    'image' => $passager->getImage()
                        ? $request->getSchemeAndHttpHost() . $passager->getImage()->getFilePath()
                        : null,
                    'roles' => $passager->getRoles(),
                    'trajetId' => $trajet->getId(),
                    'reservationId' => $reservation->getId(),
                    'statutReservation' => $reservation->getStatut(),
                    // autres champs si besoin
                ];
            }
        }

        return new JsonResponse($passagers);
    }

    #[Route('/passagersFilter/{id}', name: 'Filterpassagers', methods: ['GET'])]
    #[OA\Get(
        path: "/api/trajet/passagersFilter/{id}",
        summary: "Liste filtrée des passagers pour un trajet",
        description: "Retourne les passagers d'un trajet spécifique 
                        dont la réservation est en attente ou confirmée. 
                        L'accès est réservé au chauffeur du trajet avec 
                        le rôle approprié.",
        tags: ["Trajets"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID du trajet",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste filtrée des passagers du trajet",
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
                                    example: 5
                                ),
                                new OA\Property(
                                    property: "prenom",
                                    type: "string",
                                    example: "Jean"
                                ),
                                new OA\Property(
                                    property: "telephone",
                                    type: "string",
                                    example: "0612345678"
                                ),
                                new OA\Property(
                                    property: "email",
                                    type: "string",
                                    example: "jean@example.com"
                                ),
                                new OA\Property(
                                    property: "pseudo",
                                    type: "string",
                                    example: "JeanD"
                                ),
                                new OA\Property(
                                    property: "image",
                                    type: "string",
                                    example: "https://example.com/uploads/user1.jpg"
                                ),
                                new OA\Property(
                                    property: "roles",
                                    type: "array",
                                    items: new OA\Items(type: "string"),
                                    example: ["ROLE_PASSAGER"]
                                ),
                                new OA\Property(
                                    property: "trajetId",
                                    type: "integer",
                                    example: 12
                                ),
                                new OA\Property(
                                    property: "reservationId",
                                    type: "integer",
                                    example: 34
                                ),
                                new OA\Property(
                                    property: "statutReservation",
                                    type: "string",
                                    example: "CONFIRMEE"
                                )
                            ]
                        )
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Utilisateur non authentifié"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès interdit",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Accès interdit"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Trajet non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Trajet non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function Filterpassagers(int $id, Request $request): JsonResponse
    {

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                [
                    'error' => 'Utilisateur non authentifié'
                ],
                401
            );
        }

        $trajet = $this->repository->find($id);

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé'],
                404
            );
        }

        // Vérifie que l'utilisateur est le chauffeur de ce trajet
        $chauffeur = $trajet->getChauffeur();
        $userId = $user->getId();

        $roles = $user->getRoles();
        $isChauffeur = $chauffeur && $chauffeur->getId() === $userId;
        $hasRightRole = in_array(
            'ROLE_CHAUFFEUR',
            $roles
        )
            ||
            in_array(
                'ROLE_PASSAGER_CHAUFFEUR',
                $roles
            );

        if (!($isChauffeur && $hasRightRole)) {
            return new JsonResponse(
                ['error' => 'Accès interdit'],
                403
            );
        }

        // récupérer les passagers via les réservations
        $passagers = [];
        foreach ($trajet->getReservations() as $reservation) {
            // 💡 Ne garder que les réservations EN_ATTENTE ou CONFIRMEE
            if (!in_array($reservation->getStatut(), ['EN_ATTENTE', 'CONFIRMEE'])) {
                continue; // on ignore les autres statuts (ex: ANNULEE)
            }

            $passager = $reservation->getUser();

            // On exclut le chauffeur du trajet
            if ($passager && $passager->getId() !== $chauffeur->getId()) {
                $passagers[] = [
                    'id' => $passager->getId(),
                    'prenom' => $passager->getPrenom(),
                    'telephone' => $passager->getTelephone(),
                    'email' => $passager->getEmail(),
                    'pseudo' => $passager->getPseudo(),
                    'image' => $passager->getImage()
                        ? $request->getSchemeAndHttpHost() . $passager->getImage()->getFilePath()
                        : null,
                    'roles' => $passager->getRoles(),
                    'trajetId' => $trajet->getId(),
                    'reservationId' => $reservation->getId(),
                    'statutReservation' => $reservation->getStatut(),
                    // autres champs si besoin
                ];
            }
        }

        return new JsonResponse($passagers);
    }

    #[Route('/accepter/{trajetId}/passagers/{passagerId}', name: 'accepter_passager', methods: ['POST'])]
    #[OA\Post(
        path: "/api/trajet/accepter/{trajetId}/passagers/{passagerId}",
        summary: "Accepter un passager pour un trajet",
        description: "Permet au chauffeur du trajet 
                        d'accepter un passager. La réservation 
                        du passager est mise à jour avec le statut 
                        CONFIRMEE.",
        tags: ["Trajets"],
        parameters: [
            new OA\Parameter(
                name: "trajetId",
                in: "path",
                description: "ID du trajet",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            ),
            new OA\Parameter(
                name: "passagerId",
                in: "path",
                description: "ID du passager à accepter",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Passager accepté avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Passager accepté par le chauffeur, passager notifié."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Utilisateur non authentifié"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès interdit (l'utilisateur n'est pas le chauffeur)",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Accès interdit"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Trajet ou réservation non trouvée",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Trajet non trouvé ou Réservation non trouvée"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function accepterPassager(
        int $trajetId,
        int $passagerId
    ): JsonResponse {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié'],
                401
            );
        }

        $trajet = $this->repository->find($trajetId);

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé'],
                404
            );
        }

        // Vérifie que l'utilisateur est bien le chauffeur du trajet
        if ($trajet->getChauffeur()->getId() !== $user->getId()) {
            return new JsonResponse(
                ['error' => 'Accès interdit'],
                403
            );
        }

        // Recherche de la réservation du passager pour ce trajet
        $reservation = $this->reservationRepository->findOneBy(
            [
                'trajet' => $trajet,
                'user' => $passagerId,
            ]
        );

        if (!$reservation) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée'],
                404
            );
        }

        $reservation->setStatut('CONFIRMEE');
        $this->manager->flush();

        return new JsonResponse(
            ['message' => 'Passager accepté par le chauffeur, passager notifié.']
        );
    }

    #[Route('/refuser/{trajetId}/passagers/{passagerId}', name: 'refuser_passager', methods: ['POST'])]
    #[OA\Post(
        path: "/api/trajet/refuser/{trajetId}/passagers/{passagerId}",
        summary: "Refuser un passager pour un trajet",
        description: "Permet au chauffeur de refuser un passager. 
                        La réservation est annulée, la place est libérée 
                        et les crédits du passager sont remboursés.",
        tags: ["Trajets"],
        parameters: [
            new OA\Parameter(
                name: "trajetId",
                in: "path",
                description: "ID du trajet",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            ),
            new OA\Parameter(
                name: "passagerId",
                in: "path",
                description: "ID du passager à refuser",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Passager refusé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Passager refusé, place libérée et 
                                    crédits remboursés, passager notifié."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Utilisateur non authentifié"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès interdit (l'utilisateur n'est pas le chauffeur)",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Accès interdit"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Trajet ou réservation non trouvée",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Trajet non trouvé ou Réservation non trouvée"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function refuserPassager(
        int $trajetId,
        int $passagerId
    ): JsonResponse {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié'],
                401
            );
        }

        $trajet = $this->repository->find($trajetId);

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé'],
                404
            );
        }

        if ($trajet->getChauffeur()->getId() !== $user->getId()) {
            return new JsonResponse(
                ['error' => 'Accès interdit'],
                403
            );
        }

        $reservation = $this->reservationRepository->findOneBy(
            [
                'trajet' => $trajet,
                'user' => $passagerId,
            ]
        );

        if (!$reservation) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée'],
                404
            );
        }

        $reservation->setStatut('ANNULEE');

        // Libérer la place
        $trajet->setNombrePlacesDisponible(
            $trajet->getNombrePlacesDisponible() + 1
        );

        // Rembourser le passager
        $prixTrajet = (int) round(
            floatval($trajet->getPrix())
        );
        $passager = $reservation->getUser();
        $passager->setCredits(
            $passager->getCredits() + $prixTrajet
        );

        $reservation->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        return new JsonResponse(
            [
                'message' => 'Passager refusé, place libérée et crédits remboursés, passager notifié.'
            ]
        );
    }

    #[Route('/terminee/{id}', name: 'terminee', methods: ['POST'])]
    #[OA\Post(
        path: "/api/trajet/terminee/{id}",
        summary: "Marquer un trajet comme terminé",
        description: "Permet au chauffeur de terminer un trajet. 
                        Les crédits sont transférés à l'administrateur 
                        et le statut du trajet est mis à jour.",
        tags: ["Trajets"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID du trajet à terminer",
                required: true,
                schema: new OA\Schema(
                    type: "integer"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Trajet terminé et crédits transférés",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Trajet terminé et crédits transférés."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erreur liée aux crédits ou transfert déjà effectué",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Crédits insuffisants ou crédits déjà transférés."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Utilisateur non authentifié"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès interdit ou rôle non autorisé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Vous ne pouvez pas terminer ce trajet ou rôle non autorisé."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Trajet non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Trajet non trouvé."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erreur serveur, admin introuvable",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Admin introuvable."
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function terminee(int $id): JsonResponse
    {
        $trajet = $this->manager
            ->getRepository(
                Trajet::class
            )->find($id);

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                [
                    'error' => 'Utilisateur non authentifié'
                ],
                401
            );
        }

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé.'],
                Response::HTTP_NOT_FOUND
            );
        }

        if ($trajet->getChauffeur() !== $user) {
            return new JsonResponse(
                [
                    'error' => 'Vous ne pouvez pas terminer ce trajet.'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        if (
            !in_array(
                'ROLE_CHAUFFEUR',
                $user->getRoles()
            )
            &&
            !in_array(
                'ROLE_PASSAGER_CHAUFFEUR',
                $user->getRoles()
            )
        ) {
            return new JsonResponse(
                ['error' => 'Rôle non autorisé.'],
                Response::HTTP_FORBIDDEN
            );
        }

        // Vérifier si les crédits ont déjà été transférés
        if ($trajet->isCreditsTransferred()) {
            return new JsonResponse(
                [
                    'error' => 'Les crédits ont déjà été transférés pour ce trajet.'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Transfert de crédits à l’admin
        $admin = $this->manager
            ->getRepository(User::class)
            ->findOneBy(
                ['isAdmin' => true]
            );

        if (!$admin) {
            return new JsonResponse(
                ['error' => 'Admin introuvable.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if ($user->getCredits() < 2) {
            return new JsonResponse(
                ['error' => 'Crédits insuffisants.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->setCredits($user->getCredits() - 2);
        $admin->setCredits($admin->getCredits() + 2);

        // Mise à jour du statut et marquage
        $trajet->setStatut('TERMINEE');
        $trajet->setUpdatedAt(new \DateTimeImmutable());
        $trajet->setIsCreditsTransferred(true);

        $this->manager->flush();

        return new JsonResponse(
            [
                'message' => 'Trajet terminé et crédits transférés.'
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/admin/trajets', name: 'admin_trajets_index', methods: ['GET'])]
    #[OA\Get(
        path: "/api/trajet/admin/trajets",
        summary: "Lister tous les trajets (admin)",
        description: "Permet à un administrateur de récupérer 
                        la liste complète des trajets avec leurs 
                        informations principales.",
        tags: ["Trajets"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des trajets récupérée avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "items",
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
                                            property: "adresseDepart",
                                            type: "string",
                                            example: "10 Rue de Paris"
                                        ),
                                        new OA\Property(
                                            property: "adresseArrivee",
                                            type: "string",
                                            example: "20 Avenue de Lyon"
                                        ),
                                        new OA\Property(
                                            property: "prix",
                                            type: "number",
                                            format: "float",
                                            example: 15.5
                                        ),
                                        new OA\Property(
                                            property: "dateDepart",
                                            type: "string",
                                            format: "date",
                                            example: "23-08-2025"
                                        ),
                                        new OA\Property(
                                            property: "statut",
                                            type: "string",
                                            example: "EN_COURS"
                                        ),
                                        new OA\Property(
                                            property: "estChauffeur",
                                            type: "boolean",
                                            example: false
                                        )
                                    ]
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non connecté ou non administrateur",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Utilisateur non connecté"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): JsonResponse
    {
        $admin = $this->getUser();
        if (!$admin) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connecté'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Récupère tous les trajets
        $trajets = $this->manager
            ->getRepository(Trajet::class)
            ->createQueryBuilder('t')
            ->orderBy('t.dateDepart', 'DESC')
            ->getQuery()
            ->getResult();

        // Mise en forme identique à ta route user
        $items = array_map(static function (Trajet $trajet) {
            return [
                'id'             => $trajet->getId(),
                'adresseDepart'  => $trajet->getAdresseDepart(),
                'adresseArrivee' => $trajet->getAdresseArrivee(),
                'prix'           => $trajet->getPrix(),
                'dateDepart'     => $trajet->getDateDepart()?->format('d-m-Y'),
                'statut'         => $trajet->getStatut(),
                'estChauffeur'   => false,
            ];
        }, $trajets);

        return new JsonResponse(['items' => $items], Response::HTTP_OK);
    }
}
