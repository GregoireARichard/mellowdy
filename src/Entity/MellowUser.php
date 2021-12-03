<?php

namespace App\Entity;

use App\Repository\MellowUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MellowUserRepository::class)
 */
class MellowUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Username;


    /**
     * @ORM\OneToMany(targetEntity=Playlist::class, mappedBy="Owner", orphanRemoval=true)
     */
    private $Playlists;

    /**
     * @ORM\Column(type="text")
     */
    private $UserToken;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $frontToken;

    public function __construct()
    {
        $this->Playlists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->Username;
    }

    public function setUsername(string $Username): self
    {
        $this->Username = $Username;

        return $this;
    }


    /**
     * @return Collection|Playlist[]
     */
    public function getPlaylists(): Collection
    {
        return $this->Playlists;
    }

    public function addPlaylist(Playlist $playlist): self
    {
        if (!$this->Playlists->contains($playlist)) {
            $this->Playlists[] = $playlist;
            $playlist->setOwner($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): self
    {
        if ($this->Playlists->removeElement($playlist)) {
            // set the owning side to null (unless already changed)
            if ($playlist->getOwner() === $this) {
                $playlist->setOwner(null);
            }
        }

        return $this;
    }

    public function getUserToken(): ?string
    {
        return $this->UserToken;
    }

    public function setUserToken(string $UserToken): self
    {
        $this->UserToken = $UserToken;

        return $this;
    }

    public function getFrontToken(): ?string
    {
        return $this->frontToken;
    }

    public function setFrontToken(string $frontToken): self
    {
        $this->frontToken = $frontToken;

        return $this;
    }
}
