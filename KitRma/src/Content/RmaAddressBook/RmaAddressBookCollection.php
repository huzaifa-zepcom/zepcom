<?php

namespace KitRma\Content\RmaAddressBook;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                      add(RmaAddressBookEntity $entity)
 * @method void                      set(string $key, RmaAddressBookEntity $entity)
 * @method RmaAddressBookEntity[]    getIterator()
 * @method RmaAddressBookEntity[]    getElements()
 * @method RmaAddressBookEntity|null get(string $key)
 * @method RmaAddressBookEntity|null first()
 * @method RmaAddressBookEntity|null last()
 */
class RmaAddressBookCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return RmaAddressBookEntity::class;
    }
}
