<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderProgressTemplate;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::updateOrCreate([
            'email' => 'admin@inviteme.test',
        ], [
            'name' => 'Admin InviteMe',
            'phone' => '080000000001',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password'),
        ]);

        $customers = collect([
            ['Nadira Putri', 'nadira@example.test'],
            ['Raka Pratama', 'raka@example.test'],
            ['Maya Kirana', 'maya@example.test'],
        ])->map(fn ($customer, $index) => User::updateOrCreate([
            'email' => $customer[1],
        ], [
            'name' => $customer[0],
            'phone' => '08000000000'.($index + 2),
            'role' => User::ROLE_CUSTOMER,
            'password' => Hash::make('password'),
        ]));

        $discounts = collect([
            Discount::updateOrCreate(['code' => 'HELLO10'], ['name' => 'Promo Pembukaan', 'type' => Discount::TYPE_PERCENT, 'value' => 10, 'is_active' => true]),
            Discount::updateOrCreate(['code' => 'CERIA50'], ['name' => 'Hemat Ceria', 'type' => Discount::TYPE_FIXED, 'value' => 50000, 'is_active' => true]),
        ]);

        $categories = collect([
            ['Ulang Tahun', 'ulang-tahun', 'bg-rose'],
            ['Anniversary', 'anniversary', 'bg-sky'],
            ['Valentine', 'valentine', 'bg-pink'],
            ['Wedding', 'wedding', 'bg-sage'],
            ['Graduation', 'graduation', 'bg-blue'],
            ['Kartu Ucapan', 'kartu-ucapan', 'bg-cream'],
        ])->map(fn ($category, $index) => Category::updateOrCreate([
            'slug' => $category[1],
        ], [
            'name' => $category[0],
            'image_path' => $category[2],
            'sort_order' => $index + 1,
            'is_active' => true,
        ]));

        $imageSets = [
            ['https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=80', 'https://images.unsplash.com/photo-1523438885200-e635ba2c371e?auto=format&fit=crop&w=900&q=80'],
            ['https://images.unsplash.com/photo-1529634806980-85c3dd6d34ac?auto=format&fit=crop&w=900&q=80', 'https://images.unsplash.com/photo-1519225421980-715cb0215aed?auto=format&fit=crop&w=900&q=80'],
            ['https://images.unsplash.com/photo-1513151233558-d860c5398176?auto=format&fit=crop&w=900&q=80', 'https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?auto=format&fit=crop&w=900&q=80'],
            ['https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&w=900&q=80', 'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?auto=format&fit=crop&w=900&q=80'],
        ];

        $names = [
            'Serenade Digital Invite',
            'Aurora Wedding Web',
            'Bloom Birthday Card',
            'Lumi Anniversary Story',
            'Rosette Valentine Note',
            'Caps Off Graduation',
            'Golden Akad Invitation',
            'Pastel Save The Date',
            'Velvet Greeting Card',
            'Garden Party Invite',
            'Minimal RSVP Website',
            'Starlit Couple Story',
        ];

        foreach ($names as $index => $name) {
            $catalog = Catalog::updateOrCreate([
                'slug' => Str::slug($name),
            ], [
                'category_id' => $categories[$index % $categories->count()]->id,
                'discount_id' => $index % 3 === 0 ? $discounts->random()->id : null,
                'name' => $name,
                'description' => 'Template digital siap personalisasi dengan halaman cerita, detail acara, galeri, RSVP, dan tautan praktis untuk tamu.',
                'preview_url' => 'https://demo.inviteme.test/'.Str::slug($name),
                'base_price' => 175000 + ($index * 45000),
                'estimated_days' => 2 + ($index % 5),
                'rating_average' => 4.2 + (($index % 7) / 10),
                'reviews_count' => 4 + $index,
                'is_featured' => $index < 6,
                'is_active' => $index !== 10,
            ]);

            $catalog->images()->delete();
            $catalog->specifications()->delete();
            $catalog->inputFields()->delete();

            foreach ($imageSets[$index % count($imageSets)] as $imageIndex => $image) {
                $catalog->images()->create([
                    'path' => $image,
                    'alt_text' => $name,
                    'sort_order' => $imageIndex + 1,
                    'is_primary' => $imageIndex === 0,
                ]);
            }

            foreach ([['Format', $index % 2 === 0 ? 'Undangan Web' : 'Kartu Digital'], ['Fitur', 'Galeri, RSVP, musik, peta lokasi'], ['Revisi', '2x revisi ringan']] as $specIndex => $spec) {
                $catalog->specifications()->create(['label' => $spec[0], 'value' => $spec[1], 'sort_order' => $specIndex + 1]);
            }

            foreach ([['Nama utama', 'short_text', true], ['Tanggal acara', 'date', true], ['Catatan tambahan', 'long_text', false]] as $fieldIndex => $field) {
                $catalog->inputFields()->create([
                    'label' => $field[0],
                    'type' => $field[1],
                    'placeholder' => 'Isi '.$field[0],
                    'help_text' => 'Data ini akan dipakai untuk personalisasi desain.',
                    'is_required' => $field[2],
                    'sort_order' => $fieldIndex + 1,
                ]);
            }
        }

        $template = OrderProgressTemplate::updateOrCreate(
            ['name' => 'Default Undangan Digital'],
            ['is_default' => true],
        );

        $template->steps()->delete();

        foreach (['Order diterima', 'Data diverifikasi', 'Desain diproses', 'Preview dikirim', 'Finalisasi'] as $index => $label) {
            $template->steps()->create(['label' => $label, 'sort_order' => $index + 1]);
        }

        foreach ($customers as $index => $customer) {
            if ($customer->orders()->exists()) {
                continue;
            }

            $catalog = Catalog::where('is_active', true)->with('category')->inRandomOrder()->first();
            $total = $catalog->base_price;
            $order = Order::factory()->create([
                'user_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'subtotal' => $total,
                'discount_total' => 0,
                'total' => $total,
                'deposit_amount' => (int) ceil($total * 0.5),
                'paid_amount' => $index === 0 ? $total : 0,
                'payment_status' => $index === 0 ? Order::PAYMENT_PAID : Order::PAYMENT_PENDING,
                'work_status' => [Order::WORK_COMPLETED, Order::WORK_IN_PROGRESS, Order::WORK_PREVIEW][$index],
            ]);

            $order->items()->create([
                'catalog_id' => $catalog->id,
                'catalog_name' => $catalog->name,
                'catalog_slug' => $catalog->slug,
                'category_name' => $catalog->category->name,
                'unit_price' => $catalog->base_price,
                'quantity' => 1,
                'line_total' => $total,
                'snapshot' => ['estimated_days' => $catalog->estimated_days],
            ]);

            Payment::factory()->create([
                'order_id' => $order->id,
                'amount' => $index === 0 ? $total : $order->deposit_amount,
                'status' => $index === 0 ? Payment::STATUS_PAID : Payment::STATUS_PENDING,
                'paid_at' => $index === 0 ? now() : null,
                'type' => $index === 0 ? Payment::TYPE_FULL : Payment::TYPE_DEPOSIT,
            ]);

            foreach (['Order diterima', 'Data diverifikasi', 'Desain diproses', 'Preview dikirim', 'Finalisasi'] as $stepIndex => $label) {
                $order->progressSteps()->create([
                    'label' => $label,
                    'sort_order' => $stepIndex + 1,
                    'is_completed' => $stepIndex <= $index + 1,
                    'completed_at' => $stepIndex <= $index + 1 ? now()->subDays(5 - $stepIndex) : null,
                ]);
            }

            $order->activities()->create(['user_id' => $admin->id, 'type' => 'demo', 'message' => 'Data demo pesanan dibuat.']);

            if ($index === 0) {
                Review::create([
                    'user_id' => $customer->id,
                    'catalog_id' => $catalog->id,
                    'order_id' => $order->id,
                    'rating' => 5,
                    'comment' => 'Prosesnya rapi, preview cepat, dan hasil akhirnya mudah dibagikan.',
                ]);
            }
        }
    }
}
