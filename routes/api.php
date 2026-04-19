<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return response()->json([
        'status'  => 'success',
        'message' => 'API UTP Ecommerce berjalan dengan baik',
        'version' => '1.0.0',
        'endpoints' => [
            'GET    /api/products'        => 'Menampilkan semua produk',
            'GET    /api/products/{id}'   => 'Menampilkan produk berdasarkan ID',
            'POST   /api/products'        => 'Menambah produk baru',
            'PUT    /api/products/{id}'   => 'Memperbarui seluruh data produk',
            'PATCH  /api/products/{id}'   => 'Memperbarui sebagian data produk',
            'DELETE /api/products/{id}'   => 'Menghapus produk',
        ]
    ]);
});

// Product Routes
Route::prefix('products')->group(function () {

    // GET /api/products — tampilkan semua produk
    Route::get('/', [ProductController::class, 'index']);

    // GET /api/products/{id} — tampilkan produk by ID
    Route::get('/{id}', [ProductController::class, 'show'])
        ->where('id', '[0-9]+');

    // POST /api/products — buat produk baru
    Route::post('/', [ProductController::class, 'store']);

    // PUT /api/products/{id} — update seluruh data produk
    Route::put('/{id}', [ProductController::class, 'update'])
        ->where('id', '[0-9]+');

    // PATCH /api/products/{id} — update sebagian data produk
    Route::patch('/{id}', [ProductController::class, 'partialUpdate'])
        ->where('id', '[0-9]+');

    // DELETE /api/products/{id} — hapus produk
    Route::delete('/{id}', [ProductController::class, 'destroy'])
        ->where('id', '[0-9]+');
});

// Handle 404 untuk route yang tidak ditemukan
Route::fallback(function () {
    return response()->json([
        'status'  => 'error',
        'message' => 'Endpoint tidak ditemukan',
    ], 404);
});