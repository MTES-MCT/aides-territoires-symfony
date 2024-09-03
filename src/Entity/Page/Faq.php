<?php

namespace App\Entity\Page;

use App\Entity\Program\PageTab;
use App\Repository\Page\FaqRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FaqRepository::class)]
class Faq
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

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\OneToMany(mappedBy: 'faq', targetEntity: FaqCategory::class, cascade: ['persist'], orphanRemoval: true)]
    #[OrderBy(['position' => 'ASC'])]
    private Collection $faqCategories;

    #[ORM\ManyToOne(inversedBy: 'faqs')]
    private ?PageTab $pageTab = null;


    private ?\DateTime $latestUpdateTime = null;

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
        if ($this->faqCategories->removeElement($faqCategory) && $faqCategory->getFaq() === $this) {
            $faqCategory->setFaq(null);
        }

        return $this;
    }

    public function  __toString(): string
    {
        return $this->name ?? 'Faq';
    }

    public function getPageTab(): ?PageTab
    {
        return $this->pageTab;
    }

    public function setPageTab(?PageTab $pageTab): static
    {
        $this->pageTab = $pageTab;

        return $this;
    }

    public function getLatestUpdateTime(): ?\DateTime
    {
        return $this->latestUpdateTime;
    }

    public function setLatestUpdateTime(?\DateTime $latestUpdateTime): static
    {
        $this->latestUpdateTime = $latestUpdateTime;

        return $this;
    }
}
