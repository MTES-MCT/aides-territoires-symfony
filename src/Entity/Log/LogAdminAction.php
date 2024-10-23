<?php

namespace App\Entity\Log;

use App\Entity\User\User;
use App\Repository\Log\LogAdminActionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LogAdminActionRepository::class)]
class LogAdminAction
{
    public const ACTION_FLAG_INSERT = 1;
    public const ACTION_FLAG_UPDATE = 2;
    public const ACITON_FLAG_DELETE = 3;

    public const FIREWALL_ADMIN_NAME = 'admin';

    public const NOT_ADMIN_LOGGED_FIELDS = [
        'timeUpdate'
    ];
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $objectClass = null;

    #[ORM\Column(nullable: true)]
    private ?int $objectId = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $objectRepr = null;

    #[ORM\Column]
    private ?int $actionFlag = null;

    #[ORM\ManyToOne(inversedBy: 'logAdminActions')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $admin = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(nullable: true)]
    private ?array $changeMessage = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    public function setObjectId(?int $objectId): static
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getObjectRepr(): ?string
    {
        return $this->objectRepr;
    }

    public function setObjectRepr(?string $objectRepr): static
    {
        $this->objectRepr = $objectRepr;

        return $this;
    }

    public function getActionFlag(): ?int
    {
        return $this->actionFlag;
    }

    public function setActionFlag(int $actionFlag): static
    {
        $this->actionFlag = $actionFlag;

        return $this;
    }

    public function getObjectClass(): ?string
    {
        return $this->objectClass;
    }

    public function setObjectClass(?string $objectClass): static
    {
        $this->objectClass = $objectClass;

        return $this;
    }

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): static
    {
        $this->admin = $admin;

        return $this;
    }

    public function getTimeCreate(): ?\DateTimeInterface
    {
        return $this->timeCreate;
    }

    public function setTimeCreate(\DateTimeInterface $timeCreate): static
    {
        $this->timeCreate = $timeCreate;

        return $this;
    }

    public function getChangeMessage(): ?array
    {
        return $this->changeMessage;
    }

    public function setChangeMessage(?array $changeMessage): static
    {
        $this->changeMessage = $changeMessage;

        return $this;
    }
}
