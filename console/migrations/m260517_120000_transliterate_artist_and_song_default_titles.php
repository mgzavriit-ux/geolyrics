<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260517_120000_transliterate_artist_and_song_default_titles extends Migration
{
    public function safeUp()
    {
        $englishLanguageId = $this->findEnglishLanguageId();

        if ($englishLanguageId === null) {
            throw new RuntimeException('English language was not found.');
        }

        $rules = $this->findTransliterationRules($englishLanguageId);

        $this->updateArtistDefaultNames($rules);
        $this->updateSongDefaultTitles($rules);
    }

    public function safeDown()
    {
        echo "m260517_120000_transliterate_artist_and_song_default_titles cannot be reverted.\n";

        return false;
    }

    private function containsGeorgianCharacters(string $text): bool
    {
        return preg_match('/[ა-ჰ]/u', $text) === 1;
    }

    private function findEnglishLanguageId(): int | null
    {
        $languageId = (new Query())
            ->select(['id'])
            ->from('{{%language}}')
            ->andWhere(['code' => 'en'])
            ->scalar();

        if ($languageId === false || $languageId === null) {
            return null;
        }

        return (int) $languageId;
    }

    private function findTransliterationRules(int $languageId): array
    {
        return (new Query())
            ->select(['value', 'source_char'])
            ->from('{{%georgian_transliteration}}')
            ->andWhere(['target_language_id' => $languageId])
            ->indexBy('source_char')
            ->column();
    }

    private function transliterate(string $text, array $rules): string
    {
        if ($text === '') {
            return '';
        }

        $result = [];

        foreach (mb_str_split($text) as $char) {
            $result[] = $rules[$char] ?? $char;
        }

        return implode('', $result);
    }

    private function updateArtistDefaultNames(array $rules): void
    {
        $query = (new Query())
            ->select(['id', 'default_name'])
            ->from('{{%artist}}')
            ->where("default_name ~ '[ა-ჰ]'");

        foreach ($query->each() as $row) {
            $defaultName = (string) $row['default_name'];
            $transliteratedName = $this->transliterate($defaultName, $rules);

            if ($transliteratedName === $defaultName || $this->containsGeorgianCharacters($transliteratedName)) {
                continue;
            }

            $this->update(
                '{{%artist}}',
                ['default_name' => $transliteratedName],
                ['id' => (int) $row['id']],
            );
        }
    }

    private function updateSongDefaultTitles(array $rules): void
    {
        $query = (new Query())
            ->select(['id', 'default_title'])
            ->from('{{%song}}')
            ->where("default_title ~ '[ა-ჰ]'");

        foreach ($query->each() as $row) {
            $defaultTitle = (string) $row['default_title'];
            $transliteratedTitle = $this->transliterate($defaultTitle, $rules);

            if ($transliteratedTitle === $defaultTitle || $this->containsGeorgianCharacters($transliteratedTitle)) {
                continue;
            }

            $this->update(
                '{{%song}}',
                ['default_title' => $transliteratedTitle],
                ['id' => (int) $row['id']],
            );
        }
    }
}
