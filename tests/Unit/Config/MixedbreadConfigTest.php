<?php
namespace Tests\Unit\Config;
use PHPUnit\Framework\TestCase;
class MixedbreadConfigTest extends TestCase
{
  public function test_mixedbread_config_keys_exist(): void
  {
    $config = include __DIR__ . '/../../../config/services.php';
    $this->assertArrayHasKey('mixedbread', $config);
    $this->assertArrayHasKey('api_key', $config['mixedbread']);
    $this->assertArrayHasKey('store_id', $config['mixedbread']);
    $this->assertArrayHasKey('base_url', $config['mixedbread']);
  }
}
