<?php

namespace KitRma\Content\RmaAddressBook;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class RmaAddressBookDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'rma_address_book';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name', 255))->setFlags(new Required()),
            (new StringField('address', 'address', 255))->setFlags(new Required()),
            (new JsonField('suppliers', 'suppliers'))->setFlags(new Required())
        ]);
    }

    public function getEntityClass(): string
    {
        return RmaAddressBookEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RmaAddressBookCollection::class;
    }
}
