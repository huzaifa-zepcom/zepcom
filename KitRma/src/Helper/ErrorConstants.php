<?php

declare(strict_types=1);

namespace KitRma\Helper;

class ErrorConstants
{
    // some constants that are used to show different error messages to the customer
    public const
        CASE_NOT_FOUND = 'casenotfound',
        PRODUCT_NOT_FOUND = 'productnotfound',
        ORDER_NOT_FOUND = 'ordernotfound',
        PRODUCT_AMOUNT = 'productamount',
        CUSTOMER_NOT_FOUND = 'customernotfound',
        PRODUCT_NOT_EXISTS = 'productnotexists',
        TICKET_NOT_FOUND = 'ticketnotfound',
        TICKET_ACCESS_DENIED = 'ticketaccessdenied',
        FILE_UPLOAD = 'fileuploaderror',
        FILE_SIZE = 'filesizeerror';
}
