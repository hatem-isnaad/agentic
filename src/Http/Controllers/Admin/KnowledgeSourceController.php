<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\KnowledgeSourceAdminService;
use Agentic\Http\Requests\Admin\StoreKnowledgeSourceRequest;
use Agentic\Http\Requests\Admin\UpdateKnowledgeSourceRequest;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Http\Support\KnowledgeIngestPayload;
use Agentic\Knowledge\KnowledgeIngestor;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KnowledgeSourceController
{
    public function __construct(
        private KnowledgeSourceAdminService $sources,
        private KnowledgeOrchestrator $knowledge,
        private KnowledgeIngestor $ingestor,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = array_map(fn ($source) => $source->toArray(), $this->sources->list());

        return response()->json(AdminPaginator::paginate($items, $request));
    }

    public function show(string $slug): JsonResponse
    {
        $source = $this->sources->find($slug);

        if ($source === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        return response()->json(['data' => $source->toArray(), 'meta' => AdminLocaleMeta::build()]);
    }

    public function store(StoreKnowledgeSourceRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->sources->store($request->toData())->toArray()], 201);
    }

    public function update(UpdateKnowledgeSourceRequest $request, string $slug): JsonResponse
    {
        if ($this->sources->find($slug) === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        return response()->json(['data' => $this->sources->update($slug, $request->toData())->toArray()]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->sources->delete($slug)) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        return response()->json(null, 204);
    }

    public function indexDocuments(string $slug): JsonResponse
    {
        $source = $this->sources->find($slug);

        if ($source === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        $this->knowledge->reindex($source->toDefinition());

        return response()->json(['message' => 'Knowledge source indexed.']);
    }

    public function ingest(Request $request, string $slug): JsonResponse
    {
        if ($this->sources->find($slug) === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        $payload = KnowledgeIngestPayload::fromRequest($request);

        try {
            $source = $this->ingestor->ingest($slug, $payload, (bool) ($payload['reindex'] ?? true));
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'slug' => $source->slug,
                'name' => $source->name,
                'driver' => $source->driver,
                'status' => $source->status,
                'config' => $source->configuration,
            ],
            'meta' => array_merge(AdminLocaleMeta::build(), $this->ingestor->preview($source)),
        ]);
    }

    public function search(Request $request, string $slug): JsonResponse
    {
        $source = $this->sources->find($slug);

        if ($source === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        $validated = $request->validate([
            'query' => ['required', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $chunks = $this->knowledge->retrieveFromSources(
            [$slug],
            $validated['query'],
            null,
            (int) ($validated['limit'] ?? 5),
        );

        $results = array_map(fn ($chunk) => [
            'content' => $chunk->content,
            'source' => $chunk->source,
            'score' => $chunk->score,
            'metadata' => $chunk->metadata,
        ], $chunks);

        return response()->json(['data' => $results]);
    }
}
