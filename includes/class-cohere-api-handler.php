<?php
namespace Datolab\AutoSEO;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Cohere_API_Handler extends AI_API_Handler {
    const MAX_RETRIES = 3;

    public function call_api( $prompt ) {
        $api_url = 'https://api.cohere.ai/generate';
        $attempt = 0;

        while ( $attempt < self::MAX_RETRIES ) {
            $attempt++;
            WP_CLI::log( "Attempt {$attempt} to call Cohere API." );

            $args = array(
                'body' => json_encode( array(
                    'prompt' => $prompt,
                    'maxTokens' => 150,
                    'temperature' => 0.7,
                ) ),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'timeout' => 60,
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                WP_CLI::warning( "Cohere API request failed on attempt {$attempt}: " . $response->get_error_message() );
            } else {
                $status_code = wp_remote_retrieve_response_code( $response );
                $body = wp_remote_retrieve_body( $response );

                if ( 200 === $status_code ) {
                    $data = json_decode( $body, true );
                    return trim( $data['generations'][0]['text'] );
                } else {
                    WP_CLI::warning( "Cohere API returned status code {$status_code} on attempt {$attempt}." );
                }
            }

            sleep( pow( 4, $attempt ) ); // Exponential backoff
        }

        return new WP_Error( 'cohere_api_failed', 'Cohere API failed after multiple attempts.' );
    }
}
