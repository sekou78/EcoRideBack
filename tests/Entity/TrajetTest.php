<?php

// namespace App\Tests\Entity;

// use App\Entity\Trajet;
// use App\Entity\User;
// use PHPUnit\Framework\TestCase;

// class TrajetTest extends TestCase
// {
//     public function testSettersAndGetters(): void
//     {
//         $trajet = new Trajet();
//         $dateDepart = new \DateTime('2025-05-01 10:00');
//         $dateArrivee = new \DateTime('2025-05-01 12:00');
//         $createdAt = new \DateTimeImmutable();
//         $updatedAt = new \DateTimeImmutable();

//         $trajet->setAdresseDepart('Paris')
//             ->setAdresseArrivee('Lyon')
//             ->setDateDepart($dateDepart)
//             ->setDateArrivee($dateArrivee)
//             ->setPrix('49.99')
//             ->setEstEcologique(true)
//             ->setNombrePlacesDisponible(3)
//             ->setStatut('EN_ATTENTE')
//             ->setCreatedAt($createdAt)
//             ->setUpdatedAt($updatedAt);

//         $this->assertEquals('Paris', $trajet->getAdresseDepart());
//         $this->assertEquals('Lyon', $trajet->getAdresseArrivee());
//         $this->assertSame($dateDepart, $trajet->getDateDepart());
//         $this->assertSame($dateArrivee, $trajet->getDateArrivee());
//         $this->assertEquals('49.99', $trajet->getPrix());
//         $this->assertTrue($trajet->isEstEcologique());
//         $this->assertEquals(3, $trajet->getNombrePlacesDisponible());
//         $this->assertEquals('EN_ATTENTE', $trajet->getStatut());
//         $this->assertSame($createdAt, $trajet->getCreatedAt());
//         $this->assertSame($updatedAt, $trajet->getUpdatedAt());
//     }

//     public function testUserRelationsAndCounts(): void
//     {
//         $trajet = new Trajet();

//         $chauffeur = new User();
//         $chauffeur->setRoles(['ROLE_CHAUFFEUR']);
//         $trajet->setChauffeur($chauffeur);

//         $passager1 = new User();
//         $passager2 = new User();

//         $passager1->setRoles(['ROLE_USER']);
//         $passager2->setRoles(['ROLE_CHAUFFEUR']); // chauffeur qui est aussi passager

//         $trajet->addUser($chauffeur); // chauffeur est aussi dans la liste
//         $trajet->addUser($passager1);
//         $trajet->addUser($passager2);

//         $this->assertCount(3, $trajet->getUsers());
//         $this->assertEquals(2, $trajet->getNombrePassagers()); // 3 users - 1 chauffeur
//         $this->assertEquals(1, $trajet->getNombreChauffeurs());
//         $this->assertEquals(1, $trajet->getNombrePassagersChauffeurs()); // Reponse fausse
//     }
// }
