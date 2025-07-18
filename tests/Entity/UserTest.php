<?php

// namespace App\Tests\Entity;

// use App\Entity\User;
// use PHPUnit\Framework\TestCase;

// class UserTest extends TestCase
// {
//     public function testUserEntityGettersAndSetters(): void
//     {
//         $user = new User();

//         $email = 'test@example.com';
//         $password = 'hashedpassword';
//         $pseudo = 'JohnDoe';
//         $nom = 'Doe';
//         $prenom = 'John';
//         $telephone = '0600000000';
//         $adresse = '123 rue Exemple';
//         $dateNaissance = '1990-01-01';
//         $credits = 100;
//         $roles = ['ROLE_PASSAGER'];

//         $user->setEmail($email);
//         $user->setPassword($password);
//         $user->setPseudo($pseudo);
//         $user->setNom($nom);
//         $user->setPrenom($prenom);
//         $user->setTelephone($telephone);
//         $user->setAdresse($adresse);
//         $user->setDateNaissance($dateNaissance);
//         $user->setCredits($credits);
//         $user->setRoles($roles);
//         $user->setCompteSuspendu(true);

//         $this->assertEquals($email, $user->getEmail());
//         $this->assertEquals($email, $user->getUserIdentifier());
//         $this->assertEquals($password, $user->getPassword());
//         $this->assertEquals($pseudo, $user->getPseudo());
//         $this->assertEquals($nom, $user->getNom());
//         $this->assertEquals($prenom, $user->getPrenom());
//         $this->assertEquals($telephone, $user->getTelephone());
//         $this->assertEquals($adresse, $user->getAdresse());
//         $this->assertEquals($dateNaissance, $user->getDateNaissance());
//         $this->assertEquals($credits, $user->getCredits());
//         $this->assertContains('ROLE_PASSAGER', $user->getRoles());
//         $this->assertContains('ROLE_USER', $user->getRoles());
//         $this->assertTrue($user->hasRole('ROLE_PASSAGER'));
//         $this->assertTrue($user->isPassager());
//         $this->assertFalse($user->isChauffeur());
//         $this->assertFalse($user->isPassagerChauffeur());
//         $this->assertTrue($user->isCompteSuspendu());
//     }

//     public function testAddCredits(): void
//     {
//         $user = new User();
//         $user->setCredits(50);
//         $user->addCredits(25);
//         $this->assertEquals(75, $user->getCredits());
//     }

//     public function testApiTokenIsGenerated(): void
//     {
//         $user1 = new User();
//         $user2 = new User();
//         $this->assertNotNull($user1->getApiToken());
//         $this->assertNotEquals($user1->getApiToken(), $user2->getApiToken());
//     }
// }
