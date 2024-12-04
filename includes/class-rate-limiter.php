<?php
namespace Datolab\AutoSEO;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Rate_Limiter
 * Handles API rate limiting using WordPress transients
 *
 * @package Datolab\AutoSEO
 */
class Rate_Limiter {
    /**
     * Logger instance
     *
     * @var Error_Logger
     */
    private $logger;

    /**
     * Default rate limits for different APIs
     * Limits are per minute
     *
     * @var array
     */
    private $default_limits = array(
        'openai' => 60,     // 60 requests per minute
        'anthropic' => 45,  // 45 requests per minute
        'cohere' => 30      // 30 requests per minute
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = $GLOBALS['datolab_auto_seo_logger'];
    }

    /**
     * Check if a request can be made for a specific API
     *
     * @param string $api_name The name of the API (openai, anthropic, cohere)
     * @return bool True if request is allowed, false if rate limit exceeded
     */
    public function can_make_request($api_name) {
        $api_name = strtolower($api_name);
        $transient_key = "datolab_rate_limit_{$api_name}";
        $window_key = "datolab_rate_window_{$api_name}";
        
        // Get the rate limit for this API
        $rate_limit = $this->get_rate_limit($api_name);
        
        // Get current window and request count
        $current_window = get_transient($window_key);
        $request_count = get_transient($transient_key);
        
        $current_time = time();
        
        // If no window exists or window has expired, start a new one
        if ($current_window === false || $current_time - $current_window >= 60) {
            set_transient($window_key, $current_time, 70); // Set window with buffer
            set_transient($transient_key, 1, 70); // Start counting from 1
            
            $this->logger->log(
                "New rate limit window started for {$api_name}",
                'info',
                array(
                    'api' => $api_name,
                    'limit' => $rate_limit,
                    'window_start' => date('Y-m-d H:i:s', $current_time)
                )
            );
            
            return true;
        }
        
        // If within window, check count
        if ($request_count === false) {
            set_transient($transient_key, 1, 70);
            return true;
        }
        
        // Check if we've hit the limit
        if ($request_count >= $rate_limit) {
            $this->logger->log(
                "Rate limit exceeded for {$api_name}",
                'warning',
                array(
                    'api' => $api_name,
                    'limit' => $rate_limit,
                    'current_count' => $request_count,
                    'window_start' => date('Y-m-d H:i:s', $current_window),
                    'seconds_remaining' => 60 - ($current_time - $current_window)
                )
            );
            return false;
        }
        
        // Increment counter
        set_transient($transient_key, $request_count + 1, 70);
        return true;
    }

    /**
     * Get the time until the rate limit resets
     *
     * @param string $api_name The name of the API
     * @return int Seconds until reset, or 0 if no active window
     */
    public function get_reset_time($api_name) {
        $window_key = "datolab_rate_window_{$api_name}";
        $window_start = get_transient($window_key);
        
        if ($window_start === false) {
            return 0;
        }
        
        $time_passed = time() - $window_start;
        return max(0, 60 - $time_passed);
    }

    /**
     * Get current request count for an API
     *
     * @param string $api_name The name of the API
     * @return int Current request count, or 0 if no active window
     */
    public function get_current_count($api_name) {
        $transient_key = "datolab_rate_limit_{$api_name}";
        return (int)get_transient($transient_key) ?: 0;
    }

    /**
     * Get rate limit for an API
     *
     * @param string $api_name The name of the API
     * @return int Requests per minute limit
     */
    public function get_rate_limit($api_name) {
        $api_name = strtolower($api_name);
        $option_key = "datolab_rate_limit_{$api_name}";
        
        // Try to get custom limit from options
        $custom_limit = get_option($option_key);
        if ($custom_limit !== false && is_numeric($custom_limit)) {
            return (int)$custom_limit;
        }
        
        // Fall back to default limit
        return isset($this->default_limits[$api_name]) 
            ? $this->default_limits[$api_name] 
            : 30; // Conservative default
    }

    /**
     * Set custom rate limit for an API
     *
     * @param string $api_name The name of the API
     * @param int    $limit    Requests per minute limit
     * @return bool True if limit was set successfully
     */
    public function set_rate_limit($api_name, $limit) {
        $api_name = strtolower($api_name);
        $option_key = "datolab_rate_limit_{$api_name}";
        
        if ($limit < 1) {
            $this->logger->log(
                "Invalid rate limit attempted to be set",
                'error',
                array(
                    'api' => $api_name,
                    'attempted_limit' => $limit
                )
            );
            return false;
        }
        
        $result = update_option($option_key, $limit);
        
        if ($result) {
            $this->logger->log(
                "Rate limit updated for {$api_name}",
                'info',
                array(
                    'api' => $api_name,
                    'new_limit' => $limit
                )
            );
        }
        
        return $result;
    }

    /**
     * Reset rate limit counter for an API
     *
     * @param string $api_name The name of the API
     */
    public function reset_counter($api_name) {
        $transient_key = "datolab_rate_limit_{$api_name}";
        $window_key = "datolab_rate_window_{$api_name}";
        
        delete_transient($transient_key);
        delete_transient($window_key);
        
        $this->logger->log(
            "Rate limit counter reset for {$api_name}",
            'info',
            array('api' => $api_name)
        );
    }
}
