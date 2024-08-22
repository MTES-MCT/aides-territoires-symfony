<?php

namespace App\Entity\Site;

use App\Entity\Log\LogUrlRedirect;
use App\Repository\Site\UrlRedirectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: UrlRedirectRepository::class)]
#[ORM\Index(columns: ['old_url'], name: 'old_url_url_redirect')]
#[ORM\Index(columns: ['date_create'], name: 'date_create_url_redirect')]
class UrlRedirect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Length(max: 700)]
    #[ORM\Column(length: 700)]
    private ?string $oldUrl = null;

    #[Assert\Length(max: 700)]
    #[ORM\Column(length: 700)]
    private ?string $newUrl = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreate = null;

    /**
     * @var Collection<int, LogUrlRedirect>
     */
    #[ORM\OneToMany(mappedBy: 'urlRedirect', targetEntity: LogUrlRedirect::class)]
    private Collection $logUrlRedirects;

    public function __construct()
    {
        $this->logUrlRedirects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOldUrl(): ?string
    {
        return $this->oldUrl;
    }

    public function setOldUrl(string $oldUrl): static
    {
        $this->oldUrl = $oldUrl;

        return $this;
    }

    public function getNewUrl(): ?string
    {
        return $this->newUrl;
    }

    public function setNewUrl(string $newUrl): static
    {
        $this->newUrl = $newUrl;

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

    /**
     * @return Collection<int, LogUrlRedirect>
     */
    public function getLogUrlRedirects(): Collection
    {
        return $this->logUrlRedirects;
    }

    public function addLogUrlRedirect(LogUrlRedirect $logUrlRedirect): static
    {
        if (!$this->logUrlRedirects->contains($logUrlRedirect)) {
            $this->logUrlRedirects->add($logUrlRedirect);
            $logUrlRedirect->setUrlRedirect($this);
        }

        return $this;
    }

    public function removeLogUrlRedirect(LogUrlRedirect $logUrlRedirect): static
    {
        if ($this->logUrlRedirects->removeElement($logUrlRedirect)) {
            // set the owning side to null (unless already changed)
            if ($logUrlRedirect->getUrlRedirect() === $this) {
                $logUrlRedirect->setUrlRedirect(null);
            }
        }

        return $this;
    }
}
