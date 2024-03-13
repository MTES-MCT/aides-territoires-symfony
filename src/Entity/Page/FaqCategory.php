<?php

namespace App\Entity\Page;

use App\Repository\Page\FaqCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: FaqCategoryRepository::class)]
class FaqCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    // #[Gedmo\SortablePosition]
    private ?int $position = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\OneToMany(mappedBy: 'faqCategory', targetEntity: FaqQuestionAnswser::class, cascade: ['persist'], orphanRemoval: true)]
    #[OrderBy(['position' => 'ASC'])]
    private Collection $faqQuestionAnswsers;

    #[ORM\ManyToOne(inversedBy: 'faqCategories')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Faq $faq = null;

    public function __construct()
    {
        $this->faqQuestionAnswsers = new ArrayCollection();
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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
     * @return Collection<int, FaqQuestionAnswser>
     */
    public function getFaqQuestionAnswsers(): Collection
    {
        return $this->faqQuestionAnswsers;
    }

    public function addFaqQuestionAnswser(FaqQuestionAnswser $faqQuestionAnswser): static
    {
        if (!$this->faqQuestionAnswsers->contains($faqQuestionAnswser)) {
            $this->faqQuestionAnswsers->add($faqQuestionAnswser);
            $faqQuestionAnswser->setFaqCategory($this);
        }

        return $this;
    }

    public function removeFaqQuestionAnswser(FaqQuestionAnswser $faqQuestionAnswser): static
    {
        if ($this->faqQuestionAnswsers->removeElement($faqQuestionAnswser)) {
            // set the owning side to null (unless already changed)
            if ($faqQuestionAnswser->getFaqCategory() === $this) {
                $faqQuestionAnswser->setFaqCategory(null);
            }
        }

        return $this;
    }

    public function getFaq(): ?Faq
    {
        return $this->faq;
    }

    public function setFaq(?Faq $faq): static
    {
        $this->faq = $faq;

        return $this;
    }

    public function  __toString(): string
    {
        return $this->name;
    }
}
