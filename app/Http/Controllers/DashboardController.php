<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user->isEstablishmentUser()) {
            return redirect()->route('home');
        }

        $establishmentIds = $user->accessibleEstablishments()->pluck('id');
        $orders = Order::whereIn('establishment_id', $establishmentIds);

        $categoryCount = Category::whereIn('establishment_id', $establishmentIds)->count();
        $productCount = Product::whereHas(
            'category',
            fn ($query) => $query->whereIn('establishment_id', $establishmentIds)
        )->count();

        $orderStatusCounts = (clone $orders)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = now()->toDateString();
        $ordersToday = (clone $orders)->whereDate('ordered_at', $today)->count();
        $salesToday = (clone $orders)
            ->whereDate('ordered_at', $today)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $recentOrders = (clone $orders)
            ->with('establishment')
            ->orderByDesc('ordered_at')
            ->limit(5)
            ->get();

        $subscriptions = Subscription::with(['plan', 'establishment'])
            ->whereIn('establishment_id', $establishmentIds)
            ->orderByDesc('start_date')
            ->get();

        return view('dashboard', [
            'establishmentCount' => $establishmentIds->count(),
            'categoryCount' => $categoryCount,
            'productCount' => $productCount,
            'orderCount' => (clone $orders)->count(),
            'pendingOrderCount' => (int) $orderStatusCounts->get('pending', 0),
            'completedOrderCount' => (int) $orderStatusCounts->get('delivered', 0),
            'orderStatusCounts' => $orderStatusCounts,
            'ordersToday' => $ordersToday,
            'salesToday' => $salesToday,
            'salesChart' => self::salesByDay((clone $orders)),
            'recentOrders' => $recentOrders,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Ventas no canceladas de los últimos días, incluidos los días sin ventas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Order>  $orders
     * @return array<int, array{label: string, value: float}>
     */
    public static function salesByDay($orders, int $days = 7): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $sales = $orders
            ->where('status', '!=', 'cancelled')
            ->where('ordered_at', '>=', $start)
            ->selectRaw('DATE(ordered_at) as day, SUM(total) as sales')
            ->groupByRaw('DATE(ordered_at)')
            ->pluck('sales', 'day');

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($start, $sales) {
                $day = $start->copy()->addDays($offset);

                return [
                    'label' => $day->format('d/m'),
                    'value' => (float) ($sales[$day->toDateString()] ?? 0),
                ];
            })
            ->all();
    }
}
