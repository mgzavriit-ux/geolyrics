<?php

use yii\db\Migration;

class m260422_120000_create_catalog_reference_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%language}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(16)->notNull(),
            'locale' => $this->string(16)->null(),
            'name' => $this->string(64)->notNull(),
            'native_name' => $this->string(64)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'is_default' => $this->boolean()->notNull()->defaultValue(false),
            'sort_order' => $this->integer()->notNull()->defaultValue(100),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq_language_code', '{{%language}}', 'code', true);
        $this->createIndex('uq_language_locale', '{{%language}}', 'locale', true);

        $this->createTable('{{%media_asset}}', [
            'id' => $this->primaryKey(),
            'storage' => $this->string(32)->notNull()->defaultValue('local'),
            'path' => $this->string(1024)->notNull(),
            'original_name' => $this->string(255)->notNull(),
            'kind' => $this->string(32)->notNull(),
            'mime_type' => $this->string(128)->null(),
            'extension' => $this->string(16)->null(),
            'size_bytes' => $this->bigInteger()->null(),
            'checksum_sha256' => $this->string(64)->null(),
            'width' => $this->integer()->null(),
            'height' => $this->integer()->null(),
            'duration_ms' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq_media_asset_storage_path', '{{%media_asset}}', ['storage', 'path'], true);
        $this->createIndex('idx_media_asset_kind', '{{%media_asset}}', 'kind');

        $this->createTable('{{%artist}}', [
            'id' => $this->primaryKey(),
            'slug' => $this->string(128)->notNull(),
            'type' => $this->string(32)->notNull(),
            'default_name' => $this->string(255)->notNull(),
            'publication_status' => $this->string(32)->notNull()->defaultValue('draft'),
            'published_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq_artist_slug', '{{%artist}}', 'slug', true);
        $this->createIndex('idx_artist_type', '{{%artist}}', 'type');
        $this->createIndex('idx_artist_publication_status', '{{%artist}}', 'publication_status');

        $this->createTable('{{%artist_translation}}', [
            'id' => $this->primaryKey(),
            'artist_id' => $this->integer()->notNull(),
            'language_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'biography' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'uq_artist_translation_artist_language',
            '{{%artist_translation}}',
            ['artist_id', 'language_id'],
            true,
        );

        $this->addForeignKey(
            'fk_artist_translation_artist',
            '{{%artist_translation}}',
            'artist_id',
            '{{%artist}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_artist_translation_language',
            '{{%artist_translation}}',
            'language_id',
            '{{%language}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );

        $this->createTable('{{%tag}}', [
            'id' => $this->primaryKey(),
            'slug' => $this->string(128)->notNull(),
            'default_name' => $this->string(255)->notNull(),
            'publication_status' => $this->string(32)->notNull()->defaultValue('draft'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq_tag_slug', '{{%tag}}', 'slug', true);
        $this->createIndex('idx_tag_publication_status', '{{%tag}}', 'publication_status');

        $this->createTable('{{%tag_translation}}', [
            'id' => $this->primaryKey(),
            'tag_id' => $this->integer()->notNull(),
            'language_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'uq_tag_translation_tag_language',
            '{{%tag_translation}}',
            ['tag_id', 'language_id'],
            true,
        );

        $this->addForeignKey(
            'fk_tag_translation_tag',
            '{{%tag_translation}}',
            'tag_id',
            '{{%tag}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_tag_translation_language',
            '{{%tag_translation}}',
            'language_id',
            '{{%language}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );

        $this->createTable('{{%artist_image}}', [
            'artist_id' => $this->integer()->notNull(),
            'media_asset_id' => $this->integer()->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(100),
            'is_primary' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->addPrimaryKey(
            'pk_artist_image',
            '{{%artist_image}}',
            ['artist_id', 'media_asset_id'],
        );

        $this->addForeignKey(
            'fk_artist_image_artist',
            '{{%artist_image}}',
            'artist_id',
            '{{%artist}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_artist_image_media_asset',
            '{{%artist_image}}',
            'media_asset_id',
            '{{%media_asset}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_artist_image_media_asset', '{{%artist_image}}');
        $this->dropForeignKey('fk_artist_image_artist', '{{%artist_image}}');
        $this->dropTable('{{%artist_image}}');

        $this->dropForeignKey('fk_tag_translation_language', '{{%tag_translation}}');
        $this->dropForeignKey('fk_tag_translation_tag', '{{%tag_translation}}');
        $this->dropTable('{{%tag_translation}}');

        $this->dropTable('{{%tag}}');

        $this->dropForeignKey('fk_artist_translation_language', '{{%artist_translation}}');
        $this->dropForeignKey('fk_artist_translation_artist', '{{%artist_translation}}');
        $this->dropTable('{{%artist_translation}}');

        $this->dropTable('{{%artist}}');
        $this->dropTable('{{%media_asset}}');
        $this->dropTable('{{%language}}');
    }
}
