<?php

// namespace App\Tests\Controller;

// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// class ReservationControllerTest extends WebTestCase
// {
    // public function testPassagerCreateReservationIsSuccessful(): void
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
    //             "username" => "test@mail.com",
    //             "password" => "Azert$123",
    //         ])
    //     );

    //     // 2. Récupérer le token depuis la réponse
    //     $responseData = json_decode($client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // 3. Créer une reservation en tant que passager, avec le token dans l'en-tête
    //     $client->request(
    //         'POST',
    //         '/api/reservation',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ],
    //         json_encode([
    //             "statut" => "CONFIRMEE",
    //             "trajet" => 4
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     // 4. Vérifier la réponse
    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(201, $statusCode);
    // }

    // public function testReservationShowIsNotSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     $client->request("Get", "/api/reservation/2");

    //     // Full authentication is required to access this resource. (401 Unauthorized)
    //     self::assertResponseStatusCodeSame(401);
    // }

    // public function testPassagerEditReservationIsSuccessful(): void
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
    //             "username" => "test@mail.com",
    //             "password" => "Azert$123",
    //         ])
    //     );

    //     // 2. Récupérer le token depuis la réponse
    //     $responseData = json_decode($client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // 3. Modifier une reservation en tant que passager, avec le token dans l'en-tête
    //     $client->request(
    //         'PUT',
    //         '/api/reservation/4',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ],
    //         json_encode([
    //             "statut" => "EN_ATTENTE",
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     // 4. Vérifier la réponse
    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(200, $statusCode);
    // }

//     public function testPassagerDeleteReservationIsSuccessful(): void
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
//                 "username" => "test@mail.com",
//                 "password" => "Azert$123",
//             ])
//         );

//         // 2. Récupérer le token depuis la réponse
//         $responseData = json_decode($client->getResponse()->getContent(), true);
//         $apiToken = $responseData['apiToken'];

//         // 3. Supprimer une reservation en tant que passager, avec le token dans l'en-tête
//         $client->request(
//             'DELETE',
//             '/api/reservation/3',
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
