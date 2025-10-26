<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'games')]
#[ORM\Index(columns: ['title'], name: 'idx_game_title')]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    #[ORM\Column(length: 255)]
    private string $title;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $platform = null; // p. ej. PC, PS5, Switch...

    #[Assert\Range(min: 1970, max: 2100)]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $releaseYear = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $genre = null;

    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $developer = null;

    #[Assert\Url]
    #[Assert\Length(max: 500)]
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $coverUrl = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // getters/setters...

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getPlatform(): ?string { return $this->platform; }
    public function setPlatform(?string $platform): self { $this->platform = $platform; return $this; }

    public function getReleaseYear(): ?int { return $this->releaseYear; }
    public function setReleaseYear(?int $year): self { $this->releaseYear = $year; return $this; }

    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(?string $genre): self { $this->genre = $genre; return $this; }

    public function getDeveloper(): ?string { return $this->developer; }
    public function setDeveloper(?string $dev): self { $this->developer = $dev; return $this; }

    public function getCoverUrl(): ?string { return $this->coverUrl; }
    public function setCoverUrl(?string $url): self { $this->coverUrl = $url; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $ts): self { $this->updatedAt = $ts; return $this; }
}
