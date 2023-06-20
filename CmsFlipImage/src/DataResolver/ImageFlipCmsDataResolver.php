<?php

namespace CmsFlipImage\DataResolver;

use CmsFlipImage\Cms\FlipImageStruct;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ImageStruct;
use Shopware\Core\Content\Media\Cms\AbstractDefaultMediaResolver;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ImageFlipCmsDataResolver  extends AbstractCmsElementResolver
{
    public const CMS_DEFAULT_ASSETS_PATH = '/bundles/storefront/assets/default/cms/';

    private AbstractDefaultMediaResolver $mediaResolver;

    /**
     * @internal
     */
    public function __construct(AbstractDefaultMediaResolver $mediaResolver)
    {
        $this->mediaResolver = $mediaResolver;
    }

    public function getType(): string
    {
        return 'image-flip';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $mediaConfig = $slot->getFieldConfig()->get('media');

        if (
            $mediaConfig === null
            || $mediaConfig->isMapped()
            || $mediaConfig->isDefault()
            || $mediaConfig->getValue() === null
        ) {
            return null;
        }

        $criteria = new Criteria([$mediaConfig->getStringValue()]);

        $criteriaCollection = new CriteriaCollection();
        $criteriaCollection->add('media_' . $slot->getUniqueIdentifier(), MediaDefinition::class, $criteria);

        $mediaFlipConfig = $slot->getFieldConfig()->get('mediaFlip');
        $criteriaFlip = new Criteria([$mediaFlipConfig->getStringValue()]);
        $criteriaCollection->add('media_flip_' . $slot->getUniqueIdentifier(), MediaDefinition::class, $criteriaFlip);

        return $criteriaCollection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $image = new FlipImageStruct();
        $slot->setData($image);

        $urlConfig = $config->get('url');
        if ($urlConfig !== null) {
            if ($urlConfig->isStatic()) {
                $image->setUrl($urlConfig->getStringValue());
            }

            if ($urlConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
                $url = $this->resolveEntityValue($resolverContext->getEntity(), $urlConfig->getStringValue());
                if ($url) {
                    $image->setUrl($url);
                }
            }

            $newTabConfig = $config->get('newTab');
            if ($newTabConfig !== null) {
                $image->setNewTab($newTabConfig->getBoolValue());
            }
        }

        $mediaConfig = $config->get('media');
        if ($mediaConfig && $mediaConfig->getValue()) {
            $this->addMediaEntity($slot, $image, $result, $mediaConfig, $resolverContext, false);
        }

        $mediaFlipConfig = $config->get('mediaFlip');
        if ($mediaFlipConfig && $mediaFlipConfig->getValue()) {
            $this->addMediaEntity($slot, $image, $result, $mediaFlipConfig, $resolverContext, true);
        }
    }

    private function addMediaEntity(
        CmsSlotEntity $slot,
        FlipImageStruct $image,
        ElementDataCollection $result,
        FieldConfig $config,
        ResolverContext $resolverContext,
        bool $isFlip
    ): void {
        if ($config->isDefault()) {
            $media = $this->mediaResolver->getDefaultCmsMediaEntity($config->getStringValue());

            if ($media) {
                $image->setMedia($media);
                $image->setMediaFlip($media);
            }
        }

        if ($config->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $media = $this->resolveEntityValue($resolverContext->getEntity(), $config->getStringValue());

            if ($media instanceof MediaEntity) {
                if($isFlip) {
                    $image->setMediaFlipId($media->getUniqueIdentifier());
                    $image->setMediaFlip($media);
                } else {
                    $image->setMediaId($media->getUniqueIdentifier());
                    $image->setMedia($media);
                }
            }
        }

        if ($config->isStatic()) {
            if($isFlip) {
                $image->setMediaFlipId($config->getStringValue());

                $searchResult = $result->get('media_flip_' . $slot->getUniqueIdentifier());
            } else {
                $image->setMediaId($config->getStringValue());

                $searchResult = $result->get('media_' . $slot->getUniqueIdentifier());
            }

            if (!$searchResult) {
                return;
            }

            $media = $searchResult->get($config->getStringValue());
            if (!$media instanceof MediaEntity) {
                return;
            }

            if($isFlip) {
                $image->setMediaFlip($media);
            } else {
                $image->setMedia($media);
            }
        }
    }
}
