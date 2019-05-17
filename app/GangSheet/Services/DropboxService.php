<?php

namespace App\GangSheet\Services;

use App\GangSheet\Abstracts\OAuthService;
use App\GangSheet\Exceptions\OAuthServiceException;
use App\Models\Design;
use App\Models\InAppAuthToken;
use Illuminate\Support\Facades\Http;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\InMemoryTokenProvider;

class DropboxService extends OAuthService
{
    protected function configure(): void
    {
        $this->clientId = config('services.dropbox.key');
        $this->clientSecret = config('services.dropbox.secret');
        $this->redirectUri = route('dropbox-auth.success');
        $this->identifier = 'dropbox';
    }

    protected function createClient(): void
    {
        $this->client = new Client([$this->clientId, $this->clientSecret]);
    }

    public function getAuthorizeUrl($params): string
    {
        $state = $this->getState($params);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'token_access_type' => 'offline',
            'state' => $state,
            'force_reapprove' => "true",
            'disable_signup' => "true",
            'locale' => 'en'
        ]);

        return 'https://www.dropbox.com/oauth2/authorize?' . $query;
    }

    protected function refreshToken(InAppAuthToken $token): ?InAppAuthToken
    {
        $query = http_build_query([
            'refresh_token' => $token->refresh_token,
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);

        $response = Http::post('https://api.dropbox.com/oauth2/token?' . $query);

        if ($response->successful()) {
            $data = $response->json();

            $token->update([
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
            ]);

            return $token;
        }

        return null;
    }

    function revokeToken(string $accessToken): void
    {
        $tokenProvider = new InMemoryTokenProvider($accessToken);
        $client = new Client($tokenProvider);

        $client->revokeToken();
    }

    /**
     * @throws OAuthServiceException
     */
    public function getUserProfile(string $accessToken): array
    {
        try {
            $tokenProvider = new InMemoryTokenProvider($accessToken);
            $client = new Client($tokenProvider);

            $data = $client->getAccountInfo();

            return [
                'email' => $data['email'],
                'name' => $data['name']['display_name'],
            ];
        } catch (\Exception $exception) {
            report($exception);

            throw new OAuthServiceException('Error getting user profile from Dropbox');
        }
    }

    public function authorizeStoreFromCode(string $code, $options): array
    {
        try {
            $query = http_build_query([
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ]);

            $response = Http::post('https://api.dropbox.com/oauth2/token?' . $query);

            if ($response->successful()) {
                $data = $response->json();

                $this->createOrUpdateStoreToken($data, $options);

                return [
                    'success' => true
                ];
            }

            throw new OAuthServiceException('Error authorizing Dropbox');

        } catch (\Exception $exception) {
            report($exception);

            return [
                'success' => false,
                'response' => $exception->getMessage()
            ];
        }
    }

    public function authorizeCustomerFromCode(string $code, array $options): array
    {
        // TODO: Implement authorizeCustomerFromCode() method.

        return ['success' => true];
    }


    public function getClient($user_id): Client
    {
        $token = $this->getConnectedStoreToken($user_id);

        if ($token) {
            $tokenProvider = new InMemoryTokenProvider($token->access_token);
            return new Client($tokenProvider);
        }

        throw new \Exception('Access token not found');
    }


    public function uploadGangSheetImage($user_id, $path, $imageContent): bool
    {
        try {
            $client = $this->getClient($user_id);

            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
            }

            retry(3, function () use ($client, $path, $imageContent) {
                $client->upload($path, $imageContent, 'overwrite');
            }, 10000);

            return true;
        } catch (\Exception $exception) {
            info("Error uploading image to Dropbox for user_id: $user_id");
            info($exception->getMessage());

            return false;
        }
    }

    public function getDropboxFiles($user_id, $path): array
    {
        try {
            $client = $this->getClient($user_id);

            $path = str_replace('\\', '/', $path);

            return $client->listFolder($path);

        } catch (\Exception $exception) {
            return [];
        }
    }

    public function fileExists($user_id, $path): bool
    {
        try {
            $client = $this->getClient($user_id, false);

            $path = str_replace('\\', '/', $path);

            $client->getMetadata($path);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function uploadDesignToDropbox(Design $design): void
    {
        $dropboxPath = $design->getDropboxPath();

        if (!$this->fileExists($design->user_id, $dropboxPath)) {
            if ($content = $design->getGangSheetFileContent()) {
                $this->uploadGangSheetImage($design->user_id, $dropboxPath, $content);

                echo "Uploaded to Google Drive: $dropboxPath\n";
            }
        } else {
            echo "File already exists: $dropboxPath\n";
        }
    }

    public function syncDropboxFiles($user_id): void
    {
        $designs = Design::whereNotNull('order_id')
            ->where('user_id', $user_id)
            ->where('status', Design::STATUS_COMPLETED)
            ->get();

        foreach ($designs as $design) {
            $this->uploadDesignToDropbox($design);
        }
    }
}
