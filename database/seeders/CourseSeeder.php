<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\World;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Create one topic-matched course inside each existing world.
     */
    public function run(): void
    {
        $courses = [
            'math-for-programmers' => [
                'name' => 'Number Wizardry',
                'slug' => 'number-wizardry',
                'description' => 'Master variables, operators, and the math tricks programmers use every day.',
                'age_tier' => 'builder',
                'difficulty' => 2,
                'min_level_requirement' => 1,
                'estimated_duration' => 45,
            ],
            'computers-in-cloud' => [
                'name' => 'Cloud Foundations',
                'slug' => 'cloud-foundations',
                'description' => 'Learn what servers, networks, and the cloud really are by exploring the sky above the data centers.',
                'age_tier' => 'builder',
                'difficulty' => 2,
                'min_level_requirement' => 1,
                'estimated_duration' => 40,
            ],
            'data-engineering-for-dummies' => [
                'name' => 'Data Pipelines 101',
                'slug' => 'data-pipelines-101',
                'description' => 'Follow data on its journey from messy raw input to clean, organized treasure.',
                'age_tier' => 'coder',
                'difficulty' => 3,
                'min_level_requirement' => 1,
                'estimated_duration' => 50,
            ],
            'the-mythic-world-of-ai' => [
                'name' => 'Awakening the Machine Mind',
                'slug' => 'awakening-the-machine-mind',
                'description' => 'Discover how machines learn from examples to recognize, predict, and create.',
                'age_tier' => 'coder',
                'difficulty' => 3,
                'min_level_requirement' => 1,
                'estimated_duration' => 50,
            ],
        ];

        foreach ($courses as $worldSlug => $course) {
            $world = World::where('slug', $worldSlug)->first();

            if ($world === null) {
                $this->command->warn("World [{$worldSlug}] not found, skipping course [{$course['name']}]. Run WorldSeeder first.");

                continue;
            }

            Course::firstOrCreate(
                ['slug' => $course['slug']],
                [
                    'world_id' => $world->id,
                    'name' => $course['name'],
                    'description' => $course['description'],
                    'age_tier' => $course['age_tier'],
                    'difficulty' => $course['difficulty'],
                    'min_level_requirement' => $course['min_level_requirement'],
                    'estimated_duration' => $course['estimated_duration'],
                    'is_published' => true,
                ],
            );
        }
    }
}
