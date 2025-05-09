<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Reservation;
use App\Repository\AvisRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route("api/avis", name: "app_api_avis_")]
final class AvisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AvisRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route(methods: "POST")]
    #[IsGranted('ROLE_PASSAGER')]
    public function new(Request $request, Security $security): JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $avis = $this->serializer->deserialize(
            $request->getContent(),
            Avis::class,
            'json',
        );

        // Les avis sont invisible par defaut
        $avis->setIsVisible(false);

        // Assigner l'avis à la réservation
        if ($data['reservation']) {
            $reservation = $this->manager
                ->getRepository(Reservation::class)
                ->find($data['reservation']);
            if ($reservation) {
                $avis->setReservation($reservation);
            } else {
                return new JsonResponse(
                    ['error' => 'reservation non trouvé'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        // Récupère l'utilisateur qui poste l'avis
        $user = $security->getUser();
        $avis->setUser($user);

        $avis->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($avis);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $avis,
            'json',
            ['groups' => ['avis:read']]
        );

        $location = $this->urlGenerator->generate(
            'app_api_avis_show',
            ['id' => $avis->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse(
            $responseData,
            Response::HTTP_CREATED,
            ['Location' => $location],
            true,
        );
    }

    #[Route("/", name: "show", methods: "GET")]
    #[IsGranted('ROLE_EMPLOYE')]
    public function show(): JsonResponse
    {
        $avis = $this->manager->getRepository(Avis::class)->findAll();

        if ($avis) {
            $responseData = $this->serializer->serialize(
                $avis,
                'json',
                ['groups' => ['avis:read']]
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

    #[Route("/avisVisible", name: "avisVisible", methods: "GET")]
    public function avisVisible(): JsonResponse
    {
        $avisVisible = $this->repository->findBy(['isVisible' => true]);

        $data = array_map(function (Avis $avis) {
            $reservation = $avis->getReservation();

            return [
                'note' => $avis->getNote(),
                'commentaire' => $avis->getCommentaire(),
                'date de reservation' => $avis->getCreatedAt()->format("d-m-Y"),
                'reservation' => [
                    'id' => $reservation->getId(),
                    'statut' => $reservation->getStatut(),
                    'date' => $reservation->getCreatedAt()->format('d-m-Y'),
                ],
            ];
        }, $avisVisible);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route(
        '/employee/validate-avis/{avisId}',
        name: 'employee_validate_avis',
        methods: 'PUT'
    )]
    #[IsGranted('ROLE_EMPLOYE')]
    public function validateAvis(
        int $avisId,
        EntityManagerInterface $manager
    ): JsonResponse {
        $avis = $manager
            ->getRepository(Avis::class)
            ->findOneBy(['id' => $avisId]);

        // Vérification si l'utilisateur a le rôle requis
        if (
            !$this->isGranted('ROLE_EMPLOYE')
        ) {
            return new JsonResponse(
                ['message' => 'Accès réfusé'],
                Response::HTTP_FORBIDDEN
            );
        }

        if (!$avis) {
            return new JsonResponse(
                ['error' => 'Avis non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Valider l'avis du visiteur
        $avis->setIsVisible(true);

        $avis->setUpdatedAt(new DateTimeImmutable());

        $manager->flush();

        return new JsonResponse(
            ['message' => 'Avis validé avec succès'],
            Response::HTTP_OK
        );
    }

    #[Route("/{id}", name: "delete", methods: "DELETE")]
    #[IsGranted('ROLE_EMPLOYE')]
    public function delete(int $id): JsonResponse
    {
        $avis = $this->repository->findOneBy(['id' => $id]);

        if ($avis) {
            $this->manager->remove($avis);
            $this->manager->flush();

            return new JsonResponse(
                ["message" => "Avis supprimé"],
                Response::HTTP_OK,
            );
        }

        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
