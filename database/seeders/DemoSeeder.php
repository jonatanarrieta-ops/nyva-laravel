<?php

namespace Database\Seeders;

use App\Actions\PlaceOrder;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos ficticios para la demostración. Se puede ejecutar varias veces y no modifica otra información.
 *
 * Cuentas: demo.plataforma@nyva.test, demo.admin@nyva.test, demo.personal@nyva.test
 * y demo.cliente@nyva.test, con contraseña "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->user('Plataforma', 'Demo', 'demo.plataforma@nyva.test', 'platform_admin');
        $owner = $this->user('Admin', 'Demo', 'demo.admin@nyva.test', 'establishment_admin');
        $staff = $this->user('Personal', 'Demo', 'demo.personal@nyva.test', 'establishment_staff');
        $customer = $this->user('Cliente', 'Demo', 'demo.cliente@nyva.test', 'customer');

        $plan = Plan::firstOrCreate(['name' => 'Demo Profesional'], [
            'description' => 'Plan de demostración con todas las funcionalidades.',
            'monthly_price' => 59000,
            'annual_price' => 590000,
            'duration_days' => 365,
            'product_limit' => 100,
            'user_limit' => 5,
            'features' => array_keys(Plan::FEATURES),
            'status' => 'active',
        ]);

        $burger = $this->establishment($owner, [
            'name' => 'Hamburguesas La Esquina',
            'type' => 'Cocina oculta',
            'description' => 'Hamburguesas artesanales y bebidas para compartir.',
            'address' => 'Calle 10 # 20-30, Sincelejo',
            'phone' => '3000000001',
            'email' => 'contacto@laesquina.test',
        ], [
            'Hamburguesas' => [
                ['Hamburguesa clásica', 'Carne, queso, lechuga y tomate.', 25000, true],
                ['Hamburguesa especial', 'Doble carne, tocineta y salsa de la casa.', 30000, true],
                ['Hamburguesa vegetariana', 'Medallón de lentejas y vegetales.', 27000, false],
            ],
            'Bebidas' => [
                ['Gaseosa', 'Botella personal.', 5000, true],
                ['Limonada natural', 'Preparada al momento.', 7000, true],
            ],
        ]);

        $cafe = $this->establishment($owner, [
            'name' => 'Café Aurora',
            'type' => 'Restaurante',
            'description' => 'Café de origen y repostería del día.',
            'address' => 'Carrera 5 # 12-40, Sincelejo',
            'phone' => '3000000002',
            'email' => null,
        ], [
            'Café' => [
                ['Capuchino', 'Espresso con leche espumada.', 8000, true],
                ['Café americano', 'Café filtrado.', 5000, true],
            ],
            'Repostería' => [
                ['Torta de zanahoria', 'Porción individual.', 9000, true],
            ],
        ]);

        foreach ([$burger, $cafe] as $establishment) {
            $this->configure($establishment, $plan);
        }

        $burger->members()->syncWithoutDetaching([$staff->id => ['permissions' => json_encode(['orders'])]]);

        $this->seedOrders($burger, $customer);
    }

    private function user(string $firstName, string $lastName, string $email, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->forceFill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => 'password',
                'role' => $role,
                'status' => 'active',
                'registered_at' => now(),
            ])->save();
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<int, array{string, string, int, bool}>>  $catalog
     */
    private function establishment(User $owner, array $attributes, array $catalog): Establishment
    {
        $establishment = $owner->establishments()->where('name', $attributes['name'])->first();

        if ($establishment) {
            return $establishment;
        }

        $establishment = $owner->establishments()->create([...$attributes, 'registered_at' => now()]);

        foreach ($catalog as $categoryName => $products) {
            $category = $establishment->categories()->create([
                'name' => $categoryName,
                'status' => 'active',
            ]);

            foreach ($products as [$name, $description, $price, $available]) {
                $category->products()->create([
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'available' => $available,
                    'status' => 'active',
                ]);
            }
        }

        return $establishment;
    }

    private function configure(Establishment $establishment, Plan $plan): void
    {
        if (! $establishment->currentSubscription()) {
            $establishment->subscriptions()->create([
                'plan_id' => $plan->id,
                'billing_period' => 'annual',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays($plan->duration_days)->toDateString(),
                'status' => 'active',
            ]);
        }

        if ($establishment->schedules()->doesntExist()) {
            foreach (array_keys(Schedule::DAYS) as $day) {
                $establishment->schedules()->create([
                    'day_of_week' => $day,
                    'opens_at' => '00:00:00',
                    'closes_at' => '23:59:00',
                    'status' => 'active',
                ]);
            }
        }

        if ($establishment->frequentlyAskedQuestions()->doesntExist()) {
            $establishment->frequentlyAskedQuestions()->createMany([
                ['question' => '¿Qué métodos de pago aceptan?', 'answer' => 'Aceptamos efectivo y transferencia.', 'status' => 'active'],
                ['question' => '¿Tienen servicio a domicilio?', 'answer' => 'Sí, entregamos a domicilio en Sincelejo.', 'status' => 'active'],
            ]);
        }

        $establishment->chatbot();
    }

    private function seedOrders(Establishment $establishment, User $customer): void
    {
        if ($establishment->orders()->exists()) {
            return;
        }

        $products = $establishment->categories()->with('products')->get()
            ->flatMap->products->where('available', true)->values();

        foreach (array_keys(Order::STATUS_LABELS) as $index => $status) {
            $order = app(PlaceOrder::class)->handle(
                $establishment->id,
                [
                    $products[$index % $products->count()]->id => $index + 1,
                    $products[($index + 1) % $products->count()]->id => 1,
                ],
                [
                    'customer_name' => 'Cliente Demo',
                    'customer_phone' => '3000000003',
                    'delivery_address' => 'Avenida Principal # 1-2',
                ],
                $customer->id,
            );

            Order::whereKey($order->id)->update([
                'status' => $status,
                'ordered_at' => now()->subDays($index),
            ]);
        }
    }
}
