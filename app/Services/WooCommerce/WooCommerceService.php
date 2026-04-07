<?php

namespace App\Services\WooCommerce;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class WooCommerceService
{
    protected string $storeUrl;

    protected string $consumerKey;

    protected string $consumerSecret;

    protected string $baseUrl;

    protected int $timeout;

    protected bool $verifySsl;

    public function __construct()
    {
        $this->storeUrl = config('woocommerce.store_url');
        $this->consumerKey = config('woocommerce.consumer_key');
        $this->consumerSecret = config('woocommerce.consumer_secret');
        $this->baseUrl = rtrim($this->storeUrl, '/').'/wp-json/wc/v3';
        $this->timeout = config('woocommerce.timeout', 30);
        $this->verifySsl = config('woocommerce.verify_ssl', true);
    }

    protected function getAuthParams(): array
    {
        return [
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ];
    }

    protected function httpGet(string $endpoint, array $params = []): array
    {
        $response = Http::timeout($this->timeout)
            ->withQueryParameters(array_merge($this->getAuthParams(), $params))
            ->withOptions(['verify' => $this->verifySsl])
            ->get($this->baseUrl.'/'.ltrim($endpoint, '/'));

        return $this->handleResponse($response);
    }

    protected function httpPost(string $endpoint, array $data = []): array
    {
        $response = Http::timeout($this->timeout)
            ->withQueryParameters($this->getAuthParams())
            ->withOptions(['verify' => $this->verifySsl])
            ->post($this->baseUrl.'/'.ltrim($endpoint, '/'), $data);

        return $this->handleResponse($response);
    }

    protected function httpPut(string $endpoint, array $data = []): array
    {
        $response = Http::timeout($this->timeout)
            ->withQueryParameters($this->getAuthParams())
            ->withOptions(['verify' => $this->verifySsl])
            ->put($this->baseUrl.'/'.ltrim($endpoint, '/'), $data);

        return $this->handleResponse($response);
    }

    protected function handleResponse($response): array
    {
        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json();
    }

    public function connect(): bool
    {
        try {
            $this->get('system_status', ['fields' => 'environment']);

            return true;
        } catch (RequestException $e) {
            return false;
        }
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->httpGet($endpoint, $params);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->httpPost($endpoint, $data);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->httpPut($endpoint, $data);
    }

    public function getProductBySku(string $sku): ?array
    {
        $products = $this->httpGet('products', ['sku' => $sku]);

        if (empty($products)) {
            return null;
        }

        return $products[0] ?? null;
    }

    public function createProduct(array $data): array
    {
        return $this->httpPost('products', $data);
    }

    public function updateProduct(int $productId, array $data): array
    {
        return $this->httpPut('products/'.$productId, $data);
    }

    public function updateStock(int $productId, int $quantity, ?string $status = null): array
    {
        $data = [
            'stock_quantity' => $quantity,
        ];

        if ($status === 'outofstock' || $quantity === 0) {
            $data['stock_status'] = 'outofstock';
        } elseif ($quantity > 0) {
            $data['stock_status'] = 'instock';
        }

        return $this->updateProduct($productId, $data);
    }

    public function updateProductStockBySku(string $sku, int $quantity): ?array
    {
        $product = $this->getProductBySku($sku);

        if (! $product) {
            return null;
        }

        return $this->updateStock($product['id'], $quantity);
    }
}
