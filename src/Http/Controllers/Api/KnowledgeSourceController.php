<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KnowledgeSourceController
{
    public function __construct(
        private KnowledgeRepository $sources,
        private KnowledgeOrchestrator $knowledge,
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

    public function indexDocuments(string $slug): JsonResponse
    {
        $source = $this->sources->findBySlug($slug);

        if ($source === null) {
            return response()->json(['message' => 'Knowledge source not found.'], 404);
        }

        $this->knowledge->index($source);

        return response()->json(['message' => 'Knowledge source indexed.']);
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
