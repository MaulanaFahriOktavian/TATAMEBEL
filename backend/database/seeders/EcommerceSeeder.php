<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EcommerceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriesData = [
            [
                'name' => 'Ruang Keluarga',
                'slug' => 'ruang-keluarga',
                'description' => 'Produk Pilihan & Sofa Modular Kustom',
                'item_count' => 85,
                'sort_order' => 1,
            ],
            [
                'name' => 'Kamar Tidur',
                'slug' => 'kamar-tidur',
                'description' => 'Tempat Tidur Arsitektural, Meja Nakas & Lemari',
                'item_count' => 47,
                'sort_order' => 2,
            ],
            [
                'name' => 'Ruang Kerja Rumah',
                'slug' => 'ruang-kerja-rumah',
                'description' => 'Meja Ergonomis, Rak Terintegrasi Kabel & Kursi Kerja',
                'item_count' => 65,
                'sort_order' => 3,
            ],
            [
                'name' => 'Ruang Makan',
                'slug' => 'ruang-makan',
                'description' => 'Meja Kayu Solid, Meja Pedestal & Kursi Makan Skulptural',
                'item_count' => 38,
                'sort_order' => 4,
            ],
            [
                'name' => 'Dapur & Atelier',
                'slug' => 'dapur-atelier',
                'description' => 'Kursi Bar, Meja Pulau & Unit Penyimpanan Kayu Jati',
                'item_count' => 51,
                'sort_order' => 5,
            ],
            [
                'name' => 'Kursi',
                'slug' => 'kursi',
                'description' => 'Koleksi Kursi Santai, Kursi Makan & Kursi Lounge',
                'item_count' => 32,
                'sort_order' => 6,
            ],
            [
                'name' => 'Meja',
                'slug' => 'meja',
                'description' => 'Meja Makan Solid, Meja Kopi & Meja Samping',
                'item_count' => 28,
                'sort_order' => 7,
            ],
            [
                'name' => 'Penyimpanan',
                'slug' => 'penyimpanan',
                'description' => 'Rak Buku, Kredensa, Bufet & Lemari Modular',
                'item_count' => 24,
                'sort_order' => 8,
            ],
            [
                'name' => 'Sofa Santai',
                'slug' => 'sofa-santai',
                'description' => 'Sofa Modular, Daybed & Lounge Chair',
                'item_count' => 19,
                'sort_order' => 9,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $catData) {
            $categories[$catData['slug']] = Category::updateOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );
        }

        $productsData = [
            [
                'name' => 'Rak Buku Fjord Arm',
                'slug' => 'rak-buku-fjord-arm',
                'sku' => 'TTM-RK-01',
                'category_slug' => 'penyimpanan',
                'category_name' => 'Penyimpanan Modular',
                'badge' => 'Diskon 22%',
                'subtitle' => 'Penyimpanan Modular',
                'description' => 'Rak buku arsitektural berbahan kayu jati solid kering oven dengan modul ambalan terukur dan proporsi bersih Skandinavia.',
                'price' => 4850000,
                'original_price' => 6200000,
                'discount_percent' => 22,
                'wood_type' => 'Jati Solid Oven',
                'stock' => 14,
                'colors' => ['#44403c', '#1c1917', '#a8a29e'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA-Zu6bMFVJolDQeO2bHLBCTPGcwGDSK_maRF-61sRDT2L3w_kg1rpOQTfIlOLiVJyNlmPvR4TCe3oLlm7-JYNQB4O47g1o7qb4Ocxnw9OoSv9fsexI9clJ4Q3PikhqHCWfSkNBE1VbT3kPx-vOLDTfWdz06kJZ-VJ54Dk7OsKNuT6eRpb0gBgEjQ1ex-AbkOESE24nMLfxzOemYrlibggzxTwVH7a8ubL9aC8RS0a-',
                'is_featured' => true,
            ],
            [
                'name' => 'Kursi Santai Sumba Low',
                'slug' => 'kursi-santai-sumba-low',
                'sku' => 'TTM-KS-02',
                'category_slug' => 'kursi',
                'category_name' => 'Kursi Santai',
                'badge' => 'Karya Tangan',
                'subtitle' => 'Kursi Santai',
                'description' => 'Kursi santai berketinggian rendah dengan sambungan purus kokoh tanpa paku terbuka dan sandaran ergonomis bertekstur nyaman.',
                'price' => 3450000,
                'original_price' => 4100000,
                'discount_percent' => 16,
                'wood_type' => 'Kayu Jati Grade A',
                'stock' => 8,
                'colors' => ['#44403c', '#d6d3d1'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuC0KPPQSc6RNlOEpLBctKbWI2ut8ReYlJ08T1YHsVml-uvWe1ES9ZR8jtiy3F8W7OipIO6GO16H-4n6msxFX5gG8rawI8vag5SPLPyyINn9eUQVOXWDBtZccFoN0u3-mET4v5cL639fUfBzrPc5aZHJdK6u_bjOoYYcJXEleB_2CmkqvPxezdNIemfR8UzMXAuRPw5g7oxjTDlZgDKb9ch6Pu_H-H7Wub_z1VwvJYX5',
                'is_featured' => true,
            ],
            [
                'name' => 'Meja Makan Candi Monolith',
                'slug' => 'meja-makan-candi-monolith',
                'sku' => 'TTM-MM-03',
                'category_slug' => 'meja',
                'category_name' => 'Meja Makan',
                'badge' => 'Karya Unggulan',
                'subtitle' => 'Meja Makan',
                'description' => 'Meja makan monolitik kapasitas 8 dudukan dengan balok kaki tumpu skulptural dan finishing matte alami bertekstur serat kuat.',
                'price' => 12800000,
                'original_price' => null,
                'discount_percent' => null,
                'wood_type' => 'Jati Monolitik 8 Kursi',
                'stock' => 5,
                'colors' => ['#292524', '#78716c'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDGGZzQR6LIXaNfQHFaZPRfPw7rIHIj03_j1ftuE4sPsE7SHPaXOJ5-4irTdYgaeUL4SXrGLEhqR3GsGmbeYVcU_ImGaYnPrmF6OLVMWzUFqz8zeiPm9B7BF13YtngSsZ95j_KxKAwmwOtk3Uj5CZXvBKzzw7vj4nFnIKY6lmSbfnTtpl2QPZCf0zioIpi4HH-m2c5Ep4h5iKp7bEO-Cr5T2wcA67DVD2Qfzy4j9SUW',
                'is_featured' => true,
            ],
            [
                'name' => 'Kredensa Anyam Cane',
                'slug' => 'kredensa-anyam-cane',
                'sku' => 'TTM-KD-04',
                'category_slug' => 'penyimpanan',
                'category_name' => 'Kredensa & Bufet',
                'badge' => 'Diskon 15%',
                'subtitle' => 'Kredensa & Bufet',
                'description' => 'Bufet kabinet rendah perpaduan kayu jati solid dan anyaman rotan cane alami untuk sirkulasi udara interior yang tenang dan estetis.',
                'price' => 8900000,
                'original_price' => 10500000,
                'discount_percent' => 15,
                'wood_type' => 'Jati & Rotan Asli',
                'stock' => 7,
                'colors' => ['#57534e', '#1c1917'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAROIpGicWNYHZWzG4CIaU2Bv3uhryF-REEcdk-9wwrJfbt8JslQDdDLBd7r9gf9K8-SEB7Hyg0BZUJDpKGUaed3ihz-sI9zfCT8FmoAamUxzQoeIKJfwJZovYs7nEo-7Ot-Axur5F7XO5dzusuUCb-9b-G4qSJc5gO99hOU5gA9dtzuX6wK_1FLBoIb9GSBzAicZFU4-Wytm3XzbABWpS0Vbq56zmfM2pImrpnt7Kc',
                'is_featured' => true,
            ],
            [
                'name' => 'Verve Modular Curved Sofa',
                'slug' => 'verve-modular-curved-sofa',
                'sku' => 'TTM-SF-05',
                'category_slug' => 'sofa-santai',
                'category_name' => 'Sofa Santai',
                'badge' => 'Edisi Khusus',
                'subtitle' => 'Sofa Modular Kurva',
                'description' => 'Sofa kurva modular kontemporer berbahan beludru terracotta dengan rangka kayu solid kokoh dan busa berdensitas tinggi.',
                'price' => 15600000,
                'original_price' => 18200000,
                'discount_percent' => 14,
                'wood_type' => 'Kayu Ash Solid & Beludru Terracotta',
                'stock' => 4,
                'colors' => ['#9a3412', '#292524'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBlMY7PfDjp1eP1cOmziqxBwGmiFTBJET-NOf4uC58ErbusyTKXLzU5qbDssFxzgZ1ib3AoM_XE8vB0P9VR9rZyUuz4v6RYkO6W1q5ZlLHRdOK0SmBR9fCWqyZfFXlZe32ZZLgKV72DwCD3x9oaGjWULqou1xXStz8_0ocbz-2wsSZFd1RLEBv-KJ4sVEPYFNUrY78fk89_7mQDH5tAn6yyDSvKMqumjnuPp0LJl7tJ',
                'is_featured' => true,
            ],
            [
                'name' => 'Kursi Santai Kura Teak',
                'slug' => 'kursi-santai-kura-teak',
                'sku' => 'TTM-KS-06',
                'category_slug' => 'kursi',
                'category_name' => 'Kursi Santai',
                'badge' => 'Pesanan Khusus',
                'subtitle' => 'Kursi Santai Jati',
                'description' => 'Kursi santai kayu jati solid dengan sandaran kanvas natural dan proporsi rileks untuk area baca atau sudut ruang keluarga.',
                'price' => 3850000,
                'original_price' => 4500000,
                'discount_percent' => 14,
                'wood_type' => 'Kayu Jati Solid & Kanvas Natural',
                'stock' => 12,
                'colors' => ['#44403c', '#e7e5e4'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBXHQhR7jZEM7BbTBbOXdpMlJ1uRAnzgWtPRZiOAXbfia370dOvQ5Xls6oIDZJFgVCIxVLBPEKH2QgaOPwGNW4IvTra_kV6vo3bSmKANG45r5si--UEHHPcVRp5AowqXIFyY2HnJlHsgVdxUoyjg68upl9z-zseeosJbDamzSkX5pPycYXNAa68s8UZrypX3dtNX9qT93ezPS6a3DFA5ElzRo-zWok6QDtp3nqIPeU_',
                'is_featured' => true,
            ],
            [
                'name' => 'Koleksi Fjord Whisper',
                'slug' => 'koleksi-fjord-whisper',
                'sku' => 'TTM-KL-07',
                'category_slug' => 'ruang-keluarga',
                'category_name' => 'Edisi Terpilih',
                'badge' => 'Nuansa Kayu Alami',
                'subtitle' => 'Nuansa Kayu Alami',
                'description' => 'Kombinasi nada minimalis Skandinavia dengan kayu ash pucat serta linen organik bernapas alami.',
                'price' => 3850000,
                'original_price' => null,
                'discount_percent' => null,
                'wood_type' => 'Kayu Ash Pucat & Linen Organik',
                'stock' => 6,
                'colors' => ['#e7e5e4', '#57534e'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuC0KPPQSc6RNlOEpLBctKbWI2ut8ReYlJ08T1YHsVml-uvWe1ES9ZR8jtiy3F8W7OipIO6GO16H-4n6msxFX5gG8rawI8vag5SPLPyyINn9eUQVOXWDBtZccFoN0u3-mET4v5cL639fUfBzrPc5aZHJdK6u_bjOoYYcJXEleB_2CmkqvPxezdNIemfR8UzMXAuRPw5g7oxjTDlZgDKb9ch6Pu_H-H7Wub_z1VwvJYX5',
                'is_featured' => true,
            ],
            [
                'name' => 'Koleksi Urban Elegance',
                'slug' => 'koleksi-urban-elegance',
                'sku' => 'TTM-KL-08',
                'category_slug' => 'ruang-keluarga',
                'category_name' => 'Edisi Terpilih',
                'badge' => 'Terlaris',
                'subtitle' => 'Harmoni Arsitektural',
                'description' => 'Siluet skulptural dan pelapis terstruktur yang dirancang untuk memperkaya ruang metropolitan modern.',
                'price' => 7200000,
                'original_price' => null,
                'discount_percent' => null,
                'wood_type' => 'Kayu Jati Hitam Elegan',
                'stock' => 10,
                'colors' => ['#1c1917', '#44403c'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA-Zu6bMFVJolDQeO2bHLBCTPGcwGDSK_maRF-61sRDT2L3w_kg1rpOQTfIlOLiVJyNlmPvR4TCe3oLlm7-JYNQB4O47g1o7qb4Ocxnw9OoSv9fsexI9clJ4Q3PikhqHCWfSkNBE1VbT3kPx-vOLDTfWdz06kJZ-VJ54Dk7OsKNuT6eRpb0gBgEjQ1ex-AbkOESE24nMLfxzOemYrlibggzxTwVH7a8ubL9aC8RS0a-',
                'is_featured' => true,
            ],
            [
                'name' => 'Koleksi Cozy Haven',
                'slug' => 'koleksi-cozy-haven',
                'sku' => 'TTM-KL-09',
                'category_slug' => 'ruang-keluarga',
                'category_name' => 'Edisi Terpilih',
                'badge' => 'Tekstur Hangat Alami',
                'subtitle' => 'Tekstur Hangat Alami',
                'description' => 'Harmoni menenangkan dengan kain bouclé lembut, anyaman rotan, serta kayu keras alami pilihan.',
                'price' => 5600000,
                'original_price' => null,
                'discount_percent' => null,
                'wood_type' => 'Kayu Jati & Rotan Pilihan',
                'stock' => 8,
                'colors' => ['#d6d3d1', '#78716c'],
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAROIpGicWNYHZWzG4CIaU2Bv3uhryF-REEcdk-9wwrJfbt8JslQDdDLBd7r9gf9K8-SEB7Hyg0BZUJDpKGUaed3ihz-sI9zfCT8FmoAamUxzQoeIKJfwJZovYs7nEo-7Ot-Axur5F7XO5dzusuUCb-9b-G4qSJc5gO99hOU5gA9dtzuX6wK_1FLBoIb9GSBzAicZFU4-Wytm3XzbABWpS0Vbq56zmfM2pImrpnt7Kc',
                'is_featured' => true,
            ],
        ];

        foreach ($productsData as $prod) {
            $catSlug = $prod['category_slug'] ?? null;
            $catId = $catSlug && isset($categories[$catSlug]) ? $categories[$catSlug]->id : null;
            unset($prod['category_slug']);
            $prod['category_id'] = $catId;

            Product::updateOrCreate(
                ['slug' => $prod['slug']],
                $prod
            );
        }
    }
}
