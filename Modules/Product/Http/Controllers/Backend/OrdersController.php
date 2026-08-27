<?php

namespace Modules\Product\Http\Controllers\Backend;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Location\Models\Location;
use Modules\Product\Models\Cart;
use Modules\Product\Models\Order;
use Modules\Product\Models\OrderGroup;
use Modules\Product\Models\OrderItem;
use Modules\Product\Models\OrderUpdate;
use Modules\Product\Trait\OrderTrait;
use Yajra\DataTables\DataTables;

class OrdersController extends Controller
{
    use OrderTrait;

    public function __construct()
    {
        $this->module_title = 'sidebar.orders';
        $this->module_name = 'orders';
        $this->module_icon = 'fa-solid fa-clipboard-list';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,
            'module_name' => $this->module_name,
        ]);
    }

    /**
     * Display sales history listing with mobile-like KPIs and stats.
     */
    public function index(Request $request)
    {
        $export_import = false;
        $user = auth()->user();
        $branchId = request()->selected_session_branch_id;
        if (empty($branchId) && $user) {
            if ($user->hasRole('manager')) {
                $branchId = $user->branch_id ?: optional(\App\Models\Branch::where('manager_id', $user->id)->first())->id;
            } elseif ($user->hasRole('employee')) {
                $branchId = optional($user->mainBranch()->first())->id ?: optional($user->branch()->first()?->getBranch)->id ?: $user->branch_id;
            }
        }

        $ordersQuery = Order::query()->where('payment_status', 'paid');
        if (!empty($branchId) && (!$user || !$user->hasRole('admin'))) {
            $ordersQuery->where(function($q) use ($branchId, $user) {
                $q->where('location_id', $branchId)->orWhere('user_id', $user ? $user->id : 0);
            });
        }

        $totalSalesAmount = (float) (clone $ordersQuery)->sum('total_admin_earnings');
        $totalOrdersCount = (int) (clone $ordersQuery)->count();
        $averageTicket = $totalOrdersCount > 0 ? ($totalSalesAmount / $totalOrdersCount) : 0;
        $todaySalesAmount = (float) (clone $ordersQuery)->whereDate('created_at', Carbon::today())->sum('total_admin_earnings');
        $todayOrdersCount = (int) (clone $ordersQuery)->whereDate('created_at', Carbon::today())->count();

        $locations = Location::where('status', 1)->latest()->get();

        return view('product::backend.order.index_datatable', compact(
            'export_import', 'locations', 'totalSalesAmount', 'totalOrdersCount',
            'averageTicket', 'todaySalesAmount', 'todayOrdersCount'
        ));
    }

    /**
     * DataTables endpoint for sales history.
     */
    public function index_data(DataTables $datatable, Request $request)
    {
        $user = auth()->user();
        $branchId = request()->selected_session_branch_id;
        if (empty($branchId) && $user) {
            if ($user->hasRole('manager')) {
                $branchId = $user->branch_id ?: optional(\App\Models\Branch::where('manager_id', $user->id)->first())->id;
            } elseif ($user->hasRole('employee')) {
                $branchId = optional($user->mainBranch()->first())->id ?: optional($user->branch()->first()?->getBranch)->id ?: $user->branch_id;
            }
        }

        $orders = Order::with(['orderGroup', 'orderItems.product_variation.product', 'user']);

        if (!empty($branchId) && (!$user || !$user->hasRole('admin'))) {
            $orders->where(function ($q) use ($branchId, $user) {
                $q->where('location_id', $branchId)
                  ->orWhere('user_id', $user ? $user->id : 0);
            });
        }

        $filter = $request->filter;

        if (isset($filter)) {
            if (!empty($filter['code'])) {
                $code = $filter['code'];
                $orders->whereHas('orderGroup', function ($q) use ($code) {
                    $q->where('order_code', 'LIKE', "%{$code}%");
                });
            }

            if (!empty($filter['delivery_status'])) {
                $orders->where('delivery_status', $filter['delivery_status']);
            }

            if (!empty($filter['payment_status'])) {
                $orders->where('payment_status', $filter['payment_status']);
            }

            if (!empty($filter['payment_method'])) {
                $method = $filter['payment_method'];
                $orders->whereHas('orderGroup', function ($q) use ($method) {
                    $q->where('payment_method', $method);
                });
            }

            if (!empty($filter['location_id'])) {
                $orders->where('location_id', $filter['location_id']);
            }
        }

        return $datatable->eloquent($orders)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-'.$row->id.'" name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->editColumn('order_code', function ($data) {
                $prefix = setting('inv_prefix') ?: '#';
                $code = optional($data->orderGroup)->order_code ?: str_pad($data->id, 6, '0', STR_PAD_LEFT);
                return '<span class="badge bg-soft-primary fw-bold fs-6">'.$prefix.$code.'</span>';
            })
            ->editColumn('customer_name', function ($data) {
                $customer = $data->user;
                $name = $customer ? trim($customer->first_name . ' ' . $customer->last_name) : 'Client de passage';
                $phone = $customer ? ($customer->mobile ?: $customer->email) : '-';
                return '
                    <div>
                        <strong class="d-block text-dark">'.$name.'</strong>
                        <small class="text-muted"><i class="fa-solid fa-phone me-1"></i>'.$phone.'</small>
                    </div>
                ';
            })
            ->editColumn('placed_on', function ($data) {
                return '
                    <div>
                        <span class="d-block fw-semibold text-dark">'.$data->created_at->isoFormat('D MMMM YYYY').'</span>
                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>'.$data->created_at->format('H:i').'</small>
                    </div>
                ';
            })
            ->addColumn('items_detail', function ($data) {
                $items = $data->orderItems;
                $count = $items ? $items->sum('qty') : 0;
                $badges = [];
                foreach ($items->take(2) as $item) {
                    $prodName = optional(optional($item->product_variation)->product)->name ?: 'Prestation';
                    $badges[] = '<span class="badge bg-light text-dark border me-1 mb-1">'.$item->qty.'x '.$prodName.'</span>';
                }
                if ($items->count() > 2) {
                    $badges[] = '<span class="badge bg-secondary-subtle text-secondary">+'.$items->count() - 2 .'</span>';
                }
                return '<div class="d-flex flex-wrap align-items-center">'.implode('', $badges).'</div>';
            })
            ->editColumn('payment_method', function ($data) {
                $method = optional($data->orderGroup)->payment_method ?: 'Paiement Cash';
                $icon = 'fa-money-bill-wave';
                $badgeClass = 'bg-soft-success text-success';
                if (str_contains($method, 'Orange')) {
                    $icon = 'fa-mobile-screen';
                    $badgeClass = 'bg-soft-warning text-warning';
                } elseif (str_contains($method, 'Moov')) {
                    $icon = 'fa-signal';
                    $badgeClass = 'bg-soft-primary text-primary';
                } elseif (str_contains($method, 'Wave')) {
                    $icon = 'fa-water';
                    $badgeClass = 'bg-soft-info text-info';
                }
                return '<span class="badge '.$badgeClass.' px-2 py-1 rounded-pill"><i class="fa-solid '.$icon.' me-1"></i>'.$method.'</span>';
            })
            ->addColumn('total_amount_formatted', function ($data) {
                $amount = $data->total_admin_earnings ?: optional($data->orderGroup)->grand_total_amount;
                return '<strong class="text-primary fs-6">'.number_format($amount, 0, ',', ' ').' FCFA</strong>';
            })
            ->editColumn('status', function ($data) {
                return '<span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>Payé / Livré</span>';
            })
            ->addColumn('action', function ($data) {
                $orderJson = e(json_encode([
                    'id' => $data->id,
                    'order_code' => optional($data->orderGroup)->order_code ?: $data->id,
                    'customer_name' => optional($data->user)->full_name ?: 'Client de passage',
                    'customer_phone' => optional($data->user)->mobile ?: '-',
                    'date' => $data->created_at->isoFormat('LLLL'),
                    'total' => number_format($data->total_admin_earnings ?: optional($data->orderGroup)->grand_total_amount, 0, ',', ' ') . ' FCFA',
                    'payment_method' => optional($data->orderGroup)->payment_method ?: 'Paiement Cash',
                    'items' => $data->orderItems->map(function($item) {
                        return [
                            'name' => optional(optional($item->product_variation)->product)->name ?: 'Article',
                            'qty' => $item->qty,
                            'price' => number_format($item->unit_price, 0, ',', ' ') . ' FCFA',
                            'total' => number_format($item->total_price, 0, ',', ' ') . ' FCFA',
                        ];
                    })->toArray(),
                ]));

                return '
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle view-receipt-btn" data-order-data="'.$orderJson.'" title="Voir le ticket">
                            <i class="fa-solid fa-receipt"></i>
                        </button>
                        <a href="'.route('backend.orders.show', ['id' => $data->id]).'" class="btn btn-sm btn-outline-secondary rounded-circle" target="_blank" title="Facture">
                            <i class="fa-solid fa-file-invoice"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['action', 'check', 'order_code', 'customer_name', 'placed_on', 'items_detail', 'payment_method', 'total_amount_formatted', 'status'])
            ->orderColumns(['id'], '-:column $1')
            ->toJson();
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request)
    {
        $order = Order::with(['orderItems.product_variation.product', 'orderGroup', 'user'])->find($request->id);
        if ($order == null) {
            return abort(404);
        }

        return view('product::backend.order.show', compact('order'));
    }

    /**
     * POS Direct Sale Interface.
     */
    public function pos(Request $request)
    {
        $branchId = request()->selected_session_branch_id;
        $user = auth()->user();
        if (empty($branchId) && $user) {
            if ($user->hasRole('manager')) {
                $branchId = $user->branch_id ?: optional(\App\Models\Branch::where('manager_id', $user->id)->first())->id;
            } elseif ($user->hasRole('employee')) {
                $branchId = optional($user->mainBranch()->first())->id ?: optional($user->branch()->first()?->getBranch)->id ?: $user->branch_id;
            }
        }

        $products = \Modules\Product\Models\Product::where('status', 1)
            ->with(['media', 'product_variations.product_variation_stock'])
            ->get();

        $servicesQuery = \Modules\Service\Models\Service::active()->with('media');
        if (!empty($branchId) && (!$user || !$user->hasRole('admin'))) {
            $servicesQuery->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }
        $services = $servicesQuery->get();

        $customers = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'user');
        })->select('id', 'first_name', 'last_name', 'email', 'mobile')->get();

        if ($customers->isEmpty()) {
            $customers = \App\Models\User::select('id', 'first_name', 'last_name', 'email', 'mobile')->get();
        }

        $paymentMethods = [
            'Paiement Cash' => 'Paiement Cash',
            'Orange Money' => 'Orange Money',
            'Moov Money' => 'Moov Money',
            'Wave' => 'Wave',
            'Carte Bancaire' => 'Carte Bancaire',
            'Virement' => 'Virement'
        ];

        return view('product::backend.order.pos', compact('products', 'services', 'customers', 'paymentMethods'));
    }

    /**
     * Store POS Sale.
     */
    public function store_pos(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $branchId = $request->branch_id ?: request()->selected_session_branch_id;
        if (empty($branchId) && $user) {
            if ($user->hasRole('manager')) {
                $branchId = $user->branch_id ?: optional(\App\Models\Branch::where('manager_id', $user->id)->first())->id;
            } elseif ($user->hasRole('employee')) {
                $branchId = optional($user->mainBranch()->first())->id ?: optional($user->branch()->first()?->getBranch)->id;
            }
        }
        if (empty($branchId)) {
            $location = \Modules\Location\Models\Location::where('is_default', 1)->first();
            $branchId = $location ? $location->id : 1;
        }

        $customerId = $request->customer_id;
        if (empty($customerId) && (!empty($request->new_customer_first_name) || !empty($request->new_customer_name) || !empty($request->full_name))) {
            $fullName = trim($request->full_name ?? $request->new_customer_name ?? '');
            $firstName = trim($request->new_customer_first_name ?? '');
            $lastName = trim($request->new_customer_last_name ?? '');
            if (empty($firstName) && !empty($fullName)) {
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
            }

            $custPhone = $request->new_customer_phone ?: ($request->mobile ?: ($request->phone ?: null));
            $newCust = \App\Models\User::create([
                'first_name' => $firstName ?: 'Client',
                'last_name' => $lastName ?: 'Passage',
                'email' => $request->new_customer_email ?: ('client_' . time() . rand(100, 999) . '@salon.local'),
                'mobile' => $custPhone,
                'password' => bcrypt('12345678'),
            ]);
            $newCust->assignRole('user');
            $customerId = $newCust->id;
        }

        $userId = $customerId ?: ($user ? $user->id : 1);

        // Verify stock for products only
        foreach ($request->items as $item) {
            if (!empty($item['type']) && $item['type'] === 'service') {
                continue;
            }
            if (!empty($item['product_id'])) {
                $product = \Modules\Product\Models\Product::find($item['product_id']);
                if ($product) {
                    $requestedQty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                    $availableStock = (int) $product->stock_qty;
                    if ($requestedQty > $availableStock) {
                        return response()->json([
                            'status' => false,
                            'message' => "Stock insuffisant pour '{$product->name}'. Restant: {$availableStock} (demandé: {$requestedQty})"
                        ], 422);
                    }
                }
            }
        }

        $amountPaid = (float) ($request->amount_paid ?? $request->total_amount);
        $totalAmount = (float) $request->total_amount;
        $change = max(0, $amountPaid - $totalAmount);

        $orderGroup = new OrderGroup;
        $orderGroup->user_id = $userId;
        $orderGroup->location_id = $branchId;
        $orderGroup->order_code = (string) rand(100000, 999999);
        $orderGroup->sub_total_amount = $totalAmount;
        $orderGroup->total_tax_amount = 0;
        $orderGroup->total_coupon_discount_amount = 0;
        $orderGroup->total_shipping_cost = 0;
        $orderGroup->total_tips_amount = 0;
        $orderGroup->grand_total_amount = $totalAmount;
        $orderGroup->payment_method = $request->payment_method ?: 'Paiement Cash';
        $orderGroup->payment_status = 'paid';
        $orderGroup->type = 'pos';
        $orderGroup->payment_details = json_encode([
            'amount_paid' => $amountPaid,
            'change' => $change,
            'payment_method' => $request->payment_method ?: 'Paiement Cash',
            'notes' => $request->notes ?? ''
        ]);
        $orderGroup->save();

        $order = new Order;
        $order->order_group_id = $orderGroup->id;
        $order->user_id = $userId;
        $order->location_id = $branchId;
        $order->delivery_status = 'delivered';
        $order->payment_status = 'paid';
        $order->total_admin_earnings = $totalAmount;
        $order->save();

        foreach ($request->items as $item) {
            $isService = (!empty($item['type']) && $item['type'] === 'service') || !empty($item['service_id']);
            $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
            $price = (float) ($item['price'] ?? 0);

            if ($isService) {
                $serviceName = $item['name'] ?? ($item['service_name'] ?? 'Prestation Service');
                $serviceProduct = \Modules\Product\Models\Product::firstOrCreate(
                    ['name' => $serviceName],
                    [
                        'slug' => \Str::slug($serviceName),
                        'min_price' => $price,
                        'max_price' => $price,
                        'stock_qty' => 999999,
                        'status' => 1,
                        'is_featured' => 0,
                    ]
                );
                $variation = $serviceProduct->product_variations->first();
                if (!$variation) {
                    $variation = \Modules\Product\Models\ProductVariation::create([
                        'product_id' => $serviceProduct->id,
                        'price' => $price,
                        'sku' => 'SRV-' . ($item['service_id'] ?? rand(100, 999)),
                        'code' => 'SRV-' . ($item['service_id'] ?? rand(100, 999)),
                    ]);
                }

                $orderItem = new OrderItem;
                $orderItem->order_id = $order->id;
                $orderItem->product_variation_id = $variation->id;
                $orderItem->qty = $qty;
                $orderItem->location_id = $branchId;
                $orderItem->unit_price = $price;
                $orderItem->total_tax = 0;
                $orderItem->total_price = $price * $qty;
                $orderItem->save();
            } else {
                $product = \Modules\Product\Models\Product::find($item['product_id'] ?? $item['id'] ?? null);
                if ($product) {
                    $variation = $product->product_variations->first();
                    if (!$variation) {
                        $variation = \Modules\Product\Models\ProductVariation::create([
                            'product_id' => $product->id,
                            'price' => $product->max_price ?: $product->min_price,
                            'sku' => 'PRD-' . $product->id,
                            'code' => 'PRD-' . $product->id,
                        ]);
                    }

                    $orderItem = new OrderItem;
                    $orderItem->order_id = $order->id;
                    $orderItem->product_variation_id = $variation->id;
                    $orderItem->qty = $qty;
                    $orderItem->location_id = $branchId;
                    $orderItem->unit_price = $price ?: ($product->max_price ?: $product->min_price);
                    $orderItem->total_tax = 0;
                    $orderItem->total_price = ($price ?: ($product->max_price ?: $product->min_price)) * $qty;
                    $orderItem->save();

                    $variationStock = $variation->product_variation_stock_without_location()->where('location_id', $branchId)->first();
                    if ($variationStock) {
                        $variationStock->stock_qty = max(0, $variationStock->stock_qty - $qty);
                        $variationStock->save();
                    }

                    $product->stock_qty = max(0, (int) $product->stock_qty - $qty);
                    $product->total_sale_count = (int) $product->total_sale_count + $qty;
                    $product->save();
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Vente validée avec succès !',
            'data' => [
                'order_id' => $order->id,
                'order_code' => $orderGroup->order_code,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'change' => $change,
                'invoice_url' => route('backend.orders.show', ['id' => $order->id]),
            ]
        ], 200);
    }

    /**
     * Financial balance reporting.
     */
    public function financial_balance(Request $request)
    {
        $today = Carbon::today();
        $now = Carbon::now();

        $todayOrders = Order::whereDate('created_at', $today)->where('payment_status', 'paid')->get();
        $dailyTotal = $todayOrders->sum('total_admin_earnings');
        $todaySalesCount = $todayOrders->count();

        $weeklyOrders = Order::where('created_at', '>=', Carbon::now()->subDays(7))->where('payment_status', 'paid')->get();
        $weeklyTotal = $weeklyOrders->sum('total_admin_earnings');

        $monthlyOrders = Order::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->where('payment_status', 'paid')->get();
        $monthlyTotal = $monthlyOrders->sum('total_admin_earnings');

        $allOrders = Order::where('payment_status', 'paid')->get();
        $overallTotal = $allOrders->sum('total_admin_earnings');

        $dailyRevenueLast7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $daySum = Order::whereDate('created_at', $d)->where('payment_status', 'paid')->sum('total_admin_earnings');
            $dailyRevenueLast7Days[] = [
                'date' => $d->format('d/m'),
                'day_name' => $d->locale('fr')->isoFormat('ddd'),
                'total' => (float) $daySum,
            ];
        }

        $orderGroups = OrderGroup::where('payment_status', 'paid')->get();
        $paymentMethodsRevenue = [];
        foreach ($orderGroups as $og) {
            $m = $og->payment_method ?: 'Paiement Cash';
            $paymentMethodsRevenue[$m] = ($paymentMethodsRevenue[$m] ?? 0) + (float) $og->grand_total_amount;
        }

        $topSellingProducts = \Modules\Product\Models\Product::where('total_sale_count', '>', 0)
            ->orderBy('total_sale_count', 'desc')
            ->take(10)
            ->get();

        return view('product::backend.order.financial_balance', compact(
            'dailyTotal', 'weeklyTotal', 'monthlyTotal', 'overallTotal',
            'todaySalesCount', 'dailyRevenueLast7Days', 'paymentMethodsRevenue',
            'topSellingProducts'
        ));
    }
}
