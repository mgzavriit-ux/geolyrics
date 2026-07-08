<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260707_090000_update_song_default_titles_to_georgian extends Migration
{
    public function safeUp(): void
    {
        $georgianLanguageId = $this->findLanguageIdByCode('ka');

        $this->execute(
            <<<SQL
UPDATE {{%song}} AS song
SET default_title = song_translation.title
FROM {{%song_translation}} AS song_translation
WHERE song_translation.song_id = song.id
  AND song_translation.language_id = {$georgianLanguageId}
  AND btrim(song_translation.title) <> ''
  AND song.default_title <> song_translation.title
SQL
        );
    }

    public function safeDown(): void
    {
        echo "m260707_090000_update_song_default_titles_to_georgian cannot be reverted.\n";
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
}
