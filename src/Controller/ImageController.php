<?php

namespace App\Controller;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/image', name: 'app_api_image_')]
final class ImageController extends AbstractController
{
    private const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/jpg'];
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    public function __construct(
        private readonly ImageRepository $image,
        private readonly EntityManagerInterface $manager,
    ) {}

    #[Route(path: '/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route(methods: 'POST')]
    public function new(Request $request): Response
    {
        $entity = new Image();
        $entity->setIdentite($request->request->get('identite'));

        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        if (
            !in_array($file->getClientMimeType(), self::ALLOWED_MIME_TYPES, true)
            || $file->getSize() > 5 * 1024 * 1024
        ) {
            throw new BadRequestHttpException();
        }
        if (
            !in_array($file->getClientOriginalExtension(), self::ALLOWED_EXTENSIONS, true)
        ) {
            throw new BadRequestHttpException();
        }

        $storagePath = 'uploads/images/';
        $newFileName = str_replace(' ', '_', $entity->getIdentite()) . uniqid() . '.' . $file->guessExtension();
        $file->move($this->getParameter('kernel.project_dir') . '/public/' . $storagePath, $newFileName);

        $entity->setFilePath($storagePath . '/' . $newFileName);

        $entity->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($entity);
        $this->manager->flush();

        return new JsonResponse($entity);
    }
}
