<?php

namespace KitAutoPriceUpdate\Content\Repricer;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class RepricerDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'kit_priceupdate';
    }

    protected function defineFields(): FieldCollection
    {
        $fields = [
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),
            (new FkField('product_id', 'productId', ProductDefinition::class, 'id'))->setFlags(new Required()),
            (new IntField('geizhalsID', 'geizhalsID'))->setFlags(new Required()),
            new LongTextField('geizhalsArtikelname', 'geizhalsArtikelname'),
            (new StringField('meinPreis', 'meinPreis')),
            (new StringField('meineArtikelnummer', 'meineArtikelnummer')),
            (new LongTextField('geizhalsArtikelURL', 'geizhalsArtikelURL')),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class)
        ];

        for ($i = 1; $i <= 10; $i++) {
            $fields[] = (new StringField('price' . $i, 'price' . $i));
            $fields[] = (new StringField('anbieter' . $i, 'anbieter' . $i));
            $fields[] = (new StringField('lz' . $i, 'lz' . $i));
        }

        return new FieldCollection($fields);
    }

    public function getEntityClass(): string
    {
        return RepricerEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RepricerCollection::class;
    }
}
