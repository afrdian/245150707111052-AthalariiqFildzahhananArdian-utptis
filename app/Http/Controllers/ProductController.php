<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Info(title: "UTP Ecommerce API", version: "1.0.0", description: "Backend API ecommerce sederhana dengan mock data JSON")]
#[OA\Server(url: "http://127.0.0.1:8000", description: "Local Server")]
class ProductController extends Controller
{
    private string $jsonPath = 'products.json';

    private function readProducts(): array
    {
        if (!Storage::exists($this->jsonPath)) {
            Storage::put($this->jsonPath, json_encode([]));
        }
        return json_decode(Storage::get($this->jsonPath), true) ?? [];
    }

    private function writeProducts(array $products): void
    {
        Storage::put($this->jsonPath, json_encode(array_values($products), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function findProduct(array $products, int $id): int|false
    {
        foreach ($products as $index => $product) {
            if ($product['id'] === $id) return $index;
        }
        return false;
    }

    #[OA\Get(path: "/api/products", summary: "Ambil semua produk", tags: ["Products"])]
    #[OA\Response(response: 200, description: "List semua produk")]
    public function index()
    {
        $products = $this->readProducts();
        return response()->json([
            'status'  => 'success',
            'message' => empty($products) ? 'Belum ada produk tersedia' : 'Berhasil mengambil semua produk',
            'total'   => count($products),
            'data'    => $products
        ], 200);
    }

    #[OA\Get(path: "/api/products/{id}", summary: "Ambil produk by ID", tags: ["Products"])]
    #[OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))]
    #[OA\Response(response: 200, description: "Data produk ditemukan")]
    #[OA\Response(response: 404, description: "Produk tidak ditemukan")]
    public function show(int $id)
    {
        $products = $this->readProducts();
        $index    = $this->findProduct($products, $id);
        if ($index === false) {
            return response()->json(['status' => 'error', 'message' => "Item dengan ID {$id} tidak ditemukan"], 404);
        }
        return response()->json(['status' => 'success', 'message' => 'Berhasil mengambil produk', 'data' => $products[$index]], 200);
    }

    #[OA\Post(path: "/api/products", summary: "Tambah produk baru", tags: ["Products"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["nama_barang", "harga"],
        properties: [
            new OA\Property(property: "nama_barang", type: "string", example: "Headset Gaming"),
            new OA\Property(property: "harga", type: "number", example: 450000),
            new OA\Property(property: "stok", type: "integer", example: 20),
            new OA\Property(property: "kategori", type: "string", example: "Aksesoris"),
            new OA\Property(property: "deskripsi", type: "string", example: "Headset dengan noise cancelling"),
        ]
    ))]
    #[OA\Response(response: 201, description: "Produk berhasil ditambahkan")]
    #[OA\Response(response: 422, description: "Validasi gagal")]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:255',
            'harga'       => 'required|numeric|min:0',
            'stok'        => 'nullable|integer|min:0',
            'kategori'    => 'nullable|string|max:100',
            'deskripsi'   => 'nullable|string|max:500',
        ], [
            'nama_barang.required' => 'Nama barang wajib diisi',
            'harga.required'       => 'Harga wajib diisi',
            'harga.numeric'        => 'Harga harus berupa angka',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $products   = $this->readProducts();
        $newId      = count($products) > 0 ? max(array_column($products, 'id')) + 1 : 1;
        $newProduct = [
            'id'          => $newId,
            'nama_barang' => $request->nama_barang,
            'harga'       => (float) $request->harga,
            'stok'        => $request->stok ?? 0,
            'kategori'    => $request->kategori ?? 'Umum',
            'deskripsi'   => $request->deskripsi ?? '',
        ];

        $products[] = $newProduct;
        $this->writeProducts($products);
        return response()->json(['status' => 'success', 'message' => 'Produk berhasil ditambahkan', 'data' => $newProduct], 201);
    }

    #[OA\Put(path: "/api/products/{id}", summary: "Update seluruh data produk", tags: ["Products"])]
    #[OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["nama_barang", "harga", "stok", "kategori", "deskripsi"],
        properties: [
            new OA\Property(property: "nama_barang", type: "string", example: "Laptop Baru"),
            new OA\Property(property: "harga", type: "number", example: 9000000),
            new OA\Property(property: "stok", type: "integer", example: 10),
            new OA\Property(property: "kategori", type: "string", example: "Elektronik"),
            new OA\Property(property: "deskripsi", type: "string", example: "Update seluruh data"),
        ]
    ))]
    #[OA\Response(response: 200, description: "Produk berhasil diperbarui")]
    #[OA\Response(response: 404, description: "Produk tidak ditemukan")]
    #[OA\Response(response: 422, description: "Validasi gagal")]
    public function update(Request $request, int $id)
    {
        $products = $this->readProducts();
        $index    = $this->findProduct($products, $id);
        if ($index === false) {
            return response()->json(['status' => 'error', 'message' => "Item dengan ID {$id} tidak ditemukan"], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:255',
            'harga'       => 'required|numeric|min:0',
            'stok'        => 'required|integer|min:0',
            'kategori'    => 'required|string|max:100',
            'deskripsi'   => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $products[$index] = [
            'id'          => $id,
            'nama_barang' => $request->nama_barang,
            'harga'       => (float) $request->harga,
            'stok'        => (int) $request->stok,
            'kategori'    => $request->kategori,
            'deskripsi'   => $request->deskripsi,
        ];

        $this->writeProducts($products);
        return response()->json(['status' => 'success', 'message' => "Produk dengan ID {$id} berhasil diperbarui (seluruh data)", 'data' => $products[$index]], 200);
    }

    #[OA\Patch(path: "/api/products/{id}", summary: "Update sebagian data produk", tags: ["Products"])]
    #[OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        properties: [
            new OA\Property(property: "harga", type: "number", example: 7500000),
        ]
    ))]
    #[OA\Response(response: 200, description: "Produk berhasil diperbarui sebagian")]
    #[OA\Response(response: 404, description: "Produk tidak ditemukan")]
    #[OA\Response(response: 400, description: "Tidak ada data yang dikirim")]
    public function partialUpdate(Request $request, int $id)
    {
        $products = $this->readProducts();
        $index    = $this->findProduct($products, $id);
        if ($index === false) {
            return response()->json(['status' => 'error', 'message' => "Item dengan ID {$id} tidak ditemukan"], 404);
        }

        if (empty($request->all())) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada data yang dikirim untuk diperbarui'], 400);
        }

        $validator = Validator::make($request->all(), [
            'nama_barang' => 'sometimes|string|max:255',
            'harga'       => 'sometimes|numeric|min:0',
            'stok'        => 'sometimes|integer|min:0',
            'kategori'    => 'sometimes|string|max:100',
            'deskripsi'   => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $products[$index] = array_merge($products[$index], array_filter([
            'nama_barang' => $request->nama_barang,
            'harga'       => $request->has('harga') ? (float) $request->harga : null,
            'stok'        => $request->has('stok') ? (int) $request->stok : null,
            'kategori'    => $request->kategori,
            'deskripsi'   => $request->deskripsi,
        ], fn($v) => !is_null($v)));

        $this->writeProducts($products);
        return response()->json(['status' => 'success', 'message' => "Produk dengan ID {$id} berhasil diperbarui (sebagian data)", 'data' => $products[$index]], 200);
    }

    #[OA\Delete(path: "/api/products/{id}", summary: "Hapus produk by ID", tags: ["Products"])]
    #[OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))]
    #[OA\Response(response: 200, description: "Produk berhasil dihapus")]
    #[OA\Response(response: 404, description: "Produk tidak ditemukan")]
    public function destroy(int $id)
    {
        $products = $this->readProducts();
        $index    = $this->findProduct($products, $id);
        if ($index === false) {
            return response()->json(['status' => 'error', 'message' => "Item dengan ID {$id} tidak ditemukan"], 404);
        }

        $deleted = $products[$index];
        unset($products[$index]);
        $this->writeProducts($products);
        return response()->json(['status' => 'success', 'message' => "Produk dengan ID {$id} berhasil dihapus", 'data' => $deleted], 200);
    }
}