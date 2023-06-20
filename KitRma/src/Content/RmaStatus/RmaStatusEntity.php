<?php

namespace KitRma\Content\RmaStatus;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class RmaStatusEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $name;

    /** @var string */
    protected $nameExt;

    /** @var bool */
    protected $endstate;

    /** @var bool */
    protected $endstateFinal;

    /** @var string */
    protected $color;

    public function setName(string $value): void
    {
        $this->name = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setNameExt(string $value): void
    {
        $this->nameExt = $value;
    }

    public function getNameExt(): string
    {
        return $this->nameExt;
    }

    public function setEndstate(bool $value): void
    {
        $this->endstate = $value;
    }

    public function getEndstate(): bool
    {
        return $this->endstate;
    }

    public function setColor(string $value): void
    {
        $this->color = $value;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @return bool
     */
    public function isEndstateFinal(): bool
    {
        return $this->endstateFinal;
    }

    /**
     * @param bool $endstateFinal
     */
    public function setEndstateFinal(bool $endstateFinal): void
    {
        $this->endstateFinal = $endstateFinal;
    }
}
