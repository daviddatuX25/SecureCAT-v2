<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamDomainRequest;
use App\Http\Requests\UpdateExamDomainRequest;
use App\Models\ExamDomain;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExamDomainController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ExamDomain::class);

        $examDomains = ExamDomain::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ExamDomain $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'description' => $d->description,
                'max_items' => $d->max_items,
                'display_order' => $d->display_order,
                'is_active' => $d->is_active,
            ]);

        return Inertia::render('Admin/ExamDomains/Index', [
            'exam_domains' => $examDomains,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ExamDomain::class);

        return Inertia::render('Admin/ExamDomains/Create');
    }

    public function store(StoreExamDomainRequest $request): RedirectResponse
    {
        $data = $request->validated();

        ExamDomain::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'max_items' => (int) $data['max_items'],
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('admin.exam-domains.index')->with('success', 'Exam pillar created.');
    }

    public function edit(ExamDomain $examDomain): Response
    {
        $this->authorize('update', $examDomain);

        return Inertia::render('Admin/ExamDomains/Edit', [
            'exam_domain' => [
                'id' => $examDomain->id,
                'name' => $examDomain->name,
                'code' => $examDomain->code,
                'description' => $examDomain->description ?? '',
                'max_items' => $examDomain->max_items,
                'display_order' => $examDomain->display_order,
                'is_active' => $examDomain->is_active,
            ],
        ]);
    }

    public function update(UpdateExamDomainRequest $request, ExamDomain $examDomain): RedirectResponse
    {
        $data = $request->validated();

        $examDomain->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'max_items' => (int) $data['max_items'],
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('admin.exam-domains.index')->with('success', 'Exam pillar updated.');
    }
}
