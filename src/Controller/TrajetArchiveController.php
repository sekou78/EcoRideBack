<?php

namespace App\Controller;

use App\Document\TrajetArchive;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/archives')]
class TrajetArchiveController extends AbstractController
{
    public function __construct(
        private DocumentManager $docManager
    ) {}
    #[Route('/', name: 'archives_list', methods: ['GET'])]
    #[OA\Get(
        path: "/api/archives/",
        summary: "Lister les trajets archivés",
        description: "Permet de récupérer la liste des trajets archivés avec filtres et pagination via le MongoDB.",
        tags: ["archives"],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Numéro de la page (par défaut 1)",
                schema: new OA\Schema(
                    type: "integer",
                    example: 1
                )
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                description: "Nombre d'éléments par page (par défaut 5)",
                schema: new OA\Schema(
                    type: "integer",
                    example: 10
                )
            ),
            new OA\Parameter(
                name: "depart",
                in: "query",
                required: false,
                description: "Filtrer par adresse de départ",
                schema: new OA\Schema(
                    type: "string",
                    example: "10 de la rue 12345 La VILLE"
                )
            ),
            new OA\Parameter(
                name: "arrivee",
                in: "query",
                required: false,
                description: "Filtrer par adresse d'arrivée",
                schema: new OA\Schema(
                    type: "string",
                    example: "10 Pl. Commercial 98765 La VILLE"
                )
            ),
            new OA\Parameter(
                name: "dateDepart",
                in: "query",
                required: false,
                description: "Filtrer par date de départ",
                schema: new OA\Schema(
                    type: "string",
                    example: "08/09/2025"
                )
            ),
            new OA\Parameter(
                name: "prixMin",
                in: "query",
                required: false,
                description: "Filtrer par prix minimum",
                schema: new OA\Schema(
                    type: "number",
                    format: "float",
                    example: 10
                )
            ),
            new OA\Parameter(
                name: "prixMax",
                in: "query",
                required: false,
                description: "Filtrer par prix maximum",
                schema: new OA\Schema(
                    type: "number",
                    format: "float",
                    example: 20
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des trajets archivés récupérée avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "page",
                            type: "integer",
                            example: 1
                        ),
                        new OA\Property(
                            property: "limit",
                            type: "integer",
                            example: 5
                        ),
                        new OA\Property(
                            property: "count",
                            type: "integer",
                            example: 1
                        ),
                        new OA\Property(
                            property: "totalCount",
                            type: "integer",
                            example: 42
                        ),
                        new OA\Property(
                            property: "archives",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(
                                        property: "id",
                                        type: "string",
                                        example: "68befdff60865c22860f2a73"
                                    ),
                                    new OA\Property(
                                        property: "trajetId",
                                        type: "integer",
                                        example: 46
                                    ),
                                    new OA\Property(
                                        property: "snapshot",
                                        type: "object",
                                        properties: [
                                            new OA\Property(
                                                property: "adresseDepart",
                                                type: "string",
                                                example: "10 de la rue 12345 La VILLE"
                                            ),
                                            new OA\Property(
                                                property: "adresseArrivee",
                                                type: "string",
                                                example: "10 Pl. Commercial 98765 La VILLE"
                                            ),
                                            new OA\Property(
                                                property: "dateDepart",
                                                type: "string",
                                                example: "08-09-2025 00:00:00"
                                            ),
                                            new OA\Property(
                                                property: "dateArrivee",
                                                type: "string",
                                                example: "08-09-2025 00:00:00"
                                            ),
                                            new OA\Property(
                                                property: "prix",
                                                type: "number",
                                                format: "float",
                                                example: 14
                                            ),
                                            new OA\Property(
                                                property: "statut",
                                                type: "string",
                                                example: "TERMINEE"
                                            ),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "archivedAt",
                                        type: "string",
                                        example: "2025-09-09 16:10:05"
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erreur dans les paramètres",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "error",
                            type: "string",
                            example: "Invalid date format"
                        )
                    ]
                )
            )
        ]
    )]
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

        if ($prixMin) $filters['snapshot.prix']['gte'] = (float)$prixMin;
        if ($prixMax) {
            if (!isset($filters['snapshot.prix'])) $filters['snapshot.prix'] = [];
            $filters['snapshot.prix']['lte'] = (float)$prixMax;
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
    #[OA\Get(
        path: "/api/archives/{id}",
        summary: "Afficher une archive de trajet",
        description: "Récupérer les détails d’un trajet archivé via son id.",
        tags: ["archives"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Id de l'archive",
                schema: new OA\Schema(
                    type: "string",
                    example: "68befdff60865c22860f2a73"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Archive trouvée et renvoyée avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "id",
                            type: "string",
                            example: "68befdff60865c22860f2a73"
                        ),
                        new OA\Property(
                            property: "trajetId",
                            type: "integer",
                            example: 46
                        ),
                        new OA\Property(
                            property: "snapshot",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "adresseDepart",
                                    type: "string",
                                    example: "10 de la rue 12345 La VILLE"
                                ),
                                new OA\Property(
                                    property: "adresseArrivee",
                                    type: "string",
                                    example: "10 Pl. Commercial 98765 La VILLE"
                                ),
                                new OA\Property(
                                    property: "dateDepart",
                                    type: "string",
                                    example: "2025-09-08 00:00:00"
                                ),
                                new OA\Property(
                                    property: "dateArrivee",
                                    type: "string",
                                    example: "2025-09-08 00:00:00"
                                ),
                                new OA\Property(
                                    property: "prix",
                                    type: "number",
                                    format: "float",
                                    example: 14
                                ),
                                new OA\Property(
                                    property: "statut",
                                    type: "string",
                                    example: "TERMINEE"
                                ),
                            ]
                        ),
                        new OA\Property(
                            property: "archivedAt",
                            type: "string",
                            example: "2025-09-09 16:10:05"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Archive non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "error",
                            type: "string",
                            example: "Archive non trouvée"
                        )
                    ]
                )
            )
        ]
    )]
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
