<?php

// namespace App\Tests\Controller;

// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// class HistoriqueControllerTest extends WebTestCase
// {
//     // public function testPassagerCreateHistoriqueIsSuccessful(): void
//     // {
//     //     $client = self::createClient();
//     //     $client->followRedirects(false);

//     //     // 1. Authentification pour récupérer le token
//     //     $client->request(
//     //         "POST",
//     //         "/api/login",
//     //         [],
//     //         [],
//     //         [
//     //             "CONTENT_TYPE" => "application/json",
//     //         ],
//     //         json_encode([
//     //             "username" => "test@mail.com",
//     //             "password" => "Azert$123",
//     //         ])
//     //     );

//     //     // 2. Récupérer le token depuis la réponse
//     //     $responseData = json_decode($client->getResponse()->getContent(), true);
//     //     $apiToken = $responseData['apiToken'];

//     //     // 3. Créer un historique, avec le token dans l'en-tête
//     //     $client->request(
//     //         'POST',
//     //         '/api/historique',
//     //         [],
//     //         [],
//     //         [
//     //             'CONTENT_TYPE' => 'application/json',
//     //             'HTTP_X_AUTH_TOKEN' => $apiToken,
//     //         ],
//     //         json_encode([
//     //             "trajet" => 2
//     //         ], JSON_THROW_ON_ERROR)
//     //     );

//     //     // 4. Vérifier la réponse
//     //     $statusCode = $client->getResponse()->getStatusCode();
//     //     $this->assertEquals(201, $statusCode);
//     // }

//     // public function testHistoriqueShowIsNotSuccessful(): void
//     // {
//     //     $client = self::createClient();
//     //     $client->followRedirects(false);

//     //     $client->request("Get", "/api/historique/filter");

//     //     // Full authentication is required to access this resource. (401 Unauthorized)
//     //     self::assertResponseStatusCodeSame(401);
//     // }

//     // public function testPassagerShowHistoriqueIsSuccessful(): void
//     // {
//     //     $client = self::createClient();
//     //     $client->followRedirects(false);

//     //     // 1. Authentification pour récupérer le token
//     //     $client->request(
//     //         "POST",
//     //         "/api/login",
//     //         [],
//     //         [],
//     //         [
//     //             "CONTENT_TYPE" => "application/json",
//     //         ],
//     //         json_encode([
//     //             "username" => "test@mail.com",
//     //             "password" => "Azert$123",
//     //         ])
//     //     );

//     //     // 2. Récupérer le token depuis la réponse
//     //     $responseData = json_decode($client->getResponse()->getContent(), true);
//     //     $apiToken = $responseData['apiToken'];

//     //     // 3. Aficher son historique, avec le token dans l'en-tête
//     //     $client->request(
//     //         'GET',
//     //         '/api/historique/filter?trajet=2',
//     //         [],
//     //         [],
//     //         [
//     //             'CONTENT_TYPE' => 'application/json',
//     //             'HTTP_X_AUTH_TOKEN' => $apiToken,
//     //         ]
//     //     );

//     //     // 4. Vérifier la réponse
//     //     $statusCode = $client->getResponse()->getStatusCode();
//     //     $this->assertEquals(200, $statusCode);
//     // }

//     // public function testAdminDeleteHistoriqueIsSuccessful(): void
//     // {
//     //     $client = self::createClient();
//     //     $client->followRedirects(false);

//     //     // 1. Authentification pour récupérer le token
//     //     $client->request(
//     //         "POST",
//     //         "/api/login",
//     //         [],
//     //         [],
//     //         [
//     //             "CONTENT_TYPE" => "application/json",
//     //         ],
//     //         json_encode([
//     //             "username" => "SuperAdminTest@test.fr",
//     //             "password" => "Azerty$123",
//     //         ])
//     //     );

//     //     // 2. Récupérer le token depuis la réponse
//     //     $responseData = json_decode($client->getResponse()->getContent(), true);
//     //     $apiToken = $responseData['apiToken'];

//     //     // 3. Supprimer un historique en tant que admin, avec le token dans l'en-tête
//     //     $client->request(
//     //         'DELETE',
//     //         '/api/historique/2',
//     //         [],
//     //         [],
//     //         [
//     //             'CONTENT_TYPE' => 'application/json',
//     //             'HTTP_X_AUTH_TOKEN' => $apiToken,
//     //         ]
//     //     );

//     //     // 4. Vérifier la réponse
//     //     $statusCode = $client->getResponse()->getStatusCode();
//     //     $this->assertEquals(200, $statusCode);
//     // }

//     public function testChauffeurCancelTrajetIsSuccessful(): void
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

//         // 3. Annulation d'un trajet en tant que chauffeur, avec le token dans l'en-tête
//         $client->request(
//             'POST',
//             '/api/historique/cancel',
//             [],
//             [],
//             [
//                 'CONTENT_TYPE' => 'application/json',
//                 'HTTP_X_AUTH_TOKEN' => $apiToken,
//             ],
//             json_encode([
//                 "trajet" => 2
//             ], JSON_THROW_ON_ERROR)
//         );

//         // 4. Vérifier la réponse
//         $statusCode = $client->getResponse()->getStatusCode();
//         $this->assertEquals(200, $statusCode);
//     }
// }
