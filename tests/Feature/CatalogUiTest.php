<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_detail_renders_at_most_five_images(): void
    {
        $category = Category::factory()->create();
        $catalog = Catalog::factory()->create(['category_id' => $category->id]);

        foreach (range(1, 6) as $index) {
            $catalog->images()->create([
                'path' => "https://example.test/image-{$index}.jpg",
                'sort_order' => $index,
                'is_primary' => $index === 1,
            ]);
        }

        $response = $this->get(route('catalog.show', $catalog));

        $response->assertOk();
        $response->assertSee('image-5.jpg');
        $response->assertDontSee('image-6.jpg');
    }
}
