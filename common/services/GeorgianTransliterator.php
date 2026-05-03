<?php

declare(strict_types=1);

namespace common\services;

use common\models\GeorgianTransliteration;
use common\models\Language;

final class GeorgianTransliterator
{
    private const SLUG_LANGUAGE_CODE = 'en';

    /**
     * @var array<string, int|null>
     */
    private array $languageIdsByCode = [];

    /**
     * @var array<int, array<string, string>>
     */
    private array $rulesByLanguageId = [];

    public function transliterateByLanguageCode(string $text, string $languageCode): string
    {
        $languageId = $this->findLanguageIdByCode($languageCode);

        if ($languageId === null) {
            return $text;
        }

        return $this->transliterateByLanguageId($text, $languageId);
    }

    public function transliterateByLanguageId(string $text, int $languageId): string
    {
        if ($text === '') {
            return '';
        }

        $rules = $this->findRulesByLanguageId($languageId);
        $result = [];

        foreach (mb_str_split($text) as $char) {
            $result[] = $rules[$char] ?? $char;
        }

        return implode('', $result);
    }

    public function transliterateForSlug(string $text): string
    {
        return $this->transliterateByLanguageCode($text, self::SLUG_LANGUAGE_CODE);
    }

    private function findLanguageIdByCode(string $languageCode): int | null
    {
        if (array_key_exists($languageCode, $this->languageIdsByCode)) {
            return $this->languageIdsByCode[$languageCode];
        }

        $languageId = Language::find()
            ->select('id')
            ->andWhere(['code' => $languageCode])
            ->scalar();

        if ($languageId === false || $languageId === null) {
            $this->languageIdsByCode[$languageCode] = null;

            return null;
        }

        $this->languageIdsByCode[$languageCode] = (int) $languageId;

        return $this->languageIdsByCode[$languageCode];
    }

    private function findRulesByLanguageId(int $languageId): array
    {
        if (isset($this->rulesByLanguageId[$languageId])) {
            return $this->rulesByLanguageId[$languageId];
        }

        $this->rulesByLanguageId[$languageId] = GeorgianTransliteration::find()
            ->select(['value', 'source_char'])
            ->andWhere(['target_language_id' => $languageId])
            ->indexBy('source_char')
            ->column();

        return $this->rulesByLanguageId[$languageId];
    }
}
