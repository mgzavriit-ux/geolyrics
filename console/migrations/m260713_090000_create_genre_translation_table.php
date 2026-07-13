<?php

use yii\db\Migration;

class m260713_090000_create_genre_translation_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%genre_translation}}', [
            'id' => $this->primaryKey(),
            'genre_id' => $this->integer()->notNull(),
            'language_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'uq_genre_translation_genre_language',
            '{{%genre_translation}}',
            ['genre_id', 'language_id'],
            true,
        );

        $this->addForeignKey(
            'fk_genre_translation_genre',
            '{{%genre_translation}}',
            'genre_id',
            '{{%genre}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_genre_translation_language',
            '{{%genre_translation}}',
            'language_id',
            '{{%language}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_genre_translation_language', '{{%genre_translation}}');
        $this->dropForeignKey('fk_genre_translation_genre', '{{%genre_translation}}');
        $this->dropTable('{{%genre_translation}}');
    }
}
