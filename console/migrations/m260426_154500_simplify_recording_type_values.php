<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260426_154500_simplify_recording_type_values extends Migration
{
    public function safeUp(): void
    {
        $this->update(
            '{{%recording}}',
            ['recording_type' => 'audio'],
            ['recording_type' => ['performance', 'studio', 'live']],
        );

        $this->alterColumn(
            '{{%recording}}',
            'recording_type',
            $this->string(32)->notNull()->defaultValue('audio'),
        );
    }

    public function safeDown(): void
    {
        $this->update(
            '{{%recording}}',
            ['recording_type' => 'performance'],
            ['recording_type' => 'audio'],
        );

        $this->alterColumn(
            '{{%recording}}',
            'recording_type',
            $this->string(32)->notNull()->defaultValue('performance'),
        );
    }
}
