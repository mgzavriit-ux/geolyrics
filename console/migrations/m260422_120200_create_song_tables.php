<?php

use yii\db\Migration;

class m260422_120200_create_song_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%song}}', [
            'id' => $this->primaryKey(),
            'original_language_id' => $this->integer()->notNull(),
            'slug' => $this->string(128)->notNull(),
            'default_title' => $this->string(255)->notNull(),
            'publication_status' => $this->string(32)->notNull()->defaultValue('draft'),
            'cover_media_asset_id' => $this->integer()->null(),
            'published_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq_song_slug', '{{%song}}', 'slug', true);
        $this->createIndex('idx_song_original_language_id', '{{%song}}', 'original_language_id');
        $this->createIndex('idx_song_publication_status', '{{%song}}', 'publication_status');

        $this->addForeignKey(
            'fk_song_original_language',
            '{{%song}}',
            'original_language_id',
            '{{%language}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_song_cover_media_asset',
            '{{%song}}',
            'cover_media_asset_id',
            '{{%media_asset}}',
            'id',
            'SET NULL',
            'CASCADE',
        );

        $this->createTable('{{%song_translation}}', [
            'id' => $this->primaryKey(),
            'song_id' => $this->integer()->notNull(),
            'language_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'subtitle' => $this->string(255)->null(),
            'description' => $this->text()->null(),
            'history' => $this->text()->null(),
            'translation_source' => $this->string(32)->notNull()->defaultValue('manual'),
            'provider' => $this->string(64)->null(),
            'model' => $this->string(64)->null(),
            'review_status' => $this->string(32)->notNull()->defaultValue('approved'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'uq_song_translation_song_language',
            '{{%song_translation}}',
            ['song_id', 'language_id'],
            true,
        );

        $this->addForeignKey(
            'fk_song_translation_song',
            '{{%song_translation}}',
            'song_id',
            '{{%song}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_song_translation_language',
            '{{%song_translation}}',
            'language_id',
            '{{%language}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );

        $this->createTable('{{%song_line}}', [
            'id' => $this->primaryKey(),
            'song_id' => $this->integer()->notNull(),
            'section_code' => $this->string(32)->null(),
            'section_number' => $this->integer()->null(),
            'sort_order' => $this->integer()->notNull(),
            'original_text' => $this->text()->notNull(),
            'start_ms' => $this->integer()->null(),
            'end_ms' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'uq_song_line_song_sort_order',
            '{{%song_line}}',
            ['song_id', 'sort_order'],
            true,
        );

        $this->addForeignKey(
            'fk_song_line_song',
            '{{%song_line}}',
            'song_id',
            '{{%song}}',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->createTable('{{%song_line_translation}}', [
            'id' => $this->primaryKey(),
            'song_line_id' => $this->integer()->notNull(),
            'language_id' => $this->integer()->notNull(),
            'translated_text' => $this->text()->notNull(),
            'translation_source' => $this->string(32)->notNull()->defaultValue('manual'),
            'provider' => $this->string(64)->null(),
            'model' => $this->string(64)->null(),
            'review_status' => $this->string(32)->notNull()->defaultValue('approved'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'uq_song_line_translation_line_language',
            '{{%song_line_translation}}',
            ['song_line_id', 'language_id'],
            true,
        );

        $this->addForeignKey(
            'fk_song_line_translation_song_line',
            '{{%song_line_translation}}',
            'song_line_id',
            '{{%song_line}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_song_line_translation_language',
            '{{%song_line_translation}}',
            'language_id',
            '{{%language}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );

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

        $this->createTable('{{%song_author}}', [
            'song_id' => $this->integer()->notNull(),
            'artist_id' => $this->integer()->notNull(),
            'role' => $this->string(32)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(100),
        ]);

        $this->addPrimaryKey(
            'pk_song_author',
            '{{%song_author}}',
            ['song_id', 'artist_id', 'role'],
        );

        $this->addForeignKey(
            'fk_song_author_song',
            '{{%song_author}}',
            'song_id',
            '{{%song}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_song_author_artist',
            '{{%song_author}}',
            'artist_id',
            '{{%artist}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );

        $this->createTable('{{%song_tag}}', [
            'song_id' => $this->integer()->notNull(),
            'tag_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk_song_tag', '{{%song_tag}}', ['song_id', 'tag_id']);

        $this->addForeignKey(
            'fk_song_tag_song',
            '{{%song_tag}}',
            'song_id',
            '{{%song}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_song_tag_tag',
            '{{%song_tag}}',
            'tag_id',
            '{{%tag}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_song_tag_tag', '{{%song_tag}}');
        $this->dropForeignKey('fk_song_tag_song', '{{%song_tag}}');
        $this->dropTable('{{%song_tag}}');

        $this->dropForeignKey('fk_song_author_artist', '{{%song_author}}');
        $this->dropForeignKey('fk_song_author_song', '{{%song_author}}');
        $this->dropTable('{{%song_author}}');

        $this->dropForeignKey(
            'fk_song_line_transliteration_song_line',
            '{{%song_line_transliteration}}',
        );
        $this->dropTable('{{%song_line_transliteration}}');

        $this->dropForeignKey('fk_song_line_translation_language', '{{%song_line_translation}}');
        $this->dropForeignKey('fk_song_line_translation_song_line', '{{%song_line_translation}}');
        $this->dropTable('{{%song_line_translation}}');

        $this->dropForeignKey('fk_song_line_song', '{{%song_line}}');
        $this->dropTable('{{%song_line}}');

        $this->dropForeignKey('fk_song_translation_language', '{{%song_translation}}');
        $this->dropForeignKey('fk_song_translation_song', '{{%song_translation}}');
        $this->dropTable('{{%song_translation}}');

        $this->dropForeignKey('fk_song_cover_media_asset', '{{%song}}');
        $this->dropForeignKey('fk_song_original_language', '{{%song}}');
        $this->dropTable('{{%song}}');
    }
}
