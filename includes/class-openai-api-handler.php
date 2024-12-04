<?php
namespace Datolab\AutoSEO;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class OpenAI_API_Handler
 *
 * Handles API calls to the OpenAI service.
 */
class OpenAI_API_Handler extends AI_API_Handler {
    /**
     * Maximum number of retries for API calls.
     */
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
     * Calls the OpenAI API with retry mechanism using the chat completions endpoint.
     *
     * @param string $prompt   The prompt to send to OpenAI.
     *
     * @return string|WP_Error The API response text or WP_Error on failure.
     */
    public function call_api( $prompt ) {
        // Check rate limit before making the request
        $rate_check = $this->check_rate_limit();
        if (is_wp_error($rate_check)) {
            $this->logger->log(
                $rate_check->get_error_message(),
                'warning',
                array(
                    'api' => 'OpenAI',
                    'prompt_length' => strlen($prompt)
                )
            );
            return $rate_check;
        }

        $api_url = 'https://api.openai.com/v1/chat/completions'; // Chat completions endpoint
        $attempt = 0;

        $this->logger->log(
            "Starting OpenAI API call",
            'info',
            array(
                'prompt_length' => strlen($prompt),
                'api_url' => $api_url
            )
        );

        while ( $attempt < self::MAX_RETRIES ) {
            $attempt++;
            WP_CLI::log( "Attempt {$attempt} to call OpenAI API." );

            $args = array(
                'body'        => json_encode( array(
                    'model'       => 'gpt-4-mini', // Use a supported chat model
                    'messages'    => array(
                        array(
                            'role'    => 'user',
                            'content' => $prompt,
                        ),
                    ),
                    'max_tokens'  => 150,
                    'n'           => 1,
                    'temperature' => 0.7,
                ) ),
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'timeout'     => 60,
                'data_format' => 'body',
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                WP_CLI::warning( "OpenAI API request failed on attempt {$attempt}: " . $error_message );
                $this->logger->log_api_error(
                    'OpenAI',
                    $error_message,
                    array(
                        'attempt' => $attempt,
                        'error_code' => $response->get_error_code()
                    )
                );
            } else {
                $status_code = wp_remote_retrieve_response_code( $response );
                $body        = wp_remote_retrieve_body( $response );

                if ( 200 !== $status_code ) {
                    WP_CLI::warning( "OpenAI API returned status code {$status_code} on attempt {$attempt}." );
                    WP_CLI::warning( "Response body: {$body}" );
                    $this->logger->log_api_error(
                        'OpenAI',
                        "API returned non-200 status code: {$status_code}",
                        array(
                            'attempt' => $attempt,
                            'response_body' => $body
                        )
                    );
                } else {
                    $data = json_decode( $body, true );

                    if ( isset( $data['choices'][0]['message']['content'] ) ) {
                        $this->logger->log(
                            "OpenAI API call successful",
                            'info',
                            array(
                                'attempt' => $attempt,
                                'response_length' => strlen($data['choices'][0]['message']['content'])
                            )
                        );
                        return trim( $data['choices'][0]['message']['content'] );
                    } else {
                        $error_message = "OpenAI API response does not contain expected 'choices[0].message.content'";
                        WP_CLI::warning( "{$error_message} on attempt {$attempt}." );
                        $this->logger->log_api_error(
                            'OpenAI',
                            $error_message,
                            array(
                                'attempt' => $attempt,
                                'response_body' => $body
                            )
                        );
                    }
                }
            }

            // Exponential backoff before retrying
            $delay = pow( 5, $attempt );
            WP_CLI::log( "Waiting for {$delay} seconds before next attempt." );
            $this->logger->log(
                "Retrying OpenAI API call after delay",
                'warning',
                array(
                    'attempt' => $attempt,
                    'delay_seconds' => $delay
                )
            );
            sleep( $delay );
        }

        $final_error = new WP_Error( 'openai_api_failed', 'OpenAI API failed after multiple attempts.' );
        $this->logger->log_api_error(
            'OpenAI',
            'API failed after maximum retries',
            array('max_attempts' => self::MAX_RETRIES)
        );
        return $final_error;
    }
}
