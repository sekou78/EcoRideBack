<?php

namespace App\Controller;

use App\Entity\Image;
use App\Repository\ImageRepository;
use App\Service\ImageUploaderService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api/image', name: 'app_api_image_')]
final class ImageController extends AbstractController
{
    private string $uploadDir;

    public function __construct(
        private EntityManagerInterface $manager,
        private ImageRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private ImageUploaderService $imageUploader,
        private KernelInterface $kernel // Injection du kernel pour obtenir le répertoire
    ) {
        // Initialisation du répertoire d'upload à partir du kernel
        $this->uploadDir = $this->kernel
            ->getProjectDir() . '/public/uploads/images/';
    }

    // Ajouter une image
    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
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
        $image->setIdentite($fileName);
        $image->setFilePath('/uploads/images/' . $fileName);

        $image->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($image);
        $this->manager->flush();

        return new JsonResponse(
            $this->serializer
                ->serialize(
                    $image,
                    'json'
                ),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    //Afficher une image
    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): BinaryFileResponse
    {
        // Récupération de l'image en base de données
        $image = $this->repository->findOneBy(['id' => $id]);

        // Vérification de l'existence de l'image
        if (!$image) {
            throw $this->createNotFoundException('Image not found');
        }

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
            throw $this->createNotFoundException('File not found');
        }

        // Retourner directement l'image en réponse HTTP
        return new BinaryFileResponse($imagePath);
    }

    //Modifier une image
    #[Route('/{id}', name: 'edit', methods: 'POST')]
    public function edit(
        int $id,
        Request $request
    ): Response {
        // Récupération de l'image en base de données
        $image = $this->repository->findOneBy(['id' => $id]);

        if (!$image) {
            return new JsonResponse(
                ['error' => 'Image not found'],
                Response::HTTP_NOT_FOUND
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
    public function delete(int $id): JsonResponse
    {
        // Récupérer l'image depuis la base de données
        $image = $this->repository->find($id);

        if (!$image) {
            return new JsonResponse(
                ['error' => 'Image not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Construire le chemin absolu du fichier
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
            ['message' => 'Image deleted successfully'],
            Response::HTTP_OK
        );
    }
}
