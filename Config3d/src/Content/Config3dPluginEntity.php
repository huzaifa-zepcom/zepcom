<?php

namespace Config3d\Content;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class Config3dPluginEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $lineItemId;
    /** @var string */
    protected $productId;
    /** @var string */
    protected $orderId;
    /** @var array */
    protected $configData;
    /** @var string|null */
    protected $responseData;
    /** @var int|null */
    protected $tryAttemptNumber;
    /** @var \DateTime|null */
    protected $nextAttemptAt;
    /** @var int|null */
    protected $responseStatus;
    /** @var bool|null */
    protected $failed;

    public function setLineItemId(string $value): void
    {
        $this->lineItemId = $value;
    }

    public function getLineItemId(): string
    {
        return $this->lineItemId;
    }

    public function setProductId(string $value): void
    {
        $this->productId = $value;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setOrderId(string $value): void
    {
        $this->orderId = $value;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setConfigData(array $value): void
    {
        $this->configData = $value;
    }

    public function getConfigData(): array
    {
        return $this->configData;
    }

    public function setTryAttemptNumber(?int $value): void
    {
        $this->tryAttemptNumber = $value;
    }

    public function getTryAttemptNumber(): ?int
    {
        return $this->tryAttemptNumber;
    }

    public function setNextAttemptAt(?\DateTime $value): void
    {
        $this->nextAttemptAt = $value;
    }

    public function getNextAttemptAt(): ?\DateTime
    {
        return $this->nextAttemptAt;
    }

    public function setResponseStatus(?int $value): void
    {
        $this->responseStatus = $value;
    }

    public function getResponseStatus(): ?int
    {
        return $this->responseStatus;
    }

    public function setFailed(?bool $value): void
    {
        $this->failed = $value;
    }

    public function getFailed(): ?bool
    {
        return $this->failed;
    }

    /**
     * @return string|null
     */
    public function getResponseData(): ?string
    {
        return $this->responseData;
    }

    /**
     * @param string|null $responseData
     */
    public function setResponseData(?string $responseData): void
    {
        $this->responseData = $responseData;
    }
}