<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    /**
     * Menunjukkan seluruh daftar SO terbaru dengan detail item produknya
     * pengunaan withTrashed() agar history nama produk tetap ada meskipun produk sudah di softdelete.
     */
    public function index()
    {
        $orders = SalesOrder::with(['user:id,name,email', 'items.product:id,name,deleted_at'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ], 200);
    }

    /**
     * Create SO dengan beberapa item produk dan variannya (Bordir / Tanpa Bordir).
     *
     * Proses penting:
     * 1. Validasi produk atif atau tidak dan value variant yang diizinkan.
     * 2. Penggunaan DB::transaction() untuk menjamin penyimpanan data master dan detail (auto-rollback jika gagal menyimpan).
     * 3. Penggunaan lockForUpdate() saat membaca urutan transaksi hari ini untuk mencegah perebutan nomor order.
     * 4. Kalkulasi subtotal per item dan total_amount keseluruhan transaksi secara otomatis.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id,deleted_at,NULL',
            'items.*.variant' => 'required|in:Bordir,Tanpa Bordir',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = DB::transaction(function () use ($request) {
                $totalAmount = 0;
                $orderItemsData = [];

                // Calc subtotal dari harga produk
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $subtotal = $product->price * $item['quantity'];
                    $totalAmount += $subtotal;

                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'variant' => $item['variant'],
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                        'subtotal' => $subtotal,
                    ];
                }

                // lock SO number
                $today = date('Ymd');
                $lastOrder = SalesOrder::withTrashed()
                    ->whereDate('created_at', today())
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                $nextSequence = 1;
                if ($lastOrder) {
                    $lastNumber = (int) substr($lastOrder->order_number, -4);
                    $nextSequence = $lastNumber + 1;
                }

                $orderNumber = 'SO-' . $today . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

                $salesOrder = SalesOrder::create([
                    'order_number' => $orderNumber,
                    'user_id' => $request->user()->id,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                ]);

                $salesOrder->items()->createMany($orderItemsData);

                return $salesOrder->load(['user:id,name,email', 'items.product:id,name']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Sales order berhasil dibuat',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat sales order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail satu SO berdasarkan ID.
     * Return HTTP 404 Not Found jika data SO tidak ditemukan.
     */
    public function show($id)
    {
        $order = SalesOrder::with(['user:id,name,email', 'items.product:id,name,deleted_at'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Sales order tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ], 200);
    }

    /**
     * Update Data SO bisa untuk status nya saja, dan atau termasuk daftar item dengan variannnya juga.
     * Jika ada daftar item baru yang dikirim, item lama akan diganti semua dengan item baru dan total_amount nya akan dihitung ulang secara otomatis dalam DB::transaction().
     */
    public function update(Request $request, $id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Sales order tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'status' => 'sometimes|in:pending,completed,cancelled',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id,deleted_at,NULL',
            'items.*.variant' => 'required_with:items|in:Bordir,Tanpa Bordir',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        try {
            $updatedOrder = DB::transaction(function () use ($request, $order) {
                if ($request->has('status')) {
                    $order->status = $request->status;
                }

                if ($request->has('items')) {
                    $totalAmount = 0;
                    $newItemsData = [];

                    foreach ($request->items as $item) {
                        $product = Product::findOrFail($item['product_id']);
                        $subtotal = $product->price * $item['quantity'];
                        $totalAmount += $subtotal;

                        $newItemsData[] = [
                            'product_id' => $product->id,
                            'variant' => $item['variant'],
                            'quantity' => $item['quantity'],
                            'price' => $product->price,
                            'subtotal' => $subtotal,
                        ];
                    }

                    // delete item lama ganti yang baru
                    $order->items()->delete();
                    $order->items()->createMany($newItemsData);
                    $order->total_amount = $totalAmount;
                }

                $order->save();

                return $order->load(['user:id,name,email', 'items.product:id,name']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Sales order berhasil diperbarui',
                'data' => $updatedOrder
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui sales order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus SO secara soft delete berdasarkan ID.
     * Return HTTP 404 Not Found jika data SO tidak ditemukan.
     */
    public function destroy($id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Sales order tidak ditemukan'
            ], 404);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sales order berhasil dihapus'
        ], 200);
    }
}