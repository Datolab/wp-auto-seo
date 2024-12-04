<?php
namespace Datolab\AutoSEO;

use WP_CLI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Anthropic_API_Handler
 *
 * Handles API calls to the Anthropic service.
 */
class Anthropic_API_Handler extends AI_API_Handler {
    const MAX_RETRIES = 3;

    /**
     * Logger instance
     *
     * @var Error_Logger
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->logger = $GLOBALS['datolab_auto_seo_logger'];
    }

    /**
     * Calls the Anthropic API with a given prompt.
     *
     * @param string $prompt The prompt to send to the Anthropic service.
     * @return string|WP_Error The API response text or WP_Error on failure.
     */
    public function call_api( $prompt ) {
        $api_url = 'https://api.anthropic.com/v1/complete';
        $attempt = 0;

        $this->logger->log(
            "Starting Anthropic API call",
            'info',
            array(
                'prompt_length' => strlen($prompt),
                'api_url' => $api_url
            )
        );

        while ( $attempt < self::MAX_RETRIES ) {
            $attempt++;
            WP_CLI::log( "Attempt {$attempt} to call Anthropic API." );

            $args = array(
                'body' => json_encode( array(
                    'prompt' => $prompt,
                    'max_tokens' => 150,
                    'stop_sequences' => ["\n"],
                ) ),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'timeout' => 60,
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                WP_CLI::warning( "Anthropic API request failed on attempt {$attempt}: " . $error_message );
                $this->logger->log_api_error(
                    'Anthropic',
                    $error_message,
                    array(
                        'attempt' => $attempt,
                        'error_code' => $response->get_error_code(),
                        'api_url' => $api_url
                    )
                );
            } else {
                $status_code = wp_remote_retrieve_response_code( $response );
                $body = wp_remote_retrieve_body( $response );

                if ( 200 === $status_code ) {
                    $data = json_decode( $body, true );
                    if (isset($data['completion'])) {
                        $this->logger->log(
                            "Anthropic API call successful",
                            'info',
                            array(
                                'attempt' => $attempt,
                                'response_length' => strlen($data['completion']),
                                'status_code' => $status_code
                            )
                        );
                        return trim( $data['completion'] );
                    } else {
                        $error_message = "Anthropic API response does not contain expected 'completion' field";
                        WP_CLI::warning( $error_message );
                        $this->logger->log_api_error(
                            'Anthropic',
                            $error_message,
                            array(
                                'attempt' => $attempt,
                                'response_body' => $body,
                                'status_code' => $status_code
                            )
                        );
                    }
                } else {
                    WP_CLI::warning( "Anthropic API returned status code {$status_code} on attempt {$attempt}." );
                    $this->logger->log_api_error(
                        'Anthropic',
                        "API returned non-200 status code",
                        array(
                            'attempt' => $attempt,
                            'status_code' => $status_code,
                            'response_body' => $body
                        )
                    );
                }
            }

            $delay = pow( 4, $attempt );
            $this->logger->log(
                "Retrying Anthropic API call after delay",
                'warning',
                array(
                    'attempt' => $attempt,
                    'delay_seconds' => $delay
                )
            );
            sleep( $delay ); // Exponential backoff
        }

        $final_error = new WP_Error( 'anthropic_api_failed', 'Anthropic API failed after multiple attempts.' );
        $this->logger->log_api_error(
            'Anthropic',
            'API failed after maximum retries',
            array(
                'max_attempts' => self::MAX_RETRIES,
                'last_attempt' => $attempt
            )
        );
        return $final_error;
    }
}
