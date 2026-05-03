<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260426_171500_create_georgian_transliteration_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%georgian_transliteration}}', [
            'id' => $this->primaryKey(),
            'source_char' => $this->string(8)->notNull(),
            'target_language_id' => $this->integer()->notNull(),
            'value' => $this->string(32)->notNull()->defaultValue(''),
        ]);

        $this->createIndex(
            'uq_georgian_transliteration_source_char_target_language_id',
            '{{%georgian_transliteration}}',
            ['source_char', 'target_language_id'],
            true,
        );
        $this->createIndex(
            'idx_georgian_transliteration_target_language_id',
            '{{%georgian_transliteration}}',
            'target_language_id',
        );
        $this->addForeignKey(
            'fk_georgian_transliteration_target_language_id',
            '{{%georgian_transliteration}}',
            'target_language_id',
            '{{%language}}',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->insertInitialValues();
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_georgian_transliteration_target_language_id', '{{%georgian_transliteration}}');
        $this->dropIndex('idx_georgian_transliteration_target_language_id', '{{%georgian_transliteration}}');
        $this->dropIndex('uq_georgian_transliteration_source_char_target_language_id', '{{%georgian_transliteration}}');
        $this->dropTable('{{%georgian_transliteration}}');
    }

    private function insertInitialValues(): void
    {
        $languageIdsByCode = (new Query())
            ->select(['id', 'code'])
            ->from('{{%language}}')
            ->andWhere(['code' => ['ru', 'en', 'fr']])
            ->indexBy('code')
            ->column();

        $rows = [
            ['source_char' => 'ა', 'ru' => 'а', 'en' => 'a', 'fr' => 'a'],
            ['source_char' => 'ბ', 'ru' => 'б', 'en' => 'b', 'fr' => 'b'],
            ['source_char' => 'გ', 'ru' => 'г', 'en' => 'g', 'fr' => 'g'],
            ['source_char' => 'დ', 'ru' => 'д', 'en' => 'd', 'fr' => 'd'],
            ['source_char' => 'ე', 'ru' => 'е', 'en' => 'e', 'fr' => 'e'],
            ['source_char' => 'ვ', 'ru' => 'в', 'en' => 'v', 'fr' => 'v'],
            ['source_char' => 'ზ', 'ru' => 'з', 'en' => 'z', 'fr' => 'z'],
            ['source_char' => 'თ', 'ru' => 'т', 'en' => 't', 'fr' => 't'],
            ['source_char' => 'ი', 'ru' => 'и', 'en' => 'i', 'fr' => 'i'],
            ['source_char' => 'კ', 'ru' => 'к', 'en' => 'k', 'fr' => 'k'],
            ['source_char' => 'ლ', 'ru' => 'л', 'en' => 'l', 'fr' => 'l'],
            ['source_char' => 'მ', 'ru' => 'м', 'en' => 'm', 'fr' => 'm'],
            ['source_char' => 'ნ', 'ru' => 'н', 'en' => 'n', 'fr' => 'n'],
            ['source_char' => 'ო', 'ru' => 'о', 'en' => 'o', 'fr' => 'o'],
            ['source_char' => 'პ', 'ru' => 'п', 'en' => 'p', 'fr' => 'p'],
            ['source_char' => 'ჟ', 'ru' => 'ж', 'en' => 'zh', 'fr' => 'j'],
            ['source_char' => 'რ', 'ru' => 'р', 'en' => 'r', 'fr' => 'r'],
            ['source_char' => 'ს', 'ru' => 'с', 'en' => 's', 'fr' => 's'],
            ['source_char' => 'ტ', 'ru' => 'т', 'en' => 't', 'fr' => 't'],
            ['source_char' => 'უ', 'ru' => 'у', 'en' => 'u', 'fr' => 'ou'],
            ['source_char' => 'ფ', 'ru' => 'ф', 'en' => 'p', 'fr' => 'p'],
            ['source_char' => 'ქ', 'ru' => 'к', 'en' => 'q', 'fr' => 'q'],
            ['source_char' => 'ღ', 'ru' => 'г', 'en' => 'gh', 'fr' => 'gh'],
            ['source_char' => 'ყ', 'ru' => 'к', 'en' => 'y', 'fr' => 'y'],
            ['source_char' => 'შ', 'ru' => 'ш', 'en' => 'sh', 'fr' => 'ch'],
            ['source_char' => 'ჩ', 'ru' => 'ч', 'en' => 'ch', 'fr' => 'tch'],
            ['source_char' => 'ც', 'ru' => 'ц', 'en' => 'ts', 'fr' => 'ts'],
            ['source_char' => 'ძ', 'ru' => 'дз', 'en' => 'dz', 'fr' => 'dz'],
            ['source_char' => 'წ', 'ru' => 'ц', 'en' => 'ts', 'fr' => 'ts'],
            ['source_char' => 'ჭ', 'ru' => 'ч', 'en' => 'ch', 'fr' => 'tch'],
            ['source_char' => 'ხ', 'ru' => 'х', 'en' => 'kh', 'fr' => 'kh'],
            ['source_char' => 'ჯ', 'ru' => 'дж', 'en' => 'j', 'fr' => 'dj'],
            ['source_char' => 'ჰ', 'ru' => 'х', 'en' => 'h', 'fr' => 'h'],
        ];

        foreach ($rows as $row) {
            foreach ($languageIdsByCode as $languageCode => $languageId) {
                $this->insert('{{%georgian_transliteration}}', [
                    'source_char' => $row['source_char'],
                    'target_language_id' => (int) $languageId,
                    'value' => (string) $row[$languageCode],
                ]);
            }
        }
    }
}
