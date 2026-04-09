<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCsvImportRequest;
use App\Http\Requests\StoreKnowledgeDocumentRequest;
use App\Http\Requests\UpdateKnowledgeDocumentRequest;
use App\Models\KnowledgeDocument;
use App\Models\SystemSetting;
use App\Services\CsvToNarrativeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        if (! SystemSetting::aiCompanionEnabled()) {
            abort(403, 'AI Companion is disabled.');
        }

        $this->authorize('viewAny', KnowledgeDocument::class);

        $query = KnowledgeDocument::query()->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $term = '%' . $request->get('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('content', 'like', $term);
            });
        }

        $documents = $query->paginate(15)->withQueryString()->through(function (KnowledgeDocument $doc) {
            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'content' => \Illuminate\Support\Str::limit($doc->content, 120),
                'metadata' => $doc->metadata,
                'metadata_summary' => $doc->metadata_summary,
                'source' => $doc->source,
                'is_active' => $doc->is_active,
                'created_at' => $doc->created_at?->toIso8601String(),
                'updated_at' => $doc->updated_at?->toIso8601String(),
            ];
        });

        return Inertia::render('Admin/KnowledgeDocuments/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', KnowledgeDocument::class);

        return Inertia::render('Admin/KnowledgeDocuments/Create');
    }

    public function store(StoreKnowledgeDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        KnowledgeDocument::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'metadata' => $validated['metadata'] ?? [],
            'source' => $validated['source'] ?? KnowledgeDocument::SOURCE_MANUAL,
            'is_active' => true,
        ]);

        return redirect()->route('admin.knowledge-documents.index')->with('success', 'Knowledge document created.');
    }

    public function edit(KnowledgeDocument $knowledgeDocument): Response
    {
        $this->authorize('update', $knowledgeDocument);

        return Inertia::render('Admin/KnowledgeDocuments/Edit', [
            'document' => [
                'id' => $knowledgeDocument->id,
                'title' => $knowledgeDocument->title,
                'content' => $knowledgeDocument->content,
                'metadata' => $knowledgeDocument->metadata ?? [],
                'source' => $knowledgeDocument->source,
                'is_active' => $knowledgeDocument->is_active,
                'created_at' => $knowledgeDocument->created_at?->toIso8601String(),
                'updated_at' => $knowledgeDocument->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(UpdateKnowledgeDocumentRequest $request, KnowledgeDocument $knowledgeDocument): RedirectResponse
    {
        $validated = $request->validated();

        $knowledgeDocument->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'metadata' => $validated['metadata'] ?? $knowledgeDocument->metadata,
            'is_active' => array_key_exists('is_active', $validated) ? $validated['is_active'] : $knowledgeDocument->is_active,
        ]);

        return redirect()->route('admin.knowledge-documents.index')->with('success', 'Knowledge document updated.');
    }

    public function destroy(KnowledgeDocument $knowledgeDocument): RedirectResponse
    {
        $this->authorize('delete', $knowledgeDocument);

        $knowledgeDocument->delete();

        return redirect()->route('admin.knowledge-documents.index')->with('success', 'Knowledge document deleted.');
    }

    /**
     * T6: Show CSV import form.
     */
    public function importForm(): Response
    {
        $this->authorize('create', KnowledgeDocument::class);

        return Inertia::render('Admin/KnowledgeDocuments/Import');
    }

    /**
     * T6: Import CSV and create knowledge doc with admin-provided metadata.
     */
    public function import(StoreCsvImportRequest $request, CsvToNarrativeService $csvService): RedirectResponse
    {
        $this->authorize('create', KnowledgeDocument::class);

        try {
            $csvService->validateFile($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $result = $csvService->convert($content);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        $metadata = $request->validated('metadata') ?? [];
        $metadata = array_filter($metadata, fn ($v) => $v !== null && $v !== '');

        KnowledgeDocument::create([
            'title' => $request->validated('title'),
            'content' => $result['content'],
            'metadata' => $metadata,
            'source' => KnowledgeDocument::SOURCE_CSV_IMPORT,
            'is_active' => true,
        ]);

        return redirect()->route('admin.knowledge-documents.index')
            ->with('success', "CSV imported: {$result['row_count']} rows converted to narrative.");
    }
}
