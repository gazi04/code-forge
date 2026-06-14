<?php

namespace App\Services;

class BlockValidator
{
    /**
     * Verify a student's submitted answer against the authoritative block data.
     *
     * Block types that have no deterministic, server-checkable answer
     * (text_content, code_challenge, labyrinth_challenge) always pass — their
     * correctness is enforced elsewhere (client-side execution). A block whose
     * answer structure is absent also passes, so reward-only blocks keep working.
     *
     * @param  array<string, mixed>  $block
     */
    public function isCorrect(array $block, mixed $answer): bool
    {
        $data = $block['data'] ?? [];

        return match ($block['type'] ?? null) {
            'quiz' => $this->validateQuiz($data, $answer),
            'sequence_challenge' => $this->validateSequence($data, $answer),
            'bughunt_challenge' => $this->validateBugHunt($data, $answer),
            'variable_matching_challenge' => $this->validateMatching($data, $answer),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateQuiz(array $data, mixed $answer): bool
    {
        $answers = $data['answers'] ?? [];

        if (! is_array($answers) || $answers === []) {
            return true;
        }

        $correct = [];
        foreach ($answers as $index => $option) {
            if (($option['is_correct'] ?? false) === true) {
                $correct[] = $index;
            }
        }

        $selected = is_array($answer)
            ? array_values(array_unique(array_map('intval', $answer)))
            : [];

        sort($selected);
        sort($correct);

        return $selected === $correct;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateSequence(array $data, mixed $answer): bool
    {
        $sequence = $data['correct_sequence'] ?? [];

        if (! is_array($sequence) || $sequence === []) {
            return true;
        }

        $correct = array_map(static fn (array $item): string => (string) ($item['value'] ?? ''), $sequence);
        $submitted = is_array($answer) ? array_map(static fn ($value): string => (string) $value, $answer) : [];

        return $submitted === $correct;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateBugHunt(array $data, mixed $answer): bool
    {
        $lines = $data['code_lines'] ?? [];

        if (! is_array($lines) || $lines === []) {
            return true;
        }

        $answer = is_array($answer) ? $answer : [];

        foreach ($lines as $index => $line) {
            if (($line['type'] ?? 'clean') !== 'buggy') {
                continue;
            }

            $chosen = $answer[$index] ?? ($answer[(string) $index] ?? null);

            if ((string) $chosen !== (string) ($line['correct_text'] ?? '')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateMatching(array $data, mixed $answer): bool
    {
        $pairs = $data['pairs'] ?? [];

        if (! is_array($pairs) || $pairs === []) {
            return true;
        }

        $expected = [];
        foreach ($pairs as $pair) {
            $expected[(string) ($pair['left_item'] ?? '')] = (string) ($pair['right_item'] ?? '');
        }

        $submitted = is_array($answer) ? $answer : [];

        if (count($submitted) !== count($expected)) {
            return false;
        }

        foreach ($submitted as $pair) {
            $left = (string) ($pair['left'] ?? '');
            $right = (string) ($pair['right'] ?? '');

            if (! array_key_exists($left, $expected) || $expected[$left] !== $right) {
                return false;
            }
        }

        return true;
    }
}
