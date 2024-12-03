<?php
declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/class-openai-api-handler.php';

use PHPUnit\Framework\TestCase;
use Datolab\AutoSEO\OpenAI_API_Handler;
use WP_Mock as M;
use WP_Error;

class Datolab_Auto_SEO_OpenAI_API_Handler_Test extends TestCase {
    protected $openai_api_handler;

    protected function setUp(): void {
        M::setUp();
        $this->openai_api_handler = $this->getMockBuilder(OpenAI_API_Handler::class)
                                         ->setMethods(['get_api_key'])
                                         ->getMock();
        $this->openai_api_handler->method('get_api_key')->willReturn('test-api-key');
    }

    protected function tearDown(): void {
        M::tearDown();
    }

    public function test_call_api_successful_response() {
        $prompt = 'Test prompt';
        $response_body = json_encode([
            'choices' => [
                ['message' => ['content' => 'Test response']]
            ]
        ]);

        M::wpFunction('wp_remote_post', [
            'args' => function($url, $args) {
                return $url === 'https://api.openai.com/v1/chat/completions' &&
                       $args['headers']['Authorization'] === 'Bearer test-api-key';
            },
            'return' => [
                'body' => $response_body,
                'response' => ['code' => 200]
            ]
        ]);

        M::expectAction('WP_CLI::log', 'Attempt 1 to call OpenAI API.');

        $result = $this->openai_api_handler->call_api($prompt);
        $this->assertEquals('Test response', $result);
    }

    public function test_call_api_retry_on_error_response() {
        $prompt = 'Test prompt';
        $response_body = json_encode([
            'error' => 'API Error'
        ]);

        M::wpFunction('wp_remote_post', [
            'times' => 3,
            'return' => new WP_Error('http_request_failed', 'API error')
        ]);

        M::expectAction('WP_CLI::warning', 'OpenAI API request failed on attempt 1: API error');
        M::expectAction('WP_CLI::warning', 'OpenAI API request failed on attempt 2: API error');
        M::expectAction('WP_CLI::warning', 'OpenAI API request failed on attempt 3: API error');

        $result = $this->openai_api_handler->call_api($prompt);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('openai_api_failed', $result->get_error_code());
    }

    public function test_call_api_non_200_response_code() {
        $prompt = 'Test prompt';
        $response_body = json_encode(['error' => 'Forbidden']);

        M::wpFunction('wp_remote_post', [
            'return' => [
                'body' => $response_body,
                'response' => ['code' => 403]
            ]
        ]);

        M::expectAction('WP_CLI::warning', 'OpenAI API returned status code 403 on attempt 1.');
        M::expectAction('WP_CLI::warning', "Response body: {$response_body}");

        $result = $this->openai_api_handler->call_api($prompt);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('openai_api_failed', $result->get_error_code());
    }

    public function test_call_api_missing_message_content() {
        $prompt = 'Test prompt';
        $response_body = json_encode([
            'choices' => [
                ['message' => []]
            ]
        ]);

        M::wpFunction('wp_remote_post', [
            'return' => [
                'body' => $response_body,
                'response' => ['code' => 200]
            ]
        ]);

        M::expectAction('WP_CLI::warning', "OpenAI API response does not contain expected 'choices[0].message.content' on attempt 1.");

        $result = $this->openai_api_handler->call_api($prompt);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('openai_api_failed', $result->get_error_code());
    }
}