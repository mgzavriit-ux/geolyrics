<?php

use yii\db\Migration;

class m260706_120000_create_home_hero_image_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%home_hero_image}}', [
            'id' => $this->primaryKey(),
            'artist_id' => $this->integer()->notNull(),
            'media_asset_id' => $this->integer()->notNull(),
            'focal_point_x' => $this->integer()->notNull()->defaultValue(50),
            'focal_point_y' => $this->integer()->notNull()->defaultValue(50),
            'sort_order' => $this->integer()->notNull()->defaultValue(100),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_home_hero_image_artist_id', '{{%home_hero_image}}', 'artist_id');
        $this->createIndex('idx_home_hero_image_media_asset_id', '{{%home_hero_image}}', 'media_asset_id');
        $this->createIndex(
            'idx_home_hero_image_active_sort',
            '{{%home_hero_image}}',
            ['is_active', 'sort_order', 'id'],
        );

        $this->addForeignKey(
            'fk_home_hero_image_artist',
            '{{%home_hero_image}}',
            'artist_id',
            '{{%artist}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk_home_hero_image_media_asset',
            '{{%home_hero_image}}',
            'media_asset_id',
            '{{%media_asset}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_home_hero_image_media_asset', '{{%home_hero_image}}');
        $this->dropForeignKey('fk_home_hero_image_artist', '{{%home_hero_image}}');
        $this->dropTable('{{%home_hero_image}}');
    }
}
