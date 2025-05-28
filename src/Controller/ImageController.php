<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\User;
use App\Repository\ImageRepository;
use App\Service\ImageUploaderService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('api/image', name: 'app_api_image_')]
// #[IsGranted('ROLE_USER')]
final class ImageController extends AbstractController
{
    private string $uploadDir;

    public function __construct(
        private EntityManagerInterface $manager,
        private ImageRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private ImageUploaderService $imageUploader,
        private Security $security,
        private KernelInterface $kernel // Injection du kernel pour obtenir le répertoire
    ) {
        // Initialisation du répertoire d'upload à partir du kernel
        $this->uploadDir = $this->kernel
            ->getProjectDir() . '/public/uploads/images/';
    }

    // Ajouter une image
    #[Route(methods: 'POST')]
    #[OA\Post(
        path: "/api/image",
        summary: "Ajouter une image.",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Image à envoyer via multipart/form-data OU JSON base64",
            content: [
                new OA\MediaType(
                    mediaType: "multipart/form-data",
                    schema: new OA\Schema(
                        type: "object",
                        required: ["image"],
                        properties: [
                            new OA\Property(
                                property: "image",
                                type: "string",
                                format: "binary",
                                description: "Fichier image à uploader"
                            )
                        ]
                    )
                ),
                new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        required: ["fileName", "fileData"],
                        properties: [
                            new OA\Property(
                                property: "fileName",
                                type: "string",
                                example: "photo.png"
                            ),
                            new OA\Property(
                                property: "fileData",
                                type: "string",
                                format: "byte",
                                example: "iVBORw0KGgoAAAANSUhEUgAA..."
                            )
                        ]
                    )
                )
            ]
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Image créée avec succès",
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
                                property: "avatar",
                                type: "string",
                                example: "6820b7.....jpg"
                            ),
                            new OA\Property(
                                property: "filePath",
                                type: "string",
                                example: "/images/6820b7.....jpg"
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
                response: 400,
                description: "Requête invalide ou type de fichier non autorisé"
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié"
            ),
            new OA\Response(
                response: 500,
                description: "Erreur lors de l'enregistrement du fichier"
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connu'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a déjà une image
        if ($user->getImage()) {
            return new JsonResponse(
                [
                    'image' => json_decode($this->serializer->serialize(
                        $user->getImage(),
                        'json',
                        ['groups' => 'image:read']
                    ), true)
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Vérifier si c'est une requête multipart (fichier normal)
        $uploadedFile = $request
            ->files
            ->get('image');

        // Vérifier l'extension et le type MIME
        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        $fileExtension = strtolower(
            $uploadedFile
                ->getClientOriginalExtension()
        );
        $mimeType = $uploadedFile->getMimeType();

        if (
            !in_array(
                $fileExtension,
                $allowedExtensions
            )
            ||
            !in_array(
                $mimeType,
                $allowedMimeTypes
            )
        ) {
            return new JsonResponse(
                ['error' => 'Invalid file type'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($uploadedFile) {
            $fileName = uniqid() . '.' . $uploadedFile
                ->guessExtension();

            try {
                $uploadedFile->move(
                    $this->uploadDir,
                    $fileName
                );
            } catch (FileException $e) {
                return new JsonResponse(
                    ['error' => 'File upload failed'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } else {
            // Sinon, vérifier si c'est une requête JSON avec base64
            $data = json_decode(
                $request->getContent(),
                true
            );

            if (
                !isset($data['fileName'])
                ||
                !isset($data['fileData'])
            ) {
                return new JsonResponse(
                    ['error' => 'Invalid JSON data'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $fileName = uniqid() . '-' . $data['fileName'];
            $filePath = $this->uploadDir . $fileName;

            // Convertir base64 en fichier réel
            $decodedData = base64_decode($data['fileData']);
            if ($decodedData === false) {
                return new JsonResponse(
                    ['error' => 'Invalid base64 data'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            file_put_contents(
                $filePath,
                $decodedData
            );
        }

        // Créer une nouvelle entité Image
        $image = new Image();
        $image->setAvatar($fileName);
        $image->setFilePath('/uploads/images/' . $fileName);

        $image->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($image);

        // Associer l'image à l'utilisateur
        $user->setImage($image);
        $this->manager->persist($user);

        $this->manager->flush();

        return new JsonResponse(
            $this->serializer
                ->serialize(
                    $image,
                    'json',
                    ['groups' => 'image:read']
                ),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    //Afficher une image
    #[Route('/{id}', name: 'show', methods: 'GET')]
    #[OA\Get(
        path: "/api/image/{id}",
        summary: "Afficher l'image de l'utilisateur connecté",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de l'image à afficher",
                schema: new OA\Schema(
                    type: "integer",
                    example: 5
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Image retournée avec succès",
                content: new OA\MediaType(
                    mediaType: "image/jpeg"
                )
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé à cette image"
            ),
            new OA\Response(
                response: 404,
                description: "Image non trouvée"
            )
        ]
    )]
    // #[IsGranted('ROLE_USER')]
    public function show(int $id): BinaryFileResponse
    {
        // // Récupérer l'utilisateur connecté
        // $user = $this->security->getUser();
        // if (!$user instanceof User) {
        //     return new BinaryFileResponse(
        //         Response::HTTP_UNAUTHORIZED
        //     );
        // }

        // Récupération de l'image en base de données
        $image = $this->repository->findOneBy(['id' => $id]);

        // Vérification de l'existence de l'image
        if (!$image) {
            throw $this->createNotFoundException('Image non trouvée');
        }

        // // Vérifier que l'utilisateur connecté est bien celui lié à l'image
        // if ($user->getImage()?->getId() !== $image->getId()) {
        //     throw $this->createAccessDeniedException(
        //         "Vous n'avez pas accès à cette image."
        //     );
        // }

        // Chemin absolu du fichier sur le serveur
        $imagePath = $this
            ->getParameter(
                'kernel.project_dir'
            )
            .
            '/public'
            .
            $image
            ->getFilePath();

        // Vérification de l'existence du fichier
        if (!file_exists($imagePath)) {
            throw $this->createNotFoundException('Image non trouvée');
        }

        // Retourner directement l'image en réponse HTTP
        return new BinaryFileResponse($imagePath);
    }

    //Modifier une image
    #[Route('/{id}', name: 'edit', methods: 'POST')]
    #[OA\Post(
        path: '/api/image/{id}',
        summary: 'Modifier une image existante',
        description: 'Permet à un utilisateur connecté de modifier son image.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID de l'image à modifier",
                schema: new OA\Schema(
                    type: 'integer'
                )
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Fichier image à envoyer (jpg, jpeg, png, gif, webp)',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['image'],
                    properties: [
                        new OA\Property(
                            property: 'image',
                            type: 'string',
                            format: 'binary'
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Image modifiée avec succès',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'id',
                                type: 'integer',
                                example: 42
                            ),
                            new OA\Property(
                                property: 'filePath',
                                type: 'string',
                                example: '/uploads/images/abcd1234-image.jpg'
                            ),
                            new OA\Property(
                                property: 'updatedAt',
                                type: 'string',
                                example: '01-05-2025 15:42:00'
                            ),
                            new OA\Property(
                                property: 'message',
                                type: 'string',
                                example: 'Image updated successfully'
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Aucun fichier envoyé ou type de fichier invalide'
            ),
            new OA\Response(
                response: 401,
                description: 'Utilisateur non authentifié'
            ),
            new OA\Response(
                response: 403,
                description: "L'utilisateur n'est pas propriétaire de l'image"
            ),
            new OA\Response(
                response: 404,
                description: 'Image non trouvée'
            ),
            new OA\Response(
                response: 500,
                description: "Erreur lors du téléversement ou de l'enregistrement du fichier"
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(
        int $id,
        Request $request
    ): Response {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connu'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Récupération de l'image en base de données
        $image = $this->repository->findOneBy(['id' => $id]);

        if (!$image) {
            return new JsonResponse(
                ['error' => 'Image not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        //Vérifier que l'utilisateur est bien le créateur
        if ($user->getImage()?->getId() !== $image->getId()) {
            return new JsonResponse(
                ['error' => "Vous n'êtes pas autorisé à modifier cette image."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer le fichier envoyé
        $uploadedFile = $request
            ->files
            ->get('image');

        if (!$uploadedFile) {
            return new JsonResponse(
                ['error' => 'No file uploaded'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier l'extension et le type MIME
        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        $fileExtension = strtolower(
            $uploadedFile
                ->getClientOriginalExtension()
        );
        $mimeType = $uploadedFile->getMimeType();

        if (
            !in_array(
                $fileExtension,
                $allowedExtensions
            )
            ||
            !in_array(
                $mimeType,
                $allowedMimeTypes
            )
        ) {
            return new JsonResponse(
                ['error' => 'Invalid file type'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Supprimer l'ancien fichier s'il existe
        $oldFilePath = $this->getParameter(
            'kernel.project_dir'
        )
            .
            '/public'
            .
            $image
            ->getFilePath();

        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }

        // Générer un nouveau nom de fichier
        $fileName = uniqid() . '-' . preg_replace(
            '/[^a-zA-Z0-9\._-]/',
            '',
            $uploadedFile->getClientOriginalName()
        );

        // Déplacer le fichier vers le répertoire d'upload
        try {
            if (
                !is_dir(
                    $this->uploadDir
                )
                &&
                !mkdir(
                    $this->uploadDir,
                    0775,
                    true
                )
            ) {
                return new JsonResponse(
                    ['error' => 'Failed to create upload directory'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            $uploadedFile->move(
                $this->uploadDir,
                $fileName
            );
        } catch (FileException $e) {
            return new JsonResponse(
                ['error' => 'File upload failed'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Mettre à jour l'image dans la base de données
        $image->setFilePath('/uploads/images/' . $fileName);

        $image->setAvatar($fileName); // Ajouter cette ligne

        $image->setUpdatedAt(new DateTimeImmutable());

        $this->manager->flush();

        // Chemin absolu du fichier
        $imagePath = $this->getParameter(
            'kernel.project_dir'
        )
            .
            '/public'
            .
            $image
            ->getFilePath();

        // Vérification de l'existence du fichier
        if (!file_exists($imagePath)) {
            return new JsonResponse(
                ['error' => 'File not found after upload'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Retourner une réponse de l'image mise à jour
        return new JsonResponse([
            'id' => $image->getId(),
            'filePath' => $image->getFilePath(), // URL relative de l'image
            'updatedAt' => $image
                ->getUpdatedAt()
                ->format('d-m-Y H:i:s'),
            'message' => 'Image updated successfully'
        ], Response::HTTP_OK);
    }

    //Supprimer une image
    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    #[OA\Delete(
        path: "/api/image/{id}",
        summary: "Supprimer son image",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'image à supprimer",
                schema: new OA\Schema(
                    type: "integer",
                    example: 7
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Image supprimée avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Image supprimée avec succès."
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non authentifié",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "error",
                            type: "string",
                            example: "Utilisateur non connu"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès interdit à cette image",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "error",
                            type: "string",
                            example: "Vous n'avez pas accès à cette image, pour la supprimée."
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Image non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "error",
                            type: "string",
                            example: "Image not found"
                        )
                    ]
                )
            )
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connu'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Récupérer l'image depuis la base de données
        $image = $this->repository->findOneBy(['id' => $id]);

        if (!$image) {
            return new JsonResponse(
                ['error' => 'Image not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier que l'image appartient à l'utilisateur connecté
        if ($user->getImage()?->getId() !== $image->getId()) {
            return new JsonResponse(
                ['error' => "Vous n'avez pas accès à cette image, pour la supprimée."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Supprimer le lien entre l'utilisateur et l'image
        $user->setImage(null);
        $this->manager->persist($user);

        // Supprimer le fichier physique
        $filePath = $this->getParameter(
            'kernel.project_dir'
        )
            . '/public'
            .
            $image
            ->getFilePath();

        // Vérifier si le fichier existe et le supprimer
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Supprimer l'image de la base de données
        $this->manager->remove($image);
        $this->manager->flush();

        return new JsonResponse(
            ['message' => 'Image supprimée avec succès.'],
            Response::HTTP_OK
        );
    }
}
