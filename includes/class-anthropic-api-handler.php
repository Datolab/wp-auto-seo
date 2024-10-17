<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Anthropic_API_Handler extends AI_API_Handler {
    const MAX_RETRIES = 3;

    public function call_api( $prompt ) {
        $api_url = 'https://api.anthropic.com/v1/complete';
        $attempt = 0;

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
                WP_CLI::warning( "Anthropic API request failed on attempt {$attempt}: " . $response->get_error_message() );
            } else {
                $status_code = wp_remote_retrieve_response_code( $response );
                $body = wp_remote_retrieve_body( $response );

                if ( 200 === $status_code ) {
                    $data = json_decode( $body, true );
                    return trim( $data['completion'] );
                } else {
                    WP_CLI::warning( "Anthropic API returned status code {$status_code} on attempt {$attempt}." );
                }
            }

            sleep( pow( 4, $attempt ) ); // Exponential backoff
        }

        return new WP_Error( 'anthropic_api_failed', 'Anthropic API failed after multiple attempts.' );
    }
}

