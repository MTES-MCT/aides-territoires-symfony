<?php

namespace App\Entity\User;

use App\Repository\User\UserGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserGroupRepository::class)]
class UserGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'userGroups')]
    private Collection $users;

    /**
     * @var Collection<int, UserGroupPermission>
     */
    #[ORM\ManyToMany(targetEntity: UserGroupPermission::class, mappedBy: 'userGroups')]
    private Collection $userGroupPermissions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->userGroupPermissions = new ArrayCollection();
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return Collection<int, UserGroupPermission>
     */
    public function getUserGroupPermissions(): Collection
    {
        return $this->userGroupPermissions;
    }

    public function addUserGroupPermission(UserGroupPermission $userGroupPermission): static
    {
        if (!$this->userGroupPermissions->contains($userGroupPermission)) {
            $this->userGroupPermissions->add($userGroupPermission);
            $userGroupPermission->addUserGroup($this);
        }

        return $this;
    }

    public function removeUserGroupPermission(UserGroupPermission $userGroupPermission): static
    {
        if ($this->userGroupPermissions->removeElement($userGroupPermission)) {
            $userGroupPermission->removeUserGroup($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
