<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Knowledge\KnowledgeIngestor;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KnowledgeSourceController
{
    public function __construct(
        private KnowledgeRepository $sources,
        private KnowledgeOrchestrator $knowledge,
        private KnowledgeIngestor $ingestor,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_map(
                fn (KnowledgeSourceDefinition $source) => $this->serialize($source),
                $this->sources->allPublished(),
            ),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $source = $this->sources->findBySlug($slug);

        if ($source === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        return response()->json(['data' => $this->serialize($source)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $source = $this->sources->save($this->toDefinition($validated));

        return response()->json(['data' => $this->serialize($source)], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        if ($this->sources->findBySlug($slug) === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        $validated = $this->validatePayload($request, $slug);

        $source = $this->sources->save($this->toDefinition($validated));

        return response()->json(['data' => $this->serialize($source)]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->sources->delete($slug)) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        return response()->json(null, 204);
    }

    public function indexDocuments(Request $request, string $slug): JsonResponse
    {
        $source = $this->sources->findBySlug($slug);

        if ($source === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        $payload = $request->validate([
            'documents' => ['nullable', 'array'],
            'raw_text' => ['nullable', 'string'],
            'chunk_size' => ['nullable', 'integer', 'min:100', 'max:8000'],
            'chunk_overlap' => ['nullable', 'integer', 'min:0', 'max:2000'],
        ]);

        if ($payload !== []) {
            $configuration = $source->configuration;

            if (isset($payload['documents'])) {
                $configuration['documents'] = $payload['documents'];
            }

            if (isset($payload['raw_text'])) {
                $configuration['raw_text'] = $payload['raw_text'];
            }

            if (isset($payload['chunk_size'])) {
                $configuration['chunk_size'] = $payload['chunk_size'];
            }

            if (isset($payload['chunk_overlap'])) {
                $configuration['chunk_overlap'] = $payload['chunk_overlap'];
            }

            $source = $this->sources->save(new KnowledgeSourceDefinition(
                slug: $source->slug,
                name: $source->name,
                driver: $source->driver,
                configuration: $configuration,
                status: $source->status,
                metadata: $source->metadata,
            ));
        }

        $this->knowledge->reindex($source);

        return response()->json(['message' => 'Knowledge source indexed.']);
    }

    public function ingest(Request $request, string $slug): JsonResponse
    {
        if ($this->sources->findBySlug($slug) === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        $payload = $request->validate([
            'format' => ['nullable', 'string', 'in:text,plain,txt,markdown,html,json'],
            'documents' => ['nullable'],
            'raw_text' => ['nullable', 'string'],
            'urls' => ['nullable', 'array'],
            'urls.*' => ['url', 'max:2048'],
            'chunk_size' => ['nullable', 'integer', 'min:100', 'max:8000'],
            'chunk_overlap' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'tenant' => ['nullable', 'string', 'max:191'],
            'reindex' => ['nullable', 'boolean'],
        ]);

        try {
            $source = $this->ingestor->ingest($slug, $payload, (bool) ($payload['reindex'] ?? true));
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->serialize($source),
            'meta' => $this->ingestor->preview($source),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?string $slug = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'driver' => ['nullable', 'string', 'in:array,vector'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'config' => ['nullable', 'array'],
        ]);

        if ($slug !== null) {
            $validated['slug'] = $slug;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function toDefinition(array $validated): KnowledgeSourceDefinition
    {
        return new KnowledgeSourceDefinition(
            slug: $validated['slug'],
            name: $validated['name'],
            driver: $validated['driver'] ?? 'array',
            configuration: $validated['config'] ?? [],
            status: $validated['status'] ?? 'draft',
            metadata: [
                'description' => $validated['description'] ?? null,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(KnowledgeSourceDefinition $source): array
    {
        return [
            'slug' => $source->slug,
            'name' => $source->name,
            'description' => $source->metadata['description'] ?? null,
            'driver' => $source->driver,
            'status' => $source->status,
            'config' => $source->configuration,
        ];
    }
}
