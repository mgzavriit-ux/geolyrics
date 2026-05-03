<?php

use yii\db\Migration;

class m260424_120000_drop_song_transliteration_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%song_title_transliteration}}', true) !== null) {
            $this->dropForeignKey(
                'fk_song_title_transliteration_song',
                '{{%song_title_transliteration}}',
            );
            $this->dropTable('{{%song_title_transliteration}}');
        }

        if ($this->db->getTableSchema('{{%song_line_transliteration}}', true) !== null) {
            $this->dropForeignKey(
                'fk_song_line_transliteration_song_line',
                '{{%song_line_transliteration}}',
            );
            $this->dropTable('{{%song_line_transliteration}}');
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('{{%song_line_transliteration}}', true) === null) {
            $this->createTable('{{%song_line_transliteration}}', [
                'id' => $this->primaryKey(),
                'song_line_id' => $this->integer()->notNull(),
                'system_code' => $this->string(32)->notNull(),
                'system_name' => $this->string(64)->notNull(),
                'transliterated_text' => $this->text()->notNull(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ]);

            $this->createIndex(
                'uq_song_line_transliteration_line_system',
                '{{%song_line_transliteration}}',
                ['song_line_id', 'system_code'],
                true,
            );

            $this->addForeignKey(
                'fk_song_line_transliteration_song_line',
                '{{%song_line_transliteration}}',
                'song_line_id',
                '{{%song_line}}',
                'id',
                'CASCADE',
                'CASCADE',
            );
        }

        if ($this->db->getTableSchema('{{%song_title_transliteration}}', true) === null) {
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
    }
}
