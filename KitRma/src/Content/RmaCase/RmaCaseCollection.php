<?php

namespace KitRma\Content\RmaCase;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                       add(RmaCaseEntity $entity)
 * @method void                       set(string $key, RmaCaseEntity $entity)
 * @method RmaCaseEntity[]    getIterator()
 * @method RmaCaseEntity[]    getElements()
 * @method RmaCaseEntity|null get(string $key)
 * @method RmaCaseEntity|null first()
 * @method RmaCaseEntity|null last()
 */
class RmaCaseCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return RmaCaseEntity::class;
    }
}
