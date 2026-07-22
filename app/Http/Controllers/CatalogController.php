<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Catalog::query()
            ->where('is_active', true)
            ->with(['category', 'discount', 'images', 'specifications', 'inputFields']);

        $query->when($this->normalizedSearch($request), function ($q, string $search) {
            $q->where(function ($inner) use ($search) {
                $inner
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereHas('category', fn ($category) => $category->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%']));
            });
        });
        $query->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($category) => $category->where('slug', $request->string('category'))));
        $query->when($request->filled('min_price'), fn ($q) => $q->where('base_price', '>=', (int) $request->input('min_price')));
        $query->when($request->filled('max_price'), fn ($q) => $q->where('base_price', '<=', (int) $request->input('max_price')));

        match ($request->input('sort')) {
            'price_low' => $query->orderBy('base_price'),
            'price_high' => $query->orderByDesc('base_price'),
            'rating' => $query->orderByDesc('rating_average'),
            default => $query->latest(),
        };

        return view('catalog.index', [
            'catalogs' => $query->paginate(9)->withQueryString(),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Catalog $catalog): View
    {
        abort_unless($catalog->is_active, 404);

        return view('catalog.show', [
            'catalog' => $catalog->load(['category', 'discount', 'images', 'specifications', 'inputFields', 'reviews.user']),
        ]);
    }

    private function normalizedSearch(Request $request): ?string
    {
        if (! $request->filled('search')) {
            return null;
        }

        $search = Str::of($request->input('search'))
            ->squish()
            ->lower()
            ->toString();

        return $search === '' ? null : $search;
    }
}
