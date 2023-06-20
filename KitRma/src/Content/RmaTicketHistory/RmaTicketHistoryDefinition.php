<?php

namespace KitRma\Content\RmaTicketHistory;

use KitRma\Content\RmaStatus\RmaStatusDefinition;
use KitRma\Content\RmaTicket\RmaTicketDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\User\UserDefinition;

class RmaTicketHistoryDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'rma_ticket_history';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),
            (new FkField('ticket_id', 'ticketId', RmaTicketDefinition::class))->setFlags(new Required()),
            (new FkField('status_id', 'statusId', RmaStatusDefinition::class)),
            (new FkField('user_id', 'userId', UserDefinition::class)),

            (new StringField('sender', 'sender', 255))->setFlags(new Required()),
            (new BoolField('read', 'read'))->setFlags(new Required()),
            (new StringField('type', 'type', 255))->setFlags(new Required()),
            (new LongTextField('message', 'message'))->setFlags(new Required(), new AllowHtml()),
            new JsonField('attachment', 'attachment'),

            new ManyToOneAssociationField('ticket', 'ticket_id', RmaTicketDefinition::class),
            new ManyToOneAssociationField('status', 'status_id', RmaStatusDefinition::class),
            new ManyToOneAssociationField('user', 'user_id', UserDefinition::class),
        ]);
    }

    public function getEntityClass(): string
    {
        return RmaTicketHistoryEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RmaTicketHistoryCollection::class;
    }
}
