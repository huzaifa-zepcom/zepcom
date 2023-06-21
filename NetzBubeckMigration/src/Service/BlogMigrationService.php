<?php
declare(strict_types=1);

namespace NetzBubeckMigration\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class BlogMigrationService
{

    private EntityRepositoryInterface $blogRepository;
    private Connection $connection;
    private EntityRepositoryInterface $blogMediaRepository;

    public function __construct(
        EntityRepositoryInterface $blogRepository,
        Connection $connection,
        EntityRepositoryInterface $blogMediaRepository
    ) {
        $this->blogRepository = $blogRepository;
        $this->connection = $connection;
        $this->blogMediaRepository = $blogMediaRepository;
    }

    protected function execute()
    {
        $context = new Context(new SystemSource());

        // Load the blog media data from a CSV file
        $blogMediaArray = array_map('str_getcsv', file(__DIR__ . '/../Database/blog_media.csv'));

        // SQL query to fetch blog article mappings
        $sql = <<<SQL
SELECT old_identifier, LOWER(HEX(`entity_uuid`)) AS `product_id`
FROM `swag_migration_mapping`
WHERE `entity` = 'product_mainProduct'
SQL;

        // Fetch the blog article mappings from the database
        $blogArticlesArray = $this->connection->fetchAllKeyValue($sql);
        $totalData = [];

        $blogIdWithName = [];
        $blogImages = [];

        // Process the blog media data
        array_map(static function ($i) use (&$blogIdWithName, &$blogImages) {
            if (is_numeric($i[2])) {
                $blogIdWithName[$i[2]] = [
                    'name' => $i[3],
                    'preview' => $i[5]
                ];

                $blogImages[$i[0]][] = $i[2];
            }
        }, $blogMediaArray);

        $names = array_filter(array_column($blogMediaArray, 3));

        // Fetch the new media IDs based on the file names
        $newMediaIds = $this->connection->fetchAllKeyValue(
            'select file_name, LOWER(HEX(media_id)) as media_id from swag_migration_media_file where file_name in (?)',
            [$names], [Connection::PARAM_STR_ARRAY]
        );

        $sql = <<<SQL
SELECT b.*, b.id as blog_id, ba.article_id, bt.name as tag, bc.name as user, bc.headline, bc.comment, bc.email, bc.creation_date as comment_date, bm.media_id, bm.preview FROM s_blog b 
left join s_blog_assigned_articles ba on ba.blog_id = b.id
left join s_blog_comments bc on bc.blog_id = b.id
left join s_blog_media bm on bm.blog_id = b.id
left join s_blog_tags bt on bt.blog_id = b.id
SQL;

        // Fetch the blog data from the database
        $blogData = $this->connection->fetchAllAssociative($sql);

        foreach ($blogData as $blog) {
            $blogUuid = Uuid::fromStringToHex($blog['blog_id']);
            $mediaCriteria = new Criteria();
            $mediaCriteria->addFilter(new EqualsFilter('blogid', $blogUuid));

            // Delete existing duplicate entries
            $allMediaIds = $this->blogMediaRepository->searchIds($mediaCriteria, $context)->getIds();
            foreach ($allMediaIds as $str) {
                $this->blogMediaRepository->delete([['id' => $str]], $context);
            }

            $media = isset($blogImages[$blog['blog_id']]) ? $blogImages[$blog['blog_id']] : [];
            $blog['tag'] = $blog['tag'] ?? 'Default';

            $data = [
                'id' => $blogUuid,
                'postdate' => $blog['display_date'],
                'title' => $blog['title'],
                'teaser' => mb_substr($blog['short_description'], 0, 255),
                'slug' => self::slugify($blog['title']),
                'contents' => $blog['description'] ?: 'No content found',
                'metatitle' => $blog['meta_title'],
                'metadescription' => $blog['meta_description'],
                'category' => [
                    'id' => Uuid::fromStringToHex($blog['tag']),
                    'title' => $blog['tag']
                ],
                'author' => [
                    'id' => $blog['author_id'] ? Uuid::fromStringToHex($blog['author_id']) : Uuid::randomHex(),
                    'name' => $blog['author_id'] === '51' ? 'Kai Nagel' : 'R. Bubeck & Sohn'
                ]
            ];

            if (isset($blogArticlesArray[$blog['article_id']])) {
                $data['products'] = [
                    ['id' => $blogArticlesArray[$blog['article_id']]]
                ];
            }

            if (isset($blogIdWithName[$blog['media_id']])) {
                $image = $blogIdWithName[$blog['media_id']];
                $imageName = $image['name'];
                if (isset($newMediaIds[$imageName]) && $image['preview']) {
                    $data['imageid'] = $newMediaIds[$imageName];
                }
            }

            if ($media) {
                $blogMedia = [];
                $mediaNumber = 1;
                foreach ($media as $mediaItem) {
                    if (isset($blogIdWithName[$mediaItem])) {
                        $image = $blogIdWithName[$mediaItem];
                        $imageName = $image['name'];
                        if (isset($newMediaIds[$imageName]) && !$image['preview']) {
                            $blogMedia[] = ['mediaId' => $newMediaIds[$imageName], 'number' => $mediaNumber++];
                        }
                    }
                }

                if ($blogMedia) {
                    $data['blogmedia'] = $blogMedia;
                }
            }

            $totalData[] = $data;
        }

        if ($totalData) {
            // Upsert the blog data
            $this->blogRepository->upsert($totalData, $context);
        }
    }


    public static function slugify($str, $options = array()) {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());

        $defaults = array(
            'delimiter' => '-',
            'limit' => null,
            'lowercase' => true,
            'replacements' => array(),
            'transliterate' => false,
        );

        // Merge options
        $options = array_merge($defaults, $options);

        $char_map = array(
            // German
            'Ä' => 'AE', 'Ö' => 'OE', 'Ü' => 'UE',
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
        );

        // Make custom replacements
        $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);

        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $str = str_replace(array_keys($char_map), $char_map, $str);
        }

        // Replace non-alphanumeric characters with our delimiter
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);

        // Remove duplicate delimiters
        $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);

        // Truncate slug to max. characters
        $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');

        // Remove delimiter from ends
        $str = trim($str, $options['delimiter']);

        return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
    }
}