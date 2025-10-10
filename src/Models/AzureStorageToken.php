<?php

namespace Hwkdo\AzureStorageLaravel\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureStorageToken extends Model
{
    protected $table = 'azure_storage_laravel_table';

    protected $fillable = [
        'connection',
        'token',
        'expiration',
    ];

    protected $casts = [
        'expiration' => 'datetime',
    ];

    /**
     * Get a valid token for the specified connection
     * Retrieves from database if valid, otherwise refreshes
     */
    public static function getToken(string $connection): string
    {
        $tokenRecord = self::where('connection', $connection)
            ->where('expiration', '>', Carbon::now())
            ->first();

        if ($tokenRecord) {
            Log::debug('AzureStorageToken - Using cached token', [
                'connection' => $connection,
                'expires' => $tokenRecord->expiration,
            ]);

            return $tokenRecord->token;
        }

        return self::refreshToken($connection);
    }

    /**
     * Refresh the OAuth token for the specified connection
     *
     * @throws Exception
     */
    public static function refreshToken(string $connection): string
    {
        $config = config("azure-storage-laravel.connections.{$connection}");

        if (! $config) {
            throw new Exception("Azure Storage connection '{$connection}' not configured");
        }

        $required = ['tenant_id', 'client_id', 'client_secret'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new Exception("Missing required config '{$key}' for connection '{$connection}'");
            }
        }

        $url = "https://login.microsoftonline.com/{$config['tenant_id']}/oauth2/v2.0/token";

        try {
            $response = Http::asForm()->post($url, [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'scope' => 'https://storage.azure.com/.default',
                'grant_type' => 'client_credentials',
            ]);

            $response->throw();

            $data = $response->json();

            // Delete old token record for this connection
            self::where('connection', $connection)->delete();

            // Create new token record
            $tokenRecord = self::create([
                'connection' => $connection,
                'token' => $data['access_token'],
                'expiration' => Carbon::now()->addSeconds($data['expires_in']),
            ]);

            Log::info('AzureStorageToken - Token refreshed', [
                'connection' => $connection,
                'expires' => $tokenRecord->expiration,
            ]);

            return $tokenRecord->token;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AzureStorageToken - Failed to refresh token', [
                'connection' => $connection,
                'error' => $e->getMessage(),
                'response' => $e->response ? $e->response->body() : null,
            ]);
            throw new Exception("Failed to refresh Azure Storage token: {$e->getMessage()}", 0, $e);
        }
    }
}
