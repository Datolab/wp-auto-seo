<?php
namespace Datolab\AutoSEO;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class AI_API_Handler
 *
 * Abstract base class for handling API calls to various AI services.
 */
abstract class AI_API_Handler {
    /**
     * @var string The API key for authenticating requests.
     */
    protected $api_key;

    /**
     * AI_API_Handler constructor.
     *
     * @param string $api_key The API key for the AI service.
     */
    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    /**
     * Calls the AI API with a given prompt.
     *
     * @param string $prompt The prompt to send to the AI service.
     * @return mixed The API response or an error on failure.
     */
    abstract public function call_api( $prompt );
}
