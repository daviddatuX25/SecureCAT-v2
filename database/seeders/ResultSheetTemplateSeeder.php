<?php

namespace Database\Seeders;

use App\Models\ResultSheetTemplate;
use Illuminate\Database\Seeder;

class ResultSheetTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $content = <<<'HTML'
<div class="border border-foreground/20 rounded p-4 space-y-3" style="font-size: 0.875rem;">
  <div class="text-center">
    <h1 class="text-base font-bold">SecureCAT — Result Release Sheet</h1>
    <p class="text-xs text-muted-foreground mt-0.5">Confidential · Half folio</p>
  </div>
  <p class="leading-relaxed">
    This certifies that <strong>{{applicant_name}}</strong> (ref: <strong>{{applicant_reference}}</strong>)
    took the exam on <strong>{{exam_date}}</strong> at <strong>{{room_name}}</strong>:
  </p>
  <table class="w-full text-sm border-collapse">
    <thead><tr><th>Pillar</th><th>Score</th><th>%</th></tr></thead>
    <tbody><tr class="scores-rows-placeholder"><td colspan="3"></td></tr></tbody>
    <tfoot><tr><td>Overall</td><td>—</td><td>{{overall_pct}}%</td></tr></tfoot>
  </table>
  <div class="flex justify-between items-end pt-4">
    <div><div class="border-b w-24">&nbsp;</div><p class="text-xs">Counselor</p></div>
    <div><div class="border-b w-24">&nbsp;</div><p class="text-xs">Principal</p></div>
  </div>
</div>
HTML;

        ResultSheetTemplate::updateOrCreate(
            ['name' => 'Default'],
            [
                'mode' => 'html',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'logical_unit' => 'half_a4',
                'content' => $content,
                'docx_path' => null,
                'is_active' => true,
            ]
        );
    }
}
