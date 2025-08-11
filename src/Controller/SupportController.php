<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\SupportMessage;
use Doctrine\ORM\EntityManagerInterface;


#[Route("api/support", name: "app_api_support_")]
final class SupportController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private MailerInterface $mailer,
    ) {}

    #[Route('/send', name: 'send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $subject = $request->request->get('subject');
        $messageContent = $request->request->get('message');
        $file = $request->files->get('file'); // peut être null

        if (!$email || !$messageContent) {
            return new JsonResponse(
                ['error' => 'Email et message sont obligatoires.'],
                400
            );
        }

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
        $extension = $file->guessExtension();

        if (!in_array($extension, $allowedExtensions)) {
            return new JsonResponse(
                ['error' => 'Type de fichier non autorisé.'],
                400
            );
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse(
                ['error' => 'La taille du fichier ne doit pas dépasser 5 Mo.'],
                400
            );
        }

        if (!$file->isValid()) {
            return new JsonResponse(
                ['error' => 'Fichier invalide.'],
                400
            );
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

        $this->manager->persist($supportMessage);
        $this->manager->flush();

        // Construction du mail
        $emailObj = (new Email())
            ->from($email)
            ->to('ecoride_studi@dinga223.fr')
            ->subject('[Support] ' . ($subject ?? 'Sans objet'))
            ->text(
                "Nom: " . ($name ?? 'Anonyme') . "\n" .
                    "Email: $email\n\n" .
                    $messageContent
            );

        // Si fichier attaché, on l'ajoute
        if ($filename) {
            $path = $uploadsDir . '/' . $filename;
            $emailObj->attachFromPath($path);
        }

        $this->mailer->send($emailObj);

        return new JsonResponse(
            ['success' => true]
        );
    }

    #[Route('/showMessages', name: 'list_messages', methods: ['GET'])]
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
