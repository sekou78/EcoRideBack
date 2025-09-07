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
    public function __construct(
        private DocumentManager $docManager
    ) {}
    #[Route('/', name: 'archives_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Pagination
        $page = max(
            1,
            (int) $request->query
                ->get('page', 1)
        );
        $limit = max(
            1,
            (int) $request->query
                ->get('limit', 5)
        );
        $skip = ($page - 1) * $limit;

        // Récupération des filtres
        $depart = $request->query->get('depart');
        $arrivee = $request->query->get('arrivee');
        $dateDepart = $request->query->get('dateDepart');
        $prixMin = $request->query->get('prixMin');
        $prixMax = $request->query->get('prixMax');

        $filters = [];

        if ($depart) $filters['snapshot.adresseDepart'] = $depart;
        if ($arrivee) $filters['snapshot.adresseArrivee'] = $arrivee;

        $dateDepartFormatted = null;
        if ($dateDepart) {
            $dateDepartFormatted = \DateTime::createFromFormat('d/m/Y', $dateDepart);
            if (!$dateDepartFormatted) {
                return $this->json(
                    ['error' => 'Invalid date format. Use dd/mm/yyyy.'],
                    400
                );
            }
            $filters['snapshot.dateDepart'] = $dateDepartFormatted->format('d-m-Y') . ' 00:00:00';
        }

        if ($prixMin) $filters['snapshot.prix']['$gte'] = (float)$prixMin;
        if ($prixMax) {
            if (!isset($filters['snapshot.prix'])) $filters['snapshot.prix'] = [];
            $filters['snapshot.prix']['$lte'] = (float)$prixMax;
        }

        // Query Builder pour appliquer les filtres
        $qb = $this->docManager->createQueryBuilder(TrajetArchive::class);

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $op => $v) {
                    $qb->field($key)->$op($v);
                }
            } else {
                $qb->field($key)->equals($value);
            }
        }

        // Clone pour compte total avant pagination
        $countQb = clone $qb;
        $totalCount = $countQb->getQuery()->execute()->count();

        // Appliquer tri, skip et limit pour récupérer les archives paginées
        $qb->sort('archivedAt', -1)
            ->skip($skip)
            ->limit($limit);

        $archives = $qb->getQuery()->execute()->toArray();

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
            'totalCount' => $totalCount,
            'archives' => $data,
        ]);
    }

    #[Route('/{id}', name: 'archives_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $archive = $this->docManager
            ->getRepository(
                TrajetArchive::class
            )->find($id);

        if (!$archive) {
            return $this->json(
                ['error' => 'Archive non trouvée'],
                404
            );
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
