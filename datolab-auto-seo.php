<?php
/**
 * Plugin Name: Datolab Auto SEO with OpenAI
 * Description: Automatically generates SEO-optimized metadata for draft posts using OpenAI.
 * Version: 1.0.0
 * Author: Datolab LLC 
 * Author URI: https://www.datolab.com/
 * Text Domain: datolab-auto-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add Settings link to the plugin's action links in the Plugins list.
 *
 * @param array $links Existing action links.
 * @return array Modified action links with the Settings link added.
 */
function datolab_auto_seo_add_settings_link( $links ) {
    // Define the URL to your settings page.
    // Ensure that 'datolab-auto-seo-settings' matches the slug used in your settings page.
    $settings_url = admin_url( 'options-general.php?page=datolab-auto-seo-settings' );

    // Create the Settings link.
    $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'datolab-auto-seo' ) . '</a>';

    // Add the Settings link to the beginning of the action links array.
    array_unshift( $links, $settings_link );

    return $links;
}

// Hook the 'plugin_action_links_{plugin_basename}' filter to add the Settings link.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'datolab_auto_seo_add_settings_link' );

// Include necessary files.
require_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-datolab-auto-seo-cli.php';