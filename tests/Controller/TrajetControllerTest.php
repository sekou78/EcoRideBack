<?php

// namespace App\Tests\Controller;

// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// class TrajetControllerTest extends WebTestCase
// {
    // public function testChauffeurCreateTrajetIsSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     // 1. Authentification pour récupérer le token
    //     $client->request(
    //         "POST",
    //         "/api/login",
    //         [],
    //         [],
    //         [
    //             "CONTENT_TYPE" => "application/json",
    //         ],
    //         json_encode([
    //             "username" => "test1@mail.com",
    //             "password" => "Azert$123",
    //         ])
    //     );

    //     // 2. Récupérer le token depuis la réponse
    //     $responseData = json_decode($client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // 3. Créer un utilisateur en tant que chauffeur, avec le token dans l'en-tête
    //     $client->request(
    //         'POST',
    //         '/api/trajet',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ],
    //         json_encode([
    //             "adresseDepart" => "45 rue de la ville XXXXXX La Ville",
    //             "adresseArrivee" => "Parking de la ville XXXXXX La Ville",
    //             "dateDepart" => "2025-04-14T08:00:00",
    //             "dateArrivee" => "2025-04-14T09:00:00",
    //             "prix" => "12.5",
    //             "estEcologique" => true,
    //             "nombrePlacesDisponible" => 3,
    //             "statut" => "EN_ATTENTE"
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     // 4. Vérifier la réponse
    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(201, $statusCode);
    // }

    // public function testTrajetShowIsNotSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     $client->request("Get", "/api/trajet/2");

    //     // Full authentication is required to access this resource. (401 Unauthorized)
    //     self::assertResponseStatusCodeSame(401);
    // }

    // public function testChauffeurEditTrajetIsSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     // 1. Authentification pour récupérer le token
    //     $client->request(
    //         "POST",
    //         "/api/login",
    //         [],
    //         [],
    //         [
    //             "CONTENT_TYPE" => "application/json",
    //         ],
    //         json_encode([
    //             "username" => "test1@mail.com",
    //             "password" => "Azert$123",
    //         ])
    //     );

    //     // 2. Récupérer le token depuis la réponse
    //     $responseData = json_decode($client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // 3. Modifier un trajet en tant que chauffeur, avec le token dans l'en-tête
    //     $client->request(
    //         'PUT',
    //         '/api/trajet/4',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ],
    //         json_encode([
    //             "adresseDepart" => "45 rue de la ville XXXXXX La Ville updated",
    //             "adresseArrivee" => "Parking de la ville XXXXXX La Ville updated",
    //             "dateDepart" => "2025-04-14T08:00:00",
    //             "dateArrivee" => "2025-04-14T09:00:00",
    //             "prix" => "30",
    //             "estEcologique" => false,
    //             "nombrePlacesDisponible" => 4,
    //             "statut" => "TERMINEE"
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     // 4. Vérifier la réponse
    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(200, $statusCode);
    // }

//     public function testChauffeurDeleteTrajetIsSuccessful(): void
//     {
//         $client = self::createClient();
//         $client->followRedirects(false);

//         // 1. Authentification pour récupérer le token
//         $client->request(
//             "POST",
//             "/api/login",
//             [],
//             [],
//             [
//                 "CONTENT_TYPE" => "application/json",
//             ],
//             json_encode([
//                 "username" => "test1@mail.com",
//                 "password" => "Azert$123",
//             ])
//         );

//         // 2. Récupérer le token depuis la réponse
//         $responseData = json_decode($client->getResponse()->getContent(), true);
//         $apiToken = $responseData['apiToken'];

//         // 3. Supprimer un trajet en tant que chauffeur, avec le token dans l'en-tête
//         $client->request(
//             'DELETE',
//             '/api/trajet/3',
//             [],
//             [],
//             [
//                 'CONTENT_TYPE' => 'application/json',
//                 'HTTP_X_AUTH_TOKEN' => $apiToken,
//             ]
//         );

//         // 4. Vérifier la réponse
//         $statusCode = $client->getResponse()->getStatusCode();
//         $this->assertEquals(200, $statusCode);
//     }
// }
