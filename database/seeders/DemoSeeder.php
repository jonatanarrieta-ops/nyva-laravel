<?php

namespace Database\Seeders;

use App\Actions\PlaceOrder;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos ficticios para la demostración. No modifica la información existente.
 *
 * Cuentas: demo.admin@nyva.test y demo.cliente@nyva.test, contraseña "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'demo.admin@nyva.test')->exists()) {
            return;
        }

        $admin = $this->user('Admin', 'Demo', 'demo.admin@nyva.test', 'establishment_admin');
        $customer = $this->user('Cliente', 'Demo', 'demo.cliente@nyva.test', 'customer');

        $burger = $this->establishment($admin, [
            'name' => 'Hamburguesas La Esquina',
            'type' => 'Restaurante',
            'description' => 'Hamburguesas artesanales y bebidas para compartir.',
            'address' => 'Calle 10 # 20-30',
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

        $this->establishment($admin, [
            'name' => 'Café Aurora',
            'type' => 'Cafetería',
            'description' => 'Café de origen y repostería del día.',
            'address' => 'Carrera 5 # 12-40',
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

        $products = $burger->categories()->with('products')->get()
            ->flatMap->products->where('available', true)->values();

        $statuses = [
            'pending',
            'confirmed',
            'preparing',
            'shipped',
            'delivered',
            'cancelled',
        ];

        foreach ($statuses as $index => $status) {
            $order = app(PlaceOrder::class)->handle(
                $burger->id,
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

    private function user(string $firstName, string $lastName, string $email, string $role): User
    {
        return User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
            'registered_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<int, array{string, string, int, bool}>>  $catalog
     */
    private function establishment(User $owner, array $attributes, array $catalog): Establishment
    {
        $establishment = $owner->establishments()->create([
            ...$attributes,
            'status' => 'active',
            'registered_at' => now(),
        ]);

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
}
