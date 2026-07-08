<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260707_091000_copy_song_created_at_to_published_at extends Migration
{
    public function safeUp(): void
    {
        $this->execute(
            <<<'SQL'
UPDATE {{%song}}
SET published_at = created_at
SQL
        );
    }

    public function safeDown(): void
    {
        echo "m260707_091000_copy_song_created_at_to_published_at cannot be reverted.\n";
    }
}
