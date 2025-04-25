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
#[IsGranted('ROLE_USER')]
final class HistoriqueController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private HistoriqueRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    // #[Route(methods: "POST")]
    // public function new(Request $request): JsonResponse
    // {
    //     $data = json_decode(
    //         $request->getContent(),
    //         true
    //     );

    //     $historique = $this->serializer->deserialize(
    //         $request->getContent(),
    //         Historique::class,
    //         'json',
    //     );

    //     // Assigner le trajet à la réservation
    //     if ($data['trajet']) {
    //         $trajet = $this->manager
    //             ->getRepository(Trajet::class)
    //             ->find($data['trajet']);
    //         if ($trajet) {
    //             $historique->setTrajet($trajet);
    //         } else {
    //             return new JsonResponse(
    //                 ['error' => 'trajet non trouvé'],
    //                 Response::HTTP_BAD_REQUEST
    //             );
    //         }
    //     }

    //     // Assigner le user connecté
    //     $historique->setUser($this->getUser());
    //     // if ($data['user']) {
    //     //     $user = $this->manager
    //     //         ->getRepository(User::class)
    //     //         ->find($data['user']);
    //     //     if ($user) {
    //     //         $historique->setUser($user);
    //     //     } else {
    //     //         return new JsonResponse(
    //     //             ['error' => 'user non trouvé'],
    //     //             Response::HTTP_BAD_REQUEST
    //     //         );
    //     //     }
    //     // }

    //     $historique->setCreatedAt(new DateTimeImmutable());

    //     $this->manager->persist($historique);
    //     $this->manager->flush();

    //     $responseData = $this->serializer->serialize(
    //         $historique,
    //         'json',
    //         ['groups' => ['historique:read']]
    //     );

    //     $location = $this->urlGenerator->generate(
    //         'app_api_historique_show',
    //         ['id' => $historique->getId()],
    //         UrlGeneratorInterface::ABSOLUTE_URL,
    //     );

    //     return new JsonResponse(
    //         $responseData,
    //         Response::HTTP_CREATED,
    //         ['Location' => $location],
    //         true,
    //     );
    // }

    #[Route(name: "list", methods: "GET")]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $historiques = $this->repository->findBy(['user' => $user]);

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
    // #[Route("/{id}", name: "show", methods: "GET")]
    // public function show(int $id): JsonResponse
    // {
    //     $historique = $this->repository->findOneBy(['id' => $id]);

    //     if ($historique) {
    //         $responseData = $this->serializer->serialize(
    //             $historique,
    //             'json',
    //             ['groups' => ['historique:read']]
    //         );

    //         return new JsonResponse(
    //             $responseData,
    //             Response::HTTP_OK,
    //             [],
    //             true
    //         );
    //     }

    //     return new JsonResponse(
    //         null,
    //         Response::HTTP_NOT_FOUND
    //     );
    // }

    // #[Route("/{id}", name: "edit", methods: "PUT")]
    // public function edit(int $id, Request $request): JsonResponse
    // {
    //     $data = json_decode(
    //         $request->getContent(),
    //         true
    //     );

    //     // Récupérer la réservation existante
    //     $historique = $this->manager
    //         ->getRepository(
    //             Historique::class
    //         )
    //         ->findOneBy(
    //             ['id' => $id]
    //         );

    //     if (!$historique) {
    //         return new JsonResponse(
    //             ['error' => 'Réservation non trouvée'],
    //             Response::HTTP_NOT_FOUND
    //         );
    //     }

    //     if ($historique->getUser() !== $this->getUser()) {
    //         return new JsonResponse(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
    //     }

    //     // Mettre à jour le statut si présent
    //     if ($data['statut']) {
    //         $historique->setStatut($data['statut']);
    //     }

    //     // Mettre à jour le trajet si fourni
    //     if ($data['trajet']) {
    //         $trajet = $this->manager
    //             ->getRepository(
    //                 Trajet::class
    //             )
    //             ->find(
    //                 $data['trajet']
    //             );
    //         if (!$trajet) {
    //             return new JsonResponse(
    //                 ['error' => 'Trajet non trouvé'],
    //                 Response::HTTP_BAD_REQUEST
    //             );
    //         }
    //         $historique->setTrajet($trajet);
    //     }

    //     // Mettre à jour le user si fourni
    //     if ($data['user']) {
    //         $user = $this->manager
    //             ->getRepository(
    //                 User::class
    //             )
    //             ->find(
    //                 $data['user']
    //             );
    //         if (!$user) {
    //             return new JsonResponse(
    //                 ['error' => 'User non trouvé'],
    //                 Response::HTTP_BAD_REQUEST
    //             );
    //         }
    //         $historique->setUser($user);
    //     }

    //     $historique->setUpdatedAt(new \DateTimeImmutable());

    //     $this->manager->flush();

    //     $responseData = $this->serializer->serialize(
    //         $historique,
    //         'json',
    //         ['groups' => ['historique:read']]
    //     );

    //     return new JsonResponse(
    //         $responseData,
    //         Response::HTTP_OK,
    //         [],
    //         true
    //     );
    // }






    // #[Route("/{id}", name: "delete", methods: "DELETE")]
    // public function delete(int $id): JsonResponse
    // {
    //     $historique = $this->repository->findOneBy(['id' => $id]);

    //     if ($historique->getUser() !== $this->getUser()) {
    //         return new JsonResponse(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
    //     }

    //     if ($historique) {
    //         $this->manager->remove($historique);
    //         $this->manager->flush();

    //         return new JsonResponse(
    //             ["message" => "Historique supprimé"],
    //             Response::HTTP_OK,
    //         );
    //     }

    //     return new JsonResponse(
    //         null,
    //         Response::HTTP_NOT_FOUND
    //     );
    // }

    #[Route('/cancel/{id}', name: 'cancel', methods: 'PATCH')]
    public function cancel(int $id, Security $security, MailerInterface $mailer): JsonResponse
    {
        $user = $security->getUser();

        /** @var User $user */
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Utilisateur non valide'], Response::HTTP_UNAUTHORIZED);
        }

        $historique = $this->repository->find($id);

        if (!$historique) {
            return new JsonResponse(['message' => 'Historique non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($historique->getUser() !== $user) {
            return new JsonResponse(['message' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $trajet = $historique->getTrajet();

        if (!$trajet) {
            return new JsonResponse(['message' => 'Aucun trajet associé'], Response::HTTP_BAD_REQUEST);
        }

        $userRoles = $user->getRoles();
        $isChauffeur = in_array('ROLE_CHAUFFEUR', $userRoles, true);
        $isPassager = in_array('ROLE_PASSAGER', $userRoles, true);

        if ($isChauffeur && $trajet->getUsers() === $user) {
            // 🚗 Le chauffeur annule : tous les passagers sont remboursés
            foreach ($trajet->getUsers() as $passager) {
                $passager->setCredits($passager->getCredits() + $trajet->getPrix());
                $trajet->removeUser($passager);

                $email = (new Email())
                    ->from('noreply@tonsite.com')
                    ->to($passager->getEmail())
                    ->subject('Trajet annulé')
                    ->text(sprintf(
                        'Le trajet de %s à %s a été annulé par le chauffeur %s.',
                        $trajet->getDepart(),
                        $trajet->getDestination(),
                        $user->getPseudo()
                    ));

                $mailer->send($email);
            }

            $this->manager->remove($trajet);
            $message = 'Trajet annulé avec succès par le chauffeur.';
        } elseif ($isPassager && $trajet->getUsers()->contains($user)) {
            // 🧍 Le passager annule sa participation
            $trajet->removeUser($user);
            $user->setCredits($user->getCredits() + $trajet->getPrix());
            $trajet->setNbPlaces($trajet->getNbPlaces() + 1);
            $message = 'Votre participation au trajet a été annulée.';
        } else {
            return new JsonResponse(['message' => 'Action non autorisée ou vous ne participez pas à ce trajet'], Response::HTTP_FORBIDDEN);
        }

        // Mise à jour de l'historique (on évite la suppression)
        $historique->setStatut('annulé');
        $historique->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        return new JsonResponse(['message' => $message], Response::HTTP_OK);
    }
}
