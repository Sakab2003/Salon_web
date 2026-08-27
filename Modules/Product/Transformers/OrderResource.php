<?php

namespace Modules\Product\Transformers;

use App\Models\Setting;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        $order_prefix_data = Setting::where('name', 'inv_prefix')->first();
        $order_prefix = $order_prefix_data ? $order_prefix_data->val : '';

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'customer_name' => optional($this->user)->full_name ?: (optional($this->user)->first_name ? trim($this->user->first_name . ' ' . $this->user->last_name) : 'Client de passage'),
            'customer_phone' => optional($this->user)->mobile,
            'customer_email' => optional($this->user)->email,
            'delivery_status' => $this->delivery_status,
            'payment_status' => $this->payment_status,
            'payment_method' => optional($this->orderGroup)->payment_method ?: 'Paiement Cash',
            'total_amount' => $this->total_admin_earnings ?: optional($this->orderGroup)->grand_total_amount,
            'order_code' => $order_prefix.optional($this->orderGroup)->order_code,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'items_count' => $this->orderItems ? $this->orderItems->sum('qty') : 0,
            'product_details' => OrderItemResource::collection($this->orderItems),
        ];
    }
}
