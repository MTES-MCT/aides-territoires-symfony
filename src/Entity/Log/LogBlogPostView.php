<?php

namespace App\Entity\Log;

use App\Entity\Blog\BlogPost;
use App\Entity\Organization\Organization;
use App\Entity\User\User;
use App\Repository\Log\LogBlogPostViewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LogBlogPostViewRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_lbpv')]
class LogBlogPostView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne(inversedBy: 'logBlogPostViews')]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'logBlogPostViews')]
    private ?BlogPost $blogPost = null;

    #[ORM\ManyToOne(inversedBy: 'logBlogPostViews')]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getBlogPost(): ?BlogPost
    {
        return $this->blogPost;
    }

    public function setBlogPost(?BlogPost $blogPost): static
    {
        $this->blogPost = $blogPost;

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
