<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    private ResetPasswordHelperInterface $resetPasswordHelper;
    private EntityManagerInterface $manager;

    public function __construct(
        ResetPasswordHelperInterface $resetPasswordHelper,
        EntityManagerInterface $entityManager
    ) {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->manager = $entityManager;
    }

    /**
     * Request password reset (API JSON)
     */
    #[Route('', name: 'app_forgot_password_request', methods: ['POST'])]
    public function request(
        Request $request,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): Response {
        $data = json_decode(
            $request->getContent(),
            true
        ) ?? [];

        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json([
                'success' => false,
                'message' => 'Email manquant'
            ], 422);
        }

        $user = $this->manager
            ->getRepository(
                User::class
            )->findOneBy(
                ['email' => $email]
            );

        // Toujours répondre positivement pour ne pas divulguer l’existence du compte
        if (!$user) {
            return $this->json(
                [
                    'success' => true,
                    'message' => 'Si ce compte existe, un email de réinitialisation a été envoyé.'
                ]
            );
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json([
                'success' => true,
                'message' => 'Si ce compte existe, un email de réinitialisation a été envoyé.'
            ]);
        }

        $emailMessage = (new TemplatedEmail())
            ->from('ecoride_studi@dinga223.fr')
            ->to($user->getEmail())
            ->subject('Votre demande de réinitialisation de mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context(
                [
                    'resetToken' => $resetToken,
                    'frontendUrl' => 'http://localhost:3000/changeResetMdp?token=' . urlencode($resetToken->getToken())
                ]
            );

        $mailer->send($emailMessage);

        return $this->json([
            'success' => true,
            'message' => 'Si ce compte existe, un email de réinitialisation a été envoyé.',
            'resetToken' => $resetToken->getToken()
        ]);
    }

    /**
     * Check email after password reset request
     */
    #[Route('/check-email', name: 'app_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->json(
            [
                'success' => true,
                'message' => 'Vérifiez votre email pour réinitialiser votre mot de passe.',
            ]
        );
    }

    /**
     * Reset password
     */
    #[Route('/reset/{token}', name: 'app_reset_password_redirect', methods: ['GET'])]
    public function redirectToFrontend(?string $token = null): Response
    {
        if (!$token) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Token manquant'
                ],
                400
            );
        }

        // Redirection vers le front-end
        $frontendUrl = 'http://localhost:3000/changeResetMdp?token=' . urlencode($token);
        return $this->redirect($frontendUrl);
    }

    /**
     * Reset password API
     */
    #[Route('/reset', name: 'app_reset_password_api', methods: ['POST'])]
    public function resetPasswordApi(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator
    ): Response {
        $data = json_decode(
            $request->getContent(),
            true
        ) ?? [];

        $token = $data['token'] ?? null;
        $plainPassword = $data['plainPassword'] ?? null;

        if (!$token || !$plainPassword) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Token ou mot de passe manquant'
                ],
                422
            );
        }

        try {
            $user = $this->resetPasswordHelper
                ->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Token invalide ou expiré',
                    'reason' => $translator->trans(
                        $e->getReason(),
                        [],
                        'ResetPasswordBundle'
                    )
                ],
                400
            );
        }

        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                $plainPassword
            )
        );

        $this->manager->flush();
        $this->resetPasswordHelper
            ->removeResetRequest($token);

        return $this->json(
            [
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès.'
            ]
        );
    }

    /**
     * Process sending reset email
     */
    private function processSendingPasswordResetEmail(
        string $emailFormData,
        MailerInterface $mailer,
        TranslatorInterface $translator,
        bool $isApi = false
    ): Response {
        $user = $this->manager
            ->getRepository(User::class)
            ->findOneBy(
                ['email' => $emailFormData]
            );

        if (!$user) {
            return $this->json(
                [
                    'success' => true,
                    'message' => 'Si ce compte existe, un email de réinitialisation a été envoyé.'
                ]
            );
        }

        try {
            $resetToken = $this->resetPasswordHelper
                ->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json(
                [
                    'success' => true,
                    'message' => 'Si ce compte existe, un email de réinitialisation a été envoyé.'
                ]
            );
        }

        $email = (new TemplatedEmail())
            ->from(new Address(
                'ecoride_studi@dinga223.fr',
                'passwordReset'
            ))
            ->to($user->getEmail())
            ->subject('Votre demande de réinitialisation de mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context(
                ['resetToken' => $resetToken]
            );

        $mailer->send($email);

        return $this->json(
            [
                'success' => true,
                'message' => 'Email envoyé avec succès.',
                'resetToken' => $resetToken->getToken()
            ]
        );
    }
}
