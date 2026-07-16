<?php

declare(strict_types=1);

namespace App\Support;

class BlockSanitizer
{
    /**
     * Strip answer keys from a lesson's blocks before they are serialized to the
     * client, so correct answers can no longer be read from the page props.
     * Order-based answers (sequence/matching) are shuffled so the array order
     * itself does not leak the solution.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public function sanitize(array $blocks): array
    {
        return array_map($this->sanitizeBlock(...), $blocks);
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private function sanitizeBlock(array $block): array
    {
        $data = $block['data'] ?? [];

        $block['data'] = match ($block['type'] ?? null) {
            'quiz' => $this->sanitizeQuiz($data),
            'sequence_challenge' => $this->sanitizeSequence($data),
            'bughunt_challenge' => $this->sanitizeBugHunt($data),
            'variable_matching_challenge' => $this->sanitizeMatching($data),
            'code_challenge' => $this->sanitizeCode($data),
            default => $data,
        };

        return $block;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeQuiz(array $data): array
    {
        if (isset($data['answers']) && is_array($data['answers'])) {
            $data['answers'] = array_map(static function (array $answer): array {
                unset($answer['is_correct'], $answer['feedback']);

                return $answer;
            }, $data['answers']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeSequence(array $data): array
    {
        if (isset($data['correct_sequence']) && is_array($data['correct_sequence'])) {
            $items = array_map(static fn (array $item): array => ['value' => $item['value'] ?? ''], $data['correct_sequence']);
            shuffle($items);
            unset($data['correct_sequence']);
            $data['items'] = $items;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeBugHunt(array $data): array
    {
        if (isset($data['code_lines']) && is_array($data['code_lines'])) {
            $data['code_lines'] = array_map(static function (array $line): array {
                $sanitized = [
                    'type' => $line['type'] ?? 'clean',
                    'displayed_text' => $line['displayed_text'] ?? '',
                ];

                if (($line['type'] ?? 'clean') === 'buggy') {
                    $choices = array_values(array_unique(array_filter([
                        $line['displayed_text'] ?? null,
                        $line['correct_text'] ?? null,
                        $line['decoy_1'] ?? null,
                        $line['decoy_2'] ?? null,
                    ], static fn ($value): bool => $value !== null && $value !== '')));
                    shuffle($choices);
                    $sanitized['choices'] = $choices;
                }

                return $sanitized;
            }, $data['code_lines']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeMatching(array $data): array
    {
        if (isset($data['pairs']) && is_array($data['pairs'])) {
            $left = array_map(static fn (array $pair): string => (string) ($pair['left_item'] ?? ''), $data['pairs']);
            $right = array_map(static fn (array $pair): string => (string) ($pair['right_item'] ?? ''), $data['pairs']);
            shuffle($left);
            shuffle($right);
            unset($data['pairs']);
            $data['left_items'] = $left;
            $data['right_items'] = $right;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeCode(array $data): array
    {
        unset($data['solution_code']);

        if (isset($data['test_cases']) && is_array($data['test_cases'])) {
            $data['test_cases'] = array_map(static function (array $testCase): array {
                if (($testCase['is_hidden'] ?? false) === true) {
                    unset($testCase['expected_output'], $testCase['setup_code']);
                }

                return $testCase;
            }, $data['test_cases']);
        }

        return $data;
    }
}
