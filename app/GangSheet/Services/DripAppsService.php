<?php

namespace App\GangSheet\Services;

use Illuminate\Support\Facades\Http;

class DripAppsService
{
    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        $config = config('services.dripapps');

        return Http::withHeaders(['Content-Type' => 'application/json'])
            ->withBasicAuth($config['username'], $config['password']);
    }

    private function getUrl(string $path): string
    {
        $config = config('services.dripapps');

        return trim($config['endpoint'] . $path);
    }

    public function autoNest(array $data): array
    {
        $response = $this->request()->post($this->getUrl('/api/v3/bin-pack'), $data);

        if ($response->successful()) {
            return [
                'success' => true,
                'packs' => $response->json()
            ];
        }

        return [
            'success' => false,
            'error' => 'Something went to wrong, please try again later'
        ];
    }

    public function contour(array $data): array
    {
        $response = $this->request()->post($this->getUrl('/api/contour'), $data);

        $data = $response->json();

        if ($response->successful()) {
            return [
                'success' => true,
                'path' => $data['path'],
            ];
        }

        return [
            'success' => false,
            'error' => $data['error']
        ];
    }

    public function removeBg(array $data): array
    {
        $response = $this->request()->post($this->getUrl('/api/remove-bg'), $data);

        if ($response->successful()) {
            return [
                'success' => true,
                'image' => $response->body()
            ];
        } else {
            $error = $response->json();
            return [
                'success' => false,
                'error' => $error['error'] ?? 'Failed to process image'
            ];
        }
    }
}
