<?php

namespace App\Controller;

use App\Entity\Historique;
use App\Entity\Trajet;
use App\Repository\HistoriqueRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/historique", name: "app_api_historique_")]
final class HistoriqueController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private HistoriqueRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route(methods: "POST")]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $historique = $this->serializer->deserialize(
            $request->getContent(),
            Historique::class,
            'json',
        );

        // Assigner le trajet à la réservation
        if ($data['trajet']) {
            $trajet = $this->manager
                ->getRepository(Trajet::class)
                ->find($data['trajet']);
            if ($trajet) {
                $historique->setTrajet($trajet);
            } else {
                return new JsonResponse(
                    ['error' => 'trajet non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $historique->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($historique);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $historique,
            'json',
            ['groups' => ['historique:read']]
        );

        $location = $this->urlGenerator->generate(
            'app_api_historique_show',
            ['id' => $historique->getId()],
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
    public function show(int $id): JsonResponse
    {
        $historique = $this->repository->findOneBy(['id' => $id]);

        if ($historique) {
            $responseData = $this->serializer->serialize(
                $historique,
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

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route("/{id}", name: "edit", methods: "PUT")]
    public function edit(int $id, Request $request): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        // Récupérer la réservation existante
        $historique = $this->manager
            ->getRepository(
                Historique::class
            )
            ->findOneBy(
                ['id' => $id]
            );

        if (!$historique) {
            return new JsonResponse(
                ['error' => 'Réservation non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Mettre à jour le statut si présent
        if ($data['statut']) {
            $historique->setStatut($data['statut']);
        }

        // Mettre à jour le trajet si fourni
        if ($data['trajet']) {
            $trajet = $this->manager
                ->getRepository(
                    Trajet::class
                )
                ->find(
                    $data['trajet']
                );
            if (!$trajet) {
                return new JsonResponse(
                    ['error' => 'Trajet non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $historique->setTrajet($trajet);
        }

        $historique->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $historique,
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
}
