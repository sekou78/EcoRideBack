<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Repository\SupportCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/notification", name: "app_api_notification_")]
final class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SupportCommentRepository $commentRepo,
        private NotificationRepository $notificationRepo
    ) {}

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                [
                    'error' => 'Utilisateur non authentifié'
                ],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $userEmail = $user->getEmail();

        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_EMPLOYE', $user->getRoles())) {
            $notifications = $this->notificationRepo->findBy([], ['createdAt' => 'DESC']);
        } else {
            $qb = $this->notificationRepo->createQueryBuilder('n');
            $qb->where('n.user = :user')
                ->orWhere('n.email = :email')
                ->setParameter('user', $user)
                ->setParameter('email', $userEmail)
                ->orderBy('n.createdAt', 'DESC');

            $notifications = $qb->getQuery()->getResult();
        }

        $data = [];
        foreach ($notifications as $notif) {
            $data[] = [
                'id' => $notif->getId(),
                'message' => $notif->getMessage(),
                'createdAt' => $notif->getCreatedAt()->format('d-m-Y H:i'),
                'isRead' => $notif->isRead(),
                'supportMessageId' => $notif->getSupportMessage()?->getId(),
                'emetteur' => $notif->getAuthor() ? [
                    'id' => $notif->getAuthor()->getId(),
                    'email' => $notif->getAuthor()->getEmail(),
                    'username' => $notif->getAuthor()->getUserIdentifier()
                ] : null,
                'destinataire' => $notif->getUser() ? [
                    'id' => $notif->getUser()->getId(),
                    'email' => $notif->getUser()->getEmail(),
                    'username' => $notif->getUser()->getUserIdentifier()
                ] : ($notif->getEmail() ?? null),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/read/{id}', name: 'mark_read', methods: ['POST'])]
    public function markAsRead(Notification $notification): JsonResponse
    {
        $user = $this->getUser();

        if (
            !$user || (
                $notification->getUser()
                &&
                $notification->getUser() !== $user)
        ) {
            return new JsonResponse(
                ['error' => 'Accès refusé'],
                403
            );
        }

        $notification->setIsRead(true);
        $this->manager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/read-all', name: 'read_all', methods: ['POST'])]
    public function readAll(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Non connecté'],
                401
            );
        }

        $countNotif = $this->manager->createQueryBuilder();
        $count = $countNotif->update(Notification::class, 'n')
            ->set('n.isRead', ':true')
            ->where('n.isRead = false OR n.isRead IS NULL')
            ->andWhere('n.user = :user OR n.email = :email')
            ->setParameter('true', true)
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->getQuery();

        // retourne le nombre de notifications mises à jour
        $updatedCount = $count->execute();

        return new JsonResponse(
            [
                'success' => true,
                'updated' => $updatedCount
            ]
        );
    }
}
