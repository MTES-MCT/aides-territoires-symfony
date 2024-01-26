<?php

namespace App\Entity\Log;

use App\Entity\User\User;
use App\Repository\Log\LogUserLoginRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LogUserLoginRepository::class)]
#[ORM\Index(columns: ['action'], name: 'action_lul')]
#[ORM\Index(columns: ['type'], name: 'type_lul')]
#[ORM\Index(columns: ['data1'], name: 'data1_lul')]
#[ORM\Index(columns: ['data2'], name: 'data2_lul')]
#[ORM\Index(columns: ['date_create'], name: 'date_create_lul')]
#[ORM\Index(columns: ['ip'], name: 'ip_lul')]
#[ORM\Index(columns: ['referer'], name: 'referer_lul')]
#[ORM\Index(columns: ['request_uri'], name: 'request_uri_lul')]
class LogUserLogin
{
    const ACTION_LOGOUT = 'logout';
    const ACTION_LOST_PASSWORD = 'lost-password';
    const ACTION_REINIT_PASSWORD = 'reinit-password';
    const ACTION_CHANGE_PASSWORD = 'change-password';
    const ACTION_LOGIN = 'login';
    const TYPE_AUTOLOGIN = 'autologin';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $page = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $action = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $data1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $data2 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requestUri = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne(inversedBy: 'logUserLogins')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): ?string
    {
        return $this->page;
    }

    public function setPage(?string $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getData1(): ?string
    {
        return $this->data1;
    }

    public function setData1(?string $data1): static
    {
        $this->data1 = $data1;

        return $this;
    }

    public function getData2(): ?string
    {
        return $this->data2;
    }

    public function setData2(?string $data2): static
    {
        $this->data2 = $data2;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setReferer(?string $referer): static
    {
        $this->referer = substr($referer, 0, 255);

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getRequestUri(): ?string
    {
        return $this->requestUri;
    }

    public function setRequestUri(?string $requestUri): static
    {
        $this->requestUri = $requestUri;

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

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): static
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
