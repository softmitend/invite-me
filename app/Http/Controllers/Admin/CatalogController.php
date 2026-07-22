<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $catalogs = Catalog::query()
            ->with(['category', 'images'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = Str::of($request->input('search'))->squish()->lower()->toString();

                $query->where(function ($inner) use ($search) {
                    $inner
                        ->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(slug) LIKE ?', ['%'.$search.'%'])
                        ->orWhereHas('category', fn ($category) => $category->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%']));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.catalogs.index', [
            'catalogs' => $catalogs,
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'stats' => [
                'total' => Catalog::count(),
                'active' => Catalog::where('is_active', true)->count(),
                'inactive' => Catalog::where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, requireImage: true);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');

        $catalog = Catalog::create($data);
        $catalog->images()->create(['path' => $request->input('image_path'), 'alt_text' => $catalog->name, 'sort_order' => 1, 'is_primary' => true]);

        return back()->with('success', 'Katalog dibuat.');
    }

    public function update(Request $request, Catalog $catalog): RedirectResponse
    {
        $data = $this->validated($request, $catalog);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $catalog->update($data);

        if ($request->filled('image_path')) {
            $catalog->images()->updateOrCreate(['sort_order' => 1], ['path' => $request->input('image_path'), 'alt_text' => $catalog->name, 'is_primary' => true]);
        }

        return back()->with('success', 'Katalog diperbarui.');
    }

    public function destroy(Catalog $catalog): RedirectResponse
    {
        $catalog->delete();

        return back()->with('success', 'Katalog diarsipkan.');
    }

    private function validated(Request $request, ?Catalog $catalog = null, bool $requireImage = false): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:catalogs,slug,'.($catalog?->id ?? 'NULL')],
            'description' => ['required', 'string'],
            'preview_url' => ['required', 'url', 'max:255'],
            'base_price' => ['required', 'integer', 'min:0'],
            'estimated_days' => ['required', 'integer', 'min:1'],
            'image_path' => [$requireImage ? 'required' : 'nullable', 'url'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);
    }
}
