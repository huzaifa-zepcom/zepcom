<?php

namespace Config3d\Content;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void             add(Config3dPluginEntity $entity)
 * @method void             set(string $key, Config3dPluginEntity $entity)
 * @method Config3dPluginEntity[]    getIterator()
 * @method Config3dPluginEntity[]    getElements()
 * @method Config3dPluginEntity|null get(string $key)
 * @method Config3dPluginEntity|null first()
 * @method Config3dPluginEntity|null last()
 */
class Config3dPluginCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return Config3dPluginEntity::class;
    }
}