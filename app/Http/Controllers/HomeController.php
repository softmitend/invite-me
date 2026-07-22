<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->withCount('catalogs')->take(6)->get(),
            'featuredCatalogs' => Catalog::where('is_active', true)->where('is_featured', true)->with(['category', 'discount', 'images'])->latest()->take(6)->get(),
            'reviews' => Review::where('is_visible', true)->with(['user', 'catalog'])->latest()->take(6)->get(),
        ]);
    }
}
