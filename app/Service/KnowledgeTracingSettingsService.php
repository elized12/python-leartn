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
        ];
    }

    public function save(array $settings): void
    {
        Storage::put(self::SETTINGS_PATH, json_encode([
            'prior' => $this->normalizeProbability($settings['prior'] ?? $this->defaults()['prior']),
            'learn' => $this->normalizeProbability($settings['learn'] ?? $this->defaults()['learn']),
            'guess' => $this->normalizeProbability($settings['guess'] ?? $this->defaults()['guess']),
            'slip' => $this->normalizeProbability($settings['slip'] ?? $this->defaults()['slip']),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function normalizeProbability(mixed $value): float
    {
        return round(max(0, min(1, (float) $value)), 4);
    }
}
