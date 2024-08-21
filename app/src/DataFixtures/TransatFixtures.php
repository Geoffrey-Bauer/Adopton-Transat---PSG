<?php

namespace App\DataFixtures;

use App\Document\Transat;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;

class TransatFixtures extends Fixture
{
  public function load(ObjectManager $manager)
  {
    // Créer 10 transats sans réservations
    for ($i = 1; $i <= 12; $i++) {
      $transat = new Transat();
      $transat->setNumT('T' . $i);

      $manager->persist($transat);
    }

    $manager->flush();
  }
}