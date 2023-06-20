<?php

namespace CmsFlipImage\Cms;

use Shopware\Core\Content\Cms\SalesChannel\Struct\ImageStruct;
use Shopware\Core\Content\Media\MediaEntity;

class FlipImageStruct extends ImageStruct
{

    /**
     * @var string|null
     */
    protected $mediaFlipId;

    /**
     * @var MediaEntity|null
     */
    protected $mediaFlip;

    /**
     * @return MediaEntity|null
     */
    public function getMediaFlip(): ?MediaEntity
    {
        return $this->mediaFlip;
    }

    /**
     * @param MediaEntity|null $mediaFlip
     */
    public function setMediaFlip(?MediaEntity $mediaFlip): void
    {
        $this->mediaFlip = $mediaFlip;
    }

    /**
     * @return string|null
     */
    public function getMediaFlipId(): ?string
    {
        return $this->mediaFlipId;
    }

    /**
     * @param string|null $mediaFlipId
     */
    public function setMediaFlipId(?string $mediaFlipId): void
    {
        $this->mediaFlipId = $mediaFlipId;
    }
}