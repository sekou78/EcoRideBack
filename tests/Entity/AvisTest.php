<?php

// namespace App\Tests\Entity;

// use App\Entity\Avis;
// use App\Entity\Reservation;
// use App\Entity\User;
// use PHPUnit\Framework\TestCase;

// class AvisTest extends TestCase
// {
//     public function testAvisEntity()
//     {
//         $avis = new Avis();
//         $user = new User();
//         $reservation = new Reservation();
//         $now = new \DateTimeImmutable();
//         $later = new \DateTimeImmutable('+1 day');

//         $avis->setNote(4);
//         $avis->setCommentaire('Très bon service');
//         $avis->setUser($user);
//         $avis->setReservation($reservation);
//         $avis->setCreatedAt($now);
//         $avis->setUpdatedAt($later);
//         $avis->setIsVisible(true);

//         $this->assertSame(4, $avis->getNote());
//         $this->assertSame('Très bon service', $avis->getCommentaire());
//         $this->assertSame($user, $avis->getUser());
//         $this->assertSame($reservation, $avis->getReservation());
//         $this->assertSame($now, $avis->getCreatedAt());
//         $this->assertSame($later, $avis->getUpdatedAt());
//         $this->assertTrue($avis->isVisible());
//     }
// }
