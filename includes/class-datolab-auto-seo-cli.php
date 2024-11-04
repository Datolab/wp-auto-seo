<?php
namespace Datolab\AutoSEO;

use WP_CLI;
use WP_CLI_Command;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once 'class-ai-api-handler.php'; // Include the base AI API handler
    require_once 'class-openai-api-handler.php'; // Include the OpenAI API handler
    // TODO: Add cohere and anthropic api handlers

    /**
     * Class Datolab_Auto_SEO_CLI_Command
     *
     * Handles WP-CLI commands for the Datolab Auto SEO plugin.
     */
    class Datolab_Auto_SEO_CLI_Command extends WP_CLI_Command {

        /**
         * Maximum number of retries for API calls.
         */
        const MAX_RETRIES = 3;

        /**
         * Maximum number of categories per post.
         */
        const MAX_CATEGORIES = 4;

        /**
         * Maximum number of tags per post.
         */
        const MAX_TAGS = 5;

        /**
         * Email subject for admin notifications.
         */
        const ADMIN_NOTIFICATION_SUBJECT = 'Datolab Auto SEO: OpenAI API Processing Errors';

        /**
         * @var OpenAI_API_Handler The OpenAI API handler instance.
         */
        private $openai_api_handler;

        /**
         * Datolab_Auto_SEO_CLI_Command constructor.
         *
         * Initializes the OpenAI API handler with the API key.
         */
        public function __construct() {
            $api_key = $this->get_openai_api_key(); // Retrieve the OpenAI API key securely
            if ( ! empty( $api_key ) ) {
                $this->openai_api_handler = new OpenAI_API_Handler( $api_key ); // Initialize the OpenAI API handler
            } else {
                WP_CLI::error( 'OpenAI API key is not set. Please set it in the Datolab Auto SEO settings.' );
            }
        }

        /**
         * Processes draft posts to generate SEO tags and categories.
         *
         * ## EXAMPLES
         *
         *     wp datolab-auto-seo process
         *
         * @when after_wp_load
         *
         * @param array $args Positional arguments.
         * @param array $assoc_args Associative arguments.
         */
        public function process( $args, $assoc_args ) {
            WP_CLI::log( 'Starting Datolab Auto SEO processing.' );

            // Get all draft posts
            $draft_posts = get_posts( array(
                'post_type'      => 'post',
                'post_status'    => 'draft',
                'numberposts'    => -1,
            ) );

            if ( empty( $draft_posts ) ) {
                WP_CLI::success( 'No draft posts found.' );
                return;
            }

            foreach ( $draft_posts as $post ) {
                WP_CLI::log( "Processing post ID {$post->ID}: {$post->post_title}" );

                $this->process_categories( $post );
                $this->process_tags( $post );

                // Remove "Uncategorized" from post categories
                $this->remove_uncategorized( $post->ID );

                WP_CLI::success( "Processed post ID {$post->ID}" );
            }

            WP_CLI::success( 'All draft posts have been processed.' );
        }

        /**
         * Processes categories for a given post.
         *
         * @param WP_Post $post The post object.
         */
        private function process_categories( $post ) {
            $current_categories_count = count( wp_get_post_categories( $post->ID ) );
            $categories_needed = max( 0, self::MAX_CATEGORIES - $current_categories_count );

            if ( $categories_needed > 0 ) {
                WP_CLI::log( "Generating up to " . self::MAX_CATEGORIES . " categories." );
                $generated_categories = $this->generate_categories( $post, $categories_needed );

                foreach ( $generated_categories as $category_name ) {
                    $this->associate_category( $post->ID, $category_name );
                }
            } else {
                WP_CLI::log( "Post ID {$post->ID} already has the maximum number of categories." );
            }
        }

        /**
         * Processes tags for a given post.
         *
         * @param WP_Post $post The post object.
         */
        private function process_tags( $post ) {
            $current_tags_count = count( wp_get_post_tags( $post->ID ) );
            $tags_needed = max( 0, self::MAX_TAGS - $current_tags_count );

            if ( $tags_needed > 0 ) {
                WP_CLI::log( "Generating up to " . self::MAX_TAGS . " tags." );
                $generated_tags = $this->generate_tags( $post, $tags_needed );

                foreach ( $generated_tags as $tag_name ) {
                    $this->associate_tag( $post->ID, $tag_name );
                }
            } else {
                WP_CLI::log( "Post ID {$post->ID} already has the maximum number of tags." );
            }
        }

        /**
         * Generates categories for a given post.
         *
         * @param WP_Post $post The post object.
         * @param int $number The number of categories to generate.
         * @return array The generated categories.
         */
        private function generate_categories( $post, $number ) {
            // Fetch post content for better context
            $post_content = $post->post_content;
            $post_excerpt = $post->post_excerpt;

            $prompt = "Based on the following blog post title and content, generate {$number} SEO-optimized categories. The categories should be broad, descriptive, and relevant to the post's topic. Do not include numbers. Provide the results in the following JSON format:\n\n{\n  \"categories\": [\"Category1\", \"Category2\", \"Category3\"]\n}\n\nTitle: \"{$post->post_title}\"\n\nContent: \"{$post_content}\"\n\nExcerpt: \"{$post_excerpt}\"";

            $response = $this->openai_api_handler->call_api( $prompt );

            if ( is_wp_error( $response ) ) {
                WP_CLI::warning( "OpenAI API error while generating categories for post ID {$post->ID}: " . $response->get_error_message() );
                return array();
            }

            // Strip Markdown code blocks if present
            $response = $this->strip_markdown_code_blocks( $response );

            // Parse JSON response
            $parsed = json_decode( $response, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                WP_CLI::warning( "Failed to parse JSON response while generating categories for post ID {$post->ID}: " . json_last_error_msg() );
                return array();
            }

            if ( ! isset( $parsed['categories'] ) || ! is_array( $parsed['categories'] ) ) {
                WP_CLI::warning( "JSON response does not contain 'categories' array for post ID {$post->ID}." );
                return array();
            }

            WP_CLI::log( "Categories parsed successfully for post ID {$post->ID}." );

            // Limit to the required number of categories
            return array_slice( $parsed['categories'], 0, $number );
        }

        /**
         * Generates tags for a given post.
         *
         * @param WP_Post $post The post object.
         * @param int $number The number of tags to generate.
         * @return array The generated tags.
         */
        private function generate_tags( $post, $number ) {
            // Fetch post content for better context
            $post_content = $post->post_content;
            $post_excerpt = $post->post_excerpt;

            $prompt = "Based on the following blog post title and content, generate {$number} SEO-optimized tags. The tags should be specific, relevant, and descriptive keywords that reflect the main topics of the post. Do not include numbers. Provide the results in the following JSON format:\n\n{\n  \"tags\": [\"Tag1\", \"Tag2\", \"Tag3\", \"Tag4\", \"Tag5\"]\n}\n\nTitle: \"{$post->post_title}\"\n\nContent: \"{$post_content}\"\n\nExcerpt: \"{$post_excerpt}\"";

            $response = $this->openai_api_handler->call_api( $prompt );

            if ( is_wp_error( $response ) ) {
                WP_CLI::warning( "OpenAI API error while generating tags for post ID {$post->ID}: " . $response->get_error_message() );
                return array();
            }

            // Strip Markdown code blocks if present
            $response = $this->strip_markdown_code_blocks( $response );

            // Parse JSON response
            $parsed = json_decode( $response, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                WP_CLI::warning( "Failed to parse JSON response while generating tags for post ID {$post->ID}: " . json_last_error_msg() );
                return array();
            }

            if ( ! isset( $parsed['tags'] ) || ! is_array( $parsed['tags'] ) ) {
                WP_CLI::warning( "JSON response does not contain 'tags' array for post ID {$post->ID}." );
                return array();
            }

            WP_CLI::log( "Tags parsed successfully for post ID {$post->ID}." );

            // Limit to the required number of tags
            return array_slice( $parsed['tags'], 0, $number );
        }

        /**
         * Associates a category with a post.
         *
         * @param int $post_id The ID of the post.
         * @param string $category_name The name of the category to associate.
         */
        private function associate_category( $post_id, $category_name ) {
            // Check if category exists
            $term = term_exists( $category_name, 'category' );
            if ( ! $term ) {
                $new_term = wp_insert_term( $category_name, 'category' );
                if ( is_wp_error( $new_term ) ) {
                    WP_CLI::warning( "Failed to create category: {$category_name}" );
                    return;
                }
                $term_id = $new_term['term_id'];
                WP_CLI::log( "Created new category: {$category_name}" );
            } else {
                $term_id = $term['term_id'];
                WP_CLI::log( "Category exists: {$category_name}" );
            }

            // Associate category with post
            wp_set_post_categories( $post_id, array_merge( wp_get_post_categories( $post_id ), array( $term_id ) ) );
        }

        /**
         * Associates a tag with a post.
         *
         * @param int $post_id The ID of the post.
         * @param string $tag_name The name of the tag to associate.
         */
        private function associate_tag( $post_id, $tag_name ) {
            if ( $this->is_valid_tag( $tag_name ) ) {
                $term = term_exists( $tag_name, 'post_tag' );
                if ( ! $term ) {
                    // If the tag doesn't exist, create it and get the new term ID
                    $new_term = wp_insert_term( $tag_name, 'post_tag' );
                    if ( is_wp_error( $new_term ) ) {
                        WP_CLI::warning( "Failed to create tag: {$tag_name}" );
                        return;
                    }
                    $term_id = $new_term['term_id'];
                    WP_CLI::log( "Created new tag: {$tag_name}" );
                } else {
                    // If the tag exists, get its term ID
                    $term_id = $term['term_id'];
                    WP_CLI::log( "Tag exists: {$tag_name}" );
                }

                // Get current tags by name and add the new tag name
                $current_tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
                $updated_tags = array_merge( $current_tags, array( $tag_name ) );

                // Set the updated tags by name for the post
                wp_set_post_tags( $post_id, $updated_tags );
            } else {
                WP_CLI::warning( "Invalid tag generated (numeric or irrelevant): {$tag_name}" );
                $this->log_to_file( "Invalid tag generated for post ID {$post_id}: {$tag_name}" );
            }
        }

        /**
         * Clears all categories and tags from a post.
         *
         * @param int $post_id The ID of the post.
         */
        private function clear_categories_and_tags( $post_id ) {
            // Clear all categories by setting to an empty array (will assign new ones later)
            wp_set_post_categories( $post_id, array() );
            WP_CLI::log( "Cleared all categories from post ID {$post_id}." );
            $this->log_to_file( "Cleared all categories from post ID {$post_id}." );

            // Clear all tags by setting to an empty array
            wp_set_post_tags( $post_id, array() );
            WP_CLI::log( "Cleared all tags from post ID {$post_id}." );
            $this->log_to_file( "Cleared all tags from post ID {$post_id}." );
        }

        /**
         * Validates if a tag is meaningful and not purely numeric.
         *
         * @param string $tag The tag to validate.
         *
         * @return bool True if valid, False otherwise.
         */
        private function is_valid_tag( $tag ) {
            // Check if the tag is purely numeric
            if ( is_numeric( $tag ) ) {
                return false;
            }

            // Ensure the tag contains at least one alphabet character
            if ( ! preg_match( '/[A-Za-z]/', $tag ) ) {
                return false;
            }

            return true;
        }

        /**
         * Strips Markdown code block markers from a string if present.
         *
         * @param string $response The API response string.
         *
         * @return string The cleaned response string.
         */
        private function strip_markdown_code_blocks( $response ) {
            // Trim whitespace from the beginning and end
            $response = trim( $response );

            // Check if the response starts with ```json and ends with ```
            if ( strpos( $response, '```json' ) === 0 && substr( $response, -3 ) === '```' ) {
                // Remove the first line (```json) and the last line (```)
                $lines = explode( "\n", $response );

                // Remove the first and last lines
                array_shift( $lines );
                array_pop( $lines );

                // Reconstruct the response
                $response = implode( "\n", $lines );

                WP_CLI::log( "Stripped Markdown code blocks from OpenAI response." );
                $this->log_to_file( "Stripped Markdown code blocks from OpenAI response." );
            }

            return $response;
        }

        /**
         * Sends an email notification to the site administrator.
         *
         * @param string $message The message to send.
         */
        private function notify_admin( $message ) {
            $admin_email = get_option( 'admin_email' );
            if ( ! $admin_email ) {
                WP_CLI::warning( 'Admin email is not set. Cannot send notification.' );
                return;
            }

            $subject = self::ADMIN_NOTIFICATION_SUBJECT;
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );

            $body = "<p>{$message}</p>";
            $body .= "<p>Please check the plugin logs for more details.</p>";

            $mail_sent = wp_mail( $admin_email, $subject, $body, $headers );

            if ( $mail_sent ) {
                WP_CLI::log( "Admin notified via email at {$admin_email}." );
                $this->log_to_file( "Admin notified via email at {$admin_email}." );
            } else {
                WP_CLI::warning( "Failed to send notification email to {$admin_email}." );
                $this->log_to_file( "Failed to send notification email to {$admin_email}." );
            }
        }

        /**
         * Retrieves the OpenAI API key securely.
         *
         * @return string The OpenAI API key or an empty string if not set.
         */
        private function get_openai_api_key() {
            // Bypass capability check if running in WP-CLI mode
            if (defined('WP_CLI') && WP_CLI) {
                WP_CLI::log('Running in WP-CLI mode, bypassing capability check.');
            } else {
                // Ensure the current user has the required capability
                if (!current_user_can('manage_options')) {
                    WP_CLI::warning('Current user lacks manage_options capability.');
                    return '';
                }
            }

            // Retrieve the API key securely
            $api_key = getenv( 'OPENAI_API_KEY' ) ? getenv( 'OPENAI_API_KEY' ) : get_option( 'datolab_auto_seo_openai_api_key', '' );

            if ( empty( $api_key ) ) {
                WP_CLI::warning( 'OpenAI API key is empty.' );
            } else {
                WP_CLI::log( 'OpenAI API key retrieved successfully.' );
            }

            return sanitize_text_field( $api_key );
        }

        /**
         * Removes the "Uncategorized" category from a post's categories.
         *
         * @param int $post_id The ID of the post.
         */
        private function remove_uncategorized( $post_id ) {
            // Get the "Uncategorized" category
            $uncategorized = get_category_by_slug( 'uncategorized' );

            if ( ! $uncategorized ) {
                WP_CLI::warning( 'Uncategorized category does not exist.' );
                $this->log_to_file( 'Uncategorized category does not exist.' );
                return;
            }

            $uncategorized_id = (int) $uncategorized->term_id;

            // Get current categories assigned to the post
            $current_categories = wp_get_post_categories( $post_id );

            if ( in_array( $uncategorized_id, $current_categories, true ) ) {
                // Remove "Uncategorized" from the categories array
                $updated_categories = array_diff( $current_categories, array( $uncategorized_id ) );

                // Update the post's categories
                wp_set_post_categories( $post_id, $updated_categories );

                WP_CLI::log( "Removed 'Uncategorized' category from post ID {$post_id}." );
                $this->log_to_file( "Removed 'Uncategorized' category from post ID {$post_id}." );
            } else {
                WP_CLI::log( "'Uncategorized' category is not assigned to post ID {$post_id}." );
                $this->log_to_file( "'Uncategorized' category is not assigned to post ID {$post_id}." );
            }
        }

        /**
         * Logs messages to a dedicated log file for persistent records.
         *
         * @param string $message The message to log.
         */
        private function log_to_file( $message ) {
            $log_file = plugin_dir_path( __FILE__ ) . 'logs/datolab-auto-seo.log';

            // Ensure the logs directory exists
            if ( ! file_exists( dirname( $log_file ) ) ) {
                mkdir( dirname( $log_file ), 0755, true );
            }

            $date = date( 'Y-m-d H:i:s' );
            $formatted_message = "[{$date}] {$message}\n";
            error_log( $formatted_message, 3, $log_file );
        }
    }

    WP_CLI::add_command( 'datolab-auto-seo', __NAMESPACE__ . '\\Datolab_Auto_SEO_CLI_Command' );
}
