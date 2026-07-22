<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProgressStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.orders.index', [
            'orders' => Order::query()
                ->with(['items', 'progressSteps'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = Str::of($request->input('search'))->squish()->lower()->toString();

                    $query->where(function ($inner) use ($search) {
                        $inner
                            ->whereRaw('LOWER(order_number) LIKE ?', ['%'.$search.'%'])
                            ->orWhereRaw('LOWER(customer_name) LIKE ?', ['%'.$search.'%'])
                            ->orWhereRaw('LOWER(customer_email) LIKE ?', ['%'.$search.'%'])
                            ->orWhereRaw('LOWER(customer_phone) LIKE ?', ['%'.$search.'%'])
                            ->orWhereHas('items', fn ($items) => $items->whereRaw('LOWER(catalog_name) LIKE ?', ['%'.$search.'%']));
                    });
                })
                ->when($request->filled('work_status'), fn ($query) => $query->where('work_status', $request->input('work_status')))
                ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->input('payment_status')))
                ->when($request->input('dashboard_group') === 'active', fn ($query) => $query->whereNotIn('work_status', [Order::WORK_COMPLETED, Order::WORK_CANCELLED]))
                ->when($request->input('publication_status') === 'published', fn ($query) => $query->whereNotNull('final_url')->where('work_status', Order::WORK_COMPLETED))
                ->when($request->filled('pipeline'), function ($query) use ($request) {
                    match ($request->input('pipeline')) {
                        'awaiting_dp' => $query->where('work_status', Order::WORK_RECEIVED)->whereIn('payment_status', [Order::PAYMENT_UNPAID, Order::PAYMENT_PENDING]),
                        'queued' => $query->where('work_status', Order::WORK_RECEIVED)->whereIn('payment_status', [Order::PAYMENT_PARTIALLY_PAID, Order::PAYMENT_PAID]),
                        'revision_in_progress' => $query->where('work_status', Order::WORK_REVISION)->whereHas('progressSteps', fn ($steps) => $steps->where('is_completed', false)),
                        'approved' => $query->whereNotNull('preview_approved_at')->where('work_status', '!=', Order::WORK_COMPLETED),
                        default => null,
                    };
                })
                ->when($request->input('action') === 'overdue', function ($query) {
                    $query
                        ->whereNotIn('work_status', [Order::WORK_COMPLETED, Order::WORK_CANCELLED])
                        ->where('created_at', '<', now()->subDays(3));
                })
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'stats' => [
                'total' => Order::count(),
                'new' => Order::where('work_status', Order::WORK_RECEIVED)->count(),
                'in_progress' => Order::where('work_status', Order::WORK_IN_PROGRESS)->count(),
                'pending_payment' => Order::whereIn('payment_status', [Order::PAYMENT_UNPAID, Order::PAYMENT_PENDING, Order::PAYMENT_PARTIALLY_PAID])->count(),
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'inputValues', 'payments', 'progressSteps', 'activities.user', 'revisions.user']);

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'work_status' => ['required', 'in:received,in_progress,preview,revision,completed,cancelled'],
            'preview_url' => ['nullable', 'url'],
            'final_url' => ['nullable', 'url'],
        ]);

        $order->update($data);
        $order->activities()->create(['user_id' => $request->user()->id, 'type' => 'admin_update', 'message' => 'Admin memperbarui status pesanan.']);

        return back()->with('success', 'Pesanan diperbarui.');
    }

    public function storeProgressStep(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:160'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $hadNoSteps = $order->progressSteps()->doesntExist();

        $order->progressSteps()->create([
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? (($order->progressSteps()->max('sort_order') ?? 0) + 1),
            'is_completed' => false,
        ]);

        if ($hadNoSteps && $order->work_status === Order::WORK_RECEIVED) {
            $order->update(['work_status' => Order::WORK_IN_PROGRESS]);
        }

        $order->activities()->create([
            'user_id' => $request->user()->id,
            'type' => 'progress_step_created',
            'message' => $hadNoSteps
                ? 'Admin membuat checklist pertama dan mulai memproses pesanan.'
                : 'Admin menambahkan checklist progress.',
        ]);

        return back()->with('success', 'Checklist progress ditambahkan.');
    }

    public function updateProgressStep(Request $request, Order $order, OrderProgressStep $step): RedirectResponse
    {
        abort_unless($step->order_id === $order->id, 404);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:160'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_completed' => ['required', 'boolean'],
        ]);

        $wasCompleted = $step->is_completed;
        $isCompleted = (bool) $data['is_completed'];

        $step->update([
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? $step->sort_order,
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? ($wasCompleted ? $step->completed_at : now()) : null,
        ]);

        $order->activities()->create([
            'user_id' => $request->user()->id,
            'type' => 'progress_step_updated',
            'message' => 'Admin memperbarui checklist progress.',
        ]);

        return back()->with('success', 'Checklist progress diperbarui.');
    }

    public function destroyProgressStep(Request $request, Order $order, OrderProgressStep $step): RedirectResponse
    {
        abort_unless($step->order_id === $order->id, 404);

        $step->delete();
        $order->activities()->create([
            'user_id' => $request->user()->id,
            'type' => 'progress_step_deleted',
            'message' => 'Admin menghapus checklist progress.',
        ]);

        return back()->with('success', 'Checklist progress dihapus.');
    }
}
