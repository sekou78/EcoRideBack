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
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: "/api/supportComment/add/{id}",
        summary: "Ajouter un commentaire à un support message",
        description: "Permet à un ADMIN ou EMPLOYE d’ajouter un 
                        commentaire à un support message. Une notification 
                        est automatiquement envoyée au propriétaire du message.",
        tags: ["Reponse contact Support"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du message auquel ajouter le commentaire",
                schema: new OA\Schema(
                    type: "integer",
                    example: 42
                )
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Contenu du commentaire",
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    required: ["comment"],
                    properties: [
                        new OA\Property(
                            property: "comment",
                            type: "string",
                            example: "Merci pour votre retour, nous traitons votre demande."
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Commentaire ajouté avec succès",
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
                response: 400,
                description: "Commentaire vide",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Le commentaire est vide."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (utilisateur non ADMIN ou EMPLOYE)",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Accès refusé."
                            )
                        ]
                    )
                )
            )
        ]
    )]
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

    #[Route('/list/{id}', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: "/api/supportComment/list/{id}",
        summary: "Lister les commentaires d’un support message",
        description: "Retourne tous les commentaires associés à 
                        un SupportMessage spécifique.",
        tags: ["Reponse contact Support"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du message dont on veut récupérer les commentaires",
                schema: new OA\Schema(
                    type: "integer",
                    example: 42
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des commentaires",
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
                                    example: 7
                                ),
                                new OA\Property(
                                    property: "authorName",
                                    type: "string",
                                    example: "AdminSupport"
                                ),
                                new OA\Property(
                                    property: "content",
                                    type: "string",
                                    example: "Votre demande a été traitée."
                                ),
                                new OA\Property(
                                    property: "createdAt",
                                    type: "string",
                                    example: "22-08-2025 14:32"
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
    #[OA\Put(
        path: "/api/supportComment/{id}",
        summary: "Modifier un commentaire",
        description: "Permet à l’auteur d’un commentaire de mettre à jour son contenu.",
        tags: ["Reponse contact Support"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du commentaire à modifier",
                schema: new OA\Schema(
                    type: "integer",
                    example: 7
                )
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Nouveau contenu du commentaire",
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    required: ["content"],
                    properties: [
                        new OA\Property(
                            property: "content",
                            type: "string",
                            example: "Contenu mis à jour du commentaire"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Commentaire mis à jour avec succès",
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
                response: 400,
                description: "Contenu vide ou invalide",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Le contenu ne peut pas être vide"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (utilisateur non auteur)",
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
                response: 404,
                description: "Commentaire non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Commentaire non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
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
    #[OA\Delete(
        path: "/api/supportComment/{id}",
        summary: "Supprimer un commentaire",
        description: "Permet à l’auteur d’un commentaire de le supprimer.",
        tags: ["Reponse contact Support"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du commentaire à supprimer",
                schema: new OA\Schema(
                    type: "integer",
                    example: 7
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Commentaire supprimé avec succès",
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
                description: "Accès refusé (utilisateur non auteur)",
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
                response: 404,
                description: "Commentaire non trouvé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Commentaire non trouvé"
                            )
                        ]
                    )
                )
            )
        ]
    )]
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
