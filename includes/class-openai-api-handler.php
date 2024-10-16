<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OpenAI_API_Handler {
    /**
     * Maximum number of retries for API calls.
     */
    const MAX_RETRIES = 3;

    /**
     * Calls the OpenAI API with retry mechanism using the chat completions endpoint.
     *
     * @param string $prompt   The prompt to send to OpenAI.
     * @param string $api_key  The OpenAI API key.
     *
     * @return string|WP_Error The API response text or WP_Error on failure.
     */
    public function call_openai_api_with_retries( $prompt, $api_key ) {
        $api_url = 'https://api.openai.com/v1/chat/completions'; // Chat completions endpoint
        $attempt = 0;

        while ( $attempt < self::MAX_RETRIES ) {
            $attempt++;
            WP_CLI::log( "Attempt {$attempt} to call OpenAI API." );

            $args = array(
                'body'        => json_encode( array(
                    'model'       => 'gpt-4o', // Use a supported chat model
                    'messages'    => array(
                        array(
                            'role'    => 'user',
                            'content' => $prompt,
                        ),
                    ),
                    'max_tokens'  => 150, // Increased tokens to accommodate JSON response
                    'n'           => 1,
                    'temperature' => 0.7,
                ) ),
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'timeout'     => 60,
                'data_format' => 'body',
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                WP_CLI::warning( "OpenAI API request failed on attempt {$attempt}: " . $response->get_error_message() );
            } else {
                $status_code = wp_remote_retrieve_response_code( $response );
                $body        = wp_remote_retrieve_body( $response );

                if ( 200 !== $status_code ) {
                    WP_CLI::warning( "OpenAI API returned status code {$status_code} on attempt {$attempt}." );
                    WP_CLI::warning( "Response body: {$body}" );
                } else {
                    $data = json_decode( $body, true );

                    if ( isset( $data['choices'][0]['message']['content'] ) ) {
                        return trim( $data['choices'][0]['message']['content'] );
                    } else {
                        WP_CLI::warning( "OpenAI API response does not contain expected 'choices[0].message.content' on attempt {$attempt}." );
                    }
                }
            }

            // Exponential backoff before retrying
            $delay = pow( 4, $attempt ); // 4^1 = 4, 4^2 = 16, 4^3 = 64 seconds
            WP_CLI::log( "Waiting for {$delay} seconds before next attempt." );
            sleep( $delay );
        }

        return new WP_Error( 'openai_api_failed', 'OpenAI API failed after multiple attempts.' );
    }

    // Additional methods for generating categories and tags can be added here
}