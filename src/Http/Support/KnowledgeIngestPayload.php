<?php

namespace Agentic\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class KnowledgeIngestPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request): array
    {
        if (is_string($request->input('urls'))) {
            $lines = preg_split('/\r\n|\r|\n/', $request->input('urls')) ?: [];
            $request->merge([
                'urls' => array_values(array_filter(array_map('trim', $lines))),
            ]);
        }

        $payload = $request->validate([
            'format' => ['nullable', 'string', 'in:text,plain,txt,markdown,html,json,pdf'],
            'documents' => ['nullable'],
            'raw_text' => ['nullable', 'string'],
            'urls' => ['nullable', 'array'],
            'urls.*' => ['url', 'max:2048'],
            'chunk_size' => ['nullable', 'integer', 'min:100', 'max:8000'],
            'chunk_overlap' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'tenant' => ['nullable', 'string', 'max:191'],
            'reindex' => ['nullable'],
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => ['file', 'mimes:pdf', 'max:15360'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        if (array_key_exists('reindex', $payload)) {
            $payload['reindex'] = filter_var($payload['reindex'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        }

        $uploads = self::collectUploads($request);

        if ($uploads !== []) {
            $payload['format'] = $payload['format'] ?? 'pdf';
            $documents = [];

            foreach ($uploads as $uploaded) {
                if (! $uploaded instanceof UploadedFile || ! $uploaded->isValid()) {
                    continue;
                }

                $binary = $uploaded->get();

                if ($binary === '') {
                    continue;
                }

                $documents[] = [
                    'title' => $uploaded->getClientOriginalName(),
                    'content' => $binary,
                ];
            }

            if ($documents !== []) {
                $payload['documents'] = $documents;
            }
        }

        return $payload;
    }

    /**
     * @return list<UploadedFile>
     */
    private static function collectUploads(Request $request): array
    {
        $files = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file instanceof UploadedFile) {
                    $files[] = $file;
                }
            }
        }

        if ($request->hasFile('file')) {
            $single = $request->file('file');

            if ($single instanceof UploadedFile) {
                $files[] = $single;
            }
        }

        return $files;
    }
}
