<?php

namespace App\Entity\Page;

use App\Entity\Search\SearchPage;
use App\Repository\Page\PageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Validator as AtAssert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[UniqueEntity(fields: ['url'], message: 'Cette url existe déjà')]
#[ORM\Index(columns: ['url'], name: 'url_page')]
class Page // NOSONAR too much methods
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 255, unique: true)]
    #[AtAssert\PageUrl]
    private ?string $url = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $enableComments = false;

    #[ORM\Column]
    private ?bool $registrationRequired = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\ManyToOne(inversedBy: 'pages')]
    private ?SearchPage $searchPage = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isEnableComments(): ?bool
    {
        return $this->enableComments;
    }

    public function setEnableComments(bool $enableComments): static
    {
        $this->enableComments = $enableComments;

        return $this;
    }

    public function isRegistrationRequired(): ?bool
    {
        return $this->registrationRequired;
    }

    public function setRegistrationRequired(bool $registrationRequired): static
    {
        $this->registrationRequired = $registrationRequired;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getSearchPage(): ?SearchPage
    {
        return $this->searchPage;
    }

    public function setSearchPage(?SearchPage $searchPage): static
    {
        $this->searchPage = $searchPage;

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

    public function getTimeUpdate(): ?\DateTimeInterface
    {
        return $this->timeUpdate;
    }

    public function setTimeUpdate(?\DateTimeInterface $timeUpdate): static
    {
        $this->timeUpdate = $timeUpdate;

        return $this;
    }


    /**
     * Pour avoir le nom
     *
     * @return string
     */
    public function __toString()
    {
        return $this->url .' - '.$this->name;
    }
}
