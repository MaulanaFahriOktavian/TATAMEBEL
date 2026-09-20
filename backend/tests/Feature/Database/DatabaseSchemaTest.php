<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;
    /**
     * The 16 core business tables required by TATAMEBEL architecture.
     *
     * @var list<string>
     */
    protected array $businessTables = [
        'workshops',
        'users',
        'customers',
        'orders',
        'order_items',
        'specifications',
        'change_requests',
        'production_stages',
        'production_updates',
        'media',
        'qc_inspections',
        'qc_items',
        'qc_defects',
        'payments',
        'shipping',
        'activity_logs',
    ];

    /**
     * Test that all 16 business tables exist in MySQL database.
     */
    public function test_all_16_business_tables_exist(): void
    {
        foreach ($this->businessTables as $tableName) {
            $this->assertTrue(
                Schema::hasTable($tableName),
                "Expected table '{$tableName}' to exist in the database."
            );
        }
    }

    /**
     * Test that all business tables (except workshops itself) enforce tenant foundation with workshop_id.
     */
    public function test_tenant_foundation_has_workshop_id_on_all_business_tables(): void
    {
        foreach ($this->businessTables as $tableName) {
            if ($tableName === 'workshops') {
                $this->assertTrue(Schema::hasColumn($tableName, 'id'));
                $this->assertTrue(Schema::hasColumn($tableName, 'slug'));
                continue;
            }

            $this->assertTrue(
                Schema::hasColumn($tableName, 'workshop_id'),
                "Expected table '{$tableName}' to have tenant column 'workshop_id'."
            );
        }
    }

    /**
     * Test essential columns on core models.
     */
    public function test_essential_columns_on_orders_and_specifications(): void
    {
        $this->assertTrue(Schema::hasColumns('orders', [
            'id', 'workshop_id', 'customer_id', 'order_number', 'title',
            'status', 'total_amount', 'public_token', 'confirmed_at',
            'completed_at', 'cancelled_at', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('specifications', [
            'id', 'workshop_id', 'order_item_id', 'version', 'width',
            'height', 'depth', 'dimension_unit', 'material', 'wood_grade',
            'finishing', 'color', 'fabric', 'design_reference', 'special_request',
            'production_note', 'status', 'locked_at', 'locked_by',
        ]));

        $this->assertTrue(Schema::hasColumns('shipping', [
            'id', 'workshop_id', 'order_id', 'courier', 'tracking_number',
            'shipping_address', 'shipped_at', 'estimated_arrival',
            'delivered_at', 'status', 'notes',
        ]));
    }
}
