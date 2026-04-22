<?php

use yii\db\Migration;

class m260422_120300_create_recording_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%recording}}', [
            'id' => $this->primaryKey(),
            'song_id' => $this->integer()->notNull(),
            'slug' => $this->string(128)->notNull(),
            'default_title' => $this->string(255)->notNull(),
            'recording_type' => $this->string(32)->notNull()->defaultValue('performance'),
            'publication_status' => $this->string(32)->notNull()->defaultValue('draft'),
            'cover_media_asset_id' => $this->integer()->null(),
            'release_year' => $this->integer()->null(),
            'duration_ms' => $this->integer()->null(),
            'chords_text' => $this->text()->null(),
            'description' => $this->text()->null(),
            'published_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq_recording_slug', '{{%recording}}', 'slug', true);
        $this->createIndex('idx_recording_song_id', '{{%recording}}', 'song_id');
        $this->createIndex('idx_recording_publication_status', '{{%recording}}', 'publication_status');

        $this->addForeignKey(
            'fk_recording_song',
            '{{%recording}}',
            'song_id',
            '{{%song}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_recording_cover_media_asset',
            '{{%recording}}',
            'cover_media_asset_id',
            '{{%media_asset}}',
            'id',
            'SET NULL',
            'CASCADE',
        );

        $this->createTable('{{%recording_artist}}', [
            'recording_id' => $this->integer()->notNull(),
            'artist_id' => $this->integer()->notNull(),
            'role' => $this->string(32)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(100),
        ]);

        $this->addPrimaryKey(
            'pk_recording_artist',
            '{{%recording_artist}}',
            ['recording_id', 'artist_id', 'role'],
        );

        $this->addForeignKey(
            'fk_recording_artist_recording',
            '{{%recording_artist}}',
            'recording_id',
            '{{%recording}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_recording_artist_artist',
            '{{%recording_artist}}',
            'artist_id',
            '{{%artist}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );

        $this->createTable('{{%recording_media}}', [
            'recording_id' => $this->integer()->notNull(),
            'media_asset_id' => $this->integer()->notNull(),
            'role' => $this->string(32)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(100),
            'is_primary' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->addPrimaryKey(
            'pk_recording_media',
            '{{%recording_media}}',
            ['recording_id', 'media_asset_id', 'role'],
        );

        $this->addForeignKey(
            'fk_recording_media_recording',
            '{{%recording_media}}',
            'recording_id',
            '{{%recording}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_recording_media_media_asset',
            '{{%recording_media}}',
            'media_asset_id',
            '{{%media_asset}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_recording_media_media_asset', '{{%recording_media}}');
        $this->dropForeignKey('fk_recording_media_recording', '{{%recording_media}}');
        $this->dropTable('{{%recording_media}}');

        $this->dropForeignKey('fk_recording_artist_artist', '{{%recording_artist}}');
        $this->dropForeignKey('fk_recording_artist_recording', '{{%recording_artist}}');
        $this->dropTable('{{%recording_artist}}');

        $this->dropForeignKey('fk_recording_cover_media_asset', '{{%recording}}');
        $this->dropForeignKey('fk_recording_song', '{{%recording}}');
        $this->dropTable('{{%recording}}');
    }
}
