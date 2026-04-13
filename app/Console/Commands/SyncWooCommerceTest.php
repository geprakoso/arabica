<?php

namespace App\Console\Commands;

use App\Services\WooCommerce\WooCommerceService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;

class SyncWooCommerceTest extends Command
{
    protected $signature = 'sync:woocommerce:test';

    protected $description = 'Test WooCommerce API connection';

    public function handle(): int
    {
        $this->info('Testing WooCommerce API connection...');

        $storeUrl = config('woocommerce.store_url');
        $consumerKey = config('woocommerce.consumer_key');
        $consumerSecret = config('woocommerce.consumer_secret');

        if (! $storeUrl || ! $consumerKey || ! $consumerSecret) {
            $this->error('WooCommerce configuration is incomplete. Please check your .env file.');
            $this->table(
                ['Setting', 'Status'],
                [
                    ['WOOCOMMERCE_STORE_URL', $storeUrl ? '✓ Set' : '✗ Missing'],
                    ['WOOCOMMERCE_CONSUMER_KEY', $consumerKey ? '✓ Set' : '✗ Missing'],
                    ['WOOCOMMERCE_CONSUMER_SECRET', $consumerSecret ? '✓ Set' : '✗ Missing'],
                ]
            );

            return self::FAILURE;
        }

        $this->info("Store URL: {$storeUrl}");
        $this->info('Consumer Key: '.substr($consumerKey, 0, 10).'...');
        $this->info('Consumer Secret: '.substr($consumerSecret, 0, 10).'...');
        $this->line('');

        try {
            $wooService = new WooCommerceService;
            $this->info('Connecting to WooCommerce...');

            if ($wooService->connect()) {
                $this->info('✓ Connection successful!');
                $this->line('');
                $this->info('Testing getProductBySku with a sample SKU...');

                $testSku = 'TEST-SKU-001';
                $result = $wooService->getProductBySku($testSku);

                if ($result === null) {
                    $this->warn("✓ API works! No product found with SKU '{$testSku}' (expected for new setup)");
                } else {
                    $this->info("✓ API works! Found product: {$result['name']} (ID: {$result['id']})");
                }

                return self::SUCCESS;
            } else {
                $this->error('✗ Connection failed!');

                return self::FAILURE;
            }
        } catch (RequestException $e) {
            $this->error('✗ Connection failed with error:');
            $this->line($e->getMessage());

            if ($e->response) {
                $this->line('');
                $this->error('Response status: '.$e->response->status());
                $this->error('Response body: '.$e->response->body());
            }

            return self::FAILURE;
        }
    }
}
