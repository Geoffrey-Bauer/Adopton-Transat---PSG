<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class Reservation
{
  #[MongoDB\Id]
  private $id;

  #[MongoDB\Field(type: "date")]
  private $startTime;

  #[MongoDB\Field(type: "date")]
  private $endTime;

  #[MongoDB\Field(type: "string")]
  private $password;

  #[MongoDB\ReferenceOne(targetDocument: Transat::class, storeAs: "ref")]
  private ?Transat $transat = null;

  public function getId(): ?string
  {
    return $this->id;
  }

  public function getStartTime(): ?\DateTimeInterface
  {
    return $this->startTime;
  }

  public function setStartTime(\DateTimeInterface $startTime): self
  {
    $this->startTime = $startTime;
    return $this;
  }

  public function getEndTime(): ?\DateTimeInterface
  {
    return $this->endTime;
  }

  public function setEndTime(\DateTimeInterface $endTime): self
  {
    $this->endTime = $endTime;
    return $this;
  }

  public function setPassword(string $password): self
  {
    $this->password = password_hash($password, PASSWORD_DEFAULT);
    return $this;
  }

  public function verifyPassword(string $password): bool
  {
    return password_verify($password, $this->password);
  }

  public function getTransat(): ?Transat
  {
    return $this->transat;
  }

  public function setTransat(?Transat $transat): self
  {
    $this->transat = $transat;
    return $this;
  }
}