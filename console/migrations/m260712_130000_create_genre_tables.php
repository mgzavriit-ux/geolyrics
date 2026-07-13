<?php

use yii\db\Migration;

class m260712_130000_create_genre_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%genre}}', [
            'id' => $this->primaryKey(),
            'slug' => $this->string(128)->notNull(),
            'default_name' => $this->string(255)->notNull(),
            'publication_status' => $this->string(32)->notNull()->defaultValue('draft'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq_genre_slug', '{{%genre}}', 'slug', true);
        $this->createIndex('idx_genre_publication_status', '{{%genre}}', 'publication_status');

        $this->createTable('{{%song_genre}}', [
            'song_id' => $this->integer()->notNull(),
            'genre_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk_song_genre', '{{%song_genre}}', ['song_id', 'genre_id']);

        $this->addForeignKey(
            'fk_song_genre_song',
            '{{%song_genre}}',
            'song_id',
            '{{%song}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_song_genre_genre',
            '{{%song_genre}}',
            'genre_id',
            '{{%genre}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_song_genre_genre', '{{%song_genre}}');
        $this->dropForeignKey('fk_song_genre_song', '{{%song_genre}}');
        $this->dropTable('{{%song_genre}}');

        $this->dropTable('{{%genre}}');
    }
}
