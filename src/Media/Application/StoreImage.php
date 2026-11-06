<?php

declare(strict_types=1);

namespace App\Media\Application;

use App\Media\Domain\Image;
use App\Media\Domain\ImageEncoder;
use App\Media\Domain\ImageFormat;
use App\Media\Domain\ImageUsage;
use App\Media\Domain\MediaRuleViolation;
use App\Media\Domain\MediaStorage;

/**
 * Contrôle un fichier envoyé, génère ses variantes et les stocke (ADR 0002). Le même contenu pour le même usage donne
 * la même empreinte : ses variantes déjà stockées ne sont pas réencodées.
 */
final readonly class StoreImage
{
    public const int MAX_BYTES = 10 * 1024 * 1024;
    public const array ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

    public function __construct(
        private ImageEncoder $encoder,
        private MediaStorage $storage,
    ) {
    }

    /**
     * @throws MediaRuleViolation
     */
    public function __invoke(ImageUpload $upload, ImageUsage $usage): Image
    {
        $alt = ImageUpload::alt($upload->alt, $upload->decorative);

        if (\strlen($upload->content) > self::MAX_BYTES) {
            throw new MediaRuleViolation('L\'image pèse plus de 10 Mo.');
        }
        $source = $this->encoder->inspect($upload->content);
        if (null === $source) {
            throw new MediaRuleViolation('Le fichier ne se lit pas comme une image.');
        }
        if (!\in_array($source->mimeType, self::ACCEPTED_TYPES, true)) {
            throw new MediaRuleViolation('Formats acceptés : JPEG, PNG, WebP ou AVIF.');
        }
        [$ratioWidth, $ratioHeight] = $usage->ratio();
        $croppedWidth = min($source->width, intdiv($source->height * $ratioWidth, $ratioHeight));
        if ($croppedWidth < $usage->largestWidth()) {
            throw new MediaRuleViolation(\sprintf('Recadrée au format %d:%d, l\'image doit faire au moins %d × %d px : elle fait %d × %d px.', $ratioWidth, $ratioHeight, $usage->largestWidth(), $usage->heightFor($usage->largestWidth()), $source->width, $source->height));
        }

        $key = substr(hash('sha256', $usage->value."\n".$upload->content), 0, 32);
        if (!$this->isStored($key, $usage)) {
            foreach ($this->encoder->variants($upload->content, $key, $usage) as $fileName => $content) {
                $this->storage->put($fileName, $content);
            }
        }

        return new Image($key, $alt);
    }

    private function isStored(string $key, ImageUsage $usage): bool
    {
        foreach ($usage->widths() as $width) {
            foreach (ImageFormat::cases() as $format) {
                if (!$this->storage->has(Image::fileName($key, $width, $format))) {
                    return false;
                }
            }
        }

        return true;
    }
}
