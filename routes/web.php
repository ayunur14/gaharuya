<?php


use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HOME REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect('/dashboard');
});

/*
|--------------------------------------------------------------------------
| DASHBOARD (SETELAH LOGIN)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    $products  = \App\Models\Product::latest()->get();
    $cartCount = array_sum(session('cart', []));
    return view('dashboard', compact('products', 'cartCount'));
})->name('dashboard');

Route::post('/cart/add/{id}', function (\Illuminate\Http\Request $r, $id) {
    $qty  = max(1, (int) $r->input('qty', 1));
    $cart = session()->get('cart', []);
    $cart[$id] = ($cart[$id] ?? 0) + $qty;
    session(['cart' => $cart]);
    // Persist ke DB agar tidak hilang saat logout
    if (\Illuminate\Support\Facades\Auth::check()) {
        \Illuminate\Support\Facades\Auth::user()->update(['cart' => $cart]);
    }
    $count = array_sum($cart);
    return response()->json(['count' => $count]);
});

Route::post('/cart/remove/{id}', function ($id) {
    $cart = session()->get('cart', []);
    unset($cart[$id]);
    session(['cart' => $cart]);
    // Persist ke DB
    if (\Illuminate\Support\Facades\Auth::check()) {
        \Illuminate\Support\Facades\Auth::user()->update(['cart' => $cart]);
    }
    return back()->with('success', 'Item dihapus dari keranjang.');
})->middleware('auth');

Route::get('/cart', function () {
    $cart = session('cart', []);
    $ids  = array_keys($cart);
    $products = empty($ids)
        ? collect()
        : \App\Models\Product::whereIn('id', $ids)->get()->keyBy('id');
    return view('cart', compact('cart', 'products'));
});

Route::get('/produk/tambah', function () {
    return view('produk.tambah');
})->middleware(['auth', 'super_admin']);

Route::post('/produk/tambah', function (\Illuminate\Http\Request $r) {
    $r->validate([
        'sku'      => 'nullable|string|max:50|unique:products,sku',
        'name'     => 'required|string|max:255',
        'price'    => 'required|numeric|min:0',
        'stock'    => 'required|integer|min:0',
        'desc'     => 'required|string|max:500',
        'images'   => 'nullable|array|max:5',
        'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
    ], [
        'images.max' => 'Maksimal 5 gambar per produk.',
        'sku.unique' => 'Kode barang sudah dipakai produk lain.',
    ]);

    $imagePaths = [];
    if ($r->hasFile('images')) {
        foreach (array_slice($r->file('images'), 0, 5) as $file) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('products'), $filename);
            $imagePaths[] = 'products/' . $filename;
        }
    }
    if (empty($imagePaths)) {
        $imagePaths = ['logo_gaharu.jpg'];
    }

    \App\Models\Product::create([
        'sku'         => $r->sku ?: null,
        'name'        => $r->name,
        'price'       => $r->price,
        'stock'       => $r->stock,
        'description' => $r->desc,
        'images'      => $imagePaths,
    ]);

    return redirect('/dashboard')->with('success', 'Produk berhasil ditambahkan.');
})->middleware(['auth', 'super_admin']);

Route::get('/edit/{id}', function ($id) {
    $product = \App\Models\Product::findOrFail($id);
    return view('edit', compact('product'));
})->middleware(['auth', 'super_admin']);

Route::post('/update/{id}', function (\Illuminate\Http\Request $r, $id) {
    $r->validate([
        'sku'      => 'nullable|string|max:50|unique:products,sku,' . $id,
        'name'     => 'required|string|max:255',
        'price'    => 'required|numeric|min:0',
        'stock'    => 'required|integer|min:0',
        'desc'     => 'required|string|max:500',
        'images'   => 'nullable|array|max:5',
        'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
    ], [
        'images.max' => 'Maksimal 5 gambar per produk.',
        'sku.unique' => 'Kode barang sudah dipakai produk lain.',
    ]);

    $product = \App\Models\Product::findOrFail($id);

    $imagePaths = $product->images;
    if ($r->hasFile('images')) {
        if ($imagePaths) {
            foreach ($imagePaths as $img) {
                if ($img !== 'logo_gaharu.jpg') {
                    $fullPath = public_path($img);
                    if (file_exists($fullPath)) unlink($fullPath);
                }
            }
        }
        $imagePaths = [];
        foreach (array_slice($r->file('images'), 0, 5) as $file) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('products'), $filename);
            $imagePaths[] = 'products/' . $filename;
        }
    }
    if (empty($imagePaths)) {
        $imagePaths = ['logo_gaharu.jpg'];
    }

    $product->update([
        'sku'         => $r->sku ?: null,
        'name'        => $r->name,
        'price'       => $r->price,
        'stock'       => $r->stock,
        'description' => $r->desc,
        'images'      => $imagePaths,
    ]);
    return redirect('/dashboard')->with('success', 'Produk berhasil diperbarui.');
})->middleware(['auth', 'super_admin']);

Route::post('/delete/{id}', function ($id) {
    $product = \App\Models\Product::findOrFail($id);
    if ($product->images) {
        foreach ($product->images as $img) {
            if ($img !== 'logo_gaharu.jpg') {
                $fullPath = public_path($img);
                if (file_exists($fullPath)) unlink($fullPath);
            }
        }
    }
    $product->delete();
    return back()->with('success', 'Produk berhasil dihapus.');
})->middleware(['auth', 'super_admin']);

/*
|--------------------------------------------------------------------------
| PROFILE (BREEZE)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| USER MANAGEMENT (SUPER ADMIN ONLY)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'super_admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| MIDTRANS WEBHOOK CALLBACK
|--------------------------------------------------------------------------
*/
Route::post('/midtrans/callback', [OrderController::class, 'midtransCallback'])->name('midtrans.callback');

/*
|--------------------------------------------------------------------------
| ORDERS (PESANAN)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/orders',                    [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders',                   [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}/proof',      [OrderController::class, 'proofView'])->name('orders.proofView');
    Route::post('/orders/{order}/proof',     [OrderController::class, 'uploadProof'])->name('orders.proof');
    Route::post('/orders/{order}/update',    [OrderController::class, 'update'])->name('orders.update');
    Route::post('/orders/{order}/delete',    [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('/orders/{order}/mark-paid',      [OrderController::class, 'markPaid'])->name('orders.markPaid');
    Route::post('/orders/{order}/cancel',         [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/generate-snap',  [OrderController::class, 'generateSnapToken'])->name('orders.generateSnap');
});
