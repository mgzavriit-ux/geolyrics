<?php

use yii\db\Migration;
use yii\db\Query;

class m260503_210000_create_song_arrangement_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%song_arrangement}}', [
            'id' => $this->primaryKey(),
            'song_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'source_format' => $this->string(32)->notNull()->defaultValue('chordpro'),
            'source_text' => $this->text()->notNull(),
            'original_key' => $this->string(16)->null(),
            'capo' => $this->integer()->null(),
            'parsed_payload' => $this->text()->null(),
            'sort_order' => $this->integer()->notNull()->defaultValue(100),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_song_arrangement_song_id', '{{%song_arrangement}}', 'song_id');
        $this->createIndex('idx_song_arrangement_sort_order', '{{%song_arrangement}}', 'sort_order');
        $this->addForeignKey(
            'fk_song_arrangement_song',
            '{{%song_arrangement}}',
            'song_id',
            '{{%song}}',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->migrateLegacyRecordingChords();
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_song_arrangement_song', '{{%song_arrangement}}');
        $this->dropTable('{{%song_arrangement}}');
    }

    private function migrateLegacyRecordingChords(): void
    {
        $query = (new Query())
            ->from('{{%recording}}')
            ->select([
                'id',
                'song_id',
                'default_title',
                'chords_text',
                'created_at',
                'updated_at',
            ])
            ->andWhere(['not', ['chords_text' => null]])
            ->andWhere(['<>', 'chords_text', '']);

        $sortOrderBySongId = [];

        foreach ($query->each() as $row) {
            $songId = (int) $row['song_id'];
            $sortOrderBySongId[$songId] = ($sortOrderBySongId[$songId] ?? 0) + 10;
            $title = trim((string) $row['default_title']);

            if ($title === '') {
                $title = 'Аранжировка записи #' . (int) $row['id'];
            }

            $this->insert('{{%song_arrangement}}', [
                'song_id' => $songId,
                'title' => $title,
                'source_format' => 'plain_text',
                'source_text' => (string) $row['chords_text'],
                'original_key' => null,
                'capo' => null,
                'parsed_payload' => json_encode([
                    'format' => 'plain_text',
                    'blocks' => $this->createPlainTextBlocks((string) $row['chords_text']),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'sort_order' => $sortOrderBySongId[$songId],
                'created_at' => (int) $row['created_at'],
                'updated_at' => (int) $row['updated_at'],
            ]);
        }
    }

    private function createPlainTextBlocks(string $sourceText): array
    {
        $blocks = [];
        $normalizedText = str_replace(["\r\n", "\r"], "\n", $sourceText);

        foreach (explode("\n", $normalizedText) as $line) {
            if ($line === '') {
                $blocks[] = ['type' => 'empty'];
                continue;
            }

            $blocks[] = [
                'type' => 'line',
                'text' => $line,
            ];
        }

        return $blocks;
    }
}
