<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

    /**
     * Class Datolab_Related_Posts_CLI_Command
     *
     * Handles WP-CLI commands for associating related posts based on shared categories and tags.
     */
    class Datolab_Related_Posts_CLI_Command extends WP_CLI_Command {

        /**
         * Searches for shared categories and tags among posts and creates records to associate them.
         *
         * ## EXAMPLES
         *
         *     wp datolab-related-posts associate
         *
         * @when after_wp_load
         *
         * @param array $args Positional arguments.
         * @param array $assoc_args Associative arguments.
         */
        public function associate( $args, $assoc_args ) {
            WP_CLI::log( 'Starting to associate related posts based on shared categories and tags.' );

            $posts = get_posts( array(
                'post_type'      => 'post',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ) );

            if ( empty( $posts ) ) {
                WP_CLI::success( 'No published posts found to process.' );
                return;
            }

            foreach ( $posts as $post ) {
                $related_posts = $this->find_related_posts( $post );

                if ( ! empty( $related_posts ) ) {
                    // Here you would typically create records in your database or a custom post type
                    // to associate the posts with each other. This is a placeholder for that logic.
                    WP_CLI::log( "Found " . count( $related_posts ) . " related posts for \"{$post->post_title}\"." );
                    // Example function call: $this->associate_posts( $post->ID, $related_posts );
                } else {
                    WP_CLI::log( "No related posts found for \"{$post->post_title}\"." );
                }
            }

            WP_CLI::success( 'Finished associating related posts.' );
        }

        /**
         * Finds related posts based on shared categories and tags.
         *
         * @param WP_Post $post The post to find related posts for.
         *
         * @return array An array of post IDs that are related.
         */
        private function find_related_posts( $post ) {
            $categories = wp_get_post_categories( $post->ID );
            $tags = wp_get_post_tags( $post->ID, array( 'fields' => 'ids' ) );

            $related_posts = array();

            if ( ! empty( $categories ) ) {
                $related_posts_by_cat = get_posts( array(
                    'category__in'   => $categories,
                    'posts_per_page' => -1,
                    'post__not_in'   => array( $post->ID ), // Exclude the current post
                    'fields'         => 'ids', // Only get post IDs to improve performance
                ) );

                $related_posts = array_merge( $related_posts, $related_posts_by_cat );
            }

            if ( ! empty( $tags ) ) {
                $related_posts_by_tag = get_posts( array(
                    'tag__in'        => $tags,
                    'posts_per_page' => -1,
                    'post__not_in'   => array( $post->ID ), // Exclude the current post
                    'fields'         => 'ids', // Only get post IDs to improve performance
                ) );

                $related_posts = array_merge( $related_posts, $related_posts_by_tag );
            }

            return array_unique( $related_posts );
        }

        // Placeholder for a method to associate posts with each other in your database or a custom post type.
        // private function associate_posts( $post_id, $related_posts ) {
        //     // Your logic to create records associating the posts goes here.
        // }
    }

    WP_CLI::add_command( 'datolab-related-posts', 'Datolab_Related_Posts_CLI_Command' );
}
