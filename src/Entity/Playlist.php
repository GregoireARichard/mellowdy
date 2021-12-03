<?php

namespace App\Entity;

use App\Repository\PlaylistRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PlaylistRepository::class)
 */
class Playlist
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxLimit;

    /**
     * @ORM\ManyToOne(targetEntity=MellowUser::class, inversedBy="Playlists")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Owner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaxLimit(): ?int
    {
        return $this->maxLimit;
    }

    public function setMaxLimit(int $maxLimit): self
    {
        $this->maxLimit = $maxLimit;

        return $this;
    }

    public function getOwner(): ?MellowUser
    {
        return $this->Owner;
    }

    public function setOwner(?MellowUser $Owner): self
    {
        $this->Owner = $Owner;

        return $this;
    }
}
