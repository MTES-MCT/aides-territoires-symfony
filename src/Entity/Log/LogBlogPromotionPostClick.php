<?php

namespace App\Entity\Log;

use App\Entity\Blog\BlogPromotionPost;
use App\Repository\Log\LogBlogPromotionPostClickRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LogBlogPromotionPostClickRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_lbppc')]
#[ORM\Index(columns: ['source'], name: 'date_create_lbppc')]
class LogBlogPromotionPostClick
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $querystring = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne(inversedBy: 'logBlogPromotionPostClicks')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?BlogPromotionPost $blogPromotionPost = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuerystring(): ?string
    {
        return $this->querystring;
    }

    public function setQuerystring(?string $querystring): static
    {
        $this->querystring = $querystring;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

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

    public function getBlogPromotionPost(): ?BlogPromotionPost
    {
        return $this->blogPromotionPost;
    }

    public function setBlogPromotionPost(?BlogPromotionPost $blogPromotionPost): static
    {
        $this->blogPromotionPost = $blogPromotionPost;

        return $this;
    }
}
