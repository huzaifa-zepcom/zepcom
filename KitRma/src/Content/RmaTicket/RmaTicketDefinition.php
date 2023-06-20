<?php

namespace KitRma\Content\RmaTicket;

use KitRma\Content\RmaCase\RmaCaseDefinition;
use KitRma\Content\RmaStatus\RmaStatusDefinition;
use KitSupplier\Content\KitSupplier\KitSupplierDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\User\UserDefinition;

class RmaTicketDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'rma_ticket';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
            [
                (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),
                (new FkField('case_id', 'caseId', RmaCaseDefinition::class, 'id')),
                (new FkField('customer_id', 'customerId', CustomerDefinition::class, 'id')),
                (new FkField('product_id', 'productId', ProductDefinition::class, 'id')),
                (new FkField('order_id', 'orderId', OrderDefinition::class, 'id')),
                (new FkField('user_id', 'userId', UserDefinition::class, 'id')),
                (new FkField('status_id', 'statusId', RmaStatusDefinition::class, 'id')),
                (new FkField('supplier_id', 'supplierId', KitSupplierDefinition::class, 'id')),
                (new IntField('amount', 'amount'))->setFlags(new Required()),
                new LongTextField('badges', 'badges'),
                (new JsonField('ticket_content', 'ticketContent')),
                new JsonField('files', 'files'),
                (new LongTextField('delivery_address', 'deliveryAddress')),
                (new StringField('rma_number', 'rmaNumber')),
                (new IntField('ticket_serial_number', 'ticketSerialNumber')),
                (new JsonField('product_serial_numbers', 'productSerialNumbers')),
                (new LongTextField('additional_info', 'additionalInfo')),
                (new StringField('restocking_fee_customer', 'feeCustomer')),
                (new StringField('product_name', 'productName')),
                (new StringField('product_number', 'productNumber')),
                (new StringField('restocking_fee_supplier', 'feeSupplier')),
                (new StringField('kit_voucher', 'kitVoucher')),
                (new StringField('supplier_voucher', 'supplierVoucher')),
                (new StringField('supplier_rma_number', 'supplierRmaNumber')),
                (new StringField('customer_email', 'customerEmail')),
                (new StringField('link', 'link')),
                (new StringField('hash', 'hash')),
                (new CreatedAtField())->addFlags(new Required()),
                (new UpdatedAtField()),

                new ManyToOneAssociationField('supplier', 'supplier_id', KitSupplierDefinition::class),
                new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class),
                new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class),
                new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class),
                new ManyToOneAssociationField('status', 'status_id', RmaStatusDefinition::class),
                new ManyToOneAssociationField('user', 'user_id', UserDefinition::class),
                new ManyToOneAssociationField('case', 'case_id', RmaCaseDefinition::class)
            ]
        );
    }

    public function getEntityClass(): string
    {
        return RmaTicketEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RmaTicketCollection::class;
    }
}
