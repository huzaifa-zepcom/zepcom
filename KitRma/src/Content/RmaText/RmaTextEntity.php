<?php

namespace KitRma\Content\RmaText;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class RmaTextEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $name;

    /** @var string|null */
    protected $description;

    /** @var string */
    protected $type;

    public function setName(string $value): void
    {
        $this->name = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(?string $value): void
    {
        $this->description = $value;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setType(string $value): void
    {
        $this->type = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
