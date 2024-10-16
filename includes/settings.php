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
        // Register the setting with the correct option group name and unique option name
        register_setting(
            'datolab_auto_seo_settings_group',           // Option group
            'datolab_auto_seo_openai_api_key',          // Option name (unique)
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        // Add a new section in the settings page
        add_settings_section(
            'datolab_auto_seo_main_section',            // Section ID
            'OpenAI Configuration',                     // Section title
            array( $this, 'main_section_callback' ),    // Callback function
            'datolab-auto-seo-settings'                 // Page slug
        );

        // Add a new field in the section
        add_settings_field(
            'openai_api_key',                             // Field ID
            'OpenAI API Key',                             // Field title
            array( $this, 'openai_api_key_callback' ),    // Callback function
            'datolab-auto-seo-settings',                  // Page slug
            'datolab_auto_seo_main_section'               // Section ID
        );
    }

    public function main_section_callback() {
        echo '<p>' . esc_html__( 'Enter your OpenAI API key to enable automatic SEO tag and category generation.', 'datolab-auto-seo' ) . '</p>';
    }

    public function openai_api_key_callback() {
        // Retrieve the existing value from the database
        $api_key = get_option( 'datolab_auto_seo_openai_api_key', '' );

        // Output the input field with type password to mask the API key
        echo '<input type="password" id="openai_api_key" name="datolab_auto_seo_openai_api_key" value="' . esc_attr( $api_key ) . '" size="50" />';
        
        // Add a button to toggle visibility of the API key
        echo '<button type="button" id="toggle_api_key" style="margin-left:10px;">' . esc_html__( 'Show', 'datolab-auto-seo' ) . '</button>';
        echo '<button type="button" id="reset_api_key" style="margin-left:10px;">' . esc_html__( 'Reset API Key', 'datolab-auto-seo' ) . '</button>';
        echo '<p class="description">' . esc_html__( 'Your OpenAI API key is stored securely. You can reveal it by clicking the "Show" button or reset it if needed.', 'datolab-auto-seo' ) . '</p>';
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                var toggleButton = document.getElementById('toggle_api_key');
                if (toggleButton) {
                    toggleButton.addEventListener('click', function() {
                        var apiKeyField = document.getElementById('openai_api_key');
                        if (apiKeyField.type === 'password') {
                            apiKeyField.type = 'text';
                            toggleButton.textContent = '<?php echo esc_js( __( "Hide", "datolab-auto-seo" ) ); ?>';
                        } else {
                            apiKeyField.type = 'password';
                            toggleButton.textContent = '<?php echo esc_js( __( "Show", "datolab-auto-seo" ) ); ?>';
                        }
                    });
                }

                var resetButton = document.getElementById('reset_api_key');
                if (resetButton) {
                    resetButton.addEventListener('click', function() {
                        if (confirm('<?php echo esc_js( __( "Are you sure you want to reset your OpenAI API key? This action cannot be undone.", "datolab-auto-seo" ) ); ?>')) {
                            var apiKeyField = document.getElementById('openai_api_key');
                            apiKeyField.value = '';
                            toggleButton = document.getElementById('toggle_api_key');
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