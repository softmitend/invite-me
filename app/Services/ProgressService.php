<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProgressTemplate;

class ProgressService
{
    public function seedDefaultSteps(Order $order): void
    {
        $template = OrderProgressTemplate::where('is_default', true)->with('steps')->first();
        $labels = $template?->steps->pluck('label')->all() ?: ['Order diterima', 'Data diverifikasi', 'Desain diproses', 'Preview dikirim', 'Finalisasi'];

        foreach ($labels as $index => $label) {
            $order->progressSteps()->firstOrCreate(
                ['sort_order' => $index + 1],
                [
                    'label' => $label,
                    'is_completed' => $index === 0,
                    'completed_at' => $index === 0 ? now() : null,
                ]
            );
        }
    }

    public function percent(Order $order): int
    {
        $steps = $order->progressSteps;

        if ($steps->isEmpty()) {
            return 0;
        }

        return (int) round(($steps->where('is_completed', true)->count() / $steps->count()) * 100);
    }
}
