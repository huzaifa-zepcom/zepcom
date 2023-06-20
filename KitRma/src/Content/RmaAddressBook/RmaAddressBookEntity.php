<?php

namespace KitRma\Content\RmaAddressBook;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class RmaAddressBookEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $name;

    /** @var string */
    protected $address;

    /** @var string[] */
    protected $suppliers;

    public function setName(string $value): void
    {
        $this->name = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAddress(string $value): void
    {
        $this->address = $value;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return string[]
     */
    public function getSuppliers(): array
    {
        return $this->suppliers;
    }

    /**
     * @param string[] $suppliers
     */
    public function setSuppliers(array $suppliers): void
    {
        $this->suppliers = $suppliers;
    }
}
