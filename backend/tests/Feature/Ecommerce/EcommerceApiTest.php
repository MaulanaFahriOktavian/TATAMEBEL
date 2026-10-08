<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\EcommerceSeeder;
use Tests\TestCase;

class EcommerceApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceSeeder::class);
    }

    public function test_can_list_products()
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'category_name',
                    ],
                ],
            ]);
    }

    public function test_can_list_categories()
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
            ]);
    }

    public function test_can_add_to_cart_and_retrieve_cart()
    {
        $product = Product::first();
        $this->assertNotNull($product);

        $sessionToken = 'test-session-' . uniqid();

        // Add to cart
        $addResponse = $this->withHeaders([
            'X-Cart-Session' => $sessionToken,
        ])->postJson('/api/v1/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $addResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Get cart
        $getResponse = $this->withHeaders([
            'X-Cart-Session' => $sessionToken,
        ])->getJson('/api/v1/cart');

        $getResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.total_count', 2);
    }

    public function test_can_submit_custom_inquiry()
    {
        $response = $this->postJson('/api/v1/inquiries', [
            'name' => 'Budi Santoso',
            'email' => 'budi@studio.com',
            'project_type' => 'Residensial (Hunian Pribadi)',
            'timeline' => '1–3 Bulan',
            'description' => 'Kebutuhan meja makan jati 8 kursi dan kredensa rotan kustom.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'project_type',
                    'created_at',
                ],
            ]);
    }
}
