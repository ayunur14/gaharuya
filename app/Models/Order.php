<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'items', 'total', 'status', 'payment_proof', 'notes', 'order_number',
        'snap_token', 'payment_method',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        static::created(function ($order) {
            $date = $order->created_at ?? now();
            $seq = str_pad($order->id, 3, '0', STR_PAD_LEFT);
            $order_number = 'Order/' . $date->format('d/m/Y') . '/' . $seq;
            $order->order_number = $order_number;
            $order->save();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_payment'      => 'Menunggu Pembayaran',
            'waiting_confirmation' => 'Menunggu Konfirmasi',
            'paid'                 => 'Lunas',
            'cancelled'            => 'Dibatalkan',
            default                => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending_payment'      => 'yellow',
            'waiting_confirmation' => 'blue',
            'paid'                 => 'green',
            'cancelled'            => 'red',
            default                => 'gray',
        };
    }
}
