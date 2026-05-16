<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResultSheetTemplateRequest;
use App\Http\Requests\UpdateResultSheetTemplateRequest;
use App\Models\AptitudeArea;
use App\Models\ResultSheetTemplate;
use App\Services\ResultSheetTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class ResultSheetTemplateController extends Controller
{
    public function __construct(
        private ResultSheetTemplateService $templateService
    ) {}

    public function index(): Response
    {
        $templates = ResultSheetTemplate::query()->orderBy('name')->get([
            'id', 'name', 'mode', 'paper_size', 'orientation', 'logical_unit', 'is_active', 'created_at',
        ]);

        return Inertia::render('Admin/ResultSheetTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        $domains = AptitudeArea::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']);
        $domainPlaceholders = $domains->map(fn ($d) => [
            'slug' => $this->templateService->aptitudeAreaSlug($d->name),
            'name' => $d->name,
            'code' => $d->code,
            'example' => '{{'.$this->templateService->aptitudeAreaSlug($d->name).'}}',
            'exampleRaw' => '{{'.$this->templateService->aptitudeAreaSlug($d->name).'_raw}}',
        ])->values()->all();

        return Inertia::render('Admin/ResultSheetTemplates/Create', [
            'placeholdersApplicant1' => ['{{applicant_name}}', '{{applicant_reference}}', '{{exam_date}}', '{{room_name}}', '{{scores_rows}}', '{{overall_pct}}'],
            'placeholdersApplicant2' => ['{{applicant_name_2}}', '{{applicant_reference_2}}', '{{scores_rows_2}}', '{{overall_pct_2}}'],
            'domainPlaceholders' => $domainPlaceholders,
            'htmlScoresNote' => 'For the scores table in HTML mode, put this inside tbody: <code>&lt;tr class="scores-rows-placeholder"&gt;&lt;td colspan="3"&gt;&lt;/td&gt;&lt;/tr&gt;</code> (Do not use {{scores_rows}}; it is stripped by the sanitizer.)',
            'htmlTemplateRules' => 'Single root div. Placeholders: {{applicant_name}}, {{applicant_reference}}, {{exam_date}}, {{room_name}}, {{overall_pct}}. Half-crosswise: keep content under ~148mm height per sheet (use p-4, text-sm, space-y-3). Full page: ~297mm. No scripts.',
            'docxPlaceholderNote' => 'DOCX supports {{applicant_name}}, {{scores_rows}} (block), and per-domain placeholders like {{spatial_awareness}} and {{spatial_awareness_raw}}. See the list below. Add _2 for applicant 2 in dual layout.',
            'layoutOptions' => [
                'full' => 'Full page',
                'half_a4' => 'Half-crosswise',
            ],
        ]);
    }

    public function store(StoreResultSheetTemplateRequest $request): RedirectResponse
    {
        $mode = $request->validated('mode', 'html');
        $data = [
            'name' => $request->validated('name'),
            'mode' => $mode,
            'paper_size' => $request->validated('paper_size', 'a4'),
            'orientation' => $request->validated('orientation', 'portrait'),
            'logical_unit' => $request->validated('logical_unit', 'full'),
            'is_active' => $request->validated('is_active', true),
        ];

        if ($mode === ResultSheetTemplate::MODE_HTML) {
            $data['content'] = Purifier::clean($request->validated('content'), 'result_sheet');
            $data['docx_path'] = null;
            $template = ResultSheetTemplate::create($data);
        } else {
            $data['content'] = '';
            $data['docx_path'] = null;
            $template = ResultSheetTemplate::create($data);
            $file = $request->file('docx');
            $path = $file->storeAs(
                'result-sheet-templates',
                $template->id.'.docx',
                'local'
            );
            $template->update(['docx_path' => $path]);
        }

        return redirect()->route('admin.release.result-templates.index')->with('success', 'Template created.');
    }

    public function edit(ResultSheetTemplate $result_template): Response
    {
        $domains = AptitudeArea::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']);
        $domainPlaceholders = $domains->map(fn ($d) => [
            'slug' => $this->templateService->aptitudeAreaSlug($d->name),
            'name' => $d->name,
            'code' => $d->code,
            'example' => '{{'.$this->templateService->aptitudeAreaSlug($d->name).'}}',
            'exampleRaw' => '{{'.$this->templateService->aptitudeAreaSlug($d->name).'_raw}}',
        ])->values()->all();

        return Inertia::render('Admin/ResultSheetTemplates/Edit', [
            'template' => $result_template,
            'placeholdersApplicant1' => ['{{applicant_name}}', '{{applicant_reference}}', '{{exam_date}}', '{{room_name}}', '{{scores_rows}}', '{{overall_pct}}'],
            'placeholdersApplicant2' => ['{{applicant_name_2}}', '{{applicant_reference_2}}', '{{scores_rows_2}}', '{{overall_pct_2}}'],
            'domainPlaceholders' => $domainPlaceholders,
            'htmlScoresNote' => 'For the scores table in HTML mode, put this inside tbody: <code>&lt;tr class="scores-rows-placeholder"&gt;&lt;td colspan="3"&gt;&lt;/td&gt;&lt;/tr&gt;</code> (Do not use {{scores_rows}}; it is stripped by the sanitizer.)',
            'htmlTemplateRules' => 'Single root div. Placeholders: {{applicant_name}}, {{applicant_reference}}, {{exam_date}}, {{room_name}}, {{overall_pct}}. Half-crosswise: keep content under ~148mm height per sheet (use p-4, text-sm, space-y-3). Full page: ~297mm. No scripts.',
            'docxPlaceholderNote' => 'DOCX supports {{applicant_name}}, {{scores_rows}} (block), and per-domain placeholders like {{spatial_awareness}} and {{spatial_awareness_raw}}. See the list below. Add _2 for applicant 2 in dual layout.',
            'layoutOptions' => [
                'full' => 'Full page',
                'half_a4' => 'Half-crosswise',
            ],
        ]);
    }

    public function update(UpdateResultSheetTemplateRequest $request, ResultSheetTemplate $result_template): RedirectResponse
    {
        $mode = $request->validated('mode', $result_template->mode);
        $data = array_filter([
            'name' => $request->validated('name'),
            'mode' => $mode,
            'paper_size' => $request->validated('paper_size'),
            'orientation' => $request->validated('orientation'),
            'logical_unit' => $request->validated('logical_unit'),
            'is_active' => $request->validated('is_active'),
        ], fn ($v) => $v !== null);

        if ($mode === ResultSheetTemplate::MODE_HTML) {
            if ($request->has('content')) {
                $data['content'] = Purifier::clean($request->validated('content'), 'result_sheet');
            }
            if ($result_template->docx_path) {
                Storage::disk('local')->delete($result_template->docx_path);
                $data['docx_path'] = null;
            }
        } else {
            $data['content'] = '';
            if ($request->hasFile('docx')) {
                if ($result_template->docx_path) {
                    Storage::disk('local')->delete($result_template->docx_path);
                }
                $path = $request->file('docx')->storeAs(
                    'result-sheet-templates',
                    $result_template->id.'.docx',
                    'local'
                );
                $data['docx_path'] = $path;
            } elseif (! $result_template->docx_path) {
                return redirect()->back()->withErrors(['docx' => 'DOCX file is required for DOCX mode.']);
            }
        }

        $result_template->update($data);

        return redirect()->route('admin.release.result-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(ResultSheetTemplate $result_template): RedirectResponse
    {
        if ($result_template->docx_path) {
            Storage::disk('local')->delete($result_template->docx_path);
        }
        $result_template->delete();

        return redirect()->route('admin.release.result-templates.index')->with('success', 'Template deleted.');
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'mode' => ['required', Rule::in(['html', 'docx'])],
            'content' => ['required_if:mode,html', 'nullable', 'string'],
            'template_id' => ['nullable', 'exists:result_sheet_templates,id'],
            'docx' => ['nullable', 'file', 'max:5120'],
        ]);

        $mode = $request->input('mode');
        if ($mode === 'docx' && ! $request->hasFile('docx') && ! $request->input('template_id')) {
            return response()->json(['error' => 'Either docx file or template_id is required for DOCX preview.'], 422);
        }
        $paperSize = $request->input('paper_size', 'a4');
        $orientation = $request->input('orientation', 'portrait');
        $logicalUnit = $request->input('logical_unit', 'full');

        try {
            if ($mode === 'html') {
                $content = Purifier::clean($request->input('content', ''), 'result_sheet');
                $renderResult = $this->templateService->renderHtmlContent($content, [], true, $paperSize, $orientation, $logicalUnit);
            } else {
                if ($request->hasFile('docx')) {
                    $uploaded = $request->file('docx');
                    $path = $uploaded->getRealPath() ?: $uploaded->getPathname();
                    $renderResult = $this->templateService->renderDocxFile($path, [], true, $paperSize, $orientation, $logicalUnit);
                } else {
                    $template = ResultSheetTemplate::findOrFail($request->input('template_id'));
                    $renderResult = $this->templateService->render($template, [], true);
                }
            }

            $dims = $renderResult->pageDimensions();

            return response()->json([
                'html' => $renderResult->html,
                'dimensions' => [
                    'width' => $dims['width'].'mm',
                    'height' => $dims['height'].'mm',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
