<?php

declare(strict_types=1);

namespace KitRma\Helper;

use RuntimeException;
use Shopware\Core\Content\Media\Exception\IllegalFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\Exception\UploadException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use const PATHINFO_FILENAME;

class FileUploader
{
    protected SystemConfigService $systemConfig;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var FileSaver
     */
    private $fileSaver;

    /**
     * FileUploader constructor.
     */
    public function __construct(MediaService $mediaService, FileSaver $fileSaver, SystemConfigService $systemConfig)
    {
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->systemConfig = $systemConfig;
    }

    public function uploadFile(UploadedFile $file, Context $context, ?string $filename = null): array
    {
        $path = $file->getPathname();
        $mime = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        $size = $file->getSize();
        $filename = $filename ?? $file->getClientOriginalName();

        return $this->validateAndUpload($context, $filename, $path, $mime, $extension, $size);
    }

    private function validateAndUpload(
        Context $context,
        string $filename,
        string $path,
        string $mime,
        string $extension,
        int $size
    ): array
    {
        $configLimit = $this->systemConfig->get('KitRma.config.maxUploadSize');
        if (($size / 1024 / 1024) > $configLimit) {
            throw new RuntimeException('Filesize must be below ' . $configLimit . ' MB');
        }

        $this->checkValidFile($extension, $filename);

        $mediaFile = new MediaFile(
            $path,
            $mime,
            $extension,
            $size
        );

        try {
            $mediaId = $this->mediaService->createMediaInFolder('KitRma', $context, false);
            $context->scope(
                Context::SYSTEM_SCOPE,
                function (Context $context) use ($mediaFile, $mediaId, $filename): void {
                    $filename = pathinfo(Utility::trimString($filename), PATHINFO_FILENAME);

                    // Randomize the name to make it unique
                    $filename = sprintf("%s%s", $filename, Random::getAlphanumericString(10));
                    $this->fileSaver->persistFileToMedia(
                        $mediaFile,
                        $filename,
                        $mediaId,
                        $context
                    );
                }
            );

            return ['id' => $mediaId, 'name' => $filename, 'mediaId' => $mediaId];
        } catch (MediaNotFoundException $e) {
            throw new UploadException($e->getMessage());
        }
    }

    /**
     * Valid file types also need to updated in the `config/packages/shopware.yaml` file.
     * @param $extension
     * @param $filename
     *
     * @return void
     */
    public function checkValidFile($extension, $filename): void
    {
        $testSupportedExtension = [
            "jpg",
            "JPG",
            "jpeg",
            "JPEG",
            "png",
            "PNG",
            "webp",
            "WEBP",
            "gif",
            "GIF",
            "svg",
            "SVG",
            "bmp",
            "BMP",
            "tiff",
            "TIFF",
            "tif",
            "TIF",
            "eps",
            "webm",
            "mkv",
            "flv",
            "ogv",
            "ogg",
            "mov",
            "mp4",
            "avi",
            "wmv",
            "pdf",
            "PDF",
            "aac",
            "mp3",
            "wav",
            "flac",
            "oga",
            "wma",
            "txt",
            "doc",
            "DOC",
            "docx",
            "DOCX",
            "zip",
            "rar",
            "ico"
        ];

        if (!in_array($extension, $testSupportedExtension, false)) {
            throw new UploadException($extension . ' not supported');
        }

        if (preg_match('/.+\.ph(p([3457s]|-s)?|t|tml)/', $filename)) {
            throw new IllegalFileNameException($filename, 'contains unsupported file extension');
        }
    }

    public function getMediaAsAttachment(MediaEntity $media, Context $context): array
    {
        return $this->mediaService->getAttachment(
            $media,
            $context
        );
    }
}
