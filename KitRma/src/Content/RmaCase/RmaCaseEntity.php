<?php

namespace KitRma\Content\RmaCase;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class RmaCaseEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $name;

    /** @var array */
    protected $freetext;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $value): void
    {
        $this->name = $value;
    }

    /**
     * @return array
     */
    public function getFreetext(): array
    {
        return $this->freetext;
    }

    public function setFreetext(array $freetext): void
    {
        $this->freetext = $freetext;
    }
}
