<?php

// namespace App\Tests\Entity;

// use App\Entity\Historique;
// use App\Entity\Trajet;
// use App\Entity\User;
// use PHPUnit\Framework\TestCase;

// class HistoriqueTest extends TestCase
// {
//     public function testHistoriqueEntity()
//     {
//         $historique = new Historique();

//         $statut = 'En attente';
//         $role = 'ROLE_PASSAGER';
//         $createdAt = new \DateTimeImmutable('2023-01-01 12:00:00');
//         $updatedAt = new \DateTimeImmutable('2023-01-02 13:00:00');
//         $trajet = new Trajet();
//         $user = new User();

//         $historique->setStatut($statut);
//         $historique->setRole($role);
//         $historique->setCreatedAt($createdAt);
//         $historique->setUpdatedAt($updatedAt);
//         $historique->setTrajet($trajet);
//         $historique->setUser($user);

//         $this->assertEquals($statut, $historique->getStatut());
//         $this->assertEquals($role, $historique->getRole());
//         $this->assertEquals($createdAt, $historique->getCreatedAt());
//         $this->assertEquals($updatedAt, $historique->getUpdatedAt());
//         $this->assertSame($trajet, $historique->getTrajet());
//         $this->assertSame($user, $historique->getUser());
//     }
// }
