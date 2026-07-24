<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    private function configureMidtrans(): void
    {
        \Midtrans\Config::$serverKey    = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized  = true;
        \Midtrans\Config::$is3ds        = true;
    }

    /** Tampilkan daftar pesanan (user: milik sendiri, super admin: semua) */
    public function index()
    {
        $user = Auth::user();
        $orders = $user->isSuperAdmin()
            ? Order::with('user')->latest()->get()
            : Order::where('user_id', $user->id)->latest()->get();

        return view('orders.index', compact('orders'));
    }

    /** Buat pesanan baru dari isi keranjang */
    public function store(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $cart = session('cart', []);

        if (empty($cart)) {
            return back()->with('error', 'Keranjang masih kosong.');
        }

        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        $items = [];
        $total = 0;
        foreach ($cart as $id => $qty) {
            $p = $products->get((int) $id);
            if (!$p) continue;
            if ($p->stock < $qty) {
                return back()->with('error', "Stok {$p->name} tidak mencukupi (tersisa {$p->stock}).");
            }
            $subtotal = $p->price * $qty;
            $total   += $subtotal;
            $items[]  = [
                'product_id' => $id,
                'name'       => $p->name,
                'price'      => $p->price,
                'qty'        => $qty,
                'subtotal'   => $subtotal,
            ];
        }

        if (empty($items)) {
            return back()->with('error', 'Tidak ada produk valid di keranjang.');
        }

        // Kurangi stok
        foreach ($items as $item) {
            Product::where('id', $item['product_id'])->decrement('stock', $item['qty']);
        }

        $order = Order::create([
            'user_id' => Auth::id(),
            'items'   => $items,
            'total'   => $total,
            'status'  => 'pending_payment',
            'notes'   => $request->notes,
        ]);

        // Generate Midtrans Snap token
        try {
            $this->configureMidtrans();

            $itemDetails = array_map(fn($item) => [
                'id'       => (string) $item['product_id'],
                'price'    => (int) $item['price'],
                'quantity' => (int) $item['qty'],
                'name'     => substr($item['name'], 0, 50),
            ], $items);

            $user = Auth::user();
            $params = [
                'transaction_details' => [
                    'order_id'    => 'ORDER-' . $order->id,
                    'gross_amount' => (int) $total,
                ],
                'customer_details' => [
                    'first_name' => $user->name ?? $user->username,
                    'email'      => $user->email,
                ],
                'item_details' => $itemDetails,
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $order->update(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans snap token error: ' . $e->getMessage());
        }

        // Kosongkan keranjang
        session(['cart' => []]);
        Auth::user()->update(['cart' => []]);

        return redirect()->route('orders.index')
            ->with('success', 'Pesanan berhasil dibuat! Silakan selesaikan pembayaran.');
    }

    /** Generate ulang snap token untuk order yang belum punya token */
    public function generateSnapToken(Order $order)
    {
        if ((int) $order->user_id !== (int) Auth::id() && !Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        if ($order->status !== 'pending_payment') {
            return back()->with('error', 'Pesanan ini tidak dalam status menunggu pembayaran.');
        }

        try {
            $this->configureMidtrans();

            $itemDetails = array_map(fn($item) => [
                'id'       => (string) $item['product_id'],
                'price'    => (int) $item['price'],
                'quantity' => (int) $item['qty'],
                'name'     => substr($item['name'], 0, 50),
            ], $order->items);

            $user = Auth::user();
            $params = [
                'transaction_details' => [
                    'order_id'     => 'ORDER-' . $order->id . '-' . time(),
                    'gross_amount' => (int) $order->total,
                ],
                'customer_details' => [
                    'first_name' => $user->name ?? $user->username,
                    'email'      => $user->email,
                ],
                'item_details' => $itemDetails,
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $order->update(['snap_token' => $snapToken]);

            return back()->with('success', 'Silakan klik Bayar Sekarang untuk melanjutkan pembayaran.');
        } catch (\Exception $e) {
            Log::error('Midtrans regenerate snap token error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memuat halaman pembayaran. Coba lagi.');
        }
    }

    /** Webhook callback dari Midtrans */
    public function midtransCallback(Request $request)
    {
        $this->configureMidtrans();

        try {
            $notification = new \Midtrans\Notification();
        } catch (\Exception $e) {
            Log::error('Midtrans callback error: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid notification'], 400);
        }

        $orderId = str_replace('ORDER-', '', $notification->order_id);
        $order   = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transactionStatus = $notification->transaction_status;
        $fraudStatus       = $notification->fraud_status ?? null;
        $paymentType       = $notification->payment_type ?? null;

        if ($transactionStatus === 'capture') {
            $newStatus = $fraudStatus === 'accept' ? 'paid' : 'waiting_confirmation';
            $order->update(['status' => $newStatus, 'payment_method' => $paymentType]);
        } elseif ($transactionStatus === 'settlement') {
            $order->update(['status' => 'paid', 'payment_method' => $paymentType]);
        } elseif ($transactionStatus === 'pending') {
            $order->update(['payment_method' => $paymentType]);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire', 'failure'])) {
            $order->update(['status' => 'cancelled']);
        }

        return response()->json(['message' => 'OK']);
    }

    /** Super admin: edit status & catatan pesanan */
    public function update(Request $request, Order $order)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }
        $request->validate([
            'status' => 'required|in:pending_payment,waiting_confirmation,paid,cancelled',
            'notes'  => 'nullable|string|max:500',
        ]);
        $order->update([
            'status' => $request->status,
            'notes'  => $request->notes,
        ]);
        return back()->with('success', "Pesanan #{$order->id} berhasil diperbarui.");
    }

    /** Super admin: hapus pesanan */
    public function destroy(Order $order)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }
        if ($order->payment_proof) {
            Storage::disk('public')->delete($order->payment_proof);
        }
        $orderId = $order->id;
        $order->delete();
        return back()->with('success', "Pesanan #{$orderId} berhasil dihapus.");
    }

    /** Super admin: tandai pesanan sebagai lunas */
    public function markPaid(Order $order)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $order->update(['status' => 'paid']);

        return back()->with('success', "Pesanan #{$order->id} telah ditandai sebagai Lunas.");
    }

    public function cancel(Order $order)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && (int)$order->user_id !== (int)$user->id) {
            abort(403);
        }

        if (in_array($order->status, ['paid', 'cancelled'])) {
            return back()->with('error', 'Pesanan ini tidak dapat dibatalkan.');
        }

        // Kembalikan stok
        foreach ($order->items as $item) {
            Product::where('id', $item['product_id'])->increment('stock', $item['qty']);
        }

        $order->update(['status' => 'cancelled']);

        return back()->with('success', "Pesanan #{$order->id} telah dibatalkan.");
    }
}
