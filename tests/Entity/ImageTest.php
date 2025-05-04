<?php

// namespace App\Tests\Entity;

// use App\Entity\Image;
// use App\Entity\User;
// use PHPUnit\Framework\TestCase;

// class ImageTest extends TestCase
// {
//     public function testGettersAndSetters(): void
//     {
//         $image = new Image();

//         $createdAt = new \DateTimeImmutable('2024-01-01');
//         $updatedAt = new \DateTimeImmutable('2024-02-01');
//         $filePath = '/images/photo.jpg';
//         $user = new User(); // Si User a besoin d’arguments, adapte en conséquence
//         $identite = fopen('php://memory', 'r+'); // Simule un BLOB

//         fwrite($identite, 'fake image data');
//         rewind($identite);

//         $image->setCreatedAt($createdAt)
//             ->setUpdatedAt($updatedAt)
//             ->setFilePath($filePath)
//             ->setUser($user)
//             ->setIdentite($identite);

//         $this->assertSame($createdAt, $image->getCreatedAt());
//         $this->assertSame($updatedAt, $image->getUpdatedAt());
//         $this->assertSame($filePath, $image->getFilePath());
//         $this->assertSame($user, $image->getUser());

//         // Vérifie si le contenu du BLOB correspond
//         $identiteFromEntity = $image->getIdentite();
//         $this->assertIsResource($identiteFromEntity);
//         rewind($identiteFromEntity);
//         $this->assertEquals('fake image data', stream_get_contents($identiteFromEntity));
//     }
// }
