<?php

// namespace App\Tests\Controller;

// use App\Entity\Image;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
// use Symfony\Component\HttpFoundation\File\UploadedFile;

// class ImageControllerTest extends WebTestCase
// {
//     private EntityManagerInterface $manager;
//     private $client;

//     // Initialisation avant chaque test
//     protected function setUp(): void
//     {
//         // Créer le client de test
//         $this->client = self::createClient();

//         // Récupérer l'EntityManager via le container du client
//         $this->manager = $this->client
//             ->getContainer()
//             ->get(EntityManagerInterface::class);
//     }


//     private function isFileUploaded(string $directory): bool
//     {
//         $files = glob($directory . '*');
//         return !empty($files);
//     }

    // public function testPassager_ChauffeurCreateImageIsSuccessful(): void
    // {
    //     $this->client->followRedirects();

    //     // 1. Authentification pour récupérer le token
    //     $this->client->request(
    //         "POST",
    //         "/api/login",
    //         [],
    //         [],
    //         [
    //             "CONTENT_TYPE" => "application/json",
    //         ],
    //         json_encode([
    //             "username" => "pauline.rocher@dupont.com",
    //             "password" => "Azerty$123",
    //         ])
    //     );

    //     // 2. Récupérer le token depuis la réponse
    //     $responseData = json_decode($this->client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // Vérifier que l'image originale existe avant de l'envoyer
    //     $originalImagePath = __DIR__ . '/../../tests/controller/me.jpg';
    //     if (!file_exists($originalImagePath)) {
    //         throw new \Exception("Le fichier de test 'me.jpg' n'existe pas à l'emplacement spécifié.");
    //     }

    //     // Créer un objet UploadedFile avec le bon type MIME
    //     $imageFile = new UploadedFile(
    //         $originalImagePath,         // Fichier à envoyer
    //         'me.jpg',       // Nom du fichier
    //         'image/jpeg',           // Type MIME forcé
    //         null,
    //         true                    // Mode test pour éviter la vérification de l'upload
    //     );

    //     // 3. Créer un image en tant que passager_chauffeur, avec le token dans l'en-tête
    //     $this->client->request(
    //         'POST',
    //         '/api/image',
    //         [],
    //         ['image' => $imageFile],
    //         [
    //             'CONTENT_TYPE' => 'multipart/form-data',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ]
    //     );

    //     // 4. Vérifier la réponse
    //     $statusCode = $this->client->getResponse()->getStatusCode();
    //     $this->assertEquals(201, $statusCode, "L'upload d'image a échoué.");

    //     // Vérifier que le répertoire des images existe
    //     $uploadedFilePath = __DIR__ . '/../../public/uploads/images/';
    //     $this->assertDirectoryExists($uploadedFilePath, 'Le répertoire de stockage des images n\'existe pas.');

    //     // Vérifier que l’image est bien sauvegardée dans le dossier upload
    //     $this->assertTrue(
    //         $this->isFileUploaded($uploadedFilePath),
    //         "L'image n'a pas été stockée après l'upload."
    //     );
    // }

    // public function testImageShowIsNotSuccessful(): void
    // {
    //     $this->client->followRedirects(false);

    //     $this->client->request("Get", "/api/image/1");

    //     // Full authentication is required to access this resource. (401 Unauthorized)
    //     self::assertResponseStatusCodeSame(401);
    // }

    // public function testPassager_ChauffeurEditImageIsSuccessful(): void
    // {
    //     $this->client->followRedirects(false);

    //     // Authentification pour récupérer le token
    //     $this->client->request(
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

    //     // Récupérer le token depuis la réponse
    //     $responseData = json_decode($this->client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // Récupérer une image existante
    //     $image = $this->manager
    //         ->getRepository(Image::class)
    //         ->find(1); // ID de l'image existante

    //     // Vérifier que l'image existe
    //     $this->assertNotNull(
    //         $image,
    //         "L'image avec l'ID n'existe pas."
    //     );

    //     // Créer une nouvelle image à uploader pour l'édition
    //     $updatedImagePath = __DIR__ . '/../../tests/controller/code.jpg';
    //     $this->assertFileExists(
    //         $updatedImagePath,
    //         "L'image de mise à jour est introuvable."
    //     );

    //     $updatedImageFile = new UploadedFile(
    //         $updatedImagePath,          // Fichier mis à jour à envoyer
    //         'code.jpg',                 // Nom du fichier mis à jour
    //         'image/jpeg',               // Type MIME forcé
    //         null,                       // Aucun paramètre de taille
    //         true                        // Mode test pour éviter la vérification de l'upload
    //     );

    //     // Tester la route pour éditer l'image
    //     $this->client->request(
    //         'POST',
    //         '/api/image/' . $image->getId(), // L'URL pour éditer l'image
    //         [],
    //         ['image' => $updatedImageFile],
    //         [
    //             'CONTENT_TYPE' => 'multipart/form-data',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ]
    //     );

    //     // Vérifier que la réponse est un succès pour la mise à jour
    //     $statusCode = $this->client->getResponse()->getStatusCode();
    //     $this->assertEquals(
    //         200,
    //         $statusCode,
    //         "La mise à jour de l'image a échoué."
    //     );

    //     // Vérifier que l'image existe en base de données après modification
    //     $updatedImage = $this->manager
    //         ->getRepository(Image::class)
    //         ->find($image->getId());
    //     $this->assertNotNull(
    //         $updatedImage,
    //         "L'image mise à jour n'a pas été trouvée en base de données."
    //     );
    //     $this->assertStringContainsString(
    //         'code.jpg',
    //         $updatedImage->getFilePath(),
    //         "Le fichier d'image n'a pas été mis à jour."
    //     );

    //     // Vérifier que le fichier physique a bien été modifié
    //     $imagePath = __DIR__ . '/../../public' . $updatedImage->getFilePath();
    //     $this->assertFileExists(
    //         $imagePath,
    //         "L'image mise à jour n'existe pas sur le disque."
    //     );
    // }

    // public function testPassager_ChauffeurDeleteImageIsSuccessful(): void
    // {
    //     $this->client->followRedirects(false);

    //     // Authentification pour récupérer le token
    //     $this->client->request(
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

    //     // Récupérer le token depuis la réponse
    //     $responseData = json_decode($this->client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // ID de l'image à supprimer
    //     $imageId = 1; // Exemple d'ID de l'image à supprimer

    //     // Tester la route DELETE pour supprimer l'image
    //     $this->client->request(
    //         'DELETE',
    //         '/api/image/' . $imageId, // L'URL pour supprimer l'image
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ]
    //     );

    //     // Vérifier que la réponse est un succès pour la suppression
    //     $statusCode = $this->client->getResponse()->getStatusCode();
    //     $this->assertEquals(
    //         200,
    //         $statusCode,
    //         "La suppression de l'image a échoué."
    //     );

    //     // Vérifier que l'image n'existe plus en base de données
    //     $deletedImage = $this->manager
    //         ->getRepository(Image::class)
    //         ->find($imageId);

    //     // L'image ne doit pas exister après la suppression
    //     $this->assertNull(
    //         $deletedImage,
    //         "L'image avec l'ID " . $imageId . " n'a pas été supprimée en base de données."
    //     );
    // }

    // public function testPassager_ChauffeurViewYourImageIsNotSuccessful(): void
    // {
    //     $this->client->followRedirects(false);

    //     // Authentification pour récupérer le token
    //     $this->client->request(
    //         "POST",
    //         "/api/login",
    //         [],
    //         [],
    //         [
    //             "CONTENT_TYPE" => "application/json",
    //         ],
    //         json_encode([
    //             "username" => "pauline.rocher@dupont.com",
    //             "password" => "Azerty$123",
    //         ])
    //     );

    //     // Récupérer le token depuis la réponse
    //     $responseData = json_decode($this->client->getResponse()->getContent(), true);
    //     $apiToken = $responseData['apiToken'];

    //     // Afficher l'image lier a l'utilisateur
    //     $this->client->request(
    //         'Get',
    //         '/api/image/users/74/image',
    //         [],
    //         [],
    //         [
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_X_AUTH_TOKEN' => $apiToken,
    //         ]
    //     );

    //     // Vérifier que la réponse est un succès pour la suppression
    //     $statusCode = $this->client->getResponse()->getStatusCode();
    //     $this->assertEquals(
    //         404,
    //         $statusCode
    //     );
    // }
// }
