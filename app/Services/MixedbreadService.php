<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class MixedbreadService
{
  private string $apiKey;
  private string $baseUrl;
  public function __construct()
  {
    $this->apiKey  = config('services.mixedbread.api_key', '');
    $this->baseUrl = rtrim(config('services.mixedbread.base_url', 'https://api.mixedbread.com/v1'), '/');
  }
  public function uploadDocument(string $storeId, string $content, string $title, array $metadata = []): array
  {
    $tmpPath = tempnam(sys_get_temp_dir(), 'mxb_') . '.txt';
    file_put_contents($tmpPath, $content);
    try {
      $response = Http::withToken($this->apiKey)
        ->attach('file', fopen($tmpPath, 'r'), "{$title}.txt", ['Content-Type' => 'text/plain'])
        ->post("{$this->baseUrl}/stores/{$storeId}/files", array_filter([
          'external_id' => $title,
          'metadata'    => ! empty($metadata) ? json_encode($metadata) : null,
        ]));
    } finally {
      @unlink($tmpPath);
    }
    if (! $response->successful()) {
      throw new \RuntimeException("Mixedbread upload failed: HTTP {$response->status()} — {$response->body()}");
    }
    return $response->json();
  }
  public function deleteDocument(string $storeId, string $fileId): void
  {
    $response = Http::withToken($this->apiKey)
      ->delete("{$this->baseUrl}/stores/{$storeId}/files/{$fileId}");
    if ($response->status() === 404) {
      Log::debug("Mixedbread deleteDocument: file {$fileId} not found in store {$storeId}, skipping.");
      return;
    }
    if (! $response->successful()) {
      throw new \RuntimeException("Mixedbread delete failed: HTTP {$response->status()} — {$response->body()}");
    }
  }
  public function search(string $storeId, string $query, array $filters = [], int $topK = 3): array
  {
    $payload = [
      'query'             => $query,
      'store_identifiers' => [$storeId],
      'top_k'             => $topK,
    ];
    if (! empty($filters)) {
      $payload['filters'] = $filters;
    }
    $response = Http::withToken($this->apiKey)
      ->post("{$this->baseUrl}/stores/search", $payload);
    if (! $response->successful()) {
      throw new \RuntimeException("Mixedbread search failed: HTTP {$response->status()} — {$response->body()}");
    }
    return $response->json('results', []);
  }
  public function getFileStatus(string $storeId, string $fileId): string
  {
    $response = Http::withToken($this->apiKey)
      ->get("{$this->baseUrl}/stores/{$storeId}/files/{$fileId}");
    if (! $response->successful()) {
      throw new \RuntimeException("Mixedbread getFileStatus failed: HTTP {$response->status()} — {$response->body()}");
    }
    return (string) $response->json('status', 'unknown');
  }
}