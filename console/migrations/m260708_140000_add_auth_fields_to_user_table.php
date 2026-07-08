<?php

use yii\db\Migration;

final class m260708_140000_add_auth_fields_to_user_table extends Migration
{
    public function up(): void
    {
        $this->addColumn('{{%user}}', 'name', $this->string()->defaultValue(null));
        $this->addColumn('{{%user}}', 'role', $this->string(32)->notNull()->defaultValue('user'));
        $this->addColumn('{{%user}}', 'google_subject', $this->string()->defaultValue(null));

        $this->createIndex('uq_user_google_subject', '{{%user}}', 'google_subject', true);
        $this->createIndex('idx_user_role', '{{%user}}', 'role');

        $this->update('{{%user}}', ['role' => 'admin']);
        $this->execute("UPDATE {{%user}} SET [[name]] = [[username]] WHERE [[name]] IS NULL");
    }

    public function down(): void
    {
        $this->dropIndex('idx_user_role', '{{%user}}');
        $this->dropIndex('uq_user_google_subject', '{{%user}}');

        $this->dropColumn('{{%user}}', 'google_subject');
        $this->dropColumn('{{%user}}', 'role');
        $this->dropColumn('{{%user}}', 'name');
    }
}
