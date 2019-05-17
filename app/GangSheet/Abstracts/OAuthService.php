<?php

namespace App\GangSheet\Abstracts;

use App\GangSheet\Exceptions\OAuthInitializeException;
use App\Models\InAppAuthSession;
use App\Models\InAppAuthToken;
use App\Repositories\CustomerRepository;
use Illuminate\Database\Eloquent\Builder;

abstract class OAuthService
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $redirectUri;

    protected string $identifier;

    protected object $client;

    /**
     * @throws OAuthInitializeException
     */
    public function __construct()
    {
        $this->configure();

        if (!$this->clientId || !$this->clientSecret || !$this->redirectUri || !$this->identifier) {
            throw new OAuthInitializeException('OAuth service is not initialized');
        }

        $this->createClient();
    }

    abstract protected function configure(): void;

    abstract protected function createClient(): void;

    abstract public function getAuthorizeUrl($params): string;

    abstract protected function refreshToken(InAppAuthToken $token): ?InAppAuthToken;

    abstract protected function revokeToken(string $accessToken): void;

    abstract public function getUserProfile(string $accessToken): array;

    abstract public function authorizeStoreFromCode(string $code, array $options): array;

    abstract public function authorizeCustomerFromCode(string $code, array $options): array;

    protected function getState($params): string
    {
        $params['timestamp'] = time();

        $signature = app_signature($params);

        $params['signature'] = $signature;

        return base64_encode(json_encode($params));
    }

    protected function createOrUpdateStoreToken($token_response, $options): void
    {
        $userData = $this->getUserProfile($token_response['access_token']);
        $type = $options['type'] ?? 'gang_sheets';

        InAppAuthToken::updateOrCreate(
            [
                'user_id' => $options['user_id'],
                'type' => $type,
                'identifier' => $this->identifier
            ],
            [
                'email' => $userData['email'],
                'name' => $userData['name'],
                'access_token' => $token_response['access_token'],
                'refresh_token' => $token_response['refresh_token'],
                'expires_in' => $token_response['expires_in'],
                'grant_type' => $token_response['scope'] ?? null
            ],
        );
    }

    protected function createOrUpdateCustomerToken($token_response, $options): void
    {
        $userData = $this->getUserProfile($token_response['access_token']);
        $type = $options['type'] ?? 'customer_designs';

        $customer = CustomerRepository::getCustomer([
            'customer_id' => $options['customer_id'] ?? null,
            'session_id' => $options['session_id'],
        ]);

        $authToken = InAppAuthToken::updateOrCreate(
            [
                'user_id' => $options['customer_id'] ?? null,
                'type' => $type,
                'identifier' => $this->identifier
            ],
            [
                'email' => $userData['email'] ?? $customer->email ?? null,
                'name' => $userData['name'] ?? $customer->name ?? null,
                'access_token' => $token_response['access_token'],
                'refresh_token' => $token_response['refresh_token'],
                'expires_in' => $token_response['expires_in'],
                'grant_type' => $token_response['scope'],
            ],
        );

        InAppAuthSession::updateOrCreate([
            'session_id' => $options['session_id'],
            'token_id' => $authToken->id,
        ]);
    }

    public function getConnectedStoreToken(int $user_id, array $options = []): ?InAppAuthToken
    {
        $type = $options['type'] ?? 'gang_sheets';

        $token = InAppAuthToken::where('identifier', $this->identifier)
            ->where('type', $type)
            ->where('user_id', $user_id)
            ->first();

        if ($token && $token->isExpired()) {
            return $this->refreshToken($token);
        }

        return $token;
    }

    public function revokeStoreAccess(int $user_id): void
    {
        $token = $this->getConnectedStoreToken($user_id);

        if ($token) {
            $this->revokeToken($token->access_token);
            $token->delete();
        }
    }

    public function getConnectedCustomerToken($options): ?InAppAuthToken
    {
        $session_id = $options['session_id'] ?? null;
        $customer_id = $options['customer_id'] ?? null;
        $type = $options['type'] ?? 'customer_designs';

        if ($customer_id) {
            $token = InAppAuthToken::where('user_id', $customer_id)
                ->where('type', $type)
                ->where('identifier', $this->identifier)
                ->latest('updated_at')
                ->first();
        }

        if (empty($token)) {
            $session = InAppAuthSession::where('session_id', $session_id)
                ->whereHas('token', function (Builder $query) use ($type) {
                    $query->where('type', $type)
                        ->where('identifier', $this->identifier);
                })
                ->latest('updated_at')
                ->first();

            if ($session) {
                $token = $session->token;
            }
        }

        if (!empty($token)) {
            if (empty($token->user_id) && $customer_id) {
                $token->update(['user_id' => $customer_id]);
            }

            if ($token->isExpired()) {
                return $this->refreshToken($token);
            }

            return $token;
        }

        return null;
    }

    public function revokeCustomerAccess($options): void
    {
        $token = $this->getConnectedCustomerToken($options);

        if ($token) {
            $this->revokeToken($token->access_token);
            $token->delete();
        }
    }
}
