<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260708_120000_update_artist_default_names_to_georgian extends Migration
{
    public function safeUp(): void
    {
        $georgianLanguageId = $this->findLanguageIdByCode('ka');

        $this->execute(
            <<<SQL
UPDATE {{%artist}} AS artist
SET default_name = artist_translation.name
FROM {{%artist_translation}} AS artist_translation
WHERE artist_translation.artist_id = artist.id
  AND artist_translation.language_id = {$georgianLanguageId}
  AND btrim(artist_translation.name) <> ''
  AND artist.default_name <> artist_translation.name
SQL
        );
    }

    public function safeDown(): void
    {
        echo "m260708_120000_update_artist_default_names_to_georgian cannot be reverted.\n";
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
