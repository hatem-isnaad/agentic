<?php

namespace Agentic\Widget\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

final class WidgetAttachmentService
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{path: string, name: string, mime: string, size: int}>
     */
    public function store(array $files, string $conversationId): array
    {
        $max = max(1, (int) config('agentic.widget.attachments.max_files', 3));
        $stored = [];

        foreach (array_slice($files, 0, $max) as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $mime = (string) $file->getMimeType();
            if (! $this->allowed($mime)) {
                continue;
            }

            $name = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('agentic/attachments/'.$conversationId, $name, 'local');
            if (! is_string($path) || $path === '') {
                continue;
            }

            $stored[] = [
                'path' => $path,
                'name' => (string) $file->getClientOriginalName(),
                'mime' => $mime,
                'size' => (int) $file->getSize(),
            ];
        }

        return $stored;
    }

    /**
     * @param  list<array<string, mixed>>  $files
     * @return list<array{name: string, mime: string, size: int, url: string, image: bool}>
     */
    public function present(array $files, string $conversationId, bool $adminUrls = false): array
    {
        $rows = [];

        foreach ($files as $file) {
            if (! is_array($file)) {
                continue;
            }
            $path = (string) ($file['path'] ?? '');
            $name = (string) ($file['name'] ?? basename($path));
            $mime = (string) ($file['mime'] ?? '');
            if ($path === '') {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'mime' => $mime,
                'size' => (int) ($file['size'] ?? 0),
                'url' => $adminUrls ? $this->adminUrl($conversationId, $path) : $this->url($conversationId, $path),
                'image' => str_starts_with($mime, 'image/'),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $files
     */
    public function presentHtml(string $html, array $files, string $conversationId, bool $adminUrls = false): string
    {
        $presented = $this->present($files, $conversationId, $adminUrls);
        if ($presented === []) {
            return $html;
        }

        $extra = '';
        foreach ($presented as $file) {
            if ($file['image']) {
                $extra .= '<figure class="ag-attach">'
                    .'<img src="'.e($file['url']).'" alt="" loading="lazy" decoding="async" class="ag-attach-img">'
                    .'<figcaption class="ag-attach-caption">'.e($file['name']).'</figcaption>'
                    .'</figure>';
            } else {
                $extra .= '<p class="ag-attach-file"><a href="'.e($file['url']).'" rel="noopener noreferrer" target="_blank">'
                    .e($file['name']).'</a></p>';
            }
        }

        return trim($html === '' ? $extra : $html.$extra);
    }

    public function url(string $conversationId, string $path): string
    {
        $hours = max(1, (int) config('agentic.widget.attachments.signed_url_hours', 12));

        return URL::temporarySignedRoute(
            'agentic.widget.conversations.files.show',
            now()->addHours($hours),
            ['id' => $conversationId, 'file' => basename($path)],
            absolute: false,
        );
    }

    public function adminUrl(string $conversationId, string $path): string
    {
        $prefix = trim((string) config('agentic.admin.api.prefix', 'api/agentic/admin'), '/');
        $file = basename($path);

        return '/'.$prefix.'/conversations/'.$conversationId.'/files/'.$file;
    }

    public function absolutePath(string $conversationId, string $file): ?string
    {
        $safe = basename($file);
        if ($safe === '' || $safe !== $file || str_contains($safe, '..')) {
            return null;
        }

        $relative = 'agentic/attachments/'.$conversationId.'/'.$safe;
        $disk = Storage::disk('local');
        if (! $disk->exists($relative)) {
            return null;
        }

        $absolute = $disk->path($relative);
        $root = $disk->path('agentic/attachments/'.$conversationId);
        if ($absolute === '' || ! str_starts_with($absolute, $root)) {
            return null;
        }

        return $absolute;
    }

    public function mime(string $absolute): string
    {
        $mime = mime_content_type($absolute);

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }

    private function allowed(string $mime): bool
    {
        $allowed = config('agentic.widget.attachments.mimes', [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf',
        ]);

        return in_array($mime, $allowed, true);
    }
}
