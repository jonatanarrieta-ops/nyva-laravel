<?php

namespace Database\Seeders;

use App\Models\Conversation;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        try {

            Conversation::create([
                'establishment_id' => 1,
                'customer_name' => 'Cliente prueba',
                'message' => '¿Tienen servicio a domicilio?',
                'response' => 'Sí, contamos con servicio de entrega.',
                'status' => true,
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}