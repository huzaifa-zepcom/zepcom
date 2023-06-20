<?php

namespace KitAutoPriceUpdate\Content\Repricer;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                 add(RepricerEntity $entity)
 * @method void                 set(string $key, RepricerEntity $entity)
 * @method RepricerEntity[]    getIterator()
 * @method RepricerEntity[]    getElements()
 * @method RepricerEntity|null get(string $key)
 * @method RepricerEntity|null first()
 * @method RepricerEntity|null last()
 */
class RepricerCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return RepricerEntity::class;
    }
}
