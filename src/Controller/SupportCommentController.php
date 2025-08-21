<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\SupportMessage;
use App\Entity\SupportComment;
use App\Repository\NotificationRepository;
use App\Repository\SupportCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("api/supportComment", name: "app_api_supportComment_")]
final class SupportCommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SupportCommentRepository $commentRepo,
        private NotificationRepository $notificationRepo
    ) {}

    // Ajouter un commentaire à un message support
    #[Route('/add/{id}', name: 'add', methods: ['POST'])]
    public function add(
        Request $request,
        SupportMessage $message
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EMPLOYE')) {
            return new JsonResponse(
                ['error' => 'Accès refusé.'],
                403
            );
        }

        $data = json_decode($request->getContent(), true);
        $commentText = $data['comment'] ?? null;

        if (!$commentText) {
            return new JsonResponse(
                ['error' => 'Le commentaire est vide.'],
                400
            );
        }

        $comment = new SupportComment();
        $comment->setSupportMessage($message);
        $comment->setContent($commentText);

        $user = $this->getUser();
        if ($user) {
            $comment->setAuthor($user);
            $comment->setAuthorName($user->getUserIdentifier());
        } else {
            $comment->setAuthorName('Anonyme');
        }

        $comment->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($comment);

        $owner = $message->getUser();
        $ownerEmail = $owner ? $owner->getEmail() : $message->getEmail();

        if ($owner || $ownerEmail) {
            $notification = new Notification();

            if ($owner) {
                $notification->setUser($owner);
            } else {
                $notification->setEmail($ownerEmail);
            }

            $notification->setMessage("Un membre du support a répondu à votre demande.");
            $notification->setIsRead(false);
            $notification->setAuthor($this->getUser());
            $notification->setSupportMessage($message);
            $notification->setCreatedAt(new \DateTimeImmutable());

            $this->manager->persist($notification);
        }
        $this->manager->flush();

        return new JsonResponse(
            ['success' => true]
        );
    }

    // Lister tous les commentaires d’un message support
    #[Route('/list/{id}', name: 'list', methods: ['GET'])]
    public function list(SupportMessage $message): JsonResponse
    {
        $comments = $message->getSupportComments();

        $data = [];
        foreach ($comments as $comment) {
            $data[] = [
                'id' => $comment->getId(),
                'authorName' => $comment->getAuthorName(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('d-m-Y H:i'),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'update_comment', methods: ['PUT'])]
    public function updateComment(
        int $id,
        Request $request,
        EntityManagerInterface $manager
    ): JsonResponse {
        $comment = $this->commentRepo->find($id);
        if (!$comment) {
            return new JsonResponse(
                ['error' => 'Commentaire non trouvé'],
                404
            );
        }

        $user = $this->getUser();
        if (!$user || $user->getUserIdentifier() !== $comment->getAuthorName()) {
            return new JsonResponse(
                ['error' => 'Accès refusé'],
                403
            );
        }

        $data = json_decode(
            $request->getContent(),
            true
        );
        $content = $data['content'] ?? null;

        if (!$content || trim($content) === '') {
            return new JsonResponse(
                ['error' => 'Le contenu ne peut pas être vide'],
                400
            );
        }

        $comment->setContent($content);
        $comment->setUpdatedAt(new \DateTimeImmutable());

        $manager->flush();

        return new JsonResponse(
            ['success' => true]
        );
    }

    #[Route('/{id}', name: 'delete_comment', methods: ['DELETE'])]
    public function deleteComment(
        int $id,
        EntityManagerInterface $manager
    ): JsonResponse {
        $comment = $this->commentRepo->find($id);
        if (!$comment) {
            return new JsonResponse(
                ['error' => 'Commentaire non trouvé'],
                404
            );
        }

        $user = $this->getUser();
        if (!$user || $user->getUserIdentifier() !== $comment->getAuthorName()) {
            return new JsonResponse(
                ['error' => 'Accès refusé'],
                403
            );
        }

        $manager->remove($comment);
        $manager->flush();

        return new JsonResponse(
            ['success' => true]
        );
    }
}
