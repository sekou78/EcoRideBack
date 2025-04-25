<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\TrajetRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/trajet", name: "app_api_trajet_")]
final class TrajetController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private TrajetRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security
    ) {}

    #[Route(methods: "POST")]
    #[IsGranted("ROLE_CHAUFFEUR", "ROLE_PASSAGER_CHAUFFEUR")]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $trajet = $this->serializer->deserialize(
            $request->getContent(),
            Trajet::class,
            'json'
        );

        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        // Vérifier si l'utilisateur possède l'un des rôles requis
        if (!$user || !in_array(
            'ROLE_CHAUFFEUR',
            $user->getRoles()
        ) && !in_array(
            'ROLE_PASSAGER_CHAUFFEUR',
            $user->getRoles()
        )) {
            return new JsonResponse(
                [
                    'error' => "L'utilisateur doit être un chauffeur ou un passager_chauffeur."
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer les utilisateurs passagers
        if ($data['user'] && is_array($data['user'])) {
            foreach ($data['user'] as $userId) {
                $userEntity = $this->manager
                    ->getRepository(User::class)
                    ->find($userId);
                if ($userEntity) {
                    $trajet->addUser($userEntity);
                }
            }
        }

        // Vérifier que le nombre de passagers ne dépasse pas le nombre de places disponibles
        $passagers = array_filter(
            $trajet->getUsers()->toArray(),
            function ($user) {
                return in_array(
                    'ROLE_PASSAGER',
                    $user->getRoles()
                );
            }
        );

        if (count($passagers) > $trajet->getNombrePlacesDisponible()) {
            return new JsonResponse(
                [
                    'error' => "Le nombre de passagers ne peut pas dépasser le nombre de places disponibles."
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        //Il doit y avoir au moins un chauffeur et un passager
        if (count($passagers) === 0) {
            return new JsonResponse(
                ['error' => "Il doit y avoir au moins un passager."],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Assigner l'utilisateur authentifié comme chauffeur du trajet
        $trajet->setChauffeur($user);

        $trajet->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($trajet);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $trajet,
            'json',
            ['groups' => 'trajet:read']
        );

        $location = $this->urlGenerator->generate(
            'app_api_trajet_show',
            ['id' => $trajet->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_CREATED,
            ['Location' => $location],
            true
        );
    }


    #[Route("/{id}", name: "show", methods: "GET")]
    public function show(int $id): JsonResponse
    {
        $trajet = $this->repository->findOneBy(['id' => $id]);

        if ($trajet) {
            $responseData = $this->serializer->serialize(
                $trajet,
                'json',
                ['groups' => 'trajet:read']
            );

            return new JsonResponse(
                $responseData,
                Response::HTTP_OK,
                [],
                true
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route("/{id}", name: "edit", methods: "PUT")]
    #[IsGranted("ROLE_CHAUFFEUR", "ROLE_PASSAGER_CHAUFFEUR")]
    public function edit(int $id, Request $request): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $trajet = $this->manager
            ->getRepository(Trajet::class)
            ->findOneBy(['id' => $id]);

        if (!$trajet) {
            return new JsonResponse(
                ['error' => 'Trajet non trouvé.'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        // Vérifier si l'utilisateur est un chauffeur ou un passager_chauffeur
        if (!$user || !in_array(
            'ROLE_CHAUFFEUR',
            $user->getRoles()
        ) && !in_array(
            'ROLE_PASSAGER_CHAUFFEUR',
            $user->getRoles()
        )) {
            return new JsonResponse(
                [
                    'error' => "L'utilisateur doit être un chauffeur ou un passager_chauffeur."
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Si des nouvelles données sont envoyées, on les utilise pour mettre à jour le trajet
        if ($data['adresseDepart']) {
            $trajet->setAdresseDepart($data['adresseDepart']);
        }

        if ($data['adresseArrivee']) {
            $trajet->setAdresseArrivee($data['adresseArrivee']);
        }

        if ($data['dateDepart']) {
            $trajet->setDateDepart(new \DateTimeImmutable($data['dateDepart']));
        }

        if ($data['dateArrivee']) {
            $trajet->setDateArrivee(new \DateTimeImmutable($data['dateArrivee']));
        }

        if ($data['prix']) {
            $trajet->setPrix($data['prix']);
        }

        if ($data['estEcologique']) {
            $trajet->setEstEcologique($data['estEcologique']);
        }

        if ($data['nombrePlacesDisponible']) {
            $trajet->setNombrePlacesDisponible($data['nombrePlacesDisponible']);
        }

        if ($data['statut']) {
            $trajet->setStatut($data['statut']);
        }

        // Réinitialiser les utilisateurs passagers existants avant d'ajouter les nouveaux
        if ($data['user'] && is_array($data['user'])) {
            foreach ($trajet->getUsers() as $existingUser) {
                $trajet->removeUser($existingUser);
            }

            // Ajouter les nouveaux passagers
            foreach ($data['user'] as $userId) {
                $userEntity = $this->manager
                    ->getRepository(User::class)
                    ->find($userId);
                if ($userEntity) {
                    $trajet->addUser($userEntity);
                }
            }
        }

        // Vérifier que le nombre de passagers ne dépasse pas le nombre de places disponibles
        $passagers = array_filter(
            $trajet->getUsers()->toArray(),
            function ($user) {
                return in_array(
                    'ROLE_PASSAGER',
                    $user->getRoles()
                );
            }
        );

        $totalPassagers = count($passagers);

        if ($totalPassagers > $trajet->getNombrePlacesDisponible()) {
            return new JsonResponse(
                [
                    'error' => "Le nombre de passagers ne peut pas dépasser le nombre de places disponibles."
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier qu'il y a au moins un passager
        if ($totalPassagers === 0) {
            return new JsonResponse(
                [
                    'error' => "Il doit y avoir au moins un passager."
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $trajet->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $trajet,
            'json',
            ['groups' => 'trajet:read']
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route("/{id}", name: "delete", methods: "DELETE")]
    #[IsGranted("ROLE_ADMIN")]
    public function delete(int $id): JsonResponse
    {
        $trajet = $this->repository->findOneBy(['id' => $id]);

        if ($trajet) {
            $this->manager->remove($trajet);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Trajet supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
