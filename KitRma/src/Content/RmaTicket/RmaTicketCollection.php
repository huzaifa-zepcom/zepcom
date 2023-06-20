<?php

namespace KitRma\Content\RmaTicket;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                 add(RmaTicketEntity $entity)
 * @method void                 set(string $key, RmaTicketEntity $entity)
 * @method RmaTicketEntity[]    getIterator()
 * @method RmaTicketEntity[]    getElements()
 * @method RmaTicketEntity|null get(string $key)
 * @method RmaTicketEntity|null first()
 * @method RmaTicketEntity|null last()
 */
class RmaTicketCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return RmaTicketEntity::class;
    }
}
