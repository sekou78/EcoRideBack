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
use OpenApi\Attributes as OA;

#[Route("api/notification", name: "app_api_notification_")]
final class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SupportCommentRepository $commentRepo,
        private NotificationRepository $notificationRepo
    ) {}

    #[Route('/', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: "/api/notification/",
        summary: "Lister les notifications",
        description: "Retourne les notifications de l’utilisateur connecté. 
                        - Si l’utilisateur est **admin** ou **employé**, il voit toutes les notifications.  
                        - Sinon, il ne voit que les siennes (liées à son compte ou son email).",
        tags: ["Notifications"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des notifications",
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
                                    example: 42
                                ),
                                new OA\Property(
                                    property: "message",
                                    type: "string",
                                    example: "Votre réservation a été confirmée."
                                ),
                                new OA\Property(
                                    property: "createdAt",
                                    type: "string",
                                    example: "22-08-2025 14:32"
                                ),
                                new OA\Property(
                                    property: "isRead",
                                    type: "boolean",
                                    example: false
                                ),
                                new OA\Property(
                                    property: "supportMessageId",
                                    type: "integer",
                                    nullable: true,
                                    example: 101
                                ),
                                new OA\Property(
                                    property: "emetteur",
                                    type: "object",
                                    nullable: true,
                                    properties: [
                                        new OA\Property(
                                            property: "id",
                                            type: "integer",
                                            example: 7
                                        ),
                                        new OA\Property(
                                            property: "email",
                                            type: "string",
                                            example: "auteur@test.com"
                                        ),
                                        new OA\Property(
                                            property: "username",
                                            type: "string",
                                            example: "AuteurTest"
                                        )
                                    ]
                                ),
                                new OA\Property(
                                    property: "destinataire",
                                    oneOf: [
                                        new OA\Schema(
                                            type: "object",
                                            properties: [
                                                new OA\Property(
                                                    property: "id",
                                                    type: "integer",
                                                    example: 15
                                                ),
                                                new OA\Property(
                                                    property: "email",
                                                    type: "string",
                                                    example: "client@test.com"
                                                ),
                                                new OA\Property(
                                                    property: "username",
                                                    type: "string",
                                                    example: "ClientX"
                                                )
                                            ]
                                        ),
                                        new OA\Schema(
                                            type: "string",
                                            example: "autre@mail.com"
                                        ),
                                        new OA\Schema(type: "null")
                                    ]
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
            )
        ]
    )]
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
    #[OA\Post(
        path: "/api/notification/read/{id}",
        summary: "Marquer une notification comme lue",
        description: "Permet de marquer une notification comme lue.  
                        - L’utilisateur doit être connecté.  
                        - L’accès est refusé si la notification n’appartient pas à l’utilisateur.",
        tags: ["Notifications"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID de la notification à marquer comme lue",
                in: "path",
                required: true,
                schema: new OA\Schema(
                    type: "integer",
                    example: 12
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Notification marquée comme lue",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "success",
                                type: "boolean",
                                example: true
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Accès refusé"
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
            )
        ]
    )]
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
    #[OA\Post(
        path: "/api/notification/read-all",
        summary: "Marquer toutes les notifications comme lues",
        description: "Met toutes les notifications comme lues  
                        et retourne le nombre de notifications mises à jour.",
        tags: ["Notifications"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Toutes les notifications mises à jour",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "success",
                                type: "boolean",
                                example: true
                            ),
                            new OA\Property(
                                property: "updated",
                                type: "integer",
                                description: "Nombre de notifications mises à jour",
                                example: 7
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Utilisateur non connecté",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Non connecté"
                            )
                        ]
                    )
                )
            )
        ]
    )]
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

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: "/api/notification/{id}",
        summary: "Afficher une notification",
        description: "Retourne le détail d’une notification ainsi 
                        que ses commentaires liés à un message. 
                        L’accès est autorisé si l’utilisateur est :  
                            - ADMIN ou EMPLOYE  
                            - Propriétaire de la notification (
                                ou correspond à son email)",
        tags: ["Notifications"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la notification à récupérer",
                schema: new OA\Schema(
                    type: "integer",
                    example: 12
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détail de la notification avec ses commentaires",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 12
                            ),
                            new OA\Property(
                                property: "supportMessageId",
                                type: "integer",
                                nullable: true,
                                example: 101
                            ),
                            new OA\Property(
                                property: "comments",
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
                                            property: "content",
                                            type: "string",
                                            example: "Merci pour votre retour."
                                        ),
                                        new OA\Property(
                                            property: "author",
                                            type: "string",
                                            example: "UtilisateurX"
                                        ),
                                        new OA\Property(
                                            property: "createdAt",
                                            type: "string",
                                            example: "22-08-2025 14:32"
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
            )
        ]
    )]
    public function show(Notification $notification): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié'],
                401
            );
        }

        // Autorisation basique
        if (
            !in_array('ROLE_ADMIN', $user->getRoles()) &&
            !in_array('ROLE_EMPLOYE', $user->getRoles()) &&
            $notification->getUser() !== $user &&
            $notification->getEmail() !== $user->getEmail()
        ) {
            return new JsonResponse(
                ['error' => 'Accès interdit'],
                403
            );
        }

        $commentsData = [];
        if ($notification->getSupportMessage()) {
            foreach (
                $notification->getSupportMessage()
                    ->getSupportComments() as $comment
            ) {
                $commentsData[] = [
                    'id'        => $comment->getId(),
                    'content'   => $comment->getContent(),
                    'author'    => $comment->getAuthor()?->getPseudo()
                        ?? $comment->getAuthor()?->getEmail(),
                    'createdAt' => $comment->getCreatedAt()->format('d-m-Y H:i'),
                ];
            }
        }

        $data = [
            'id' => $notification->getId(),
            'supportMessageId' => $notification->getSupportMessage()?->getId(),
            'comments' => $commentsData,
        ];

        return new JsonResponse($data);
    }
}
