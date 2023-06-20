<?php

namespace KitRma\Content\RmaTicketHistory;

use KitRma\Content\RmaStatus\RmaStatusEntity;
use KitRma\Content\RmaTicket\RmaTicketEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\User\UserEntity;

class RmaTicketHistoryEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $sender;

    /** @var string */
    protected $type;

    /** @var string */
    protected $message;

    /** @var array|null */
    protected $attachment;

    /** @var string */
    protected $ticketId;

    /** @var RmaTicketEntity */
    protected $ticket;

    /** @var string */
    protected $userId;

    /** @var UserEntity|null */
    protected $user;

    /** @var string */
    protected $statusId;

    /** @var RmaStatusEntity|null */
    protected $status;

    /** @var bool */
    protected $read;

    /**
     * @return string
     */
    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    /**
     * @param string $ticketId
     */
    public function setTicketId(string $ticketId): void
    {
        $this->ticketId = $ticketId;
    }

    /**
     * @return RmaTicketEntity
     */
    public function getTicket(): RmaTicketEntity
    {
        return $this->ticket;
    }

    /**
     * @param RmaTicketEntity $ticket
     */
    public function setTicket(RmaTicketEntity $ticket): void
    {
        $this->ticket = $ticket;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
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

    public function setSender(string $value): void
    {
        $this->sender = $value;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setType(string $value): void
    {
        $this->type = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setMessage(string $value): void
    {
        $this->message = $value;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->read;
    }

    /**
     * @param bool $read
     */
    public function setRead(bool $read): void
    {
        $this->read = $read;
    }

    /**
     * @return array|null
     */
    public function getAttachment(): ?array
    {
        return $this->attachment;
    }

    /**
     * @param array|null $attachment
     */
    public function setAttachment(?array $attachment): void
    {
        $this->attachment = $attachment;
    }
}
