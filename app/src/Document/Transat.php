<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[MongoDB\Document]
class Transat
{
  #[MongoDB\Id]
  private $id;

  #[MongoDB\Field(type: "string")]
  private $numT;

  #[MongoDB\ReferenceMany(targetDocument: Reservation::class, mappedBy: "transat")]
  private $reservations;

  public function __construct()
  {
    $this->reservations = new ArrayCollection();
  }

  public function getId(): ?string
  {
    return $this->id;
  }

  public function getNumT(): ?string
  {
    return $this->numT;
  }

  public function setNumT(string $numT): self
  {
    $this->numT = $numT;
    return $this;
  }

  /**
   * @return Collection|Reservation[]
   */
  public function getReservations(): Collection
  {
    return $this->reservations;
  }

  public function addReservation(Reservation $reservation): self
  {
    if (!$this->reservations->contains($reservation)) {
      $this->reservations[] = $reservation;
      $reservation->setTransat($this);
    }
    return $this;
  }

  public function removeReservation(Reservation $reservation): self
  {
    if ($this->reservations->removeElement($reservation)) {
      if ($reservation->getTransat() === $this) {
        $reservation->setTransat(null);
      }
    }
    return $this;
  }

  public function isAvailable(\DateTimeInterface $start, \DateTimeInterface $end): bool
  {
    foreach ($this->reservations as $reservation) {
      if ($reservation->getStartTime() < $end && $reservation->getEndTime() > $start) {
        return false;
      }
    }
    return true;
  }
}