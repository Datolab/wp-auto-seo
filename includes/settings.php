<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Datolab_Auto_SEO_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_page() {
        add_options_page(
            'Datolab Auto SEO Settings',    // Page title
            'Datolab Auto SEO',             // Menu title
            'manage_options',               // Capability
            'datolab-auto-seo-settings',    // Menu slug
            array( $this, 'create_settings_page' ) // Callback function
        );
    }

    public function create_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Datolab Auto SEO Settings', 'datolab-auto-seo' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                    // Output security fields for the registered setting "datolab_auto_seo_settings_group"
                    settings_fields( 'datolab_auto_seo_settings_group' );

                    // Output setting sections and their fields
                    do_settings_sections( 'datolab-auto-seo-settings' );

                    // Output save settings button
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        // Register the settings for OpenAI API key
        register_setting(
            'datolab_auto_seo_settings_group',           // Option group
            'datolab_auto_seo_openai_api_key',          // Option name (unique)
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        // Register the settings for Cohere API key
        register_setting(
            'datolab_auto_seo_settings_group',           // Option group
            'datolab_auto_seo_cohere_api_key',          // Option name (unique)
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        // Register the settings for Anthropic API key
        register_setting(
            'datolab_auto_seo_settings_group',           // Option group
            'datolab_auto_seo_anthropic_api_key',       // Option name (unique)
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        // Add a new section in the settings page
        add_settings_section(
            'datolab_auto_seo_main_section',            // Section ID
            'API Configuration',                         // Section title
            array( $this, 'main_section_callback' ),    // Callback function
            'datolab-auto-seo-settings'                 // Page slug
        );

        // Add fields for OpenAI API key
        add_settings_field(
            'openai_api_key',                             // Field ID
            'OpenAI API Key',                             // Field title
            array( $this, 'openai_api_key_callback' ),    // Callback function
            'datolab-auto-seo-settings',                  // Page slug
            'datolab_auto_seo_main_section'               // Section ID
        );

        // Add fields for Cohere API key
        add_settings_field(
            'cohere_api_key',                             // Field ID
            'Cohere API Key',                             // Field title
            array( $this, 'cohere_api_key_callback' ),    // Callback function
            'datolab-auto-seo-settings',                  // Page slug
            'datolab_auto_seo_main_section'               // Section ID
        );

        // Add fields for Anthropic API key
        add_settings_field(
            'anthropic_api_key',                          // Field ID
            'Anthropic API Key',                          // Field title
            array( $this, 'anthropic_api_key_callback' ), // Callback function
            'datolab-auto-seo-settings',                  // Page slug
            'datolab_auto_seo_main_section'               // Section ID
        );
    }

    public function main_section_callback() {
        echo '<p>' . esc_html__( 'Enter your API keys to enable automatic SEO tag and category generation.', 'datolab-auto-seo' ) . '</p>';
    }

    public function openai_api_key_callback() {
        $api_key = get_option( 'datolab_auto_seo_openai_api_key', '' );
        echo '<input type="password" id="openai_api_key" name="datolab_auto_seo_openai_api_key" value="' . esc_attr( $api_key ) . '" size="50" />';
        $this->add_toggle_reset_buttons('openai_api_key');
    }

    public function cohere_api_key_callback() {
        $api_key = get_option( 'datolab_auto_seo_cohere_api_key', '' );
        echo '<input type="password" id="cohere_api_key" name="datolab_auto_seo_cohere_api_key" value="' . esc_attr( $api_key ) . '" size="50" />';
        $this->add_toggle_reset_buttons('cohere_api_key');
    }

    public function anthropic_api_key_callback() {
        $api_key = get_option( 'datolab_auto_seo_anthropic_api_key', '' );
        echo '<input type="password" id="anthropic_api_key" name="datolab_auto_seo_anthropic_api_key" value="' . esc_attr( $api_key ) . '" size="50" />';
        $this->add_toggle_reset_buttons('anthropic_api_key');
    }

    private function add_toggle_reset_buttons($field_id) {
        echo '<button type="button" id="toggle_' . $field_id . '" style="margin-left:10px;">' . esc_html__( 'Show', 'datolab-auto-seo' ) . '</button>';
        echo '<button type="button" id="reset_' . $field_id . '" style="margin-left:10px;">' . esc_html__( 'Reset API Key', 'datolab-auto-seo' ) . '</button>';
        echo '<p class="description">' . esc_html__( 'Your API key is stored securely. You can reveal it by clicking the "Show" button or reset it if needed.', 'datolab-auto-seo' ) . '</p>';
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                var toggleButton = document.getElementById('toggle_<?php echo $field_id; ?>');
                if (toggleButton) {
                    toggleButton.addEventListener('click', function() {
                        var apiKeyField = document.getElementById('<?php echo $field_id; ?>');
                        if (apiKeyField.type === 'password') {
                            apiKeyField.type = 'text';
                            toggleButton.textContent = '<?php echo esc_js( __( "Hide", "datolab-auto-seo" ) ); ?>';
                        } else {
                            apiKeyField.type = 'password';
                            toggleButton.textContent = '<?php echo esc_js( __( "Show", "datolab-auto-seo" ) ); ?>';
                        }
                    });
                }

                var resetButton = document.getElementById('reset_<?php echo $field_id; ?>');
                if (resetButton) {
                    resetButton.addEventListener('click', function() {
                        if (confirm('<?php echo esc_js( __( "Are you sure you want to reset your API key? This action cannot be undone.", "datolab-auto-seo" ) ); ?>')) {
                            var apiKeyField = document.getElementById('<?php echo $field_id; ?>');
                            apiKeyField.value = '';
                            toggleButton = document.getElementById('toggle_<?php echo $field_id; ?>');
                            if (toggleButton) {
                                toggleButton.textContent = '<?php echo esc_js( __( "Show", "datolab-auto-seo" ) ); ?>';
                                apiKeyField.type = 'password';
                            }
                        }
                    });
                }
            });
        </script>
        <?php
    }
}

new Datolab_Auto_SEO_Settings();
