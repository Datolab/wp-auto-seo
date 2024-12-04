<?php
namespace Datolab\AutoSEO;

use WP_Error;

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
     * @var Rate_Limiter The rate limiter instance.
     */
    protected $rate_limiter;

    /**
     * @var Error_Logger The logger instance.
     */
    protected $logger;

    /**
     * @var string The name of the API service.
     */
    protected $api_name;

    /**
     * AI_API_Handler constructor.
     */
    public function __construct() {
        $this->api_key = $this->get_api_key();
        $this->rate_limiter = $GLOBALS['datolab_auto_seo_rate_limiter'];
        $this->logger = $GLOBALS['datolab_auto_seo_logger'];
        
        // Set API name based on class name
        $class_name = get_class($this);
        $this->api_name = strtolower(
            str_replace(
                array('Datolab\\AutoSEO\\', '_API_Handler'),
                '',
                $class_name
            )
        );
    }

    /**
     * Get the API key from WordPress options
     *
     * @return string|null The API key or null if not set
     */
    protected function get_api_key() {
        $option_key = "datolab_auto_seo_{$this->api_name}_api_key";
        return get_option($option_key);
    }

    /**
     * Check if we can make an API request
     *
     * @return bool|WP_Error True if request can be made, WP_Error if rate limited
     */
    protected function check_rate_limit() {
        if (!$this->rate_limiter->can_make_request($this->api_name)) {
            $reset_time = $this->rate_limiter->get_reset_time($this->api_name);
            $current_count = $this->rate_limiter->get_current_count($this->api_name);
            $limit = $this->rate_limiter->get_rate_limit($this->api_name);
            
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    'Rate limit exceeded for %s API. Current count: %d/%d. Reset in %d seconds.',
                    ucfirst($this->api_name),
                    $current_count,
                    $limit,
                    $reset_time
                )
            );
        }
        
        return true;
    }

    /**
     * Calls the AI API with a given prompt.
     *
     * @param string $prompt The prompt to send to the AI service.
     * @return mixed The API response or an error on failure.
     */
    abstract public function call_api( $prompt );
}
