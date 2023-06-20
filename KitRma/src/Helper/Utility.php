<?php

declare(strict_types=1);

namespace KitRma\Helper;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

class Utility
{
    public const TIMEZONE = 'Europe/Berlin';
    public const DATE_TIME_FORMAT = 'd.m.Y H:i:s';
    public const DATE_FORMAT = 'd.m.Y';
    public const SENDER_KLARSICHT = 'KLARSICHT';
    public const SENDER_CUSTOMER = 'CUSTOMER';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_INTERNAL = 'internal';

    public static function getTemplatesData(): array
    {
        return [
            'kit.rma.internal.answer' => [
                'name' => 'KitRmaInternalAnswer',
                'subject' => 'Neue Antwort auf RMA-Antrag'

            ],
            'kit.rma.external.answer' => [
                'name' => 'KitRmaExternalAnswer',
                'subject' => 'Ihre Reklamation / Rücknahmeanfrage {{ rma.rma_number }}'
            ],
            'kit.rma.internal.new' => [
                'name' => 'KitRmaInternalNew',
                'subject' => 'Neuer RMA-Antrag'
            ],
            'kit.rma.external' => [
                'name' => 'KitRmaExternal',
                'subject' => 'Neue Information zu Ihrer Reklamation / Rücknahmeanfrage {{ rma.rma_number }}'
            ]
        ];
    }

    public static function convertDateToTimezone($value = 'now', $format = self::DATE_TIME_FORMAT): string
    {
        if ($value === null) {
            $value = 'now';
        }

        $userTimezone = new DateTimeZone(self::TIMEZONE);
        $createdOn = $value instanceof DateTimeImmutable ? $value : new DateTime($value);
        $offset = $userTimezone->getOffset($createdOn);
        $myInterval = DateInterval::createFromDateString($offset . ' seconds');
        $createdOn->add($myInterval);

        return $createdOn->format($format);
    }

    public static function convertNameToFieldName(?string $string): ?string
    {
        if (!$string) {
            return null;
        }
        $string = str_replace('\\', '', addslashes(strip_tags($string)));
        $string = str_replace(["\n", "\r", "\t", ' '], '_', $string);
        $string = self::removeControlCharacters($string);

        return trim($string);
    }

    public static function removeControlCharacters(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        $result = preg_replace('/[\x{0000}-\x{001F}]|[\x{007F}]|[\x{0080}-\x{009F}]/u', '', $string);

        return $result ?? $string;
    }

    public static function trimString(?string $string, string $replace = ''): ?string
    {
        if (!$string) {
            return null;
        }

        $string = self::cleanString($string);
        $string = str_replace(' ', $replace, $string);

        return trim($string);
    }

    public static function truncate(string $string)
    {
        return (strlen($string) > 70) ? substr($string, 0, 70) . "..." : $string;
    }

    public static function cleanString(?string $string): ?string
    {
        if (!$string) {
            return null;
        }
        $string = str_replace('\\', '', addslashes(strip_tags($string)));
        $string = str_replace(["\n", "\r", "\t", '_'], ' ', $string);
        $string = self::removeControlCharacters($string);

        return trim($string);
    }

    public static function removeSpecialChars(string $string): string
    {
        return preg_replace('/[^äöüA-Za-z0-9:_-]/u', '', $string);
    }
}
