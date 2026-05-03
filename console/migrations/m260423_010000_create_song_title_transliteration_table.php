<?php

use yii\db\Migration;

class m260423_010000_create_song_title_transliteration_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%song_title_transliteration}}', [
            'id' => $this->primaryKey(),
            'song_id' => $this->integer()->notNull(),
            'system_code' => $this->string(32)->notNull(),
            'system_name' => $this->string(64)->notNull(),
            'transliterated_text' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'uq_song_title_transliteration_song_system',
            '{{%song_title_transliteration}}',
            ['song_id', 'system_code'],
            true,
        );

        $this->addForeignKey(
            'fk_song_title_transliteration_song',
            '{{%song_title_transliteration}}',
            'song_id',
            '{{%song}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_song_title_transliteration_song', '{{%song_title_transliteration}}');
        $this->dropTable('{{%song_title_transliteration}}');
    }
}
