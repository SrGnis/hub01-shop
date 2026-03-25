<?php

namespace App\Services;

class DownloadStatsUserAgentFilterService
{
    /**
     * @var array<int, string>|null
     */
    private ?array $ignoredUserAgentPatterns = null;

    public function shouldIgnore(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return false;
        }

        $normalizedUserAgent = $this->normalize($userAgent);

        foreach ($this->getIgnoredUserAgentPatterns() as $pattern) {
            if ($pattern !== '' && str_contains($normalizedUserAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function getIgnoredUserAgentPatterns(): array
    {
        if ($this->ignoredUserAgentPatterns !== null) {
            return $this->ignoredUserAgentPatterns;
        }

        $configuredPatterns = config('download_stats.bad_user_agent_patterns');
        if (is_array($configuredPatterns)) {
            return $this->ignoredUserAgentPatterns = $this->normalizeLines($configuredPatterns);
        }

        $path = (string) config('download_stats.bad_user_agent_list_path');
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return $this->ignoredUserAgentPatterns = [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return $this->ignoredUserAgentPatterns = $this->normalizeLines($lines ?: []);
    }

    /**
     * @param array<int, string> $lines
     * @return array<int, string>
     */
    private function normalizeLines(array $lines): array
    {
        $patterns = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $normalized = $this->normalize($line);
            if ($normalized !== '') {
                $patterns[] = $normalized;
            }
        }

        return array_values(array_unique($patterns));
    }

    private function normalize(string $value): string
    {
        $value = str_replace('\\', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return strtolower(trim($value));
    }
}

