<?php

namespace App\Controller;

use App\Document\TrajetArchive;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/archives')]
class TrajetArchiveController extends AbstractController
{
    #[Route('/', name: 'archives_list', methods: ['GET'])]
    public function list(DocumentManager $dm, Request $request): JsonResponse
    {
        // Pagination : page et limit via query params
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 5));
        $skip = ($page - 1) * $limit;

        // Paramètres de filtrage
        $depart = $request->query->get('depart');
        $arrivee = $request->query->get('arrivee');
        $dateDepart = $request->query->get('dateDepart');
        $prixMin = $request->query->get('prixMin');
        $prixMax = $request->query->get('prixMax');

        $filters = [];

        // Filtrage par adresseDepart
        if ($depart) {
            $filters['snapshot.adresseDepart'] = $depart;
        }

        // Filtrage par adresseArrivee
        if ($arrivee) {
            $filters['snapshot.adresseArrivee'] = $arrivee;
        }

        // Filtrage par dateDepart
        if ($dateDepart) {
            // Convertir la date au format `d/m/Y` reçu dans la requête
            $dateDepartFormatted = \DateTime::createFromFormat('d/m/Y', $dateDepart);

            if ($dateDepartFormatted) {
                // Convertir en format `d-m-Y H:i:s` pour correspondre à ce qui est stocké dans MongoDB
                $filters['snapshot.dateDepart'] = $dateDepartFormatted->format('d-m-Y') . ' 00:00:00';
            } else {
                // Si la date est mal formatée
                return $this->json(['error' => 'Invalid date format. Use dd/mm/yyyy.'], 400);
            }
        }

        // Filtrage par prixMin
        if ($prixMin) {
            $filters['snapshot.prix']['$gte'] = (float)$prixMin;
        }

        // Filtrage par prixMax
        if ($prixMax) {
            if (!isset($filters['snapshot.prix'])) {
                $filters['snapshot.prix'] = [];
            }
            $filters['snapshot.prix']['$lte'] = (float)$prixMax;
        }

        // Requête avec les filtres générés
        $archives = $dm->getRepository(TrajetArchive::class)
            ->findBy($filters, ['archivedAt' => -1], $limit, $skip);

        $data = array_map(function (TrajetArchive $archive) {
            return [
                'id' => $archive->getId(),
                'trajetId' => $archive->getTrajetId(),
                'snapshot' => $archive->getSnapshot(),
                'archivedAt' => $archive->getArchivedAt()->format('d-m-Y H:i:s'),
            ];
        }, $archives);

        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'count' => count($data),
            'archives' => $data,
        ]);
    }

    #[Route('/{id}', name: 'archives_show', methods: ['GET'])]
    public function show(DocumentManager $dm, string $id): JsonResponse
    {
        $archive = $dm->getRepository(TrajetArchive::class)->find($id);

        if (!$archive) {
            return $this->json(['error' => 'Archive non trouvée'], 404);
        }

        $data = [
            'id' => $archive->getId(),
            'trajetId' => $archive->getTrajetId(),
            'snapshot' => $archive->getSnapshot(),
            'archivedAt' => $archive->getArchivedAt()->format('Y-m-d H:i:s'),
        ];

        return $this->json($data);
    }
}
