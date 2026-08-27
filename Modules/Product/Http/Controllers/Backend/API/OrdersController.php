<?php

namespace Modules\Product\Http\Controllers\Backend\API;

use App\Models\Setting;
use Auth;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Constant\Models\Constant;
use Modules\Logistic\Models\LogisticZone;
use Modules\Product\Http\Requests\OrderRequest;
use Modules\Product\Models\Cart;
use Modules\Product\Models\Order;
use Modules\Product\Models\OrderGroup;
use Modules\Product\Models\OrderItem;
use Modules\Product\Models\OrderUpdate;
use Modules\Product\Trait\OrderTrait;
use Modules\Product\Transformers\OrderDetailsResource;
use Modules\Product\Transformers\OrderResource;

class OrdersController extends Controller
{
    use OrderTrait;

    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function index()
    {
        return view('product::index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Renderable
     */
    public function create()
    {
        return view('product::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Renderable
     */
    public function store(OrderRequest $request)
    {
        $userId = Auth::id();

        $location_id = $request['location_id'];

        $carts = Cart::where('user_id', $userId)->where('location_id', $location_id)->get();

        if (count($carts) > 0) {
            foreach ($carts as $cart) {
                $productVariationStock = $cart->product_variation->product_variation_stock ? $cart->product_variation->product_variation_stock->stock_qty : 0;
                if ($cart->qty > $productVariationStock) {
                    $message = $cart->product_variation->product->name.' is out of stock';

                    return response()->json(['message' => $message, 'status' => false]);
                }
            }

            // create new order group
            $orderGroup = new OrderGroup;
            $orderGroup->user_id = $userId;
            $orderGroup->shipping_address_id = $request['shipping_address_id'];
            $orderGroup->billing_address_id = $request['billing_address_id'];
            $orderGroup->location_id = $location_id;
            $orderGroup->phone_no = $request['phone'];
            $orderGroup->alternative_phone_no = $request['alternative_phone'];
            $orderGroup->sub_total_amount = getSubTotal($carts, false, '', false);
            $orderGroup->payment_details = $request['payment_details'];

            $tax_data = getTaxamount($orderGroup->sub_total_amount);

            $orderGroup->total_tax_amount = $tax_data['total_tax_amount'];
            $orderGroup->total_coupon_discount_amount = 0;
            $orderGroup->type = 'online';
            $logisticZone = LogisticZone::where('id', $request['chosen_logistic_zone_id'])->first();
            $orderGroup->total_shipping_cost = $logisticZone->standard_delivery_charge ?? 0;
            $orderGroup->total_tips_amount = $request['tips'] ?? 0;

            $orderGroup->grand_total_amount = $orderGroup->sub_total_amount + $orderGroup->total_tax_amount + $orderGroup->total_shipping_cost + $orderGroup->total_tips_amount - $orderGroup->total_coupon_discount_amount;
            $orderGroup->save();

            $order = new Order;
            $order->order_group_id = $orderGroup->id;
            $order->user_id = $userId;
            $order->location_id = $location_id;
            $order->total_admin_earnings = $orderGroup->grand_total_amount;
            $order->logistic_id = $logisticZone->logistic_id ?? null;
            $order->logistic_name = ($logisticZone->logistic)->name ?? null;
            $order->payment_status = $request['payment_status'];
            $order->shipping_cost = $orderGroup->total_shipping_cost;
            $order->tips_amount = $orderGroup->total_tips_amount;

            $order->save();

            // order items
            foreach ($carts as $cart) {
                $discounted_price = variationDiscountedPrice($cart->product_variation->product, $cart->product_variation);
                $tax_data = getTaxamount($discounted_price);

                $orderItem = new OrderItem;
                $orderItem->order_id = $order->id;
                $orderItem->product_variation_id = $cart->product_variation_id;
                $orderItem->qty = $cart->qty;
                $orderItem->location_id = $location_id;
                $orderItem->unit_price = $discounted_price;
                $orderItem->total_tax = $tax_data['total_tax_amount'];
                $orderItem->total_price = $orderItem->unit_price * $orderItem->qty;
                $orderItem->save();

                $product = $cart->product_variation->product;
                $product->total_sale_count += $orderItem->qty;

                // minus stock qty
                try {
                    $productVariationStock = $cart->product_variation->product_variation_stock;
                    $productVariationStock->stock_qty -= $orderItem->qty;
                    $productVariationStock->save();
                } catch (\Throwable $th) {
                    //throw $th;
                }

                $product->stock_qty -= $orderItem->qty;
                $product->save();

                // category sales count
                if ($product->categories()->count() > 0) {
                    foreach ($product->categories as $category) {
                        $category->total_sale_count += $orderItem->qty;
                        $category->save();
                    }
                }
                $cart->delete();
            }

            $order->save();
            $orderGroup->payment_method = $request['payment_method'];
            $orderGroup->payment_status = $request['payment_status'];
            $orderGroup->save();

            $order_status = [
                'order_id' => $order->id,
                'user_id' => $userId,
                'note' => 'Your Order has been placed.',
            ];

            OrderUpdate::create($order_status);

            $order_prefix_data = Setting::where('name', 'inv_prefix')->first();
            $order_prefix = $order_prefix_data ? $order_prefix_data->val : '';

            try {
                $notification_data = [
                    'id' => $order->id,
                    'order_code' => $order_prefix.optional($order->orderGroup)->order_code,
                    'user_id' => $order->user_id,
                    'user_name' => optional($order->user)->first_name.' '.optional($order->user)->last_name ?? default_user_name(),
                    'order_date' => $order->created_at->format('d/m/Y'),
                    'order_time' => $order->created_at->format('h:i A'),
                ];

                $this->sendNotificationOnOrderUpdate('order_placed', $notification_data);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }

            $message = __('product.order_palce');

            return response()->json(['message' => $message, 'product' => $order, 'status' => true], 200);
        } else {
            $message = __('product.empty_cart');

            return response()->json(['message' => $message, 'product' => $order, 'status' => true], 200);
        }
    }

    public function statusList()
    {
        $order_status = Constant::where('type', 'ORDER_STATUS')->get();

        return response()->json([
            'status' => true,
            'data' => $order_status,
            'message' => __('product.order_status_list'),
        ], 200);
    }

    public function orderList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $user = Auth::user();

        $orderQuery = Order::with(['orderItems', 'orderGroup', 'user'])->orderBy('created_at', 'desc');

        if ($user) {
            if ($user->hasRole('admin')) {
                // Admin sees all
            } elseif ($user->hasRole('manager')) {
                $branchId = $user->branch_id ?: optional(\App\Models\Branch::where('manager_id', $user->id)->first())->id;
                if ($branchId) {
                    $orderQuery->where(function($q) use ($branchId, $user) {
                        $q->where('location_id', $branchId)
                          ->orWhere('user_id', $user->id);
                    });
                } else {
                    $orderQuery->where('user_id', $user->id);
                }
            } elseif ($user->hasRole('employee')) {
                $branchId = optional($user->mainBranch()->first())->id ?: optional($user->branch()->first()?->getBranch)->id;
                if ($branchId) {
                    $orderQuery->where(function($q) use ($branchId, $user) {
                        $q->where('location_id', $branchId)
                          ->orWhere('user_id', $user->id);
                    });
                } else {
                    $orderQuery->where('user_id', $user->id);
                }
            } else {
                $orderQuery->where('user_id', $user->id);
            }
        }

        if ($request->has('delivery_status') && $request->delivery_status != '') {
            $delivery_status = explode(',', $request->delivery_status); 
            $orderQuery->whereIn('delivery_status', $delivery_status);
        }

        $orderQuery = $orderQuery->paginate($perPage);
        $orderCollection = OrderResource::collection($orderQuery);

        return response()->json([
            'status' => true,
            'data' => $orderCollection,
            'message' => __('product.order_list'),
        ], 200);
    }

    public function cancleOrder(Request $request)
    {
        $userId = Auth::id();

        if ($request->has('id') && $request->id != '') {
            $order = Order::where('id', $request->id)->first();

            if ($order) {
                $order->delivery_status = 'cancelled';
                $order->save();

                $order_status = [
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'note' => 'Your Order has been Cancelled.',
                ];

                OrderUpdate::create($order_status);

                return response()->json(['message' => 'Order cancelled', 'status' => true], 200);
            }
        }
        return response()->json(['message' => 'Order not found', 'status' => false], 404);
    }

    public function orderDetails(Request $request)
    {
        $id = $request->id;
        $orderQuery = Order::where('id', $id)->with(['orderItems', 'orderGroup', 'user'])->first();

        if (!$orderQuery) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        }

        $orderCollection = new OrderDetailsResource($orderQuery);

        return response()->json([
            'status' => true,
            'data' => $orderCollection,
            'message' => __('product.order_details'),
        ], 200);
    }

    public function posSale(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'total_amount' => 'required|numeric',
        ]);

        $user = Auth::user();
        $branchId = $request->branch_id;
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

        // Handle customer
        $customerId = $request->customer_id;
        if (empty($customerId) && (!empty($request->customer_name) || !empty($request->customer_first_name) || !empty($request->new_customer_name))) {
            $firstName = trim($request->customer_first_name ?? '');
            $lastName = trim($request->customer_last_name ?? '');
            $fullName = trim($request->customer_name ?? $request->new_customer_name ?? '');
            if (empty($firstName) && !empty($fullName)) {
                $parts = explode(' ', $fullName, 2);
                $firstName = $parts[0];
                $lastName = $parts[1] ?? '';
            }

            $custPhone = $request->customer_phone ?: ($request->new_customer_phone ?: null);
            $newCust = \App\Models\User::create([
                'first_name' => $firstName ?: 'Client',
                'last_name' => $lastName ?: 'Passage',
                'email' => $request->customer_email ?: ('client_' . time() . rand(100, 999) . '@salon.local'),
                'mobile' => $custPhone,
                'password' => bcrypt('12345678'),
            ]);
            $newCust->assignRole('user');
            $customerId = $newCust->id;
        }

        $userId = $customerId ?: ($user ? $user->id : 1);

        $orderGroup = new OrderGroup;
        $orderGroup->user_id = $userId;
        $orderGroup->location_id = $branchId;
        $orderGroup->order_code = (string) rand(100000, 999999);
        $orderGroup->sub_total_amount = $request->total_amount;
        $orderGroup->total_tax_amount = 0;
        $orderGroup->total_coupon_discount_amount = 0;
        $orderGroup->total_shipping_cost = 0;
        $orderGroup->total_tips_amount = 0;
        $orderGroup->grand_total_amount = $request->total_amount;
        $orderGroup->payment_method = $request->payment_method ?: 'Paiement Cash';
        $orderGroup->payment_status = 'paid';
        $orderGroup->type = 'pos';
        $orderGroup->save();

        $order = new Order;
        $order->order_group_id = $orderGroup->id;
        $order->user_id = $userId;
        $order->location_id = $branchId;
        $order->delivery_status = 'delivered';
        $order->payment_status = 'paid';
        $order->total_admin_earnings = $orderGroup->grand_total_amount;
        $order->save();

        foreach ($request->items as $item) {
            $isService = (!empty($item['type']) && $item['type'] === 'service') || !empty($item['service_id']);
            $qty = isset($item['quantity']) ? (int)$item['quantity'] : (isset($item['qty']) ? (int)$item['qty'] : 1);
            $price = isset($item['price']) ? (float)$item['price'] : (isset($item['selling_price']) ? (float)$item['selling_price'] : 0);

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
                $product = null;
                if (!empty($item['product_id'])) {
                    $product = \Modules\Product\Models\Product::find($item['product_id']);
                } elseif (!empty($item['id'])) {
                    $product = \Modules\Product\Models\Product::find($item['id']);
                } elseif (!empty($item['product_name'])) {
                    $product = \Modules\Product\Models\Product::where('name', 'LIKE', trim($item['product_name']))->first();
                }

                $variation = null;
                if ($product) {
                    $variation = $product->product_variations->first();
                }

                $unitPrice = $price ?: ($product ? ($product->max_price ?: $product->min_price) : 0);

                if ($variation) {
                    $orderItem = new OrderItem;
                    $orderItem->order_id = $order->id;
                    $orderItem->product_variation_id = $variation->id;
                    $orderItem->qty = $qty;
                    $orderItem->location_id = $branchId;
                    $orderItem->unit_price = $unitPrice;
                    $orderItem->total_tax = 0;
                    $orderItem->total_price = $unitPrice * $qty;
                    $orderItem->save();

                    $variationStock = $variation->product_variation_stock_without_location()->where('location_id', $branchId)->first();
                    if ($variationStock) {
                        $variationStock->stock_qty = max(0, $variationStock->stock_qty - $qty);
                        $variationStock->save();
                    }
                }

                if ($product) {
                    $product->stock_qty = max(0, (int)$product->stock_qty - $qty);
                    $product->total_sale_count = (int)$product->total_sale_count + $qty;
                    $product->save();
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Vente enregistrée avec succès.',
            'data' => [
                'order_id' => $order->id,
                'order_code' => $orderGroup->order_code,
                'total' => $orderGroup->grand_total_amount,
            ]
        ], 200);
    }
}
