<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$form = DB::table('audit_forms')->where('code', '1130C')->first();
echo "Form: {$form->code} - {$form->name}\n\n";

$sections = DB::table('audit_form_sections')->where('form_id', $form->id)->orderBy('section_order')->get();
foreach ($sections as $s) {
    echo "[Section: {$s->section_name}]\n";
    $fields = DB::table('audit_form_fields')->where('section_id', $s->id)->orderBy('field_order')->get();
    $resp = DB::table('audit_form_responses')->where('form_id', $form->id)->first();
    foreach ($fields as $f) {
        echo "  {$f->field_name} ({$f->field_type})\n";
        if ($resp) {
            $ans = DB::table('audit_form_answers')
                ->where('response_id', $resp->id)
                ->where('field_id', $f->id)
                ->first();
            if ($ans) {
                $val = mb_substr($ans->answer_value, 0, 120);
                echo "    => {$val}\n";
            }
        }
    }
    echo "\n";
}
