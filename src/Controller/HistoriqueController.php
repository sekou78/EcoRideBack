<?php

namespace App\Controller;

use App\Entity\Historique;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\HistoriqueRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/historique", name: "app_api_historique_")]
final class HistoriqueController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private HistoriqueRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security
    ) {}

    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = json_decode(
            $request->getContent(),
            true
        );

        if (!$data || empty($data['trajet'])) {
            return new JsonResponse(
                ['error' => 'Le champ "trajet" est obligatoire'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Récupérer le trajet
        $trajet = $this->manager
            ->getRepository(Trajet::class)
            ->find($data['trajet']);
        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier si l'utilisateur est le chauffeur (créateur du trajet)
        $isChauffeur = $trajet->getChauffeur() === $user;

        // Vérifier si l'utilisateur est un passager
        $reservations = $trajet->getReservations();
        $isPassager = false;
        foreach ($reservations as $reservation) {
            if ($reservation->getUser() === $user) {
                $isPassager = true;
                break;
            }
        }

        // Si l'utilisateur n'est ni chauffeur ni passager, il n'a pas le droit
        if (!$isChauffeur && !$isPassager) {
            return new JsonResponse(
                [
                    'error' => 'Vous ne pouvez pas créer un historique pour ce trajet'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Créer l'historique
        $historique = new Historique();
        $historique->setTrajet($trajet);

        $historique->setCreatedAt(new \DateTimeImmutable());

        $historique->setUser($user);

        $historique->setStatut($trajet->getStatut());

        // Assigner le rôle
        if ($isChauffeur) {
            $historique->setRole('chauffeur');
        } elseif ($isPassager) {
            $historique->setRole('passager');
        }

        $this->manager->persist($historique);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $historique,
            'json',
            ['groups' => ['historique:read']]
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/filter', methods: 'GET')]
    public function filter(Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $statut = $request->query->get('statut');  // ?statut=EN_ATTENTE
        $trajetId = $request->query->get('trajet'); // ?trajet=3

        $criteria = ['user' => $user];

        if ($statut) {
            $criteria['statut'] = $statut;
        }

        if ($trajetId) {
            $criteria['trajet'] = $trajetId;
        }

        $historiques = $this->repository->findBy($criteria);

        if (empty($historiques)) {
            return new JsonResponse(
                [
                    'message' => 'Aucun historique trouvé'
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $responseData = $this->serializer->serialize(
            $historiques,
            'json',
            ['groups' => ['historique:read']]
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route("/{id}", name: "delete", methods: "DELETE")]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $historique = $this->repository->findOneBy(['id' => $id]);

        if ($historique) {
            $this->manager->remove($historique);

            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Historique supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route('/cancel', methods: 'POST')]
    public function cancel(Request $request, MailerInterface $mailer): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['trajet'])) {
            return new JsonResponse(
                [
                    'error' => 'Le champ "trajet" est obligatoire'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $trajet = $this->manager
            ->getRepository(Trajet::class)
            ->find($data['trajet']);
        if (!$trajet) {
            return new JsonResponse(
                [
                    'error' => 'Trajet non trouvé'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $isChauffeur = $trajet->getChauffeur() === $user;
        $reservations = $trajet->getReservations();
        $isPassager = false;
        $userReservation = null;

        foreach ($reservations as $reservation) {
            if ($reservation->getUser() === $user) {
                $isPassager = true;
                $userReservation = $reservation;
                break;
            }
        }

        if (!$isChauffeur && !$isPassager) {
            return new JsonResponse(
                [
                    'error' => "Vous n'êtes pas autorisé à annuler ce trajet"
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $montant = $trajet->getPrix() ?? 0;

        if ($isChauffeur) {
            $trajet->setStatut('ANNULEE');

            foreach ($reservations as $reservation) {
                $passager = $reservation->getUser();

                $email = (new Email())
                    ->from('no-reply@tonsite.com')
                    ->to($passager->getEmail())
                    ->subject('Annulation du covoiturage')
                    ->html("
                Bonjour {$passager->getPseudo()},<br><br>
                Nous vous informons que votre trajet a été annulé par le chauffeur.<br><br>
                Merci de votre compréhension.
            ");

                $mailer->send($email);

                //Remboursement
                if ($montant > 0) {
                    $passager->setCredits($passager->getCredits() + $montant);
                    $this->manager->persist($passager);
                }

                //Retirer le passager du trajet
                if ($trajet->getUsers()->contains($passager)) {
                    $trajet->removeUser($passager);
                }

                //Supprimer la réservation
                $trajet->removeReservation($reservation);
                $this->manager->remove($reservation);
            }
        } elseif ($isPassager && $userReservation) {
            //upprimer sa réservation
            $trajet->removeReservation($userReservation);
            $this->manager->remove($userReservation);

            //Remboursement
            if ($montant > 0) {
                $user->addCredits($montant);
                $this->manager->persist($user);
            }

            //Retirer le passager du trajet
            if ($trajet->getUsers()->contains($user)) {
                $trajet->removeUser($user);
            }

            //Libérer une place
            $trajet->setNombrePlacesDisponible(
                $trajet->getNombrePlacesDisponible() + 1
            );
        }

        $this->manager->flush();

        return new JsonResponse(
            [
                'message' => "Annulation effectuée avec succès."
            ],
            Response::HTTP_OK
        );
    }
}
