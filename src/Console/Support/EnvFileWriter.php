<?php

namespace Agentic\Console\Support;

final class EnvFileWriter
{
    public function __construct(private ?string $path = null) {}

    /**
     * @param  array<string, string|null>  $updates  null value removes the key from file
     * @return list<string> keys that were newly appended
     */
    public function merge(array $updates): array
    {
        $path = $this->path ?? base_path('.env');
        if (! is_file($path)) {
            throw new \RuntimeException(".env not found at {$path}. Copy .env.example first.");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new \RuntimeException("Could not read {$path}");
        }

        $remaining = array_filter($updates, fn ($v) => $v !== null);
        $remove = array_keys(array_filter($updates, fn ($v) => $v === null));
        $appended = [];

        foreach ($lines as $index => $line) {
            $trim = ltrim($line);
            if ($trim === '' || str_starts_with($trim, '#')) {
                continue;
            }

            if (! preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $matches)) {
                continue;
            }

            $key = $matches[1];
            if (in_array($key, $remove, true)) {
                $lines[$index] = null;

                continue;
            }

            if (array_key_exists($key, $remaining)) {
                $lines[$index] = $this->formatLine($key, $remaining[$key]);
                unset($remaining[$key]);
            }
        }

        $lines = array_values(array_filter($lines, fn ($line) => $line !== null));

        if ($remaining !== []) {
            $lines[] = '';
            $lines[] = '# --- Agentic (php artisan agentic:install) ---';
            foreach ($remaining as $key => $value) {
                $lines[] = $this->formatLine($key, $value);
                $appended[] = $key;
            }
        }

        $content = implode(PHP_EOL, $lines).PHP_EOL;
        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException("Could not write {$path}");
        }

        return $appended;
    }

    private function formatLine(string $key, string $value): string
    {
        if ($value === '') {
            return $key.'=';
        }

        if (preg_match('/[\s#"$]/', $value)) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return $key.'="'.$escaped.'"';
        }

        return $key.'='.$value;
    }
}
