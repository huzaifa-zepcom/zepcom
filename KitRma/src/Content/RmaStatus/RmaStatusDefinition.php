<?php

namespace KitRma\Content\RmaStatus;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class RmaStatusDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'rma_status';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name'))->setFlags(new Required()),
            (new StringField('name_ext', 'nameExt'))->setFlags(new Required()),
            (new BoolField('endstate', 'endstate'))->setFlags(new Required()),
            (new BoolField('endstate_final', 'endstateFinal'))->setFlags(new Required()),
            (new StringField('color', 'color', 255))->setFlags(new Required())
        ]);
    }

    public function getEntityClass(): string
    {
        return RmaStatusEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RmaStatusCollection::class;
    }
}
