<?php

namespace App\Controller;

use App\Entity\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\SupportMessage;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;


#[Route("api/supportMessage", name: "app_api_supportMessage_")]
final class SupportMessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private MailerInterface $mailer,
    ) {}

    #[Route('/send', name: 'send', methods: ['POST'])]
    #[OA\Post(
        path: "/api/supportMessage/send",
        summary: "Envoyer un message au support",
        description: "Permet à un utilisateur connecté ou non 
                        d’envoyer un message au support avec un 
                        fichier attaché optionnel. Une notification 
                        est créée pour le destinataire et un email est envoyé.",
        tags: ["Contact Support"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données du message et fichier éventuel",
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    required: [
                        "email",
                        "message"
                    ],
                    properties: [
                        new OA\Property(
                            property: "name",
                            type: "string",
                            example: "Jean Dupont"
                        ),
                        new OA\Property(
                            property: "email",
                            type: "string",
                            format: "email",
                            example: "jean.dupont@mail.com"
                        ),
                        new OA\Property(
                            property: "subject",
                            type: "string",
                            example: "Problème de réservation"
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Bonjour, je rencontre un problème avec ma réservation."
                        ),
                        new OA\Property(
                            property: "file",
                            type: "string",
                            format: "binary",
                            description: "Fichier attaché optionnel (pdf, jpg, png, docx, txt, gif, webp, zip)"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Message envoyé avec succès",
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
                description: "Erreur de validation (email ou message manquant, fichier invalide)",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Email et message sont obligatoires."
                            )
                        ]
                    )
                )
            )
        ]
    )]
    public function send(Request $request): JsonResponse
    {
        // récupère l'utilisateur connecté, ou null
        $user = $this->getUser();

        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $subject = $request->request->get('subject');
        $messageContent = $request->request->get('message');
        $file = $request->files->get('file'); // peut être null

        // Validation de base
        if (!$email || !$messageContent) {
            return new JsonResponse(
                ['error' => 'Email et message sont obligatoires.'],
                400
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(
                ['error' => 'Email invalide.'],
                400
            );
        }

        if ($file) {
            $allowedExtensions = [
                'pdf',
                'jpg',
                'jpeg',
                'png',
                'docx',
                'txt',
                'gif',
                'webp',
                'zip',
            ];
            $extension = $file->guessExtension();

            if (!in_array(
                $extension,
                $allowedExtensions
            )) {
                return new JsonResponse(
                    ['error' => 'Type de fichier non autorisé.'],
                    400
                );
            }

            if ($file->getSize() > 5 * 1024 * 1024) {
                return new JsonResponse(
                    [
                        'error' => 'La taille du fichier ne doit pas dépasser 5 Mo.'
                    ],
                    400
                );
            }

            if (!$file->isValid()) {
                return new JsonResponse(
                    ['error' => 'Fichier invalide.'],
                    400
                );
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(
                ['error' => 'Email invalide.'],
                400
            );
        }

        // Gestion du fichier uploadé
        $filename = null;
        if ($file) {
            if (!$file->isValid()) {
                return new JsonResponse(
                    ['error' => 'Fichier invalide.'],
                    400
                );
            }

            // Exemple simple: on déplace le fichier dans un dossier uploads/support
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/support';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $filename = uniqid() . '-' . preg_replace('/[^a-z0-9_\.-]/i', '_', $file->getClientOriginalName());
            $file->move($uploadsDir, $filename);
        }

        // Enregistrement en base
        $supportMessage = new SupportMessage();
        $supportMessage->setName($name ?? 'Anonyme');
        $supportMessage->setEmail($email);
        $supportMessage->setSubject($subject ?? 'Sans objet');
        $supportMessage->setMessage($messageContent);
        $supportMessage->setFilename($filename);
        $supportMessage->setStatus('new'); // par défaut
        $supportMessage->setPriority('normal'); // par défaut


        $supportMessage->setCreatedAt(new \DateTimeImmutable());

        // lie le message à l'utilisateur connecté
        if ($user) {
            $supportMessage->setUser($user);
        }

        $this->manager->persist($supportMessage);

        $user = $this->getUser();

        if ($user) {
            // Notification pour utilisateur connecté
            $notification = new Notification();
            $notification->setUser($user);
            $notification->setMessage("Votre demande a bien été reçue.");
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTimeImmutable());
            $notification->setSupportMessage($supportMessage);

            $this->manager->persist($notification);
        } else {
            // Notification pour utilisateur non connecté → stocke l'email
            $notification = new Notification();
            $notification->setEmail($email); // ici l’email de l’anonyme
            $notification->setMessage("Votre demande a bien été reçue.");
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTimeImmutable());
            $notification->setSupportMessage($supportMessage);

            $this->manager->persist($notification);
        }
        $this->manager->flush();

        // Construction du mail vers support
        $emailObj = (new Email())
            ->from('ecoride_studi@dinga223.fr')
            ->replyTo($email)
            ->to('ecoride_studi@dinga223.fr')
            ->subject('[Support] ' . ($subject ?? 'Sans objet'))
            ->text(
                "Nom: " . ($name ?? 'Anonyme') . "\n" .
                    "Email: $email\n\n" .
                    $messageContent
            );

        if ($filename) {
            $emailObj->attachFromPath($uploadsDir . '/' . $filename);
        }

        $this->mailer->send($emailObj);

        // Si non connecté → mail de confirmation au user
        if (!$user) {
            $confirmationEmail = (new Email())
                ->from('ecoride_studi@dinga223.fr')
                ->to($email)
                ->subject('[Support] Confirmation de réception')
                ->text("Bonjour " . ($name ?? 'Utilisateur') . ",\n\nVotre message a bien été reçu. Nous vous répondrons dès que possible.");

            $this->mailer->send($confirmationEmail);
        }

        return new JsonResponse(
            ['success' => true]
        );
    }

    #[Route('/showMessages', name: 'list_messages', methods: ['GET'])]
    #[OA\Get(
        path: "/api/supportMessage/showMessages",
        summary: "Lister tous les messages support",
        description: "Retourne tous les messages de support 
                        pour les utilisateurs ADMIN ou EMPLOYE, 
                        triés par date de création descendante.",
        tags: ["Contact Support"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des messages support",
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
                                    example: 12
                                ),
                                new OA\Property(
                                    property: "name",
                                    type: "string",
                                    example: "Jean Dupont"
                                ),
                                new OA\Property(
                                    property: "email",
                                    type: "string",
                                    example: "jean.dupont@mail.com"
                                ),
                                new OA\Property(
                                    property: "subject",
                                    type: "string",
                                    example: "Problème de réservation"
                                ),
                                new OA\Property(
                                    property: "message",
                                    type: "string",
                                    example: "Je rencontre un problème avec ma réservation."
                                ),
                                new OA\Property(
                                    property: "filename",
                                    type: "string",
                                    nullable: true,
                                    example: "document.pdf"
                                ),
                                new OA\Property(
                                    property: "createdAt",
                                    type: "string",
                                    example: "23-08-2025 15:42"
                                ),
                                new OA\Property(
                                    property: "status",
                                    type: "string",
                                    example: "new"
                                ),
                                new OA\Property(
                                    property: "priority",
                                    type: "string",
                                    example: "normal"
                                ),
                                new OA\Property(
                                    property: "assignedTo",
                                    type: "string",
                                    nullable: true,
                                    example: "Admin1"
                                )
                            ]
                        )
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (non ADMIN / EMPLOYE)",
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
    public function list_messages(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EMPLOYE')) {
            return new JsonResponse(
                ['error' => 'Accès refusé.'],
                403
            );
        }

        $repository = $this->manager
            ->getRepository(
                SupportMessage::class
            );
        $messages = $repository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        $data = [];

        foreach ($messages as $message) {
            $data[] = [
                'id' => $message->getId(),
                'name' => $message->getName(),
                'email' => $message->getEmail(),
                'subject' => $message->getSubject(),
                'message' => $message->getMessage(),
                'filename' => $message->getFilename(),
                'createdAt' => $message->getCreatedAt()->format('d-m-Y H:i'),
                'status' => $message->getStatus(),
                'priority' => $message->getPriority(),
                'assignedTo' => $message->getAssignedTo(),
            ];
        }

        return new JsonResponse($data);
    }

    // Lister les messages support filtrés par statut
    #[Route('/showMessagesFilter', name: 'list_messages_filter', methods: ['GET'])]
    #[OA\Get(
        path: "/api/supportMessage/showMessagesFilter",
        summary: "Lister les messages support sauf les résolus",
        description: "Retourne tous les messages support dont 
                        le statut n'est pas 'resolved' pour les 
                        utilisateurs ADMIN ou EMPLOYE, triés par 
                        date de création descendante.",
        tags: ["Contact Support"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des messages support filtrés",
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
                                    example: 12
                                ),
                                new OA\Property(
                                    property: "name",
                                    type: "string",
                                    example: "Jean Dupont"
                                ),
                                new OA\Property(
                                    property: "email",
                                    type: "string",
                                    example: "jean.dupont@mail.com"
                                ),
                                new OA\Property(
                                    property: "subject",
                                    type: "string",
                                    example: "Problème de réservation"
                                ),
                                new OA\Property(
                                    property: "message",
                                    type: "string",
                                    example: "Je rencontre un problème avec ma réservation."
                                ),
                                new OA\Property(
                                    property: "filename",
                                    type: "string",
                                    nullable: true,
                                    example: "document.pdf"
                                ),
                                new OA\Property(
                                    property: "createdAt",
                                    type: "string",
                                    example: "23-08-2025 15:42"
                                ),
                                new OA\Property(
                                    property: "status",
                                    type: "string",
                                    example: "new"
                                ),
                                new OA\Property(
                                    property: "priority",
                                    type: "string",
                                    example: "normal"
                                ),
                                new OA\Property(
                                    property: "assignedTo",
                                    type: "string",
                                    nullable: true,
                                    example: "Admin1"
                                )
                            ]
                        )
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (non ADMIN / EMPLOYE)",
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
    public function list_messages_filter(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EMPLOYE')) {
            return new JsonResponse(
                ['error' => 'Accès refusé.'],
                403
            );
        }

        $repository = $this->manager->getRepository(SupportMessage::class);

        $qb = $repository->createQueryBuilder('m')
            ->where('m.status != :excludedStatus')
            ->setParameter('excludedStatus', 'resolved')
            ->orderBy('m.createdAt', 'DESC');

        $messages = $qb->getQuery()->getResult();

        $data = [];

        foreach ($messages as $message) {
            $data[] = [
                'id' => $message->getId(),
                'name' => $message->getName(),
                'email' => $message->getEmail(),
                'subject' => $message->getSubject(),
                'message' => $message->getMessage(),
                'filename' => $message->getFilename(),
                'createdAt' => $message->getCreatedAt()->format('d-m-Y H:i'),
                'status' => $message->getStatus(),
                'priority' => $message->getPriority(),
                'assignedTo' => $message->getAssignedTo(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/contact/status/{id}', name: 'update_status', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/supportMessage/contact/status/{id}",
        summary: "Mettre à jour le statut d'un message support",
        description: "Permet à un utilisateur ADMIN ou EMPLOYE 
                        de changer le statut d'un message support.",
        tags: ["Contact Support"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du message support",
                schema: new OA\Schema(
                    type: "integer",
                    example: 5
                )
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Nouveau statut du message",
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: "object",
                    required: ["status"],
                    properties: [
                        new OA\Property(
                            property: "status",
                            type: "string",
                            description: "Nouveau statut du message",
                            example: "read",
                            enum: ["new", "read", "resolved"]
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Statut mis à jour avec succès",
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
                description: "Erreur de validation (statut manquant ou invalide)",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "error",
                                type: "string",
                                example: "Le champ \"status\" est requis."
                            )
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (non ADMIN / EMPLOYE)",
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
    public function updateStatus(
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

        if (!isset($data['status'])) {
            return new JsonResponse(
                ['error' => 'Le champ "status" est requis.'],
                400
            );
        }

        $newStatus = $data['status'];

        $validStatuses = ['new', 'read', 'resolved'];

        if (!in_array($newStatus, $validStatuses)) {
            return new JsonResponse(
                ['error' => 'Statut invalide'],
                400
            );
        }

        $message->setStatus($newStatus);

        $message->setUpdatedAt(new \DateTimeImmutable());

        $user = $this->getUser();
        if ($user) {
            $message->setUpdatedBy($user->getUserIdentifier());
        }

        $this->manager->flush();

        return new JsonResponse(
            ['success' => true]
        );
    }
}
