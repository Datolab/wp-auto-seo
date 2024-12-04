<?php
namespace Datolab\AutoSEO;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Logs_Admin
 * Handles the admin interface for viewing logs
 *
 * @package Datolab\AutoSEO
 */
class Logs_Admin {
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
        $this->logger = $GLOBALS['datolab_auto_seo_logger'];
        add_action('admin_menu', array($this, 'add_logs_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_datolab_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_datolab_get_logs', array($this, 'ajax_get_logs'));
    }

    /**
     * Add the logs page to the admin menu
     */
    public function add_logs_page() {
        add_submenu_page(
            'options-general.php',
            'Datolab Auto SEO Logs',
            'Auto SEO Logs',
            'manage_options',
            'datolab-auto-seo-logs',
            array($this, 'render_logs_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook The current admin page
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_datolab-auto-seo-logs' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'datolab-auto-seo-admin',
            plugins_url('assets/css/admin.css', dirname(__FILE__)),
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'datolab-auto-seo-admin',
            plugins_url('assets/js/admin.js', dirname(__FILE__)),
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('datolab-auto-seo-admin', 'datolabAutoSEO', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('datolab_auto_seo_nonce'),
        ));
    }

    /**
     * Render the logs page
     */
    public function render_logs_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="datolab-auto-seo-logs-controls">
                <div class="filters">
                    <select id="log-level-filter">
                        <option value="">All Levels</option>
                        <option value="error">Errors</option>
                        <option value="warning">Warnings</option>
                        <option value="info">Info</option>
                    </select>

                    <select id="log-source-filter">
                        <option value="">All Sources</option>
                        <option value="OpenAI">OpenAI</option>
                        <option value="Anthropic">Anthropic</option>
                        <option value="Cohere">Cohere</option>
                    </select>

                    <input type="text" id="log-search" placeholder="Search logs...">
                </div>

                <div class="actions">
                    <button id="refresh-logs" class="button">
                        <span class="dashicons dashicons-update"></span> Refresh
                    </button>
                    <button id="clear-logs" class="button button-secondary">
                        <span class="dashicons dashicons-trash"></span> Clear Logs
                    </button>
                    <button id="download-logs" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span> Download
                    </button>
                </div>
            </div>

            <div class="datolab-auto-seo-logs-container">
                <div id="logs-content" class="logs-content">
                    <div class="loading">Loading logs...</div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX request to clear logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('datolab_auto_seo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $this->logger->clear_logs();
        wp_send_json_success('Logs cleared successfully');
    }

    /**
     * Handle AJAX request to get logs
     */
    public function ajax_get_logs() {
        check_ajax_referer('datolab_auto_seo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
        $source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = 50;

        $logs = $this->logger->get_logs(0); // Get all logs
        $log_entries = array_filter(
            explode("\n", $logs),
            function($line) {
                return !empty(trim($line));
            }
        );

        // Apply filters
        if (!empty($level) || !empty($source) || !empty($search)) {
            $log_entries = array_filter($log_entries, function($entry) use ($level, $source, $search) {
                if (!empty($level) && stripos($entry, "[$level]") === false) {
                    return false;
                }
                if (!empty($source) && stripos($entry, $source) === false) {
                    return false;
                }
                if (!empty($search) && stripos($entry, $search) === false) {
                    return false;
                }
                return true;
            });
        }

        // Sort logs by timestamp (newest first)
        usort($log_entries, function($a, $b) {
            preg_match('/\[(.*?)\]/', $a, $match_a);
            preg_match('/\[(.*?)\]/', $b, $match_b);
            $time_a = strtotime($match_a[1] ?? '0');
            $time_b = strtotime($match_b[1] ?? '0');
            return $time_b - $time_a;
        });

        // Paginate results
        $total_pages = ceil(count($log_entries) / $per_page);
        $offset = ($page - 1) * $per_page;
        $log_entries = array_slice($log_entries, $offset, $per_page);

        // Format logs for display
        $formatted_logs = array_map(function($entry) {
            // Extract timestamp
            preg_match('/\[(.*?)\]/', $entry, $time_match);
            $timestamp = $time_match[1] ?? '';

            // Extract log level
            preg_match('/\[(error|warning|info)\]/i', $entry, $level_match);
            $level = strtolower($level_match[1] ?? 'info');

            // Extract message and context
            $message_start = strpos($entry, '] |') + 3;
            $message = substr($entry, $message_start);

            return array(
                'timestamp' => $timestamp,
                'level' => $level,
                'message' => $message,
                'raw' => $entry
            );
        }, $log_entries);

        wp_send_json_success(array(
            'logs' => $formatted_logs,
            'pagination' => array(
                'current_page' => $page,
                'total_pages' => $total_pages,
                'per_page' => $per_page
            )
        ));
    }
}
