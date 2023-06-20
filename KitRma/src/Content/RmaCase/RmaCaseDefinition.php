<?php

namespace KitRma\Content\RmaCase;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class RmaCaseDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'rma_case';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name', 255))->setFlags(new Required()),
            (new JsonField('freetext', 'freetext'))->setFlags(new Required())
        ]);
    }

    public function getEntityClass(): string
    {
        return RmaCaseEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RmaCaseCollection::class;
    }
}
