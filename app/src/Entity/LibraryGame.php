<?php

namespace App\Entity;

use App\Enum\PlayStatus;
use App\Repository\LibraryGameRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LibraryGameRepository::class)]
#[ORM\Table(name: 'library_games')]
#[ORM\UniqueConstraint(columns: ['library_id', 'game_id'], name: 'uniq_library_game')]
class LibraryGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Library::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Library $library;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\Column(enumType: PlayStatus::class)]
    private PlayStatus $status = PlayStatus::BACKLOG;

    #[Assert\Range(min: 0, max: 100)]
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true], nullable: true)]
    private ?int $rating = null; // 0..100

    #[Assert\Range(min: 0, max: 100)]
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true], nullable: true)]
    private ?int $progressPercent = null; // 0..100

    #[Assert\Range(min: 0)]
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1, nullable: true, options: ['unsigned' => true])]
    private ?string $hoursPlayed = null; // guarda como string (DECIMAL)

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $addedAt;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
        $this->status = PlayStatus::BACKLOG;
    }

    // getters/setters...

    public function getId(): ?int { return $this->id; }

    public function getLibrary(): Library { return $this->library; }
    public function setLibrary(Library $library): self { $this->library = $library; return $this; }

    public function getGame(): Game { return $this->game; }
    public function setGame(Game $game): self { $this->game = $game; return $this; }

    public function getStatus(): PlayStatus { return $this->status; }
    public function setStatus(PlayStatus $status): self { $this->status = $status; return $this; }

    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $rating): self { $this->rating = $rating; return $this; }

    public function getProgressPercent(): ?int { return $this->progressPercent; }
    public function setProgressPercent(?int $p): self { $this->progressPercent = $p; return $this; }

    // DECIMAL en Doctrine se mapea a string
    public function getHoursPlayed(): ?string { return $this->hoursPlayed; }
    public function setHoursPlayed(?string $h): self { $this->hoursPlayed = $h; return $this; }

    public function getAddedAt(): \DateTimeImmutable { return $this->addedAt; }
    public function setAddedAt(\DateTimeImmutable $ts): self { $this->addedAt = $ts; return $this; }
}
