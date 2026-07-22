<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with('items')
            ->when($request->filled('status'), fn ($q) => $q->where('work_status', $request->string('status')))
            ->latest()
            ->paginate(8);

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order, ProgressService $progress): View
    {
        $this->authorizeOrder($request, $order);

        return view('orders.show', [
            'order' => $order->load(['items', 'inputValues', 'payments', 'progressSteps', 'revisions', 'activities']),
            'progressPercent' => $progress->percent($order),
        ]);
    }

    public function approvePreview(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);
        abort_unless($order->work_status === Order::WORK_PREVIEW, 422);

        $order->update(['preview_approved_at' => now()]);
        $order->activities()->create(['user_id' => $request->user()->id, 'type' => 'preview_approved', 'message' => 'Customer menyetujui preview.']);

        return back()->with('success', 'Preview disetujui.');
    }

    public function requestRevision(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);
        abort_unless($order->work_status === Order::WORK_PREVIEW, 422);

        $data = $request->validate(['note' => ['required', 'string', 'min:10']]);
        $order->revisions()->create(['user_id' => $request->user()->id, 'note' => $data['note']]);
        $order->update(['work_status' => Order::WORK_REVISION]);
        $order->activities()->create(['user_id' => $request->user()->id, 'type' => 'revision_requested', 'message' => 'Customer mengajukan revisi.']);

        return back()->with('success', 'Catatan revisi dikirim.');
    }

    public function invoice(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        return view('orders.invoice', ['order' => $order->load(['items', 'payments'])]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($request->user()?->isAdmin() || $order->user_id === $request->user()?->id, 403);
    }
}
