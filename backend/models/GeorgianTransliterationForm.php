<?php

declare(strict_types=1);

namespace backend\models;

use common\models\GeorgianTransliteration;
use common\models\Language;
use Yii;
use yii\base\Model;
use yii\db\Connection;

final class GeorgianTransliterationForm extends Model
{
    private const SOURCE_LANGUAGE_CODE = 'ka';

    private const SOURCE_LETTERS = [
        'ა',
        'ბ',
        'გ',
        'დ',
        'ე',
        'ვ',
        'ზ',
        'თ',
        'ი',
        'კ',
        'ლ',
        'მ',
        'ნ',
        'ო',
        'პ',
        'ჟ',
        'რ',
        'ს',
        'ტ',
        'უ',
        'ფ',
        'ქ',
        'ღ',
        'ყ',
        'შ',
        'ჩ',
        'ც',
        'ძ',
        'წ',
        'ჭ',
        'ხ',
        'ჯ',
        'ჰ',
    ];

    /**
     * @var Language[]
     */
    private array $languages;

    /**
     * @var array<string, array<int, GeorgianTransliteration>>
     */
    private array $models = [];

    public array $matrix = [];

    /**
     * @param Language[] $languages
     */
    public function __construct(array $languages, array $config = [])
    {
        $this->languages = $this->filterLanguages($languages);

        parent::__construct($config);

        $this->initializeMatrix();
    }

    public function rules(): array
    {
        return [
            [['matrix'], 'safe'],
        ];
    }

    /**
     * @return Language[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function getValue(string $sourceChar, int $languageId): string
    {
        return (string) ($this->matrix[$sourceChar][$languageId] ?? '');
    }

    public function getSourceLetters(): array
    {
        return self::SOURCE_LETTERS;
    }

    public function load($data, $formName = null): bool
    {
        $isLoaded = parent::load($data, $formName);

        if ($isLoaded) {
            $this->normalizeMatrix();
        }

        return $isLoaded;
    }

    public function save(): void
    {
        $transaction = $this->getDb()->beginTransaction();

        try {
            foreach ($this->getSourceLetters() as $sourceChar) {
                foreach ($this->languages as $language) {
                    $this->saveRule($sourceChar, $language);
                }
            }

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    private function createRule(string $sourceChar, int $languageId): GeorgianTransliteration
    {
        $rule = new GeorgianTransliteration();
        $rule->source_char = $sourceChar;
        $rule->target_language_id = $languageId;

        return $rule;
    }

    private function filterLanguages(array $languages): array
    {
        $items = [];

        foreach ($languages as $language) {
            if ($language->code === self::SOURCE_LANGUAGE_CODE) {
                continue;
            }

            $items[] = $language;
        }

        return $items;
    }

    private function getDb(): Connection
    {
        return Yii::$app->db;
    }

    private function initializeMatrix(): void
    {
        $languageIds = [];

        foreach ($this->languages as $language) {
            $languageIds[] = $language->id;
        }

        if ($languageIds === []) {
            return;
        }

        $existingRules = GeorgianTransliteration::find()
            ->andWhere(['target_language_id' => $languageIds])
            ->all();

        foreach ($existingRules as $rule) {
            $this->models[$rule->source_char][(int) $rule->target_language_id] = $rule;
            $this->matrix[$rule->source_char][(int) $rule->target_language_id] = $rule->value;
        }

        foreach ($this->getSourceLetters() as $sourceChar) {
            foreach ($this->languages as $language) {
                $this->matrix[$sourceChar][$language->id] = $this->getValue($sourceChar, $language->id);
            }
        }
    }

    private function normalizeMatrix(): void
    {
        foreach ($this->getSourceLetters() as $sourceChar) {
            foreach ($this->languages as $language) {
                $this->matrix[$sourceChar][$language->id] = trim((string) ($this->matrix[$sourceChar][$language->id] ?? ''));
            }
        }
    }

    private function saveRule(string $sourceChar, Language $language): void
    {
        $rule = $this->models[$sourceChar][$language->id] ?? $this->createRule($sourceChar, $language->id);
        $rule->value = $this->getValue($sourceChar, $language->id);
        $rule->save(false);
        $this->models[$sourceChar][$language->id] = $rule;
    }
}
