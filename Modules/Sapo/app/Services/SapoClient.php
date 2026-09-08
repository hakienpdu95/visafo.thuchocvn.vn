<?php

namespace Modules\Sapo\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SapoClient
{
    public function post(string $path, array $payload): Response
    {
        return $this->request()->post($this->url($path), $payload);
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->request()->get($this->url($path), $query);
    }

    public function getProducts(int $page = 1, int $limit = 50): Response
    {
        return $this->get('/admin/products.json', [
            'page'   => $page,
            'limit'  => $limit,
            'fields' => 'id,name,vendor,image,variants',
        ]);
    }

    private function request()
    {
        return Http::withHeaders([
            'X-Sapo-Access-Token' => config('sapo.access_token'),
        ])->acceptJson();
    }

    private function url(string $path): string
    {
        return rtrim((string) config('sapo.base_url'), '/') . '/' . ltrim($path, '/');
    }
}
