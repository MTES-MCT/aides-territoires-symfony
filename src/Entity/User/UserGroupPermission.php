<?php

namespace App\Entity\User;

use App\Repository\User\UserGroupPermissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// TODO a supprimer
#[ORM\Entity(repositoryClass: UserGroupPermissionRepository::class)]
class UserGroupPermission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $codename = null;

    #[ORM\ManyToMany(targetEntity: UserGroup::class, inversedBy: 'userGroupPermissions')]
    private Collection $userGroups;

    public function __construct()
    {
        $this->userGroups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCodename(): ?string
    {
        return $this->codename;
    }

    public function setCodename(string $codename): static
    {
        $this->codename = $codename;

        return $this;
    }

    /**
     * @return Collection<int, UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    public function addUserGroup(UserGroup $userGroup): static
    {
        if (!$this->userGroups->contains($userGroup)) {
            $this->userGroups->add($userGroup);
        }

        return $this;
    }

    public function removeUserGroup(UserGroup $userGroup): static
    {
        $this->userGroups->removeElement($userGroup);

        return $this;
    }
}
