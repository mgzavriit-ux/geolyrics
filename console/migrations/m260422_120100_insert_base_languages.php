<?php

use yii\db\Migration;

class m260422_120100_insert_base_languages extends Migration
{
    public function safeUp()
    {
        $timestamp = time();

        $this->batchInsert(
            '{{%language}}',
            [
                'code',
                'locale',
                'name',
                'native_name',
                'is_active',
                'is_default',
                'sort_order',
                'created_at',
                'updated_at',
            ],
            [
                ['ka', 'ka-GE', 'Georgian', 'ქართული', true, false, 10, $timestamp, $timestamp],
                ['ru', 'ru-RU', 'Russian', 'Русский', true, true, 20, $timestamp, $timestamp],
                ['en', 'en-US', 'English', 'English', true, false, 30, $timestamp, $timestamp],
                ['fr', 'fr-FR', 'French', 'Français', true, false, 40, $timestamp, $timestamp],
            ],
        );
    }

    public function safeDown()
    {
        $this->delete('{{%language}}', [
            'code' => ['ka', 'ru', 'en', 'fr'],
        ]);
    }
}
