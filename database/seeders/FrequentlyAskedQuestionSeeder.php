<?php

namespace Database\Seeders;

use App\Models\FrequentlyAskedQuestion;
use Illuminate\Database\Seeder;

class FrequentlyAskedQuestionSeeder extends Seeder
{
    public function run(): void
    {
        try {

            FrequentlyAskedQuestion::create([
                'establishment_id' => 1,
                'question' => '¿Cuál es el horario de atención?',
                'answer' => 'Atendemos de lunes a sábado de 8:00 AM a 6:00 PM.',
                'status' => true,
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}