<?php

namespace App\Entity\Perimeter;

use ApiPlatform\Metadata\ApiFilter;
use App\Repository\Perimeter\PerimeterDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Perimeter\PerimeterDataController;
use App\Filter\PerimeterData\PerimeterDataPerimeterIdFilter;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'perimeter_data',
    operations: [
        new GetCollection(
            name: self::API_OPERATION_NAME,
            uriTemplate: '/perimeters/data/',
            controller: PerimeterDataController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: 'Lister les données supplémentaires sur un périmètre',
                tags: [Perimeter::API_TAG]
            )
        ),
    ],
)]
#[ApiFilter(PerimeterDataPerimeterIdFilter::class)]
#[ORM\Entity(repositoryClass: PerimeterDataRepository::class)]
class PerimeterData
{
    public const API_GROUP_LIST = 'perimeter_data:list';
    public const API_OPERATION_NAME = 'perimeter_data';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private ?string $prop = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\ManyToOne(inversedBy: 'perimeterDatas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Perimeter $perimeter = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProp(): ?string
    {
        return $this->prop;
    }

    public function setProp(string $prop): static
    {
        $this->prop = $prop;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

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

    public function getPerimeter(): ?Perimeter
    {
        return $this->perimeter;
    }

    public function setPerimeter(?Perimeter $perimeter): static
    {
        $this->perimeter = $perimeter;

        return $this;
    }

    public function __toString(): string
    {
        return $this->prop . ' : ' . $this->value;
    }
}
