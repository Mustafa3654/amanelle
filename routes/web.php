<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', [
        'featured' => Product::active()->featured()
            ->with(['brand', 'variants', 'references'])
            ->latest('published_at')
            ->take(8)
            ->get(),
        'categories' => Category::active()->orderBy('sort_order')->get(),
    ]);
})->name('home');

Route::get('/shop', function () {
    return view('shop', [
        'categories' => Category::active()->withCount('products')->orderBy('sort_order')->get(),
        'products' => Product::active()
            ->with(['brand', 'variants', 'references'])
            ->latest('published_at')
            ->paginate(12),
    ]);
})->name('shop');

Route::get('/c/{category:slug}', function (Category $category) {
    return view('category', [
        'category' => $category,
        'products' => $category->products()
            ->active()
            ->with(['brand', 'variants', 'references'])
            ->paginate(12),
    ]);
})->name('category');

Route::get('/p/{product:slug}', function (Product $product) {
    abort_unless($product->is_active, 404);

    $product->load(['brand', 'category', 'variants.inventories', 'references']);

    return view('product', ['product' => $product]);
})->name('product');

Route::view('/cart', 'cart')->name('cart');
Route::view('/checkout', 'checkout')->name('checkout');

Route::get('/order/{number}', function (string $number) {
    // Looked up by order number rather than id: the number is what the
    // customer is told, and sequential ids invite guessing at other people's
    // orders.
    $order = \App\Models\Order::where('number', $number)->with('items')->firstOrFail();

    return view('order-confirmation', ['order' => $order]);
})->name('order.confirmation');

Route::view('/about', 'about')->name('about');

Route::get('/contact', fn () => view('contact'))->name('contact');

Route::post('/contact', function (Request $request) {
    $data = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:190'],
        'message' => ['required', 'string', 'max:2000'],
    ]);

    // Stored nowhere yet — wiring this to mail or a messages table is the
    // next step, and silently dropping it would be worse than saying so.
    logger()->info('Contact enquiry', $data);

    return back()->with('status', __('Thanks — we will get back to you shortly.'));
})->name('contact.send');

Route::post('/currency', function (Request $request) {
    $code = $request->string('currency')->toString();

    abort_unless(
        \App\Support\Money::currencies()->contains('code', $code),
        400
    );

    $request->session()->put(\App\Support\Money::SESSION_KEY, $code);

    return back();
})->name('currency.switch');

/**
 * Stores the locale against the session rather than the URL. Amanelle's
 * customers arrive from an Arabic Instagram bio, so the default is already
 * right for most of them and a prefixed URL would be noise.
 */
Route::post('/locale', function (Request $request) {
    $locale = $request->string('locale')->toString();

    abort_unless(array_key_exists($locale, config('amanelle.locales')), 400);

    $request->session()->put('locale', $locale);

    return back();
})->name('locale.switch');
