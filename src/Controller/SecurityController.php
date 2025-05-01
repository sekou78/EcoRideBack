<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api', name: 'app_api_')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $manager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}
    #[Route('/registration', name: 'registration', methods: 'POST')]
    #[OA\Post(
        path: "/api/registration",
        summary: "Inscription d'un utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Utilisateur à inscrire",
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    required: ["email", "password", "pseudo"],
                    properties: [
                        new OA\Property(
                            property: "email",
                            type: "string",
                            format: "email",
                            example: "mail@mail.fr"
                        ),
                        new OA\Property(
                            property: "password",
                            type: "string",
                            format: "password",
                            example: "Azerty$123"
                        ),
                        new OA\Property(
                            property: "pseudo",
                            type: "string",
                            example: "Dinga223"
                        ),
                        new OA\Property(
                            property: "roles",
                            type: "array",
                            items: new OA\Items(
                                type: "string",
                                example: "ROLE_PASSAGER"
                            )
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur inscrit avec succès',
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: "1"
                            ),
                            new OA\Property(
                                property: "user",
                                type: "string",
                                example: "mail@mail.fr"
                            ),
                            new OA\Property(
                                property: "apiToken",
                                type: "string",
                                example: "31a023e212f116124a36af14ea0c1c3806eb9378"
                            ),
                            new OA\Property(
                                property: "roles",
                                type: "array",
                                items: new OA\Items(
                                    type: "string",
                                    example: "ROLE_PASSAGER"
                                )
                            ),
                            new OA\Property(
                                property: "createdAt",
                                type: "string",
                                description: "Date de création de l'utilisateur",
                                example: "01/05/2025"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erreur de validation (email déjà utilisé ou rôle invalide)",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Email déjà utilisé"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Rôle interdit (ADMIN ou EMPLOYE)",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Vous n'êtes pas autorisé à créer ce rôle"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function register(
        Request $request
    ): JsonResponse {
        $user = $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json'
        );

        //Chaque utilisateur beneficie de 20 crédits à la création du compte
        $user->setCredits(20);

        // Etat du compte par défaut
        $user->setCompteSuspendu(false);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return new JsonResponse(
                ['errors' => $messages],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérification de l'e-mail dans la base de donnée
        $existingUser = $this->manager
            ->getRepository(User::class)
            ->findOneBy(
                ['email' => $user->getEmail()]
            );
        if ($existingUser) {
            return new JsonResponse(
                ['error' => 'Email déjà utilisé'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Interdire la création par un utilisateur (ROLE_EMPLOYE ou ROLE_ADMIN)
        if (
            in_array("ROLE_EMPLOYE", $user->getRoles()) ||
            in_array("ROLE_ADMIN", $user->getRoles())
        ) {
            return new JsonResponse(
                ['error' => "Vous n'êtes pas autorisé à créer ce rôle"],
                Response::HTTP_FORBIDDEN
            );
        }

        // Hachage du mot de passe
        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            )
        );

        $user->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            [
                "id" => $user->getId(),
                "user" => $user->getUserIdentifier(),
                "apiToken" => $user->getApiToken(),
                "roles" => $user->getRoles(),
                "Created_at" => $user->getCreatedAt()->format('d/m/Y')
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/login', name: 'login', methods: 'POST')]
    #[OA\Post(
        path: "/api/login",
        summary: "Connexion d'un Utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'utilisateur pour se connecter",
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    required: ["username", "password"],
                    properties: [
                        new OA\Property(
                            property: "username",
                            type: "string",
                            example: "mail@mail.fr"
                        ),
                        new OA\Property(
                            property: "password",
                            type: "string",
                            example: "Azerty$123"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Connexion reussie",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: "1"
                            ),
                            new OA\Property(
                                property: "user",
                                type: "string",
                                example: "Mail de connexions"
                            ),
                            new OA\Property(
                                property: "apiToken",
                                type: "string",
                                example: "31a023e212f116124a36af14ea0c1c3806eb9378"
                            ),
                            new OA\Property(
                                property: "roles",
                                type: "array",
                                items: new OA\Items(
                                    type: "string",
                                    example: "ROLE_PASSAGER"
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Identifiants manquants ou invalides",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "missing credentials"
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function login(#[CurrentUser()] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(
                [
                    'message' => 'missing credentials',
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return new JsonResponse(
            // ['message' => 'User registered successfully'],
            //Pour le test à supprimer avant production (mise en ligne)
            [
                'id' => $user->getId(),
                'user'  => $user->getUserIdentifier(),
                'apiToken' => $user->getApiToken(),
                'roles' => $user->getRoles()
            ],
        );
    }

    #[Route('/account/me', name: 'me', methods: 'GET')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        $responseData = $this->serializer
            ->serialize(
                $user,
                'json',
                [
                    AbstractNormalizer::ATTRIBUTES => [
                        'id',
                        'email',
                        'roles',
                        'username',
                        'nom',
                        'prenom'
                    ]
                ]
            );

        return new JsonResponse(
            $responseData,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/account/edit', name: 'edit', methods: 'PUT')]
    public function edit(Request $request): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        //Désérialisation des données de la requête pour mettre à jour l'utilisateur
        $user = $this->serializer
            ->deserialize(
                $request->getContent(),
                User::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $this->getUser()],
            );

        $user->setUpdatedAt(new \DateTimeImmutable());

        // Hachage du mot de passe si modifié
        if (isset($request->toArray()['password'])) {
            $user->setPassword(
                $this->passwordHasher
                    ->hashPassword(
                        $user,
                        $user->getPassword()
                    )
            );
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return new JsonResponse(
                ['errors' => $messages],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Mettre à jour l'image si fourni
        if ($data['image']) {
            $image = $this->manager
                ->getRepository(
                    Image::class
                )
                ->find(
                    $data['image']
                );
            if (!$image) {
                return new JsonResponse(
                    ['error' => 'Utilisateur non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $user->setImage($image);
        }

        $this->manager->flush();

        // Retourner la réponse JSON avec les informations mises à jour
        $responseData = $this->serializer
            ->serialize(
                $user,
                'json',
                [
                    AbstractNormalizer::ATTRIBUTES => [
                        'id',
                        'email',
                        'pseudo',
                        'roles'
                    ]
                ]
            );

        return new JsonResponse(
            $responseData,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route(
        '/admin/create-user',
        name: 'admin_create_user',
        methods: 'POST'
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function createUser(
        Request $request,
    ): JsonResponse {
        // Désérialisation de l'utilisateur depuis le contenu de la requête
        $user = $this->serializer
            ->deserialize(
                $request->getContent(),
                User::class,
                'json'
            );

        // Etat du compte par défaut
        $user->setCompteSuspendu(false);

        // Vérification de l'existence d'un utilisateur avec cet email
        $existingUser = $this->manager
            ->getRepository(User::class)
            ->findOneBy(
                ['email' => $user->getEmail()]
            );
        if ($existingUser) {
            return new JsonResponse(
                ['error' => 'Cet email est déjà utlisé'],
                Response::HTTP_BAD_REQUEST
            );
        }

        //Attribution du rôle d'employé
        $user->setRoles(['ROLE_EMPLOYE']);

        // Si l'admin tente de créer un administrateur, vérifiez s'il existe déjà un administrateur
        if (in_array("ROLE_ADMIN", $user->getRoles())) {
            $existingAdmin = $this->manager
                ->getRepository(User::class)
                ->findOneByRole('ROLE_ADMIN');
            if ($existingAdmin) {
                return new JsonResponse(
                    ['error' => 'Un compte administrateur existe déjà'],
                    Response::HTTP_FORBIDDEN
                );
            }
        }

        // Hachage du mot de passe
        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            )
        );

        $user->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            // ['message' => 'User registered successfully'],
            //Pour le test à supprimer avant production (mise en ligne)
            [
                'id' => $user->getId(),
                'user'  => $user->getUserIdentifier(),
                'apiToken' => $user->getApiToken(),
                'pseudo' => $user->getPseudo(),
                'roles' => $user->getRoles()
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route(
        '/admin/droitSuspensionComptes/{droitId}',
        name: 'admin_droitSuspensionComptes',
        methods: 'PUT'
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function droitSuspensionComptes(
        int $droitId,
        EntityManagerInterface $manager
    ): JsonResponse {
        $droit = $manager
            ->getRepository(User::class)
            ->findOneBy(['id' => $droitId]);

        // Vérification si l'utilisateur a le rôle requis
        if (
            !$this->isGranted('ROLE_ADMIN')
        ) {
            return new JsonResponse(
                ['message' => 'Accès réfusé'],
                Response::HTTP_FORBIDDEN
            );
        }

        if (!$droit) {
            return new JsonResponse(
                ['error' => 'User non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Suspension du compte
        $droit->setCompteSuspendu(true);

        $droit->setUpdatedAt(new DateTimeImmutable());

        $manager->flush();

        return new JsonResponse(
            ['message' => 'Compte suspendu'],
            Response::HTTP_OK
        );
    }
}
