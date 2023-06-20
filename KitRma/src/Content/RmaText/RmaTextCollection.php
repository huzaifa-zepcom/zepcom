<?php

namespace KitRma\Content\RmaText;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(RmaTextEntity $entity)
 * @method void               set(string $key, RmaTextEntity $entity)
 * @method RmaTextEntity[]    getIterator()
 * @method RmaTextEntity[]    getElements()
 * @method RmaTextEntity|null get(string $key)
 * @method RmaTextEntity|null first()
 * @method RmaTextEntity|null last()
 */
class RmaTextCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return RmaTextEntity::class;
    }
}
