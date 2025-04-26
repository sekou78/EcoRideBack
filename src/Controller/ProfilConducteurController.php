<?php

namespace App\Controller;

use App\Entity\ProfilConducteur;
use App\Entity\User;
use App\Repository\ProfilConducteurRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/profilConducteur", name: "app_api_profilConducteur_")]
final class ProfilConducteurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ProfilConducteurRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security
    ) {}

    #[Route(methods: "POST")]
    #[IsGranted("ROLE_USER")]
    public function new(Request $request): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(
                ['error' => 'User not authenticated'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a le rôle "chauffeur" ou "passager_chauffeur"
        if (
            !in_array(
                'ROLE_CHAUFFEUR',
                $user->getRoles()
            )
            &&
            !in_array(
                'ROLE_PASSAGER_CHAUFFEUR',
                $user->getRoles()
            )
        ) {
            return new JsonResponse(
                [
                    'error' => "Vous devez être 'chauffeur' ou 'passager_chauffeur"
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Désérialiser les données du profil conducteur
        $profilConducteur = $this->serializer->deserialize(
            $request->getContent(),
            ProfilConducteur::class,
            'json',
        );

        // Associer le profil conducteur à l'utilisateur connecté
        $profilConducteur->setUser($user);

        $profilConducteur->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($profilConducteur);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $profilConducteur,
            'json',
            ['groups' => ['profilConducteur:read']]
        );

        $location = $this->urlGenerator->generate(
            'app_api_profilConducteur_show',
            ['id' => $profilConducteur->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_CREATED,
            ['Location' => $location],
            true,
        );
    }

    #[Route("/{id}", name: "show", methods: "GET")]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if ($profilConducteur) {
            $responseData = $this->serializer->serialize(
                $profilConducteur,
                'json',
                ['groups' => ['profilConducteur:read']]
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
    #[IsGranted('ROLE_USER')]
    public function edit(int $id, Request $request): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'User not authenticated'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a le rôle "chauffeur" ou "passager_chauffeur"
        if (
            !in_array('ROLE_CHAUFFEUR', $user->getRoles()) &&
            !in_array('ROLE_PASSAGER_CHAUFFEUR', $user->getRoles())
        ) {
            return new JsonResponse(
                ['error' => "Vous devez être 'chauffeur' ou 'passager_chauffeur'."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer le profil conducteur
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if (!$profilConducteur) {
            return new JsonResponse(['error' => 'ProfilConducteur not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le profil appartient bien à l'utilisateur connecté
        if ($profilConducteur->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(
                ['error' => "Vous n'avez pas accès à ce profil pour le modifier."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Désérialiser et mettre à jour l'objet existant
        $this->serializer->deserialize(
            $request->getContent(),
            ProfilConducteur::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $profilConducteur]
        );

        // Mettre à jour la date de modification
        $profilConducteur->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $profilConducteur,
            'json',
            ['groups' => ['profilConducteur:read']]
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route("/{id}", name: "delete", methods: "DELETE")]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'User not authenticated'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur a le rôle "chauffeur" ou "passager_chauffeur"
        if (
            !in_array('ROLE_CHAUFFEUR', $user->getRoles()) &&
            !in_array('ROLE_PASSAGER_CHAUFFEUR', $user->getRoles())
        ) {
            return new JsonResponse(
                ['error' => "Vous devez être 'chauffeur' ou 'passager_chauffeur'."],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer le profil conducteur par ID
        $profilConducteur = $this->repository->findOneBy(['id' => $id]);

        if (!$profilConducteur) {
            return new JsonResponse(
                [
                    'error' => 'ProfilConducteur non trouvé'
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier que l'utilisateur connecté est bien le propriétaire du profil
        if ($profilConducteur->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(
                [
                    'error' => "Vous n'avez pas l'autorisation de supprimer ce profil."
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        // Supprimer le profil conducteur
        $this->manager->remove($profilConducteur);
        $this->manager->flush();

        return new JsonResponse(
            ['message' => 'ProfilConducteur supprimé avec succès.'],
            Response::HTTP_OK
        );
    }
}
