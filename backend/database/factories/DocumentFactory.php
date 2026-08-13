<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'docx', 'jpg', 'png']);

        return [
            'application_id' => Application::factory(),
            'name' => fake()->randomElement([
                'Resume',
                'Identity Document',
                'Education Certificate',
                'Recommendation Letter',
            ]),
            'type' => $extension,
            'file_path' => 'documents/' . fake()->uuid() . '.' . $extension,
        ];
    }
}
