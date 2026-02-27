<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdmissionSlipTemplateRequest;
use App\Http\Requests\UpdateAdmissionSlipTemplateRequest;
use App\Models\AdmissionSlipTemplate;
use App\Services\AdmissionSlipTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Mews\Purifier\Facades\Purifier;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionSlipTemplateController extends Controller
{
    public function __construct(
        private AdmissionSlipTemplateService $templateService
    ) {}

    public function index(): Response
    {
        $templates = AdmissionSlipTemplate::query()->orderBy('name')->get([
            'id', 'name', 'mode', 'paper_size', 'orientation', 'logical_unit', 'is_active', 'created_at',
        ]);

        return Inertia::render('Admin/AdmissionSlipTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AdmissionSlipTemplates/Create', [
            'placeholders' => ['{{reference_number}}', '{{full_name}}', '{{birthdate}}', '{{sex}}', '{{course_1}}', '{{course_2}}', '{{course_3}}', '{{photo_placeholder}}', '{{qr_placeholder}}'],
            'htmlTemplateRules' => 'Placeholders: reference_number, full_name, birthdate, sex, course_1, course_2, course_3, photo_placeholder, qr_placeholder. Half layout: 2 slips per physical page.',
            'docxPlaceholderNote' => 'DOCX supports the same placeholders. Add _2 for applicant 2 in dual layout.',
            'layoutOptions' => [
                'full' => 'Full page',
                'half_a4' => 'Half A4',
                'half_legal' => 'Half Legal',
                'half_letter' => 'Half Letter',
            ],
        ]);
    }

    public function store(StoreAdmissionSlipTemplateRequest $request): RedirectResponse
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

        if ($mode === AdmissionSlipTemplate::MODE_HTML) {
            $data['content'] = Purifier::clean($request->validated('content'), 'admission_slip');
            $data['docx_path'] = null;
            $template = AdmissionSlipTemplate::create($data);
        } else {
            $data['content'] = '';
            $data['docx_path'] = null;
            $template = AdmissionSlipTemplate::create($data);
            $file = $request->file('docx');
            $path = $file->storeAs('admission-slip-templates', $template->id.'.docx', 'local');
            $template->update(['docx_path' => $path]);
        }

        return redirect()->route('admin.admission-slip-templates.index')->with('success', 'Template created.');
    }

    public function edit(AdmissionSlipTemplate $admission_slip_template): Response
    {
        return Inertia::render('Admin/AdmissionSlipTemplates/Edit', [
            'template' => $admission_slip_template,
            'placeholders' => ['{{reference_number}}', '{{full_name}}', '{{birthdate}}', '{{sex}}', '{{course_1}}', '{{course_2}}', '{{course_3}}', '{{photo_placeholder}}', '{{qr_placeholder}}'],
            'htmlTemplateRules' => 'Placeholders: reference_number, full_name, birthdate, sex, course_1, course_2, course_3, photo_placeholder, qr_placeholder. Half layout: 2 slips per physical page.',
            'docxPlaceholderNote' => 'DOCX supports the same placeholders. Add _2 for applicant 2 in dual layout.',
            'layoutOptions' => [
                'full' => 'Full page',
                'half_a4' => 'Half A4',
                'half_legal' => 'Half Legal',
                'half_letter' => 'Half Letter',
            ],
        ]);
    }

    public function update(UpdateAdmissionSlipTemplateRequest $request, AdmissionSlipTemplate $admission_slip_template): RedirectResponse
    {
        $mode = $request->validated('mode', $admission_slip_template->mode);
        $data = array_filter([
            'name' => $request->validated('name'),
            'mode' => $mode,
            'paper_size' => $request->validated('paper_size'),
            'orientation' => $request->validated('orientation'),
            'logical_unit' => $request->validated('logical_unit'),
            'is_active' => $request->validated('is_active'),
        ], fn ($v) => $v !== null);

        if ($mode === AdmissionSlipTemplate::MODE_HTML) {
            if ($request->has('content')) {
                $data['content'] = Purifier::clean($request->validated('content'), 'admission_slip');
            }
            if ($admission_slip_template->docx_path) {
                Storage::disk('local')->delete($admission_slip_template->docx_path);
                $data['docx_path'] = null;
            }
        } else {
            $data['content'] = '';
            if ($request->hasFile('docx')) {
                if ($admission_slip_template->docx_path) {
                    Storage::disk('local')->delete($admission_slip_template->docx_path);
                }
                $path = $request->file('docx')->storeAs('admission-slip-templates', $admission_slip_template->id.'.docx', 'local');
                $data['docx_path'] = $path;
            } elseif (! $admission_slip_template->docx_path) {
                return redirect()->back()->withErrors(['docx' => 'DOCX file is required for DOCX mode.']);
            }
        }

        $admission_slip_template->update($data);

        return redirect()->route('admin.admission-slip-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(AdmissionSlipTemplate $admission_slip_template): RedirectResponse
    {
        if ($admission_slip_template->docx_path) {
            Storage::disk('local')->delete($admission_slip_template->docx_path);
        }
        $admission_slip_template->delete();

        return redirect()->route('admin.admission-slip-templates.index')->with('success', 'Template deleted.');
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'mode' => ['required', Rule::in(['html', 'docx'])],
            'content' => ['required_if:mode,html', 'nullable', 'string'],
            'template_id' => ['nullable', 'exists:admission_slip_templates,id'],
            'docx' => ['nullable', 'file', 'mimes:docx', 'max:5120'],
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
                $content = Purifier::clean($request->input('content', ''), 'admission_slip');
                $html = $this->templateService->renderHtmlContent($content, [], true);
            } else {
                if ($request->hasFile('docx')) {
                    $path = $request->file('docx')->getRealPath();
                    $html = $this->templateService->renderDocxFile($path, [], true);
                } else {
                    $template = AdmissionSlipTemplate::findOrFail($request->input('template_id'));
                    $html = $this->templateService->render($template, [], true);
                }
            }

            $dimensions = $this->templateService->previewDimensions($paperSize, $orientation, $logicalUnit);

            return response()->json(['html' => $html, 'dimensions' => $dimensions]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
