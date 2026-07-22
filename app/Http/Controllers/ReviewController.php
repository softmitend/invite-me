<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order, Catalog $catalog): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->work_status === Order::WORK_COMPLETED, 422, 'Ulasan hanya untuk pesanan selesai.');
        abort_unless($order->items()->where('catalog_id', $catalog->id)->exists(), 422, 'Katalog tidak ada dalam pesanan ini.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:8'],
        ]);

        Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'catalog_id' => $catalog->id, 'order_id' => $order->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'], 'is_visible' => true]
        );

        $catalog->update([
            'rating_average' => (float) $catalog->reviews()->where('is_visible', true)->avg('rating'),
            'reviews_count' => $catalog->reviews()->where('is_visible', true)->count(),
        ]);

        return back()->with('success', 'Ulasan dikirim.');
    }
}
