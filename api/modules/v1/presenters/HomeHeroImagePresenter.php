<?php

declare(strict_types=1);

namespace api\modules\v1\presenters;

use common\components\storage\StorageInterface;
use common\models\Artist;
use common\models\HomeHeroImage;
use common\models\MediaAsset;

final class HomeHeroImagePresenter
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function presentListItem(HomeHeroImage $homeHeroImage): array
    {
        return [
            'id' => $homeHeroImage->id,
            'image' => $this->presentImage($homeHeroImage->mediaAsset, $homeHeroImage->artist),
            'artist' => $this->presentArtist($homeHeroImage->artist),
            'focalPoint' => [
                'x' => (int) $homeHeroImage->focal_point_x,
                'y' => (int) $homeHeroImage->focal_point_y,
            ],
        ];
    }

    private function presentArtist(Artist | null $artist): array | null
    {
        if ($artist === null) {
            return null;
        }

        return [
            'url' => '/artists/' . $artist->slug,
            'translations' => $this->presentArtistTranslations($artist),
        ];
    }

    /**
     * @return array<string, array{name:string}>
     */
    private function presentArtistTranslations(Artist $artist): array
    {
        $translations = [];

        foreach ($artist->translations as $artistTranslation) {
            $language = $artistTranslation->language;
            $name = trim((string) $artistTranslation->name);

            if ($language === null || $name === '') {
                continue;
            }

            $translations[$language->code] = [
                'name' => $name,
            ];
        }

        ksort($translations);

        return $translations;
    }

    private function presentImage(MediaAsset | null $mediaAsset, Artist | null $artist): array | null
    {
        if ($mediaAsset === null) {
            return null;
        }

        return [
            'id' => $mediaAsset->id,
            'url' => $this->storage->getPublicUrl($mediaAsset->path),
            'alt' => $artist === null ? $mediaAsset->original_name : $artist->default_name,
        ];
    }
}
