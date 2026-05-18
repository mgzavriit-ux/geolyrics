<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260517_124500_fix_bottle_song_georgian_translation extends Migration
{
    public function safeUp(): void
    {
        $songId = $this->findSongIdBySlug('botlshi-chamtskvdeul-pikrebs');
        $languageId = $this->findLanguageIdByCode('ka');
        $translationId = $this->findSongTranslationId($songId, $languageId);
        $timestamp = time();
        $row = [
            'song_id' => $songId,
            'language_id' => $languageId,
            'title' => 'ბოთლში ჩამწყვდეულ ფიქრებს',
            'subtitle' => null,
            'description' => null,
            'history' => null,
            'translation_source' => 'manual',
            'provider' => null,
            'model' => null,
            'review_status' => 'approved',
            'updated_at' => $timestamp,
        ];

        if ($translationId === null) {
            $row['created_at'] = $timestamp;
            $this->insert('{{%song_translation}}', $row);

            return;
        }

        $this->update('{{%song_translation}}', $row, ['id' => $translationId]);
    }

    public function safeDown(): void
    {
        echo "m260517_124500_fix_bottle_song_georgian_translation cannot be reverted.\n";
    }

    private function findLanguageIdByCode(string $code): int
    {
        $languageId = (new Query())
            ->select(['id'])
            ->from('{{%language}}')
            ->andWhere(['code' => $code])
            ->scalar();

        if ($languageId === false || $languageId === null) {
            throw new RuntimeException('Language "' . $code . '" was not found.');
        }

        return (int) $languageId;
    }

    private function findSongIdBySlug(string $slug): int
    {
        $songId = (new Query())
            ->select(['id'])
            ->from('{{%song}}')
            ->andWhere(['slug' => $slug])
            ->scalar();

        if ($songId === false || $songId === null) {
            throw new RuntimeException('Song "' . $slug . '" was not found.');
        }

        return (int) $songId;
    }

    private function findSongTranslationId(int $songId, int $languageId): int | null
    {
        $translationId = (new Query())
            ->select(['id'])
            ->from('{{%song_translation}}')
            ->andWhere(['song_id' => $songId])
            ->andWhere(['language_id' => $languageId])
            ->scalar();

        if ($translationId === false || $translationId === null) {
            return null;
        }

        return (int) $translationId;
    }
}
