<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDecisionRuleRequest;
use App\Http\Requests\UpdateDecisionRuleRequest;
use App\Models\Course;
use App\Models\DecisionRule;
use App\Models\ExamDomain;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationRulesController extends Controller
{
    public function index(): Response
    {
        $rules = DecisionRule::query()
            ->with(['course:id,name,code', 'domain:id,name'])
            ->orderBy('course_id')
            ->orderBy('domain_id')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'course_id' => $r->course_id,
                'course_name' => $r->course?->name ?? '—',
                'domain_id' => $r->domain_id,
                'domain_name' => $r->domain?->name ?? 'Any',
                'min_score' => $r->min_score,
                'max_score' => $r->max_score,
                'note' => $r->note,
                'is_active' => $r->is_active,
            ]);

        $courses = Course::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $domains = ExamDomain::query()->where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']);

        return Inertia::render('Consultation/Rules/Index', [
            'rules' => $rules->values()->all(),
            'courses' => $courses->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code])->values()->all(),
            'domains' => $domains->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code])->values()->all(),
        ]);
    }

    public function store(StoreDecisionRuleRequest $request): RedirectResponse
    {
        DecisionRule::create([
            'course_id' => $request->validated('course_id'),
            'domain_id' => $request->validated('domain_id') ?: null,
            'min_score' => $request->validated('min_score'),
            'max_score' => $request->validated('max_score'),
            'note' => $request->validated('note'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('consultation.rules.index')->with('success', 'Rule created.');
    }

    public function update(UpdateDecisionRuleRequest $request, DecisionRule $decision_rule): RedirectResponse
    {
        $decision_rule->update($request->validated());

        return redirect()->route('consultation.rules.index')->with('success', 'Rule updated.');
    }

    public function destroy(DecisionRule $decision_rule): RedirectResponse
    {
        $decision_rule->delete();

        return redirect()->route('consultation.rules.index')->with('success', 'Rule deleted.');
    }
}
