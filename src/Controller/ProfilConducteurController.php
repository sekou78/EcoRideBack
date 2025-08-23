<?php

namespace App\Controller;

use App\Entity\ProfilConducteur;
use App\Entity\User;
use App\Repository\ProfilConducteurRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route("api/profilConducteur", name: "app_api_profilConducteur_")]
final class ProfilConducteurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ProfilConducteurRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security
    ) {}

    #[Route(methods: ["POST"])]
    #[OA\Post(
        path: "/api/profilConducteur",
        summary: "Créer un profil de conducteur",
        description: "Permet à un 'chauffeur' ou 'passager_chauffeur' de créer son profil de conducteur.",
        tags: ["Immatriculation Vehicules"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données du profil conducteur à créer",
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    required: [
                        "plaqueImmatriculation",
                        "dateImmatriculation",
                        "modele",
                        "marque",
                        "couleur",
                        "nombrePlaces",
                        "electrique"
                    ],
                    properties: [
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
                            description: "Modèle du véhicule",
                            example: "Clio"
                        ),
                        new OA\Property(
                            property: "marque",
                            type: "string",
                            description: "Marque du véhicule",
                            example: "Renault"
                        ),
                        new OA\Property(
                            property: "couleur",
                            type: "string",
                            description: "Couleur du véhicule",
                            example: "Rouge"
                        ),
                        new OA\Property(
                            property: "nombrePlaces",
                            type: "integer",
                            description: "Nombre de places disponible dans le véhicule",
                            example: 5
                        ),
                        new OA\Property(
                            property: "electrique",
                            type: "boolean",
                            description: "Indique si le véhicule est electrique",
                            example: true
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Profil de conducteur créé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 4
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
                            new OA\Property(
                                property: "createdAt",
                                type: "string",
                                format: "date-time"
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
                description: "Rôle non autorisé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Vous devez être 'chauffeur' ou 'passager_chauffeur'"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    #[IsGranted("ROLE_USER")]
    public function new(Request $request): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connu'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a le rôle "chauffeur" ou "passager_chauffeur"
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
                [
                    'error' => "Vous devez être 'chauffeur' ou 'passager_chauffeur"
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Désérialiser les données du profil conducteur
        $profilConducteur = $this->serializer->deserialize(
            $request->getContent(),
            ProfilConducteur::class,
            'json',
        );

        // Associer le profil conducteur à l'utilisateur connecté
        $profilConducteur->setUser($user);

        $profilConducteur->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($profilConducteur);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $profilConducteur,
            'json',
            ['groups' => ['profilConducteur:read']]
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

    #[Route("/{id}", name: "show", methods: ["GET"])]
    #[OA\Get(
        path: "/api/profilConducteur/{id}",
        summary: "Afficher un profil de conducteur",
        description: "Cette route permet d'afficher les détails d'un profil conducteur spécifique.",
        tags: ["Immatriculation Vehicules"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID du profil conducteur à afficher",
                required: true,
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Profil de conducteur récupéré avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 4
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
                            new OA\Property(
                                property: "createdAt",
                                type: "string",
                                format: "date-time"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Profil conducteur non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Profil conducteur non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connu'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a le rôle "chauffeur" ou "passager_chauffeur"
        if (
            !in_array('ROLE_CHAUFFEUR', $user->getRoles()) &&
            !in_array('ROLE_PASSAGER_CHAUFFEUR', $user->getRoles())
        ) {
            return new JsonResponse(
                ['error' => "Vous devez être 'chauffeur' ou 'passager_chauffeur'."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer le profil conducteur
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if (!$profilConducteur) {
            return new JsonResponse(
                ['error' => 'Profil Conducteur non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier que le profil appartient bien à l'utilisateur connecté
        if ($profilConducteur->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(
                ['error' => "Vous n'avez pas accès à ce profil pour le modifier."],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($profilConducteur) {
            $responseData = $this->serializer->serialize(
                $profilConducteur,
                'json',
                ['groups' => ['profilConducteur:read']]
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
        path: "/api/profilConducteur/{id}",
        summary: "Modifier un profil conducteur",
        description: "Permet à un utilisateur de modifier son profil de conducteur.",
        tags: ["Immatriculation Vehicules"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du profil conducteur à modifier",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données à mettre à jour pour le profil conducteur.",
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "plaqueImmatriculation",
                            type: "string",
                            example: "EF-456-GH"
                        ),
                        new OA\Property(
                            property: "dateImmatriculation",
                            type: "string",
                            format: "date-time",
                            example: "2010-05-25T00:00:00+02:00"
                        ),
                        new OA\Property(
                            property: "modele",
                            type: "string",
                            example: "Scala"
                        ),
                        new OA\Property(
                            property: "marque",
                            type: "string",
                            example: "Skoda"
                        ),
                        new OA\Property(
                            property: "couleur",
                            type: "string",
                            example: "Verte"
                        ),
                        new OA\Property(
                            property: "nombrePlaces",
                            type: "integer",
                            example: 3
                        ),
                        new OA\Property(
                            property: "electrique",
                            type: "boolean",
                            example: false
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Profil conducteur modifié avec succès",
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
                                property: "plaqueImmatriculation",
                                type: "string",
                                example: "EF-456-GH"
                            ),
                            new OA\Property(
                                property: "dateImmatriculation",
                                type: "string",
                                format: "date-time",
                                example: "2010-05-25T00:00:00+02:00"
                            ),
                            new OA\Property(
                                property: "modele",
                                type: "string",
                                example: "Scala"
                            ),
                            new OA\Property(
                                property: "marque",
                                type: "string",
                                example: "Skoda"
                            ),
                            new OA\Property(
                                property: "couleur",
                                type: "string",
                                example: "Verte"
                            ),
                            new OA\Property(
                                property: "nombrePlaces",
                                type: "integer",
                                example: 3
                            ),
                            new OA\Property(
                                property: "electrique",
                                type: "boolean",
                                example: false
                            ),
                            new OA\Property(
                                property: "updatedAt",
                                type: "string",
                                format: "date-time"
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
            ),
            new OA\Response(
                response: 403,
                description: "Rôle non autorisé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Vous devez être 'chauffeur' ou 'passager_chauffeur'"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Profil conducteur non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Profil Conducteur non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(int $id, Request $request): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connu'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a le rôle "chauffeur" ou "passager_chauffeur"
        if (
            !in_array('ROLE_CHAUFFEUR', $user->getRoles()) &&
            !in_array('ROLE_PASSAGER_CHAUFFEUR', $user->getRoles())
        ) {
            return new JsonResponse(
                ['error' => "Vous devez être 'chauffeur' ou 'passager_chauffeur'."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer le profil conducteur
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if (!$profilConducteur) {
            return new JsonResponse(
                ['error' => 'Profil Conducteur non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier que le profil appartient bien à l'utilisateur connecté
        if ($profilConducteur->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(
                ['error' => "Vous n'avez pas accès à ce profil pour le modifier."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Désérialiser et mettre à jour l'objet existant
        $this->serializer->deserialize(
            $request->getContent(),
            ProfilConducteur::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $profilConducteur]
        );

        // Mettre à jour la date de modification
        $profilConducteur->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $profilConducteur,
            'json',
            ['groups' => ['profilConducteur:read']]
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
        path: "/api/profilConducteur/{id}",
        summary: "Supprimer son profil conducteur",
        description: "Permet à un utilisateur de supprimer son propre profil de conducteur.",
        tags: ["Immatriculation Vehicules"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du profil conducteur à supprimer",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Profil conducteur supprimé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Profil Conducteur supprimé avec succès."
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
                                example: "Utilisateur non connecté"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Rôle non autorisé ou profil non appartenant à l'utilisateur",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Vous devez être 'chauffeur' ou 'passager_chauffeur'."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Profil conducteur non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Profil Conducteur non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connecté'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a le rôle "chauffeur" ou "passager_chauffeur"
        if (
            !in_array('ROLE_CHAUFFEUR', $user->getRoles()) &&
            !in_array('ROLE_PASSAGER_CHAUFFEUR', $user->getRoles())
        ) {
            return new JsonResponse(
                ['error' => "Vous devez être 'chauffeur' ou 'passager_chauffeur'."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer le profil conducteur par ID
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if (!$profilConducteur) {
            return new JsonResponse(
                [
                    'error' => 'Profil Conducteur non trouvé'
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier que l'utilisateur connecté est bien le propriétaire du profil
        if ($profilConducteur->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(
                [
                    'error' => "Vous n'avez pas l'autorisation de supprimer ce profil."
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Supprimer le profil conducteur
        $this->manager->remove($profilConducteur);
        $this->manager->flush();

        return new JsonResponse(
            ['message' => 'Profil Conducteur supprimé avec succès.'],
            Response::HTTP_OK
        );
    }

    #[Route("/", name: "index", methods: ["GET"])]
    #[OA\Get(
        path: '/api/profilConducteur/',
        summary: 'Liste vehicule de l’utilisateur connecté',
        description: 'Retourne tous les vehicules liés à l’utilisateur connecté',
        tags: ["Immatriculation Vehicules"],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des vehicules',
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'id',
                                type: 'integer',
                                example: 1
                            ),
                            new OA\Property(
                                property: 'plaqueImmatriculation',
                                type: 'string',
                                example: 'AB-123-CD'
                            ),
                            new OA\Property(
                                property: 'dateImmatriculation',
                                type: 'string',
                                format: 'date-time',
                                example: '2010-10-10T00:00:00+02:00'
                            ),
                            new OA\Property(
                                property: 'modele',
                                type: 'string',
                                example: 'Clio'
                            ),
                            new OA\Property(
                                property: 'marque',
                                type: 'string',
                                example: 'Renault'
                            ),
                            new OA\Property(
                                property: 'couleur',
                                type: 'string',
                                example: 'Rouge'
                            ),
                            new OA\Property(
                                property: 'nombrePlaces',
                                type: 'integer',
                                example: 5
                            ),
                            new OA\Property(
                                property: 'electrique',
                                type: 'boolean',
                                example: true
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Utilisateur non authentifié',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Utilisateur non connu'
                        )
                    ]
                )
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
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

        // Récupérer tous les profils conducteur liés à cet utilisateur
        $profilsConducteur = $this->repository
            ->findBy(
                ['user' => $user]
            );

        $json = $this->serializer->serialize(
            $profilsConducteur,
            'json',
            ['groups' => ['profilConducteur:read']]
        );

        return new JsonResponse(
            $json,
            Response::HTTP_OK,
            [],
            true
        );
    }
}
