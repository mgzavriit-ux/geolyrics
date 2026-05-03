<?php

declare(strict_types=1);

namespace common\services;

use common\models\SongArrangement;

final class ChordSheetParser
{
    public function parse(string $sourceFormat, string $sourceText): array
    {
        if ($sourceFormat === SongArrangement::FORMAT_PLAIN_TEXT) {
            return $this->parsePlainText($sourceText);
        }

        return $this->parseChordPro($sourceText);
    }

    private function parsePlainText(string $sourceText): array
    {
        return [
            'format' => SongArrangement::FORMAT_PLAIN_TEXT,
            'blocks' => $this->createPlainTextBlocks($sourceText),
        ];
    }

    private function parseChordPro(string $sourceText): array
    {
        $blocks = [];

        foreach ($this->splitLines($sourceText) as $line) {
            if ($line === '') {
                $blocks[] = ['type' => 'empty'];
                continue;
            }

            if ($this->isDirectiveLine($line)) {
                $blocks[] = $this->createDirectiveBlock($line);
                continue;
            }

            $blocks[] = $this->createChordLineBlock($line);
        }

        return [
            'format' => SongArrangement::FORMAT_CHORD_PRO,
            'blocks' => $blocks,
        ];
    }

    private function createDirectiveBlock(string $line): array
    {
        $directiveBody = trim($line, '{}');
        $parts = explode(':', $directiveBody, 2);
        $name = trim($parts[0] ?? '');
        $value = trim($parts[1] ?? '');

        return [
            'type' => 'directive',
            'name' => $name,
            'value' => $value,
            'raw' => $line,
        ];
    }

    private function createChordLineBlock(string $line): array
    {
        preg_match_all('/\[([^\]]+)\]/u', $line, $matches, PREG_OFFSET_CAPTURE);
        $segments = [];
        $lyrics = '';

        if ($matches[0] === []) {
            return [
                'type' => 'line',
                'raw' => $line,
                'lyrics' => $line,
                'segments' => [
                    [
                        'chord' => null,
                        'text' => $line,
                    ],
                ],
            ];
        }

        foreach ($matches[0] as $index => $match) {
            $fullMatch = $match[0];
            $matchOffset = $match[1];
            $textStart = $matchOffset + strlen($fullMatch);
            $nextOffset = $matches[0][$index + 1][1] ?? strlen($line);
            $text = substr($line, $textStart, $nextOffset - $textStart);
            $chord = trim((string) ($matches[1][$index][0] ?? ''));

            $segments[] = [
                'chord' => $chord !== '' ? $chord : null,
                'text' => $text,
            ];
            $lyrics .= $text;
        }

        $leadingText = '';
        $firstOffset = $matches[0][0][1] ?? 0;

        if ($firstOffset > 0) {
            $leadingText = substr($line, 0, $firstOffset);
            array_unshift($segments, [
                'chord' => null,
                'text' => $leadingText,
            ]);
            $lyrics = $leadingText . $lyrics;
        }

        return [
            'type' => 'line',
            'raw' => $line,
            'lyrics' => $lyrics,
            'segments' => $segments,
        ];
    }

    private function createPlainTextBlocks(string $sourceText): array
    {
        $blocks = [];

        foreach ($this->splitLines($sourceText) as $line) {
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

    private function isDirectiveLine(string $line): bool
    {
        return preg_match('/^\{.+\}$/u', $line) === 1;
    }

    /**
     * @return string[]
     */
    private function splitLines(string $sourceText): array
    {
        $normalizedText = str_replace(["\r\n", "\r"], "\n", $sourceText);

        return explode("\n", $normalizedText);
    }
}
