<?php

namespace KitRma\Content\RmaStatus;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                 add(RmaStatusEntity $entity)
 * @method void                 set(string $key, RmaStatusEntity $entity)
 * @method RmaStatusEntity[]    getIterator()
 * @method RmaStatusEntity[]    getElements()
 * @method RmaStatusEntity|null get(string $key)
 * @method RmaStatusEntity|null first()
 * @method RmaStatusEntity|null last()
 */
class RmaStatusCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return RmaStatusEntity::class;
    }
}
