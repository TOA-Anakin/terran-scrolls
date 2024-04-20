<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Background;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class SetWorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::factory()->create([
            'email' => 'jane.doe@example.com',
            'password' => 'JaneDoe123',
            'role_id' => 1
        ]);

        $backgrounds = Background::limit(50)->get();

        $workspace = Workspace::factory()->create([
            'name' => 'Super Dev Team',
            'slug' => StringHelper::sanitizeForSlug('Super Dev Team'),
            'user_id' => $user->id
        ]);

        $projectTitles = [
            'Project Management', 'Remote Team Meetings', 'Meeting Agenda', 'Agile Board Template',
            'Company Overview', 'Design Huddle', 'Go To Market Strategy Template', 'Kanban Template',
            'Personal Productivity System', 'Simple Project Board', 'Weekly Planning'
        ];

        $projectTitle = $projectTitles[array_rand($projectTitles)];

        Project::factory()->create([
            'title' => $projectTitle,
            'slug' => StringHelper::sanitizeForSlug($projectTitle),
            'user_id' => $user->id,
            'background_id' => $backgrounds->random()->id,
            'workspace_id' => $workspace->id
        ]);
    }
}
