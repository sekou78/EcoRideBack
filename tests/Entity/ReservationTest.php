<?php

// namespace App\Tests\Entity;

// use App\Entity\Avis;
// use App\Entity\Reservation;
// use App\Entity\Trajet;
// use App\Entity\User;
// use PHPUnit\Framework\TestCase;

// class ReservationTest extends TestCase
// {
//     public function testStatut()
//     {
//         $reservation = new Reservation();
//         $reservation->setStatut('CONFIRMEE');
//         $this->assertEquals('CONFIRMEE', $reservation->getStatut());
//     }

//     public function testUser()
//     {
//         $reservation = new Reservation();
//         $user = new User();
//         $reservation->setUser($user);
//         $this->assertSame($user, $reservation->getUser());
//     }

//     public function testTrajet()
//     {
//         $reservation = new Reservation();
//         $trajet = new Trajet();
//         $reservation->setTrajet($trajet);
//         $this->assertSame($trajet, $reservation->getTrajet());
//     }

//     public function testAvisCollection()
//     {
//         $reservation = new Reservation();
//         $avis = new Avis();

//         $this->assertCount(0, $reservation->getAvis());

//         $reservation->addAvis($avis);
//         $this->assertCount(1, $reservation->getAvis());
//         $this->assertTrue($reservation->getAvis()->contains($avis));
//         $this->assertSame($reservation, $avis->getReservation());

//         $reservation->removeAvi($avis);
//         $this->assertCount(0, $reservation->getAvis());
//         $this->assertNull($avis->getReservation());
//     }
// }
