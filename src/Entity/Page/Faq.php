<?php

namespace App\Entity\Page;

use App\Entity\Program\PageTab;
use App\Repository\Page\FaqRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: FaqRepository::class)]
class Faq
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\OneToMany(mappedBy: 'faq', targetEntity: FaqCategory::class, cascade: ['persist'], orphanRemoval: true)]
    #[OrderBy(['position' => 'ASC'])]
    private Collection $faqCategories;

    #[ORM\OneToOne(mappedBy: 'faq', cascade: ['persist'])]
    #[JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PageTab $pageTab = null;

    public function __construct()
    {
        $this->faqCategories = new ArrayCollection();
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
     * @return Collection<int, FaqCategory>
     */
    public function getFaqCategories(): Collection
    {
        return $this->faqCategories;
    }

    public function addFaqCategory(FaqCategory $faqCategory): static
    {
        if (!$this->faqCategories->contains($faqCategory)) {
            $this->faqCategories->add($faqCategory);
            $faqCategory->setFaq($this);
        }

        return $this;
    }

    public function removeFaqCategory(FaqCategory $faqCategory): static
    {
        if ($this->faqCategories->removeElement($faqCategory)) {
            // set the owning side to null (unless already changed)
            if ($faqCategory->getFaq() === $this) {
                $faqCategory->setFaq(null);
            }
        }

        return $this;
    }

    public function getPageTab(): ?PageTab
    {
        return $this->pageTab;
    }

    public function setPageTab(?PageTab $pageTab): static
    {
        // unset the owning side of the relation if necessary
        if ($pageTab === null && $this->pageTab !== null) {
            $this->pageTab->setFaq(null);
        }

        // set the owning side of the relation if necessary
        if ($pageTab !== null && $pageTab->getFaq() !== $this) {
            $pageTab->setFaq($this);
        }

        $this->pageTab = $pageTab;

        return $this;
    }

    public function  __toString(): string
    {
        return $this->name;
    }
}
