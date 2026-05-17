<?php

declare(strict_types=1);

namespace backend\widgets;

use backend\assets\ArtistGalleryAsset;
use backend\models\ArtistGalleryForm;
use yii\base\Widget;
use yii\bootstrap5\ActiveForm;

final class ArtistGalleryInputWidget extends Widget
{
    public ActiveForm $form;
    public ArtistGalleryForm $galleryForm;

    public function run(): string
    {
        ArtistGalleryAsset::register($this->getView());

        return $this->render('artist-gallery-input', [
            'form' => $this->form,
            'galleryForm' => $this->galleryForm,
            'widgetId' => $this->getId(),
        ]);
    }
}
