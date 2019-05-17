<?php

namespace App\GangSheet\Services;

use App\GangSheet\Abstracts\OAuthService;
use App\GangSheet\Exceptions\OAuthServiceException;
use App\Models\InAppAuthToken;
use Illuminate\Support\Facades\Http;

class CanvaService extends OAuthService
{
    public string $scope;

    protected function configure(): void
    {
        $this->clientId = config('services.canva.client_id');
        $this->clientSecret = config('services.canva.client_secret');
        $this->redirectUri = config('app.url') . '/canva-auth/success';
        $this->identifier = 'canva';
        $this->scope = config('services.canva.scope');
    }

    protected function createClient(): void
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $this->client = Http::withHeaders($headers)->asForm();
    }

    public function getAuthorizeUrl($params): string
    {
        $baseUrl = 'https://www.canva.com/api/oauth/authorize';

        // Generate code challenge and code verifier
        $codeVerifier = '';
        $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-._~';
        $validCharsLen = strlen($validChars);
        $i = 0;
        while ($i++ < 128) {
            $codeVerifier .= $validChars[random_int(0, $validCharsLen - 1)];
        }
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $params['cve'] = $codeVerifier;
        $state = $this->getState($params);

        $query = http_build_query([
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'scope' => $this->scope,
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ]);

        return $baseUrl . '?' . $query;
    }

    protected function refreshToken(InAppAuthToken $token): ?InAppAuthToken
    {
        $url = 'https://api.canva.com/rest/v1/oauth/token';

        $params = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'refresh_token' => $token->refresh_token,
        ];

        $response = $this->client->post($url, $params);

        if (($response->status() == 200)) {
            $data = $response->json();

            $token->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $data['expires_in'],
            ]);

            return $token;
        }

        return null;
    }

    protected function revokeToken(string $accessToken): void
    {
        $url = 'https://api.canva.com/rest/v1/oauth/revoke';

        $params = [
            'token' => $accessToken
        ];

        $this->client->post($url, $params);
    }

    /**
     * @throws OAuthServiceException
     */
    public function getUserProfile(string $accessToken): array
    {
        $url = 'https://api.canva.com/rest/v1/users/me/profile';

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $response = Http::withHeaders($headers)->get($url);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'email' => $data['email'] ?? null,
                'name' => $data['profile']['display_name']
            ];
        } else {
            throw new OAuthServiceException('Error getting user profile from Canva');
        }
    }

    public function authorizeStoreFromCode(string $code, array $options): array
    {
        // TODO: Implement authorizeStoreFromCode() method.

        return ['success' => true];
    }

    public function authorizeCustomerFromCode(string $code, array $options): array
    {
        try {
            $url = 'https://api.canva.com/rest/v1/oauth/token';

            $params = [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'code_verifier' => $options['cve'],
            ];

            $postUrl = $url . '?' . http_build_query($params);

            $response = $this->client->post($postUrl, $params);

            if ($response->successful()) {
                $this->createOrUpdateCustomerToken($response->json(), $options);

                return [
                    'success' => true,
                ];
            } else {
                throw new OAuthServiceException('Error authorizing customer from Canva');
            }
        } catch (\Exception $exception) {
            report($exception);

            return [
                'success' => false,
                'response' => $exception->getMessage()
            ];
        }
    }

    public function getDesignsByAccessToken($accessToken, $query = '')
    {
        try {
            $url = "https://api.canva.com/rest/v1/designs?query=$query";

            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
            ];

            $response = Http::withHeaders($headers)->get($url);

            if ($response->successful()) {
                return $response->json();
            } else {
                return null;
            }
        } catch (\Exception $exception) {
            report($exception);

            return null;
        }
    }

    public function createDesignExport($designId, $width, $height, $accessToken)
    {
        $url = 'https://api.canva.com/rest/v1/exports';

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        $params = [
            'design_id' => $designId,
            'format' => [
                'type' => 'png',
                'height' => $height,
                'width' => $width,
                'as_single_image' => true
            ]
        ];

        $response = Http::withHeaders($headers)->post($url, $params);

        if ($response && ($response->status() == 200)) {
            return $response->json();
        } else {
            return null;
        }
    }

    public function getDesignExport($exportId, $accessToken)
    {
        $url = 'https://api.canva.com/rest/v1/exports/' . $exportId;

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $response = Http::withHeaders($headers)->get($url);

        if ($response && ($response->status() == 200)) {
            return $response->json();
        } else {
            return null;
        }
    }
}
