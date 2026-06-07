<?php

namespace App\Service;

use Illuminate\Support\Facades\Storage;

class KnowledgeTracingSettingsService
{
    private const SETTINGS_PATH = 'settings/knowledge_tracing.json';

    public function settings(): array
    {
        $settings = Storage::exists(self::SETTINGS_PATH)
            ? json_decode((string) Storage::get(self::SETTINGS_PATH), true)
            : [];

        return array_replace($this->defaults(), is_array($settings) ? $settings : []);
    }

    public function defaults(): array
    {
        return [
            'prior' => 0.30,
            'learn' => 0.10,
            'guess' => 0.20,
            'slip' => 0.10,
            'easy_rating_max' => 1199,
            'medium_rating_max' => 1799,
            'easy_mastery_cap' => 0.70,
            'medium_mastery_cap' => 0.85,
            'hard_mastery_cap' => 1.00,
        ];
    }

    public function save(array $settings): void
    {
        $defaults = $this->defaults();

        Storage::put(self::SETTINGS_PATH, json_encode([
            'prior' => $this->normalizeProbability($settings['prior'] ?? $defaults['prior']),
            'learn' => $this->normalizeProbability($settings['learn'] ?? $defaults['learn']),
            'guess' => $this->normalizeProbability($settings['guess'] ?? $defaults['guess']),
            'slip' => $this->normalizeProbability($settings['slip'] ?? $defaults['slip']),
            'easy_rating_max' => $this->normalizeRating($settings['easy_rating_max'] ?? $defaults['easy_rating_max']),
            'medium_rating_max' => $this->normalizeRating($settings['medium_rating_max'] ?? $defaults['medium_rating_max']),
            'easy_mastery_cap' => $this->normalizeProbability($settings['easy_mastery_cap'] ?? $defaults['easy_mastery_cap']),
            'medium_mastery_cap' => $this->normalizeProbability($settings['medium_mastery_cap'] ?? $defaults['medium_mastery_cap']),
            'hard_mastery_cap' => $this->normalizeProbability($settings['hard_mastery_cap'] ?? $defaults['hard_mastery_cap']),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function normalizeProbability(mixed $value): float
    {
        return round(max(0, min(1, (float) $value)), 4);
    }

    private function normalizeRating(mixed $value): int
    {
        return max(0, min(5000, (int) $value));
    }
}
