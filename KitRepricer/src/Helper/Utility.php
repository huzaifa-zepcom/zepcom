<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate\Helper;

use DateInterval;
use DateTime;
use DateTimeZone;
use NumberFormatter;
use Shopware\Core\Content\ImportExport\Exception\FileNotReadableException;

class Utility
{
    public const DATE_FORMAT = 'd.m.Y H:i:s';
    private const ID_PARAMS = [
        'manufacturerIds',
        'categoryIds',
        'supplierIds'
    ];
    private const BOOLEAN_PARAMS = [
        'active',
        'adjustIfInStock',
        'adjustWithCompetitorInventory',
        'excluded'
    ];
    private const STRING_PARAMS = [
        'name',
        'id',
        'type',
        'productName',
        'productDesc',
        'productNumbers'
    ];
    private const DATE_PARAMS = [
        'created_at'
    ];
    public const TIMEZONE = 'Europe/Berlin';

    /**
     * @param $filePath
     * @param string $delimeter
     *
     * @return array
     */
    public static function getCsvFormattedData($filePath, $delimeter = ';'): array
    {
        if (($handle = fopen($filePath, 'rb')) === false) {
            throw new FileNotReadableException($filePath);
        }

        $isHeader = false;
        $headers = [];
        $formatted = [];
        while (($data = fgetcsv($handle, 0, $delimeter)) !== false) {
            $data = self::arrayToUTF8($data);
            $csv = [];
            if (!$isHeader) {
                $isHeader = true;
                $headers = $data;
            } else {
                foreach ($data as $i => $datum) {
                    $csv[$i] = html_entity_decode($datum);
                }

                $temporary = array_intersect_key($headers, $csv);
                $formatted[] = array_combine(array_values($temporary), array_values($csv));
            }
        }

        fclose($handle);

        return $formatted;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public static function arrayToUTF8(array $input): array
    {
        array_walk_recursive(
            $input,
            static function (&$value) {
                return self::sanitizeInput($value);
            }
        );

        return $input;
    }

    public static function cleanString($string): string
    {
        $string = str_replace('\\', '', addslashes(strip_tags($string)));
        $string = str_replace(["\n", "\r", "\t"], ' ', $string);

        return trim($string);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public static function removeControlCharacters($value): string
    {
        $result = preg_replace('/[\x00-\x1F]|[\x7F]|[\xC2\x80-\xC2\x9F]/u', '', $value);

        return $result ?: $value;
    }

    /**
     * @param $input
     *
     * @return string
     */
    public static function sanitizeInput($input): string
    {
        $fileType = mb_detect_encoding($input);

        if ($fileType) {
            return trim(($fileType === "UTF-8") ? $input : iconv($fileType, 'ascii//translit', $input));
        }

        return trim(iconv('ISO-8859-1', 'ascii//translit', $input));
    }

    public static function priceToFloat($number): float
    {
        if (!$number) {
            return 0;
        }

        $fmt = new NumberFormatter('de_DE', NumberFormatter::DECIMAL);

        return (float)$fmt->parse($number);
    }

    public static function removeSpecialCharacters($name, $replace = '-')
    {
        $name = iconv('utf-8', 'ascii//translit', $name);
        $name = preg_replace('#[^A-z0-9\-_]#', $replace, $name);
        $name = preg_replace('#-{2,}#', $replace, $name);
        $name = trim($name, $replace);

        return mb_substr($name, 0, 180);
    }

    public static function clean($string)
    {
        $string = iconv('utf-8', 'ascii//translit', $string);
        $string = str_replace(' ', '_', strtolower($string));
        $string = preg_replace('/[^A-Za-z0-9\_]/', '', $string);

        return str_replace('_', '', ucwords($string, '_'));
    }

    public static function flatten(array $array): array
    {
        $return = [];
        array_walk_recursive(
            $array,
            static function ($a) use (&$return) {
                $return[] = $a;
            }
        );

        return $return;
    }

    public static function progressBar($done, $total): void
    {
        $perc = floor(($done / $total) * 100);
        $left = 100 - $perc;
        $write = sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total", "", "");
        fwrite(STDERR, $write);
        if ($done === $total) {
            echo PHP_EOL;
        }
    }

    public static function convertDateInArrayToDE($data): array
    {
        foreach ($data as $i => $datum) {
            foreach ($datum as $key => $value) {
                if (in_array($key, self::DATE_PARAMS, false)) {
                    $formattedDate = self::convertDateToTimezone($value);
                    $data[$i][$key] = $formattedDate;
                }
            }
        }

        return $data;
    }

    public static function parseRulesData($data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::ID_PARAMS, false)) {
                $data[$key] = explode(',', $data[$key]);
                // Convert it to array for proper rendering in the admin module
                if (!is_array($data[$key])) {
                    $data[$key] = [$data[$key]];
                }

                $data[$key] = array_filter($data[$key]);
            } elseif (in_array($key, self::BOOLEAN_PARAMS, false)) {
                $data[$key] = $data[$key] ? true : false;
            } elseif (!in_array($key, self::STRING_PARAMS, false)) {
                $data[$key] = (float)$data[$key];
            }
        }

        return $data;
    }

    public static function convertDateToTimezone($value = 'now'): string
    {
        $userTimezone = new DateTimeZone(self::TIMEZONE);
        $createdOn = new DateTime($value);
        $offset = $userTimezone->getOffset($createdOn);
        $myInterval = DateInterval::createFromDateString($offset . ' seconds');
        $createdOn->add($myInterval);

        return $createdOn->format(self::DATE_FORMAT);
    }
}
