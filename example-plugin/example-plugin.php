<?php

/**
 * Plugin Name:       Example Plugin (Base)
 * Description:       A test plugin demonstrating UUPD_Updater integration.
 * Tested up to:      6.8.2
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Version:           0.9
 * Author:            Nathan Foley
 * Author URI:        https://reallyusefulplugins.com
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       example-plugin
 * Website:           https://reallyusefulplugins.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_VENDOR', 'ffffff' );
define( 'UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_VERSION', '1.0.0' );
define( 'UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_MAIN_FILE', __FILE__ );
define( 'UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_ITEM_NUMBER', 17 );
define( 'UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_FRIENDLY_NAME', 'Test Product' );
define( 'UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_SLUG', 'test-product' );
define( 'UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_SERVER_BASE', 'https://wooapi.tdlab.uk' );
define( 'UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_MENU_SLUG', UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_VENDOR . '-' . UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_SLUG );

add_action( 'plugins_loaded', function() {
    require_once __DIR__ . '/inc/updater.php';
    require_once __DIR__ . '/inc/class-uupd-license-ui.php';

    \UUPD\UI\License\V1\UUPD_License_UI::register([
        'vendor'         => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_VENDOR,
        'slug'           => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_SLUG,
        'item_id'        => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_ITEM_NUMBER,
        'license_server' => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_SERVER_BASE,
        'metadata_base'  => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_SERVER_BASE,
        'plugin_name'    => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_FRIENDLY_NAME,
        'menu_parent'    => 'top_level',
        'menu_slug'      => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_MENU_SLUG,
        'page_title'     => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_FRIENDLY_NAME . ' License',
        'menu_title'     => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_FRIENDLY_NAME . ' License',
        'capability'     => 'manage_options',
    ]);

    \UUPD\V2\UUPD_Updater_V2::register([
        'vendor'      => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_VENDOR,
        'plugin_file' => plugin_basename( UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_MAIN_FILE ),
        'slug'        => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_SLUG,
        'name'        => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_FRIENDLY_NAME,
        'version'     => UUPD_WOOAPI_FFFFFF_TEST_PRODUCT_17_VERSION,
        'server'      => '',
    ]);
}, 1 );


// 1. Add the settings page to the admin menu
add_action('admin_menu', 'esp_add_settings_page');
function esp_add_settings_page() {
    add_options_page(
        'Example Setting Page',     // Page title
        'Example Setting Page',     // Menu title
        'manage_options',           // Capability
        'esp-example-setting-page', // Menu slug
        'esp_render_settings_page'  // Callback
    );
}

// 2. Register the setting
add_action('admin_init', 'esp_register_settings');
function esp_register_settings() {
    register_setting(
        'esp_settings_group', // Option group
        'esp_text_option'     // Option name (stored in wp_options)
    );
}

// 3. Render the settings page
function esp_render_settings_page() {
    // Check capability
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>

    <div class="wrap">
        <h1>Example Setting Page</h1>

        <?php \UUPD\V1\UUPD_License_UI::render_box_for( UUPD_EXAMPLE_PLUGIN_SLUG ); ?>


        <form method="post" action="options.php">
            <?php
            // Output security fields for the registered setting
            settings_fields('esp_settings_group');

            // If you had sections, you'd call do_settings_sections() here
            // do_settings_sections('esp-example-setting-page');

            // Get existing value
            $text_value = get_option('esp_text_option', '');
            ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="esp_text_option">Example Text Field</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="esp_text_option"
                            name="esp_text_option"
                            value="<?php echo esc_attr($text_value); ?>"
                            class="regular-text"
                        />
                        <p class="description">Enter any text you like.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <?php
}
