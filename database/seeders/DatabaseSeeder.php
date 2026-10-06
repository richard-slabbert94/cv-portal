<?php

namespace Database\Seeders;

use App\Models\AccessCode;
use App\Models\CvVersion;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Seeds the "Master" CV from database/data/master-cv.json. */
    public function run(): void
    {
        $master = CvVersion::create([
            'name' => 'Master',
            'content' => json_decode(file_get_contents(database_path('data/master-cv.json')), true, flags: JSON_THROW_ON_ERROR),
        ]);

        if (app()->isLocal()) {
            $code = new AccessCode(['cv_version_id' => $master->id, 'company' => 'Demo Company', 'role' => 'Senior Developer',
                'cover_letter' => "Dear Hiring Team,\n\n[Paragraph 1 — the hook: name the role, and one specific reason this company (not any company) appeals to you.]\n\n[Paragraph 2 — the proof: pick the 2 requirements from their job ad that matter most and show, with numbers, where you have already done exactly that.]\n\n[Paragraph 3 — the close: what you would focus on in your first months, and a confident call to action.]\n\nKind regards,\n".config('cv.owner_name'),
            ]);
            $code->setPin('DEMO2026')->save();
        }
    }
}
