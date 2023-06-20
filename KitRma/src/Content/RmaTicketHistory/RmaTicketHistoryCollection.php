<?php

namespace KitRma\Content\RmaTicketHistory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                        add(RmaTicketHistoryEntity $entity)
 * @method void                        set(string $key, RmaTicketHistoryEntity $entity)
 * @method RmaTicketHistoryEntity[]    getIterator()
 * @method RmaTicketHistoryEntity[]    getElements()
 * @method RmaTicketHistoryEntity|null get(string $key)
 * @method RmaTicketHistoryEntity|null first()
 * @method RmaTicketHistoryEntity|null last()
 */
class RmaTicketHistoryCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return RmaTicketHistoryEntity::class;
    }
}
