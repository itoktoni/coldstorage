<?php

namespace Tests\Browser;

use App\Models\Customer;
use App\Models\Gudang;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WmsWorkflowBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Product $productA;
    protected Product $productB;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-dusk-'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'verified_at' => now(),
        ]);

        $uniqid = uniqid();

        $gudang = Gudang::create([
            'gudang_code' => 'GD-'.$uniqid,
            'gudang_nama' => 'Gudang '.$uniqid,
        ]);

        $lokasiRack = Lokasi::create([
            'lokasi_code' => 'RACK-'.$uniqid,
            'lokasi_nama' => 'Rack '.$uniqid,
            'lokasi_code_gudang' => $gudang->gudang_code,
            'lokasi_category' => 'meat',
            'lokasi_max_qty' => 1000,
        ]);

        Lokasi::create([
            'lokasi_code' => 'STG-'.$uniqid,
            'lokasi_nama' => 'Staging '.$uniqid,
            'lokasi_code_gudang' => $gudang->gudang_code,
            'lokasi_category' => 'staging',
            'lokasi_max_qty' => 500,
        ]);

        $this->productA = Product::create([
            'product_nama' => 'Daging Sapi',
            'product_harga' => 50000,
            'product_category' => 'meat',
        ]);

        $this->productB = Product::create([
            'product_nama' => 'Daging Ayam',
            'product_harga' => 30000,
            'product_category' => 'meat',
        ]);

        $this->customer = Customer::create([
            'customer_nama' => 'Customer Dusk',
            'customer_telepon' => '081234567890',
        ]);

        // Create initial stock
        Stock::create([
            'stock_id_product' => $this->productA->product_id,
            'stock_code_lokasi' => $lokasiRack->lokasi_code,
            'stock_qty' => 10,
            'stock_type' => Stock::TYPE_IN,
            'stock_expired_date' => now()->addDays(30),
        ]);

        Stock::create([
            'stock_id_product' => $this->productB->product_id,
            'stock_code_lokasi' => $lokasiRack->lokasi_code,
            'stock_qty' => 8,
            'stock_type' => Stock::TYPE_IN,
            'stock_expired_date' => now()->addDays(30),
        ]);
    }

    protected function login(Browser $browser): Browser
    {
        return $browser->visit('/login')
            ->type('email', $this->user->email)
            ->type('password', 'password')
            ->press('Log in')
            ->waitForLocation('/dashboard');
    }

    /**
     * Test Scenario 1: Create SO with 1 product via browser
     */
    public function test_scenario_1_create_so_single_product(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser);

            // Navigate to SO create page
            $browser->visit('/wms/so/create')
                ->waitFor('form')
                ->select('so_id_customer', $this->customer->customer_id)
                ->type('so_tanggal', now()->toDateString())
                // Add product A with qty 5
                ->type('details[0][so_detail_id_product]', $this->productA->product_id)
                ->type('details[0][so_detail_qty]', '5')
                ->press('Submit')
                ->waitFor('.alert-success, .toast-success')
                ->assertSee('SO-');
        });
    }

    /**
     * Test Scenario 2: Create SO with 2 products via browser
     */
    public function test_scenario_2_create_so_two_products(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser);

            $browser->visit('/wms/so/create')
                ->waitFor('form')
                ->select('so_id_customer', $this->customer->customer_id)
                ->type('so_tanggal', now()->toDateString())
                // Product A: 3kg
                ->type('details[0][so_detail_id_product]', $this->productA->product_id)
                ->type('details[0][so_detail_qty]', '3')
                // Product B: 5kg
                ->type('details[1][so_detail_id_product]', $this->productB->product_id)
                ->type('details[1][so_detail_qty]', '5')
                ->press('Submit')
                ->waitFor('.alert-success, .toast-success')
                ->assertSee('SO-');
        });
    }

    /**
     * Test Scenario 3: Prepare SO via browser
     */
    public function test_scenario_3_prepare_so(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser);

            // First create SO via API (setup)
            $so = \App\Models\So::create([
                'so_tanggal' => now()->toDateString(),
                'so_id_customer' => $this->customer->customer_id,
            ]);

            \App\Models\SoDetail::create([
                'so_detail_id_so' => $so->so_id,
                'so_detail_id_product' => $this->productA->product_id,
                'so_detail_qty' => 5,
                'so_detail_harga' => 50000,
            ]);

            // Navigate to prepare page
            $browser->visit('/wms/so-prepare')
                ->waitFor('table')
                ->assertSee($so->so_code)
                ->click('@prepare-' . $so->so_id)
                ->waitFor('#prepare-form');
        });
    }

    /**
     * Test Scenario 4: View stock after operations
     */
    public function test_scenario_4_view_stock(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser);

            // Navigate to stock page
            $browser->visit('/wms/stock')
                ->waitFor('table')
                ->assertSee('Daging Sapi')
                ->assertSee('Daging Ayam');
        });
    }

    /**
     * Test Scenario 5: Keluar realisasi scan page
     */
    public function test_scenario_5_keluar_scan_page(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser);

            // Create keluar detail for testing
            $keluar = \App\Models\Keluar::create([
                'out_tanggal' => now()->toDateString(),
                'out_status' => 'Pending',
            ]);

            $detail = \App\Models\KeluarDetail::create([
                'out_detail_code_keluar' => $keluar->out_code,
                'out_detail_id_product' => $this->productA->product_id,
                'out_detail_code' => $keluar->out_code.'-001',
                'out_detail_qty' => 5,
            ]);

            // Navigate to scan page
            $browser->visit('/wms/keluar-realisasi-scan/' . $detail->out_detail_id)
                ->waitFor('@barcode-input')
                ->assertSee('Scan Barcode');
        });
    }

    /**
     * Test Scenario 6: SO Prepare list page shows status
     */
    public function test_scenario_6_so_prepare_list_shows_status(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser);

            // Create SO with Prepare status
            $so = \App\Models\So::create([
                'so_tanggal' => now()->toDateString(),
                'so_id_customer' => $this->customer->customer_id,
                'so_status' => \App\Wms\SoStatusEnum::PREPARE,
            ]);

            // Navigate to prepare list
            $browser->visit('/wms/so-prepare')
                ->waitFor('table')
                ->assertSee($so->so_code)
                ->assertSee('Prepare');
        });
    }

    /**
     * Test Scenario 7: Full workflow via browser - create SO, prepare, scan
     */
    public function test_scenario_7_full_workflow_browser(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser);

            // Step 1: Create SO
            $browser->visit('/wms/so/create')
                ->waitFor('form')
                ->select('so_id_customer', $this->customer->customer_id)
                ->type('so_tanggal', now()->toDateString())
                ->type('details[0][so_detail_id_product]', $this->productA->product_id)
                ->type('details[0][so_detail_qty]', '5')
                ->press('Submit')
                ->waitFor('.alert-success, .toast-success');

            // Step 2: Navigate to SO table
            $browser->visit('/wms/so/table')
                ->waitFor('table')
                ->assertSee('SO-');

            // Step 3: Navigate to prepare list
            $browser->visit('/wms/so-prepare')
                ->waitFor('table');
        });
    }
}
