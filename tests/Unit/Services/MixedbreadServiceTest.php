<?php
namespace Tests\Unit\Services;
use App\Services\MixedbreadService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class MixedbreadServiceTest extends TestCase
{
  private MixedbreadService $service;
  protected function setUp(): void
  {
    parent::setUp();
    config(['services.mixedbread.api_key' => 'test-key', 'services.mixedbread.base_url' => 'https://api.mixedbread.com/v1']);
    $this->service = app(MixedbreadService::class);
  }
  public function test_upload_document_posts_to_store_files_endpoint(): void
  {
    Http::fake([
      'https://api.mixedbread.com/v1/stores/store_123/files' => Http::response([
        'id' => 'file_abc', 'status' => 'processing',
      ], 200),
    ]);
    $result = $this->service->uploadDocument('store_123', 'Some content text', 'Doc Title', ['category' => 'Engineering']);
    $this->assertSame('file_abc', $result['id']);
    Http::assertSent(function (Request $request) {
      return str_contains($request->url(), '/stores/store_123/files')
        && $request->hasHeader('Authorization', 'Bearer test-key');
    });
  }
  public function test_upload_document_throws_on_non_2xx(): void
  {
    Http::fake([
      'https://api.mixedbread.com/v1/stores/*/files' => Http::response(['error' => 'Unauthorized'], 401),
    ]);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Mixedbread upload failed');
    $this->service->uploadDocument('store_123', 'Content', 'Title');
  }
  public function test_delete_document_sends_delete_request(): void
  {
    Http::fake([
      'https://api.mixedbread.com/v1/stores/store_123/files/file_abc' => Http::response([], 200),
    ]);
    $this->service->deleteDocument('store_123', 'file_abc');
    Http::assertSent(function (Request $request) {
      return $request->method() === 'DELETE'
        && str_contains($request->url(), '/stores/store_123/files/file_abc');
    });
  }
  public function test_delete_document_is_silent_on_404(): void
  {
    Http::fake([
      'https://api.mixedbread.com/v1/stores/*/files/*' => Http::response([], 404),
    ]);
    $this->service->deleteDocument('store_123', 'file_not_found');
    $this->expectNotToPerformAssertions();
  }
  public function test_search_returns_results(): void
  {
    Http::fake([
      'https://api.mixedbread.com/v1/stores/search' => Http::response([
        'results' => [
          ['id' => 'chunk_1', 'content' => 'Civil Engineering pass rate is 87%.', 'metadata' => ['category' => 'Engineering'], 'score' => 0.95],
          ['id' => 'chunk_2', 'content' => 'Nursing applicants average 72 points.', 'metadata' => ['category' => 'Nursing'], 'score' => 0.87],
        ],
      ], 200),
    ]);
    $results = $this->service->search('store_123', 'What are my chances in Engineering?', ['category' => 'Engineering'], 3);
    $this->assertCount(2, $results);
    $this->assertSame('Civil Engineering pass rate is 87%.', $results[0]['content']);
  }
  public function test_search_throws_on_non_2xx(): void
  {
    Http::fake([
      'https://api.mixedbread.com/v1/stores/search' => Http::response(['error' => 'Server Error'], 500),
    ]);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Mixedbread search failed');
    $this->service->search('store_123', 'some query');
  }
  public function test_get_file_status_returns_status_string(): void
  {
    Http::fake([
      'https://api.mixedbread.com/v1/stores/store_123/files/file_abc' => Http::response([
        'id' => 'file_abc', 'status' => 'indexed',
      ], 200),
    ]);
    $status = $this->service->getFileStatus('store_123', 'file_abc');
    $this->assertSame('indexed', $status);
  }
}