<?php

use yii\db\Migration;

final class m260708_141000_create_user_refresh_token_table extends Migration
{
    public function up(): void
    {
        $this->createTable('{{%user_refresh_token}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token_hash' => $this->string(64)->notNull()->unique(),
            'user_agent' => $this->string(512)->defaultValue(null),
            'ip_address' => $this->string(64)->defaultValue(null),
            'expires_at' => $this->integer()->notNull(),
            'revoked_at' => $this->integer()->defaultValue(null),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_user_refresh_token_user_id', '{{%user_refresh_token}}', 'user_id');
        $this->createIndex('idx_user_refresh_token_expires_at', '{{%user_refresh_token}}', 'expires_at');
        $this->addForeignKey(
            'fk_user_refresh_token_user_id',
            '{{%user_refresh_token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function down(): void
    {
        $this->dropForeignKey('fk_user_refresh_token_user_id', '{{%user_refresh_token}}');
        $this->dropIndex('idx_user_refresh_token_expires_at', '{{%user_refresh_token}}');
        $this->dropIndex('idx_user_refresh_token_user_id', '{{%user_refresh_token}}');
        $this->dropTable('{{%user_refresh_token}}');
    }
}
