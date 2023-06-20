<?php

namespace KitRma\Content\RmaTicket;

use KitRma\Content\RmaCase\RmaCaseEntity;
use KitRma\Content\RmaStatus\RmaStatusEntity;
use KitSupplier\Content\KitSupplier\KitSupplierEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\User\UserEntity;

class RmaTicketEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $amount;

    /** @var KitSupplierEntity|null */
    protected $supplier;

    /** @var string|null */
    protected $supplierId;

    /** @var string|null */
    protected $badges;

    /** @var array|null */
    protected $ticketContent;

    /** @var array|null */
    protected $files;

    /** @var string|null */
    protected $deliveryAddress;

    /** @var string */
    protected $rmaNumber;

    /** @var int */
    protected $ticketSerialNumber = 100;

    /** @var string[]|null */
    protected $productSerialNumbers;

    /** @var string|null */
    protected $caseId;

    /** @var RmaCaseEntity|null */
    protected $case;

    /** @var string|null */
    protected $productId;

    /** @var ProductEntity|null */
    protected $product;

    /** @var string|null */
    protected $customerId;

    /** @var CustomerEntity|null */
    protected $customer;

    /** @var string|null */
    protected $customerEmail;

    /** @var string|null */
    protected $orderId;

    /** @var OrderEntity|null */
    protected $order;

    /** @var string|null */
    protected $userId;

    /** @var UserEntity|null */
    protected $user;

    /** @var string */
    protected $statusId;

    /** @var RmaStatusEntity|null */
    protected $status;

    /** @var string|null */
    protected $additionalInfo;

    /** @var string|null */
    protected $feeCustomer;

    /** @var string|null */
    protected $feeSupplier;

    /** @var string|null */
    protected $kitVoucher;

    /** @var string|null */
    protected $supplierVoucher;

    /** @var string|null */
    protected $supplierRmaNumber;

    /** @var string */
    protected $link;

    /** @var string */
    protected $hash;

    /** @var string|null */
    protected $productName;

    /** @var string|null */
    protected $productNumber;
    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return KitSupplierEntity|null
     */
    public function getSupplier(): ?KitSupplierEntity
    {
        return $this->supplier;
    }

    /**
     * @param KitSupplierEntity|null $supplier
     */
    public function setSupplier(?KitSupplierEntity $supplier): void
    {
        $this->supplier = $supplier;
    }

    /**
     * @return string|null
     */
    public function getSupplierId(): ?string
    {
        return $this->supplierId;
    }

    /**
     * @param string|null $supplierId
     */
    public function setSupplierId(?string $supplierId): void
    {
        $this->supplierId = $supplierId;
    }

    /**
     * @return string|null
     */
    public function getBadges(): ?string
    {
        return $this->badges;
    }

    /**
     * @param string|null $badges
     */
    public function setBadges(?string $badges): void
    {
        $this->badges = $badges;
    }

    /**
     * @return string|null
     */
    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    /**
     * @param string|null $deliveryAddress
     */
    public function setDeliveryAddress(?string $deliveryAddress): void
    {
        $this->deliveryAddress = $deliveryAddress;
    }

    /**
     * @return string
     */
    public function getRmaNumber(): string
    {
        return $this->rmaNumber;
    }

    /**
     * @param string $rmaNumber
     */
    public function setRmaNumber(string $rmaNumber): void
    {
        $this->rmaNumber = $rmaNumber;
    }

    /**
     * @return string|null
     */
    public function getCaseId(): ?string
    {
        return $this->caseId;
    }

    /**
     * @param string|null $caseId
     */
    public function setCaseId(?string $caseId): void
    {
        $this->caseId = $caseId;
    }

    /**
     * @return RmaCaseEntity|null
     */
    public function getCase(): ?RmaCaseEntity
    {
        return $this->case;
    }

    /**
     * @param RmaCaseEntity|null $case
     */
    public function setCase(?RmaCaseEntity $case): void
    {
        $this->case = $case;
    }

    /**
     * @return string|null
     */
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * @param string|null $productId
     */
    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return ProductEntity|null
     */
    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    /**
     * @param ProductEntity|null $product
     */
    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param string|null $customerId
     */
    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    /**
     * @return CustomerEntity|null
     */
    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    /**
     * @param CustomerEntity|null $customer
     */
    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return string|null
     */
    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    /**
     * @param string|null $customerEmail
     */
    public function setCustomerEmail(?string $customerEmail): void
    {
        $this->customerEmail = $customerEmail;
    }

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @param string|null $orderId
     */
    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return OrderEntity|null
     */
    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    /**
     * @param OrderEntity|null $order
     */
    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * @param string|null $userId
     */
    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return UserEntity|null
     */
    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    /**
     * @param UserEntity|null $user
     */
    public function setUser(?UserEntity $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getStatusId(): string
    {
        return $this->statusId;
    }

    /**
     * @param string $statusId
     */
    public function setStatusId(string $statusId): void
    {
        $this->statusId = $statusId;
    }

    /**
     * @return RmaStatusEntity|null
     */
    public function getStatus(): ?RmaStatusEntity
    {
        return $this->status;
    }

    /**
     * @param RmaStatusEntity|null $status
     */
    public function setStatus(?RmaStatusEntity $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    /**
     * @param string|null $additionalInfo
     */
    public function setAdditionalInfo(?string $additionalInfo): void
    {
        $this->additionalInfo = $additionalInfo;
    }

    /**
     * @return string|null
     */
    public function getFeeCustomer(): ?string
    {
        return $this->feeCustomer;
    }

    /**
     * @param string|null $feeCustomer
     */
    public function setFeeCustomer(?string $feeCustomer): void
    {
        $this->feeCustomer = $feeCustomer;
    }

    /**
     * @return string|null
     */
    public function getFeeSupplier(): ?string
    {
        return $this->feeSupplier;
    }

    /**
     * @param string|null $feeSupplier
     */
    public function setFeeSupplier(?string $feeSupplier): void
    {
        $this->feeSupplier = $feeSupplier;
    }

    /**
     * @return string|null
     */
    public function getKitVoucher(): ?string
    {
        return $this->kitVoucher;
    }

    /**
     * @param string|null $kitVoucher
     */
    public function setKitVoucher(?string $kitVoucher): void
    {
        $this->kitVoucher = $kitVoucher;
    }

    /**
     * @return string|null
     */
    public function getSupplierVoucher(): ?string
    {
        return $this->supplierVoucher;
    }

    /**
     * @param string|null $supplierVoucher
     */
    public function setSupplierVoucher(?string $supplierVoucher): void
    {
        $this->supplierVoucher = $supplierVoucher;
    }

    /**
     * @return string|null
     */
    public function getSupplierRmaNumber(): ?string
    {
        return $this->supplierRmaNumber;
    }

    /**
     * @param string|null $supplierRmaNumber
     */
    public function setSupplierRmaNumber(?string $supplierRmaNumber): void
    {
        $this->supplierRmaNumber = $supplierRmaNumber;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @param string|null $link
     */
    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return array|null
     */
    public function getFiles(): ?array
    {
        return $this->files;
    }

    /**
     * @param array|null $files
     */
    public function setFiles(?array $files): void
    {
        $this->files = $files;
    }

    /**
     * @return array|null
     */
    public function getTicketContent(): ?array
    {
        return $this->ticketContent;
    }

    /**
     * @param array|null $ticketContent
     */
    public function setTicketContent(?array $ticketContent): void
    {
        $this->ticketContent = $ticketContent;
    }

    /**
     * @return string|null
     */
    public function getProductName(): ?string
    {
        return $this->productName;
    }

    /**
     * @param string|null $productName
     */
    public function setProductName(?string $productName): void
    {
        $this->productName = $productName;
    }

    /**
     * @return int
     */
    public function getTicketSerialNumber(): int
    {
        return $this->ticketSerialNumber;
    }

    /**
     * @param int $ticketSerialNumber
     */
    public function setTicketSerialNumber(int $ticketSerialNumber): void
    {
        $this->ticketSerialNumber = $ticketSerialNumber;
    }

    /**
     * @return string|null
     */
    public function getProductNumber(): ?string
    {
        return $this->productNumber;
    }

    /**
     * @param string|null $productNumber
     */
    public function setProductNumber(?string $productNumber): void
    {
        $this->productNumber = $productNumber;
    }

    /**
     * @return string[]|null
     */
    public function getProductSerialNumbers(): ?array
    {
        return $this->productSerialNumbers;
    }

    /**
     * @param string[]|null $productSerialNumbers
     */
    public function setProductSerialNumbers(?array $productSerialNumbers): void
    {
        $this->productSerialNumbers = $productSerialNumbers;
    }
}
