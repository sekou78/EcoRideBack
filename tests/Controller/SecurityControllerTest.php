<?php

// namespace App\Tests\Controller;

// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// class SecurityControllerTest extends WebTestCase
// {
    // public function testApiDocUrlIsSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);
    //     $client->request('GET', '/api/doc');
    //     self::assertResponseIsSuccessful();
    // }

    // public function testRegistrationIsSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     $client->request(
    //         'POST',
    //         '/api/registration',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //         ],
    //         json_encode([
    //             "email" => "test2@mail.com",
    //             "password" => "Azert$123",
    //             // 'roles' => ['ROLE_PASSAGER'],
    //             // 'roles' => ['ROLE_CHAUFFEUR'],
    //             'roles' => ['ROLE_PASSAGER_CHAUFFEUR'],
    //             "pseudo" => "test",
    //             "nom" => "test",
    //             "prenom" => "test"
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(201, $statusCode);
    // }

    // public function testRegistrationIsNotSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     $client->request(
    //         'POST',
    //         '/api/registration',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //         ],
    //         json_encode([
    //             "email" => "test@mail.com",
    //             "password" => "Azert$123",
    //             "pseudo" => "test",
    //             "nom" => "test",
    //             "prenom" => "test"
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(400, $statusCode);
    // }

    // public function testLoginIsSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

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

    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(200, $statusCode);
    // }

    // public function testLoginIsNotSuccessful(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

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
    //             "password" => "Azert$12",
    //         ])
    //     );

    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(401, $statusCode);
    // }

    // public function testApiAccountMeUrlIsSecure(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     $client->request("GET", "/api/account/me");

    //     self::assertResponseStatusCodeSame(401);
    // }

    // public function testApiAccountEditUrlIsSecure(): void
    // {
    //     $client = self::createClient();
    //     $client->followRedirects(false);

    //     $client->request("PUT", "/api/account/edit");

    //     self::assertResponseStatusCodeSame(401);
    // }

    // public function testAdminCreateEmployeIsSuccessful(): void
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
    //             "username" => "SuperAdminTest@test.fr",
    //             "password" => "Azerty$123",
    //         ])
    //     );

    //     // 2. Récupérer le token depuis la réponse
    //     $responseData = json_decode($client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // 3. Créer un utilisateur en tant qu'admin, avec le token dans l'en-tête
    //     $client->request(
    //         'POST',
    //         '/api/admin/create-user',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ],
    //         json_encode([
    //             "email" => "mail223@mail.fr",
    //             "password" => "Azerty$123",
    //             "nom" => "Baba",
    //             "prenom" => "Kala",
    //             "telephone" => "+33 6 00 00 00 00",
    //             "adresse" => "Rue de le ville XXXXX La ville",
    //             "dateNaissance" => "10/10/1910",
    //             "pseudo" => "SHIN223",
    //             "roles" => ["ROLE_EMPLOYE"]
    //         ], JSON_THROW_ON_ERROR)
    //     );

    //     // 4. Vérifier la réponse
    //     $statusCode = $client->getResponse()->getStatusCode();
    //     $this->assertEquals(201, $statusCode);
    // }

    //     public function testAdminSuspendCompteIsSuccessful(): void
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
    //                 "username" => "SuperAdminTest@test.fr",
    //                 "password" => "Azerty$123",
    //             ])
    //         );

    //         // 2. Récupérer le token depuis la réponse
    //         $responseData = json_decode($client->getResponse()->getContent(), true);
    //         $apiToken = $responseData['apiToken'];

    //         // 3. Créer un utilisateur en tant qu'admin, avec le token dans l'en-tête
    //         $client->request(
    //             'PUT',
    //             '/api/admin/droitSuspensionComptes/3',
    //             [],
    //             [],
    //             [
    //                 'CONTENT_TYPE' => 'application/json',
    //                 'HTTP_X_AUTH_TOKEN' => $apiToken,
    //             ],
    //             json_encode([
    //                 "compteSuspendu" => true
    //             ], JSON_THROW_ON_ERROR)
    //         );

    //         // 4. Vérifier la réponse
    //         $statusCode = $client->getResponse()->getStatusCode();
    //         $this->assertEquals(200, $statusCode);
    //     }
// }
