<?php

namespace Config3d\Content;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;

class Config3dPluginDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'config3d_plugin';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(array(
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('line_item_id', 'lineItemId',
                OrderLineItemDefinition::class,
                'id'))->addFlags(new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class,
                'id'))->addFlags(new Required()),
            (new FkField('order_id', 'orderId', OrderDefinition::class,
                'id'))->addFlags(new Required()),
            (new JsonField('config_data', 'configData', array(), null))->addFlags(new Required()),
            new IntField('try_attempt_number', 'tryAttemptNumber', null, null),
            new DateTimeField('next_attempt_at', 'nextAttemptAt'),
            new IntField('response_status', 'responseStatus', null, null),
            (new LongTextField('response_data', 'responseData')),
            new BoolField('failed', 'failed')
        ));
    }

    public function getEntityClass(): string
    {
        return Config3dPluginEntity::class;
    }

    public function getCollectionClass(): string
    {
        return Config3dPluginCollection::class;
    }
}