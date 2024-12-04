<?php
namespace Datolab\AutoSEO;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Error_Logger
 * Handles error logging and monitoring for the Datolab Auto SEO plugin.
 *
 * @package Datolab\AutoSEO
 */
class Error_Logger {
    /**
     * Log file path
     *
     * @var string
     */
    private $log_file;

    /**
     * Maximum log file size in bytes (5MB)
     *
     * @var int
     */
    private $max_file_size = 5242880;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log_file = plugin_dir_path(dirname(__FILE__)) . 'includes/logs/datolab-auto-seo.log';
        $this->init();
    }

    /**
     * Initialize the logger
     */
    private function init() {
        // Create logs directory if it doesn't exist
        $logs_dir = dirname($this->log_file);
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }

        // Create log file if it doesn't exist
        if (!file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }

        // Set up error handlers
        add_action('admin_init', array($this, 'check_log_file_size'));
    }

    /**
     * Log an error message
     *
     * @param string $message Error message
     * @param string $level Error level (error, warning, info)
     * @param array  $context Additional context data
     */
    public function log($message, $level = 'error', $context = array()) {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $user_info = $user_id ? " | User ID: {$user_id}" : " | No user";

        // Format context data
        $context_string = '';
        if (!empty($context)) {
            $context_string = ' | Context: ' . json_encode($context);
        }

        $log_entry = "[{$timestamp}] [{$level}]{$user_info} | {$message}{$context_string}\n";

        error_log($log_entry, 3, $this->log_file);

        // If this is a critical error, send notification
        if ($level === 'error') {
            $this->notify_admin($message, $context);
        }
    }

    /**
     * Log API-related errors
     *
     * @param string $api_name Name of the API service
     * @param string $error_message Error message
     * @param array  $request_data Request data
     */
    public function log_api_error($api_name, $error_message, $request_data = array()) {
        $context = array(
            'api' => $api_name,
            'request_data' => $request_data,
        );
        $this->log("API Error ({$api_name}): {$error_message}", 'error', $context);
    }

    /**
     * Check and rotate log file if it exceeds maximum size
     */
    public function check_log_file_size() {
        if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_file_size) {
            $this->rotate_logs();
        }
    }

    /**
     * Rotate log files
     */
    private function rotate_logs() {
        $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        rename($this->log_file, $backup_file);
        file_put_contents($this->log_file, '');

        // Clean up old backup files (keep last 5)
        $backup_files = glob($this->log_file . '.*.bak');
        if (count($backup_files) > 5) {
            usort($backup_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            $files_to_delete = array_slice($backup_files, 0, count($backup_files) - 5);
            foreach ($files_to_delete as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Notify admin about critical errors
     *
     * @param string $message Error message
     * @param array  $context Additional context data
     */
    private function notify_admin($message, $context = array()) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $subject = sprintf('[%s] Datolab Auto SEO Critical Error', $site_name);
        
        $body = "A critical error occurred in the Datolab Auto SEO plugin:\n\n";
        $body .= "Error Message: {$message}\n";
        if (!empty($context)) {
            $body .= "Context: " . print_r($context, true) . "\n";
        }
        $body .= "\nTime: " . current_time('Y-m-d H:i:s') . "\n";
        $body .= "Site URL: " . get_site_url() . "\n";

        wp_mail($admin_email, $subject, $body);
    }

    /**
     * Get log file content
     *
     * @param int $lines Number of lines to retrieve (0 for all)
     * @return string
     */
    public function get_logs($lines = 100) {
        if (!file_exists($this->log_file)) {
            return '';
        }

        if ($lines === 0) {
            return file_get_contents($this->log_file);
        }

        $file = new \SplFileObject($this->log_file, 'r');
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();

        $logs = array();
        $start_line = max(0, $total_lines - $lines);

        $file->seek($start_line);
        while (!$file->eof()) {
            $logs[] = $file->fgets();
        }

        return implode('', $logs);
    }
}
