<?php

namespace Hwkdo\AzureStorageLaravel;

use Exception;
use Hwkdo\AzureStorageLaravel\Models\AzureStorageToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AzureStorageLaravel
{
    protected string $connection;

    protected array $config;

    protected string $accountName;

    protected string $container;

    /**
     * Create a new AzureStorageLaravel instance
     */
    public function __construct(?string $connection = null)
    {
        $this->connection = $connection ?? config('azure-storage-laravel.default');
        $this->config = config("azure-storage-laravel.connections.{$this->connection}");

        if (! $this->config) {
            throw new Exception("Azure Storage connection '{$this->connection}' not configured");
        }

        $this->accountName = $this->config['account_name'] ?? '';
        $this->container = $this->config['container'] ?? '';

        if (empty($this->accountName) || empty($this->container)) {
            throw new Exception('Azure Storage account_name and container must be configured');
        }
    }

    /**
     * Get a valid OAuth token for the current connection
     */
    protected function getToken(): string
    {
        return AzureStorageToken::getToken($this->connection);
    }

    /**
     * List all blobs in the container
     *
     * @param  string|null  $prefix  Optional prefix to filter blobs
     * @return array Array of blob information
     *
     * @throws Exception
     */
    public function listBlobs(?string $prefix = null): array
    {
        $url = sprintf(
            'https://%s.blob.core.windows.net/%s?restype=container&comp=list',
            $this->accountName,
            $this->container
        );

        if ($prefix) {
            $url .= '&prefix='.rawurlencode($prefix);
        }

        $date = gmdate('D, d M Y H:i:s T');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getToken(),
                'x-ms-date' => $date,
                'x-ms-version' => '2021-08-06',
            ])->get($url);

            $response->throw();

            $xml = simplexml_load_string($response->body());
            $blobs = [];

            if (isset($xml->Blobs->Blob)) {
                foreach ($xml->Blobs->Blob as $blob) {
                    $blobs[] = [
                        'name' => (string) $blob->Name,
                        'url' => (string) $blob->Url,
                        'size' => (int) $blob->Properties->{'Content-Length'},
                        'content_type' => (string) $blob->Properties->{'Content-Type'},
                        'last_modified' => (string) $blob->Properties->{'Last-Modified'},
                    ];
                }
            }

            Log::debug('AzureStorageLaravel - Blobs listed', [
                'connection' => $this->connection,
                'container' => $this->container,
                'count' => count($blobs),
            ]);

            return $blobs;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AzureStorageLaravel - Failed to list blobs', [
                'connection' => $this->connection,
                'container' => $this->container,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upload a file to Azure Blob Storage
     *
     * @param  string  $blobName  The name for the blob
     * @param  string  $pathToFile  Local path to the file
     * @return array Upload result with success, url, blob_name, size, content_type
     *
     * @throws Exception
     */
    public function uploadFile(string $blobName, string $pathToFile): array
    {
        // Sanitize blob name
        $blobName = Str::slug(Str::ascii(pathinfo($blobName, PATHINFO_FILENAME)))
            .'.'.pathinfo($blobName, PATHINFO_EXTENSION);

        if (! file_exists($pathToFile)) {
            throw new Exception("File not found: {$pathToFile}");
        }

        $fileContent = file_get_contents($pathToFile);
        $contentLength = filesize($pathToFile);
        $contentType = mime_content_type($pathToFile);

        $url = sprintf(
            'https://%s.blob.core.windows.net/%s/%s',
            $this->accountName,
            $this->container,
            rawurlencode($blobName)
        );

        $date = gmdate('D, d M Y H:i:s T');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getToken(),
                'x-ms-date' => $date,
                'x-ms-version' => '2021-08-06',
                'x-ms-blob-type' => 'BlockBlob',
                'Content-Type' => $contentType,
                'Content-Length' => $contentLength,
            ])
                ->withBody($fileContent, $contentType)
                ->put($url);

            $response->throw();

            Log::info('AzureStorageLaravel - File uploaded', [
                'connection' => $this->connection,
                'blob' => $blobName,
                'container' => $this->container,
                'size' => $contentLength,
            ]);

            return [
                'success' => true,
                'url' => $url,
                'blob_name' => $blobName,
                'container' => $this->container,
                'size' => $contentLength,
                'content_type' => $contentType,
            ];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AzureStorageLaravel - Upload failed', [
                'connection' => $this->connection,
                'blob' => $blobName,
                'error' => $e->getMessage(),
                'response' => $e->response ? $e->response->body() : null,
            ]);
            throw $e;
        }
    }

    /**
     * Delete a blob from Azure Blob Storage
     *
     * @param  string  $blobName  Name of the blob to delete
     * @return bool True on success
     *
     * @throws Exception
     */
    public function deleteBlob(string $blobName): bool
    {
        $url = sprintf(
            'https://%s.blob.core.windows.net/%s/%s',
            $this->accountName,
            $this->container,
            rawurlencode($blobName)
        );

        $date = gmdate('D, d M Y H:i:s T');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getToken(),
                'x-ms-date' => $date,
                'x-ms-version' => '2021-08-06',
            ])->delete($url);

            $response->throw();

            Log::info('AzureStorageLaravel - Blob deleted', [
                'connection' => $this->connection,
                'blob' => $blobName,
                'container' => $this->container,
            ]);

            return true;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AzureStorageLaravel - Delete failed', [
                'connection' => $this->connection,
                'blob' => $blobName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a new instance for a specific connection
     */
    public function connection(string $connection): self
    {
        return new self($connection);
    }

    /**
     * Run an Azure AI Search indexer
     *
     * @param  string|null  $indexerName  Name of the indexer to run (uses config default if null)
     * @return bool True on success
     *
     * @throws Exception
     */
    public function runIndexer(?string $indexerName = null): bool
    {
        $searchConfig = config('azure-storage-laravel.ai_search');

        if (empty($searchConfig['service_name']) || empty($searchConfig['admin_api_key'])) {
            throw new Exception('Azure AI Search service_name and admin_api_key must be configured');
        }

        $indexerName = $indexerName ?? $searchConfig['index_name'] ?? null;

        if (empty($indexerName)) {
            throw new Exception('Indexer name must be provided or configured in azure-storage-laravel.ai_search.index_name');
        }

        $url = sprintf(
            'https://%s.search.windows.net/indexers/%s/run?api-version=%s',
            $searchConfig['service_name'],
            $indexerName,
            $searchConfig['api_version']
        );

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $searchConfig['admin_api_key'],
            ])->post($url);

            $response->throw();

            Log::info('AzureStorageLaravel - Indexer run triggered', [
                'indexer' => $indexerName,
                'service' => $searchConfig['service_name'],
            ]);

            return true;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AzureStorageLaravel - Failed to run indexer', [
                'indexer' => $indexerName,
                'error' => $e->getMessage(),
                'response' => $e->response ? $e->response->body() : null,
            ]);
            throw $e;
        }
    }

    /**
     * Get the status of an Azure AI Search indexer
     *
     * @param  string|null  $indexerName  Name of the indexer (uses config default if null)
     * @return array Indexer status information
     *
     * @throws Exception
     */
    public function getIndexerStatus(?string $indexerName = null): array
    {
        $searchConfig = config('azure-storage-laravel.ai_search');

        if (empty($searchConfig['service_name']) || empty($searchConfig['admin_api_key'])) {
            throw new Exception('Azure AI Search service_name and admin_api_key must be configured');
        }

        $indexerName = $indexerName ?? $searchConfig['index_name'] ?? null;

        if (empty($indexerName)) {
            throw new Exception('Indexer name must be provided or configured in azure-storage-laravel.ai_search.index_name');
        }

        $url = sprintf(
            'https://%s.search.windows.net/indexers/%s/status?api-version=%s',
            $searchConfig['service_name'],
            $indexerName,
            $searchConfig['api_version']
        );

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $searchConfig['admin_api_key'],
            ])->get($url);

            $response->throw();

            Log::debug('AzureStorageLaravel - Indexer status retrieved', [
                'indexer' => $indexerName,
            ]);

            return $response->json();

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AzureStorageLaravel - Failed to get indexer status', [
                'indexer' => $indexerName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reset an Azure AI Search indexer
     *
     * @param  string|null  $indexerName  Name of the indexer to reset (uses config default if null)
     * @return bool True on success
     *
     * @throws Exception
     */
    public function resetIndexer(?string $indexerName = null): bool
    {
        $searchConfig = config('azure-storage-laravel.ai_search');

        if (empty($searchConfig['service_name']) || empty($searchConfig['admin_api_key'])) {
            throw new Exception('Azure AI Search service_name and admin_api_key must be configured');
        }

        $indexerName = $indexerName ?? $searchConfig['index_name'] ?? null;

        if (empty($indexerName)) {
            throw new Exception('Indexer name must be provided or configured in azure-storage-laravel.ai_search.index_name');
        }

        $url = sprintf(
            'https://%s.search.windows.net/indexers/%s/reset?api-version=%s',
            $searchConfig['service_name'],
            $indexerName,
            $searchConfig['api_version']
        );

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $searchConfig['admin_api_key'],
            ])->post($url);

            $response->throw();

            Log::info('AzureStorageLaravel - Indexer reset', [
                'indexer' => $indexerName,
            ]);

            return true;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AzureStorageLaravel - Failed to reset indexer', [
                'indexer' => $indexerName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * List all Azure AI Search indexers
     *
     * @return array List of indexers
     *
     * @throws Exception
     */
    public function listIndexers(): array
    {
        $searchConfig = config('azure-storage-laravel.ai_search');

        if (empty($searchConfig['service_name']) || empty($searchConfig['admin_api_key'])) {
            throw new Exception('Azure AI Search service_name and admin_api_key must be configured');
        }

        $url = sprintf(
            'https://%s.search.windows.net/indexers?api-version=%s',
            $searchConfig['service_name'],
            $searchConfig['api_version']
        );

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $searchConfig['admin_api_key'],
            ])->get($url);

            $response->throw();

            $data = $response->json();

            Log::debug('AzureStorageLaravel - Indexers listed', [
                'count' => count($data['value'] ?? []),
            ]);

            return $data['value'] ?? [];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AzureStorageLaravel - Failed to list indexers', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
