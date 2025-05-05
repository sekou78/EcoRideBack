<?php

// namespace App\Tests\Controller;

// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// class ProfilConducteurControllerTest extends WebTestCase
// {
    // public function testPassager_ChauffeurCreateProfilConducteurIsSuccessful(): void
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
    //             "username" => "test2@mail.com",
    //             "password" => "Azert$123",
    //         ])
    //     );

    //     // 2. Récupérer le token depuis la réponse
    //     $responseData = json_decode($client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // 3. Créer un profilConducteur en tant que passager_chauffeur, avec le token dans l'en-tête
    //     $client->request(
    //         'POST',
    //         '/api/profilConducteur',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ],
    //         json_encode([
    //             "plaqueImmatriculation"  => "AB-123-CD",
    //             "modele"  => "Clio",
    //             "marque"  => "Renault",
    //             "couleur"  => "Rouge",
    //             "nombrePlaces"  => 5,
    //             "accepteFumeur"  => true,
    //             "accepteAnimaux"  => false,
    //             "autresPreferences"  => "Pas de musique forte"
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     // 4. Vérifier la réponse
    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(201, $statusCode);
    // }

    // public function testProfilConducteurShowIsNotSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     $client->request("Get", "/api/profilConducteur/1");

    //     // Full authentication is required to access this resource. (401 Unauthorized)
    //     self::assertResponseStatusCodeSame(401);
    // }

    // public function testPassager_ChauffeurEditProfilConducteurIsSuccessful(): void
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
    //             "username" => "test2@mail.com",
    //             "password" => "Azert$123",
    //         ])
    //     );

    //     // 2. Récupérer le token depuis la réponse
    //     $responseData = json_decode($client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // 3. Modifier un profilConducteur en tant que passager_chauffeur, avec le token dans l'en-tête
    //     $client->request(
    //         'PUT',
    //         '/api/profilConducteur/4',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ],
    //         json_encode([
    //             "plaqueImmatriculation" => "AB-123-GB",
    //             "modele" => "Tesla",
    //             "marque" => "Tesla",
    //             "couleur" => "Blanc",
    //             "nombrePlaces" => 2,
    //             "accepteFumeur" => false,
    //             "accepteAnimaux" => true,
    //             "autresPreferences" => "Ce n'est que des testes"
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     // 4. Vérifier la réponse
    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(200, $statusCode);
    // }

//     public function testPassager_ChauffeurDeleteProfilConducteurIsSuccessful(): void
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
//                 "username" => "test2@mail.com",
//                 "password" => "Azert$123",
//             ])
//         );

//         // 2. Récupérer le token depuis la réponse
//         $responseData = json_decode($client->getResponse()->getContent(), true);
//         $apiToken = $responseData['apiToken'];

//         // 3. Supprimer un profilConducteur en tant que passager_chauffeur, avec le token dans l'en-tête
//         $client->request(
//             'DELETE',
//             '/api/profilConducteur/3',
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
