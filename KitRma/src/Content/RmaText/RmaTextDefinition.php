<?php

namespace KitRma\Content\RmaText;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class RmaTextDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'rma_text';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name'))->setFlags(new Required()),
            (new LongTextField('description', 'description'))->addFlags(new AllowHtml()),
            (new StringField('type', 'type', 255))->setFlags(new Required())
        ]);
    }

    public function getEntityClass(): string
    {
        return RmaTextEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RmaTextCollection::class;
    }
}
