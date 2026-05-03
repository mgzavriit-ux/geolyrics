<?php

declare(strict_types=1);

namespace backend\models;

use common\models\Artist;
use common\models\Language;
use common\models\Recording;
use common\models\RecordingArtist;
use common\models\Song;
use common\models\SongLine;
use common\models\SongLineTranslation;
use common\models\SongTranslation;
use common\services\CatalogSlugGenerator;
use common\services\RecordingRemover;
use common\services\RecordingMediaUploader;
use Yii;
use yii\base\Model;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

final class SongEditorForm extends Model
{
    private const ORIGINAL_LANGUAGE_CODE = 'ka';
    private const SONG_TEXT_FORM_NAME = 'songFullText';

    /**
     * @var Artist[]
     */
    private array $artists;

    /**
     * @var Language[]
     */
    private array $languages;

    /**
     * @var RecordingArtist[]
     */
    private array $recordingArtistFlatModels = [];

    /**
     * @var array<int, array<int, int>>
     */
    private array $recordingArtistInputIndexes = [];

    /**
     * @var array<int, RecordingArtist[]>
     */
    private array $recordingArtistModels = [];

    /**
     * @var Recording[]
     */
    private array $recordingModels = [];

    private RecordingArtist $recordingArtistPrototype;
    /**
     * @var RecordingMediaUploadForm[]
     */
    private array $recordingMediaUploadForms = [];
    /**
     * @var array<int, bool>
     */
    private array $recordingDeleteFlags = [];
    private Recording $recordingPrototype;
    private RecordingRemover $recordingRemover;
    private Song $song;
    private CatalogSlugGenerator $slugGenerator;
    private RecordingMediaUploader $recordingMediaUploader;

    /**
     * @var array<int, bool>
     */
    private array $songTextDirtyByLanguageId = [];

    /**
     * @var array<int, string>
     */
    private array $songTextValuesByLanguageId = [];

    /**
     * @var SongLineTranslation[]
     */
    private array $songLineTranslationFlatModels = [];

    /**
     * @var array<int, array<int, int>>
     */
    private array $songLineTranslationInputIndexes = [];

    /**
     * @var array<int, SongLineTranslation[]>
     */
    private array $songLineTranslationModels = [];

    /**
     * @var SongLine[]
     */
    private array $songLineModels = [];

    /**
     * @var SongTranslation[]
     */
    private array $songTranslationModels = [];

    /**
     * @var array<int, int>
     */
    private array $songTranslationInputIndexes = [];

    /**
     * @param Artist[] $artists
     * @param Language[] $languages
     */
    public function __construct(Song $song, array $languages, array $artists, array $config = [])
    {
        $this->artists = $artists;
        $this->languages = $languages;
        $this->recordingArtistPrototype = new RecordingArtist();
        $this->recordingPrototype = new Recording();
        $this->recordingRemover = new RecordingRemover($this->getStorage());
        $this->recordingMediaUploader = new RecordingMediaUploader($this->getStorage());
        $this->song = $song;
        $this->slugGenerator = new CatalogSlugGenerator();

        parent::__construct($config);

        $this->applyDefaultOriginalLanguage();
        $this->initializeModels();
        $this->prepareDefaults();
        $this->rebuildSongTextValues();
    }

    public function load($data, $formName = null): bool
    {
        $this->synchronizeSongLineModelsWithData($data);
        $this->synchronizeRecordingModelsWithData($data);
        $this->loadRecordingDeleteFlags($data);
        $this->synchronizeRecordingArtistModelsWithData($data);

        $isLoaded = $this->song->load($data);
        $isLoaded = Model::loadMultiple($this->songTranslationModels, $data) || $isLoaded;
        $isLoaded = Model::loadMultiple($this->songLineModels, $data) || $isLoaded;
        $isLoaded = Model::loadMultiple($this->songLineTranslationFlatModels, $data) || $isLoaded;
        $isLoaded = Model::loadMultiple($this->recordingModels, $data) || $isLoaded;
        $isLoaded = Model::loadMultiple($this->recordingArtistFlatModels, $data) || $isLoaded;

        if ($isLoaded) {
            $this->rebuildSongTextValues();
            $this->loadSongTextData($data);
            $this->applySongTextValuesToLineModels();
            $this->loadRecordingMediaUploadFiles();
            $this->prepareDefaults();
            $this->rebuildSongTextValues();
        }

        return $isLoaded;
    }

    public function save(): void
    {
        $this->prepareAutoSlugs();

        $transaction = $this->getDb()->beginTransaction();

        try {
            $this->song->save(false);
            $this->saveSongTranslations();
            $this->saveSongLines();
            $this->deleteOriginalLanguageTranslations();
            $this->saveRecordings();
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $this->prepareDefaults();
        $this->prepareAutoSlugs();

        $isValid = $this->song->validate();
        $isValid = Model::validateMultiple($this->songTranslationModels) && $isValid;
        $isValid = Model::validateMultiple($this->songLineModels) && $isValid;
        $isValid = Model::validateMultiple($this->songLineTranslationFlatModels) && $isValid;
        $isValid = Model::validateMultiple($this->recordingMediaUploadForms) && $isValid;
        $isValid = Model::validateMultiple($this->recordingArtistFlatModels) && $isValid;
        $isValid = $this->validateRecordingModels() && $isValid;
        $isValid = $this->validateSongLineModels() && $isValid;
        $isValid = $this->validateRecordingArtistDuplicates() && $isValid;

        return $isValid;
    }

    public function getArtistItems(): array
    {
        $items = [];

        foreach ($this->artists as $artist) {
            $items[$artist->id] = $artist->default_name;
        }

        return $items;
    }

    public function getLanguageItems(): array
    {
        $items = [];

        foreach ($this->languages as $language) {
            $items[$language->id] = $language->name;
        }

        return $items;
    }

    public function getLanguageLabels(): array
    {
        $items = [];

        foreach ($this->languages as $language) {
            $items[$language->id] = $language->native_name . ' (' . $language->code . ')';
        }

        return $items;
    }

    public function getPublicationStatusItems(): array
    {
        return $this->song->getPublicationStatusList();
    }

    public function getRecordingArtistFlatModels(): array
    {
        return $this->recordingArtistFlatModels;
    }

    public function getRecordingArtistInputIndexes(int $recordingIndex): array
    {
        return $this->recordingArtistInputIndexes[$recordingIndex] ?? [];
    }

    public function getRecordingArtistVisibleFlatIndexes(int $recordingIndex): array
    {
        $indexes = [];

        foreach ($this->getRecordingArtistInputIndexes($recordingIndex) as $artistFlatIndex) {
            if ($this->shouldRenderRecordingArtist($artistFlatIndex)) {
                $indexes[] = $artistFlatIndex;
            }
        }

        return $indexes;
    }

    public function getRecordingArtistRoleItems(): array
    {
        return $this->recordingArtistPrototype->getRoleList();
    }

    public function getRecordingMediaUploadForm(int $recordingIndex): RecordingMediaUploadForm
    {
        return $this->recordingMediaUploadForms[$recordingIndex] ?? new RecordingMediaUploadForm();
    }

    public function isRecordingMarkedForDelete(int $recordingIndex): bool
    {
        return $this->recordingDeleteFlags[$recordingIndex] ?? false;
    }

    public function getRecordingModels(): array
    {
        return $this->recordingModels;
    }

    public function getRecordingVisibleIndexes(): array
    {
        $indexes = [];

        foreach (array_keys($this->recordingModels) as $recordingIndex) {
            if ($this->shouldRenderRecording($recordingIndex)) {
                $indexes[] = $recordingIndex;
            }
        }

        return $indexes;
    }

    public function getRecordingPublicationStatusItems(): array
    {
        return $this->recordingPrototype->getPublicationStatusList();
    }

    public function getRecordingTypeItems(): array
    {
        return $this->recordingPrototype->getRecordingTypeList();
    }

    public function getSong(): Song
    {
        return $this->song;
    }

    public function getSongTranslationInputIndex(int $languageId): int | null
    {
        return $this->songTranslationInputIndexes[$languageId] ?? null;
    }

    public function getSongTranslationLanguageItems(): array
    {
        $items = [];
        $labels = $this->getLanguageLabels();

        foreach ($this->songTranslationModels as $translationModel) {
            $languageId = (int) $translationModel->language_id;
            $items[$languageId] = $labels[$languageId] ?? (string) $languageId;
        }

        return $items;
    }

    public function getSongTranslationModelByLanguageId(int $languageId): SongTranslation | null
    {
        $translationIndex = $this->getSongTranslationInputIndex($languageId);

        if ($translationIndex === null) {
            return null;
        }

        return $this->songTranslationModels[$translationIndex] ?? null;
    }

    public function getSongLineModels(): array
    {
        return $this->songLineModels;
    }

    public function getSongLineTranslationLanguageItems(): array
    {
        return $this->getSongTranslationLanguageItems();
    }

    public function getSongLineVisibleIndexes(): array
    {
        $indexes = [];

        foreach (array_keys($this->songLineModels) as $lineIndex) {
            if ($this->shouldRenderSongLine($lineIndex)) {
                $indexes[] = $lineIndex;
            }
        }

        return $indexes;
    }

    public function shouldRenderRecording(int $recordingIndex): bool
    {
        if ($this->isRecordingMarkedForDelete($recordingIndex)) {
            return false;
        }

        if ($this->isRecordingFilled($recordingIndex)) {
            return true;
        }

        if ($this->recordingModels[$recordingIndex]->hasErrors()) {
            return true;
        }

        if ($this->getRecordingMediaUploadForm($recordingIndex)->hasErrors()) {
            return true;
        }

        foreach ($this->recordingArtistModels[$recordingIndex] ?? [] as $artistModel) {
            if ($artistModel->hasErrors()) {
                return true;
            }
        }

        return false;
    }

    public function getSongLineTranslationFlatModels(): array
    {
        return $this->songLineTranslationFlatModels;
    }

    public function getSongLineTranslationInputIndexes(int $lineIndex): array
    {
        return $this->songLineTranslationInputIndexes[$lineIndex] ?? [];
    }

    public function getSongTranslationModels(): array
    {
        return $this->songTranslationModels;
    }

    public function getInitialSongTranslationLanguageId(): int | null
    {
        foreach ($this->songTranslationModels as $translationModel) {
            if ($translationModel->hasErrors()) {
                return (int) $translationModel->language_id;
            }
        }

        if ($this->songTranslationModels === []) {
            return null;
        }

        return (int) $this->songTranslationModels[0]->language_id;
    }

    public function getInitialSongTextLanguageId(): int | null
    {
        $originalLanguageId = $this->getOriginalLanguageId();

        if ($originalLanguageId !== null) {
            return $originalLanguageId;
        }

        foreach ($this->languages as $language) {
            return $language->id;
        }

        return null;
    }

    public function getSongTextLanguageItems(): array
    {
        $items = [];
        $labels = $this->getLanguageLabels();

        foreach ($this->languages as $language) {
            $items[$language->id] = $labels[$language->id] ?? (string) $language->id;
        }

        return $items;
    }

    public function getSongTextValueByLanguageId(int $languageId): string
    {
        return $this->songTextValuesByLanguageId[$languageId] ?? '';
    }

    public function hasSongLineErrors(): bool
    {
        foreach ($this->songLineModels as $lineModel) {
            if ($lineModel->hasErrors()) {
                return true;
            }
        }

        foreach ($this->songLineTranslationFlatModels as $translationModel) {
            if ($translationModel->hasErrors()) {
                return true;
            }
        }

        return false;
    }

    public function shouldRenderSongLine(int $lineIndex): bool
    {
        if ($this->isSongLineFilled($lineIndex)) {
            return true;
        }

        if ($this->songLineModels[$lineIndex]->hasErrors()) {
            return true;
        }

        foreach ($this->songLineTranslationModels[$lineIndex] ?? [] as $translationModel) {
            if ($translationModel->hasErrors()) {
                return true;
            }
        }

        return false;
    }

    private function applyDefaultOriginalLanguage(): void
    {
        if ($this->song->original_language_id !== null) {
            return;
        }

        foreach ($this->languages as $language) {
            if ($language->code === self::ORIGINAL_LANGUAGE_CODE) {
                $this->song->original_language_id = $language->id;

                return;
            }
        }
    }

    private function createEmptyRecordingModels(int $count): array
    {
        $models = [];

        for ($index = 0; $index < $count; ++$index) {
            $model = new Recording();
            $model->scenario = Recording::SCENARIO_EMBEDDED_SONG;
            $models[] = $model;
        }

        return $models;
    }

    private function createEmptySongLineModels(int $count): array
    {
        $models = [];

        for ($index = 0; $index < $count; ++$index) {
            $models[] = new SongLine();
        }

        return $models;
    }

    private function findRecordingArtistModels(): array
    {
        if ($this->song->isNewRecord) {
            return [];
        }

        return RecordingArtist::find()
            ->andWhere(['recording_id' => ArrayHelper::getColumn($this->recordingModels, 'id')])
            ->orderBy(['sort_order' => SORT_ASC, 'artist_id' => SORT_ASC])
            ->all();
    }

    private function findRecordingModels(): array
    {
        if ($this->song->isNewRecord) {
            return [];
        }

        $models = Recording::find()
            ->with(['coverMediaAsset', 'recordingMediaEntries.mediaAsset'])
            ->andWhere(['song_id' => $this->song->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        foreach ($models as $model) {
            $model->scenario = Recording::SCENARIO_EMBEDDED_SONG;
        }

        return $models;
    }

    private function findSongLineModels(): array
    {
        if ($this->song->isNewRecord) {
            return [];
        }

        return SongLine::find()
            ->andWhere(['song_id' => $this->song->id])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    private function findSongLineTranslationModels(): array
    {
        $lineIds = ArrayHelper::getColumn($this->songLineModels, 'id');

        if ($lineIds === []) {
            return [];
        }

        return SongLineTranslation::find()
            ->andWhere(['song_line_id' => $lineIds])
            ->all();
    }

    private function findSongTranslationModels(): array
    {
        if ($this->song->isNewRecord) {
            return [];
        }

        return SongTranslation::find()
            ->andWhere(['song_id' => $this->song->id])
            ->all();
    }

    private function getDb(): Connection
    {
        return Yii::$app->db;
    }

    private function getStorage(): \common\components\storage\StorageInterface
    {
        /** @var \common\app\WebApplication $app */
        $app = Yii::$app;

        return $app->storage;
    }

    private function getOriginalLanguageId(): int | null
    {
        if ($this->song->original_language_id !== null) {
            return (int) $this->song->original_language_id;
        }

        foreach ($this->languages as $language) {
            if ($language->code === self::ORIGINAL_LANGUAGE_CODE) {
                return $language->id;
            }
        }

        return null;
    }

    private function getLineSortOrder(int $lineIndex): int
    {
        $sortOrder = $this->songLineModels[$lineIndex]->sort_order;

        if ($sortOrder !== null) {
            return $sortOrder;
        }

        return ($lineIndex + 1) * 10;
    }

    private function hasLineTranslationContent(int $lineIndex): bool
    {
        foreach ($this->songLineTranslationModels[$lineIndex] ?? [] as $translationModel) {
            if ($translationModel->hasContent()) {
                return true;
            }
        }

        return false;
    }

    private function hasRecordingArtistContent(int $recordingIndex): bool
    {
        foreach ($this->recordingArtistModels[$recordingIndex] ?? [] as $artistModel) {
            if ($artistModel->hasContent()) {
                return true;
            }
        }

        return false;
    }

    private function getTranslationLanguages(): array
    {
        $originalLanguageId = $this->getOriginalLanguageId();
        $languages = [];

        foreach ($this->languages as $language) {
            if ($language->code === self::ORIGINAL_LANGUAGE_CODE) {
                continue;
            }

            if ($originalLanguageId !== null && $language->id === $originalLanguageId) {
                continue;
            }

            $languages[] = $language;
        }

        return $languages;
    }

    private function initializeModels(): void
    {
        $this->initializeSongTranslationModels();
        $this->initializeSongLineModels();
        $this->initializeRecordingModels();
    }

    private function initializeRecordingArtistModels(): void
    {
        $artistModelsByRecordingId = [];

        foreach ($this->findRecordingArtistModels() as $artistModel) {
            $artistModelsByRecordingId[$artistModel->recording_id][] = $artistModel;
        }

        foreach ($this->recordingModels as $recordingIndex => $recordingModel) {
            $models = array_values($artistModelsByRecordingId[$recordingModel->id] ?? []);

            foreach ($models as $artistSlotIndex => $artistModel) {
                if ($recordingModel->id !== null) {
                    $artistModel->recording_id = $recordingModel->id;
                }

                $flatIndex = count($this->recordingArtistFlatModels);
                $this->recordingArtistFlatModels[] = $artistModel;
                $this->recordingArtistInputIndexes[$recordingIndex][$artistSlotIndex] = $flatIndex;
                $this->recordingArtistModels[$recordingIndex][] = $artistModel;
            }
        }
    }

    private function initializeRecordingModels(): void
    {
        $this->recordingModels = array_values($this->findRecordingModels());
        $this->initializeRecordingMediaUploadForms();
        $this->initializeRecordingArtistModels();
    }

    private function initializeRecordingMediaUploadForms(): void
    {
        foreach (array_keys($this->recordingModels) as $recordingIndex) {
            $this->recordingMediaUploadForms[$recordingIndex] = $this->recordingMediaUploadForms[$recordingIndex] ?? new RecordingMediaUploadForm();
        }
    }

    private function initializeSongLineModels(): void
    {
        $models = $this->findSongLineModels();
        $this->songLineModels = array_values($models);
        $this->rebuildSongLineTranslationModels();
    }

    private function initializeSongTranslationModels(): void
    {
        $translationModels = ArrayHelper::index($this->findSongTranslationModels(), 'language_id');

        foreach ($this->getTranslationLanguages() as $language) {
            $translationModel = $translationModels[$language->id] ?? new SongTranslation();
            $translationModel->language_id = $language->id;

            if ($this->song->id !== null) {
                $translationModel->song_id = $this->song->id;
            }

            $this->songTranslationInputIndexes[$language->id] = count($this->songTranslationModels);
            $this->songTranslationModels[] = $translationModel;
        }
    }

    private function isRecordingFilled(int $recordingIndex): bool
    {
        return $this->recordingModels[$recordingIndex]->hasContent()
            || $this->hasRecordingArtistContent($recordingIndex)
            || $this->getRecordingMediaUploadForm($recordingIndex)->hasContent();
    }

    private function isSongLineFilled(int $lineIndex): bool
    {
        return $this->songLineModels[$lineIndex]->hasContent() || $this->hasLineTranslationContent($lineIndex);
    }

    private function prepareDefaults(): void
    {
        $this->applyDefaultOriginalLanguage();

        if (trim((string) $this->song->publication_status) === '') {
            $this->song->publication_status = Song::PUBLICATION_STATUS_DRAFT;
        }

        foreach ($this->recordingModels as $recordingIndex => $recordingModel) {
            if ($this->isRecordingMarkedForDelete($recordingIndex)) {
                continue;
            }

            if ($this->isRecordingFilled($recordingIndex) === false) {
                continue;
            }

            if (trim((string) $recordingModel->recording_type) === '') {
                $recordingModel->recording_type = Recording::TYPE_AUDIO;
            }

            if (trim((string) $recordingModel->publication_status) === '') {
                $recordingModel->publication_status = Recording::PUBLICATION_STATUS_DRAFT;
            }
        }

        foreach ($this->recordingArtistModels as $recordingArtists) {
            foreach ($recordingArtists as $artistIndex => $artistModel) {
                if ($artistModel->artist_id !== null && trim((string) $artistModel->role) === '') {
                    $artistModel->role = RecordingArtist::ROLE_PERFORMER;
                }

                if ($artistModel->hasContent() && $artistModel->sort_order === null) {
                    $artistModel->sort_order = ($artistIndex + 1) * 10;
                }
            }
        }
    }

    private function saveRecordingArtists(int $recordingIndex, Recording $recordingModel): void
    {
        foreach ($this->recordingArtistModels[$recordingIndex] ?? [] as $artistModel) {
            if ($artistModel->hasContent() === false) {
                if ($artistModel->isNewRecord === false) {
                    $artistModel->delete();
                }

                continue;
            }

            $artistModel->recording_id = $recordingModel->id;
            $artistModel->save(false);
        }
    }

    private function saveRecordingMedia(int $recordingIndex, Recording $recordingModel): void
    {
        $uploadForm = $this->getRecordingMediaUploadForm($recordingIndex);

        if ($uploadForm->audioFile instanceof UploadedFile) {
            $this->recordingMediaUploader->uploadAudioFile($recordingModel, $uploadForm->audioFile);
        }

        if ($uploadForm->coverFile instanceof UploadedFile) {
            $this->recordingMediaUploader->uploadCoverFile($recordingModel, $uploadForm->coverFile);
        }

        if ($uploadForm->videoFile instanceof UploadedFile) {
            $this->recordingMediaUploader->uploadVideoFile($recordingModel, $uploadForm->videoFile);
        }
    }

    private function saveRecordings(): void
    {
        foreach ($this->recordingModels as $recordingIndex => $recordingModel) {
            if ($this->isRecordingMarkedForDelete($recordingIndex)) {
                if ($recordingModel->isNewRecord === false) {
                    $this->recordingRemover->deleteRecording($recordingModel);
                }

                continue;
            }

            if ($this->isRecordingFilled($recordingIndex) === false) {
                if ($recordingModel->isNewRecord === false) {
                    $this->recordingRemover->deleteRecording($recordingModel);
                }

                continue;
            }

            $recordingModel->song_id = $this->song->id;
            $recordingModel->save(false);
            $this->saveRecordingMedia($recordingIndex, $recordingModel);
            $this->saveRecordingArtists($recordingIndex, $recordingModel);
        }
    }

    private function saveSongLineTranslations(int $lineIndex, SongLine $lineModel): void
    {
        foreach ($this->songLineTranslationModels[$lineIndex] ?? [] as $translationModel) {
            if ($translationModel->hasContent() === false) {
                if ($translationModel->isNewRecord === false) {
                    $translationModel->delete();
                }

                continue;
            }

            $translationModel->song_line_id = $lineModel->id;
            $translationModel->save(false);
        }
    }

    private function saveSongLines(): void
    {
        foreach ($this->songLineModels as $lineIndex => $lineModel) {
            if ($this->isSongLineFilled($lineIndex) === false) {
                if ($lineModel->isNewRecord === false) {
                    $lineModel->delete();
                }

                continue;
            }

            $lineModel->song_id = $this->song->id;
            $lineModel->sort_order = $this->getLineSortOrder($lineIndex);
            $lineModel->save(false);
            $this->saveSongLineTranslations($lineIndex, $lineModel);
        }
    }

    private function saveSongTranslations(): void
    {
        foreach ($this->songTranslationModels as $translationModel) {
            if ($translationModel->hasContent() === false) {
                if ($translationModel->isNewRecord === false) {
                    $translationModel->delete();
                }

                continue;
            }

            $translationModel->song_id = $this->song->id;
            $translationModel->save(false);
        }
    }

    private function deleteOriginalLanguageTranslations(): void
    {
        $originalLanguageId = $this->getOriginalLanguageId();

        if ($originalLanguageId === null || $this->song->id === null) {
            return;
        }

        SongTranslation::deleteAll([
            'song_id' => $this->song->id,
            'language_id' => $originalLanguageId,
        ]);

        $lineIds = SongLine::find()
            ->select('id')
            ->andWhere(['song_id' => $this->song->id])
            ->column();

        if ($lineIds === []) {
            return;
        }

        SongLineTranslation::deleteAll([
            'song_line_id' => $lineIds,
            'language_id' => $originalLanguageId,
        ]);
    }

    private function createRecordingArtistModels(int $count): array
    {
        $models = [];

        for ($index = 0; $index < $count; ++$index) {
            $models[] = new RecordingArtist();
        }

        return $models;
    }

    private function validateRecordingArtistDuplicates(): bool
    {
        $isValid = true;

        foreach ($this->recordingArtistModels as $recordingArtists) {
            $keys = [];

            foreach ($recordingArtists as $artistModel) {
                if ($artistModel->artist_id === null || trim((string) $artistModel->role) === '') {
                    continue;
                }

                $key = $artistModel->artist_id . '|' . trim((string) $artistModel->role);

                if (isset($keys[$key])) {
                    $artistModel->addError('artist_id', 'Такая связь исполнителя уже добавлена.');
                    $isValid = false;
                    continue;
                }

                $keys[$key] = true;
            }
        }

        return $isValid;
    }

    private function validateRecordingModels(): bool
    {
        $isValid = true;
        $slugs = [];

        foreach ($this->recordingModels as $recordingIndex => $recordingModel) {
            if ($this->isRecordingMarkedForDelete($recordingIndex)) {
                continue;
            }

            if ($this->isRecordingFilled($recordingIndex) === false) {
                continue;
            }

            $recordingModel->scenario = Recording::SCENARIO_EMBEDDED_SONG;

            if ($recordingModel->validate() === false) {
                $isValid = false;
            }

            $slug = trim((string) $recordingModel->slug);

            if ($slug === '') {
                continue;
            }

            if (isset($slugs[$slug])) {
                $recordingModel->addError('slug', 'Такой slug уже используется в этой форме.');
                $isValid = false;
                continue;
            }

            $slugs[$slug] = true;
        }

        return $isValid;
    }

    private function validateSongLineModels(): bool
    {
        $isValid = true;
        $sortOrders = [];

        foreach ($this->songLineModels as $lineIndex => $lineModel) {
            if ($this->isSongLineFilled($lineIndex) === false) {
                continue;
            }

            if (trim((string) $lineModel->original_text) === '') {
                $lineModel->addError('original_text', 'Укажите исходную строку песни.');
                $isValid = false;
            }

            $sortOrder = $this->getLineSortOrder($lineIndex);

            if (isset($sortOrders[$sortOrder])) {
                $lineModel->addError('sort_order', 'Порядок строки должен быть уникальным.');
                $isValid = false;
                continue;
            }

            $sortOrders[$sortOrder] = true;
        }

        return $isValid;
    }

    private function rebuildSongLineTranslationModels(): void
    {
        $translationModelsByLineId = [];

        foreach ($this->findSongLineTranslationModels() as $translationModel) {
            $translationModelsByLineId[$translationModel->song_line_id][(int) $translationModel->language_id] = $translationModel;
        }

        $existingTranslationModels = $this->songLineTranslationModels;
        $this->songLineTranslationFlatModels = [];
        $this->songLineTranslationInputIndexes = [];
        $this->songLineTranslationModels = [];

        foreach ($this->songLineModels as $lineIndex => $lineModel) {
            foreach ($this->getTranslationLanguages() as $languageOffset => $language) {
                $translationModel = $existingTranslationModels[$lineIndex][$language->id]
                    ?? $translationModelsByLineId[$lineModel->id][(int) $language->id]
                    ?? new SongLineTranslation();

                $translationModel->language_id = $language->id;

                if ($lineModel->id !== null) {
                    $translationModel->song_line_id = $lineModel->id;
                }

                $flatIndex = count($this->songLineTranslationFlatModels);
                $this->songLineTranslationFlatModels[] = $translationModel;
                $this->songLineTranslationInputIndexes[$lineIndex][$language->id] = $flatIndex;
                $this->songLineTranslationModels[$lineIndex][$language->id] = $translationModel;
            }
        }
    }

    private function synchronizeSongLineModelsWithData(array $data): void
    {
        $formName = (new SongLine())->formName();
        $postedSongLineRows = $data[$formName] ?? null;
        $postedSongLineCount = is_array($postedSongLineRows) ? count($postedSongLineRows) : 0;
        $postedSongTextLineCount = $this->findPostedSongTextLineCount($data);
        $requiredSongLineCount = max($postedSongLineCount, $postedSongTextLineCount);
        $currentSongLineCount = count($this->songLineModels);

        if ($requiredSongLineCount <= $currentSongLineCount) {
            return;
        }

        $this->songLineModels = array_merge(
            $this->songLineModels,
            $this->createEmptySongLineModels($requiredSongLineCount - $currentSongLineCount),
        );
        $this->rebuildSongLineTranslationModels();
    }

    private function synchronizeRecordingArtistModelsWithData(array $data): void
    {
        $formName = (new RecordingArtist())->formName();
        $postedArtistRows = $data[$formName] ?? null;

        if (is_array($postedArtistRows) === false) {
            return;
        }

        $postedRecordingIndexes = $data['recordingArtistRecordingIndexes'] ?? [];
        $existingFlatModels = $this->recordingArtistFlatModels;
        $this->recordingArtistFlatModels = [];
        $this->recordingArtistInputIndexes = [];
        $this->recordingArtistModels = [];

        foreach ($postedArtistRows as $flatIndex => $_row) {
            if (isset($postedRecordingIndexes[$flatIndex]) === false) {
                continue;
            }

            $recordingIndex = (int) $postedRecordingIndexes[$flatIndex];

            if (isset($this->recordingModels[$recordingIndex]) === false) {
                continue;
            }

            $artistModel = $existingFlatModels[$flatIndex] ?? new RecordingArtist();
            $this->recordingArtistFlatModels[$flatIndex] = $artistModel;
            $this->recordingArtistInputIndexes[$recordingIndex][] = (int) $flatIndex;
            $this->recordingArtistModels[$recordingIndex][] = $artistModel;
        }
    }

    private function synchronizeRecordingModelsWithData(array $data): void
    {
        $formName = (new Recording())->formName();
        $postedRecordingRows = $data[$formName] ?? null;

        if (is_array($postedRecordingRows) === false) {
            return;
        }

        $existingRecordingModels = $this->recordingModels;
        $this->recordingModels = [];

        foreach ($postedRecordingRows as $recordingIndex => $_row) {
            $recordingIndex = (int) $recordingIndex;
            $recordingModel = $existingRecordingModels[$recordingIndex] ?? new Recording();
            $recordingModel->scenario = Recording::SCENARIO_EMBEDDED_SONG;
            $this->recordingModels[$recordingIndex] = $recordingModel;
        }

        $this->initializeRecordingMediaUploadForms();
    }

    private function loadRecordingDeleteFlags(array $data): void
    {
        $postedDeleteFlags = $data['recordingDeleteFlags'] ?? [];
        $this->recordingDeleteFlags = [];

        foreach (array_keys($this->recordingModels) as $recordingIndex) {
            $this->recordingDeleteFlags[$recordingIndex] = ((string) ($postedDeleteFlags[$recordingIndex] ?? '0')) === '1';
        }
    }

    private function shouldRenderRecordingArtist(int $artistFlatIndex): bool
    {
        $artistModel = $this->recordingArtistFlatModels[$artistFlatIndex] ?? null;

        if ($artistModel === null) {
            return false;
        }

        if ($artistModel->hasContent()) {
            return true;
        }

        return $artistModel->hasErrors();
    }

    private function prepareAutoSlugs(): void
    {
        $this->prepareSongSlug();
        $this->prepareRecordingSlugs();
    }

    private function loadRecordingMediaUploadFiles(): void
    {
        foreach ($this->recordingMediaUploadForms as $recordingIndex => $uploadForm) {
            $uploadForm->audioFile = UploadedFile::getInstance($uploadForm, '[' . $recordingIndex . ']audioFile');
            $uploadForm->coverFile = UploadedFile::getInstance($uploadForm, '[' . $recordingIndex . ']coverFile');
            $uploadForm->videoFile = UploadedFile::getInstance($uploadForm, '[' . $recordingIndex . ']videoFile');
        }
    }

    private function prepareRecordingSlugs(): void
    {
        foreach ($this->recordingModels as $recordingIndex => $recordingModel) {
            if ($this->isRecordingMarkedForDelete($recordingIndex)) {
                continue;
            }

            if ($this->isRecordingFilled($recordingIndex) === false) {
                continue;
            }

            $recordingSlug = $this->slugGenerator->generateRecordingSlug($recordingModel, $this->song);

            if ($recordingSlug === '') {
                if (trim((string) $recordingModel->recording_type) !== '' && trim((string) $this->song->slug) !== '') {
                    $recordingModel->addError('recording_type', 'Не удалось сформировать slug записи. Проверь таблицу транслитерации.');
                }

                continue;
            }

            $recordingModel->slug = $recordingSlug;
        }
    }

    private function prepareSongSlug(): void
    {
        if (trim((string) $this->song->slug) !== '') {
            return;
        }

        $songSlug = $this->slugGenerator->generateSongSlug($this->song);

        if ($songSlug === '') {
            if (trim((string) $this->song->default_title) !== '') {
                $this->song->addError('default_title', 'Не удалось сформировать slug песни. Проверь таблицу транслитерации.');
            }

            return;
        }

        $this->song->slug = $songSlug;
    }

    private function applySongTextValuesToLineModels(): void
    {
        if ($this->hasDirtySongTextValues() === false) {
            return;
        }

        $originalLanguageId = $this->getOriginalLanguageId();

        if ($originalLanguageId === null) {
            return;
        }

        $dirtyLanguageIds = [];

        foreach ($this->songTextDirtyByLanguageId as $languageId => $isDirty) {
            if ($isDirty) {
                $dirtyLanguageIds[] = (int) $languageId;
            }
        }

        $rebuildAllLanguages = in_array($originalLanguageId, $dirtyLanguageIds, true);
        $linesByLanguageId = [];
        $maxLineCount = count($this->songLineModels);

        foreach ($this->getSongTextLanguageItems() as $languageId => $_languageLabel) {
            if ($rebuildAllLanguages === false && in_array($languageId, $dirtyLanguageIds, true) === false) {
                continue;
            }

            $lines = $this->splitSongTextToLines($this->songTextValuesByLanguageId[$languageId] ?? '');
            $linesByLanguageId[$languageId] = $lines;
            $maxLineCount = max($maxLineCount, count($lines));
        }

        if ($maxLineCount > count($this->songLineModels)) {
            $this->songLineModels = array_merge(
                $this->songLineModels,
                $this->createEmptySongLineModels($maxLineCount - count($this->songLineModels)),
            );
            $this->rebuildSongLineTranslationModels();
        }

        foreach ($this->songLineModels as $lineIndex => $lineModel) {
            if ($rebuildAllLanguages) {
                $lineModel->original_text = $linesByLanguageId[$originalLanguageId][$lineIndex] ?? '';
            }

            foreach ($this->songLineTranslationModels[$lineIndex] ?? [] as $languageId => $translationModel) {
                if ($rebuildAllLanguages === false && in_array($languageId, $dirtyLanguageIds, true) === false) {
                    continue;
                }

                $translationModel->translated_text = $linesByLanguageId[$languageId][$lineIndex] ?? '';
            }
        }
    }

    private function findPostedSongTextLineCount(array $data): int
    {
        $postedSongTexts = $data[self::SONG_TEXT_FORM_NAME] ?? null;

        if (is_array($postedSongTexts) === false) {
            return 0;
        }

        $maxLineCount = 0;

        foreach ($postedSongTexts as $postedSongText) {
            if (is_array($postedSongText) === false) {
                continue;
            }

            if (((string) ($postedSongText['dirty'] ?? '0')) !== '1') {
                continue;
            }

            $text = trim((string) ($postedSongText['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $maxLineCount = max($maxLineCount, count($this->splitSongTextToLines($text)));
        }

        return $maxLineCount;
    }

    private function hasDirtySongTextValues(): bool
    {
        foreach ($this->songTextDirtyByLanguageId as $isDirty) {
            if ($isDirty) {
                return true;
            }
        }

        return false;
    }

    private function loadSongTextData(array $data): void
    {
        $postedSongTexts = $data[self::SONG_TEXT_FORM_NAME] ?? null;

        if (is_array($postedSongTexts) === false) {
            return;
        }

        foreach ($this->getSongTextLanguageItems() as $languageId => $_languageLabel) {
            $postedSongText = $postedSongTexts[$languageId] ?? null;

            if (is_array($postedSongText) === false) {
                continue;
            }

            $isDirty = ((string) ($postedSongText['dirty'] ?? '0')) === '1';
            $this->songTextDirtyByLanguageId[$languageId] = $isDirty;

            if ($isDirty) {
                $this->songTextValuesByLanguageId[$languageId] = (string) ($postedSongText['text'] ?? '');
            }
        }
    }

    private function rebuildSongTextValues(): void
    {
        $this->songTextValuesByLanguageId = [];
        $this->songTextDirtyByLanguageId = [];
        $originalLanguageId = $this->getOriginalLanguageId();

        foreach ($this->getSongTextLanguageItems() as $languageId => $_languageLabel) {
            $lines = [];

            foreach ($this->songLineModels as $lineIndex => $_lineModel) {
                if ($this->isSongLineFilled($lineIndex) === false) {
                    continue;
                }

                if ($languageId === $originalLanguageId) {
                    $lines[] = trim((string) $this->songLineModels[$lineIndex]->original_text);
                    continue;
                }

                $translationModel = $this->songLineTranslationModels[$lineIndex][$languageId] ?? null;
                $lines[] = $translationModel === null ? '' : trim((string) $translationModel->translated_text);
            }

            $this->songTextValuesByLanguageId[$languageId] = implode("\n", $lines);
            $this->songTextDirtyByLanguageId[$languageId] = false;
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitSongTextToLines(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $line): string => trim($line), $lines),
            static fn (string $line): bool => $line !== '',
        ));
    }

}
