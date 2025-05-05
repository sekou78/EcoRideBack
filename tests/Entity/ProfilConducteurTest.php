<?php

// namespace App\Tests\Entity;

// use App\Entity\ProfilConducteur;
// use App\Entity\User;
// use PHPUnit\Framework\TestCase;

// class ProfilConducteurTest extends TestCase
// {
//     public function testProfilConducteurEntity()
//     {
//         $profil = new ProfilConducteur();
//         $user = new User();

//         $now = new \DateTimeImmutable();
//         $later = new \DateTimeImmutable('+1 day');

//         $profil
//             ->setPlaqueImmatriculation('AB-123-CD')
//             ->setModele('Clio')
//             ->setMarque('Renault')
//             ->setCouleur('Rouge')
//             ->setNombrePlaces(4)
//             ->setAccepteFumeur(true)
//             ->setAccepteAnimaux(false)
//             ->setAutresPreferences('Pas de musique forte')
//             ->setCreatedAt($now)
//             ->setUpdatedAt($later)
//             ->setUser($user);

//         $this->assertSame('AB-123-CD', $profil->getPlaqueImmatriculation());
//         $this->assertSame('Clio', $profil->getModele());
//         $this->assertSame('Renault', $profil->getMarque());
//         $this->assertSame('Rouge', $profil->getCouleur());
//         $this->assertSame(4, $profil->getNombrePlaces());
//         $this->assertTrue($profil->isAccepteFumeur());
//         $this->assertFalse($profil->isAccepteAnimaux());
//         $this->assertSame('Pas de musique forte', $profil->getAutresPreferences());
//         $this->assertSame($now, $profil->getCreatedAt());
//         $this->assertSame($later, $profil->getUpdatedAt());
//         $this->assertSame($user, $profil->getUser());
//     }
// }
