<?php
/**
 * Plugin Name: YITH WooCommerce Compare
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-compare/
 * Description: The <code><strong>YITH WooCommerce Compare</strong></code> plugin allow you to compare in a simple and efficient way products on sale in your shop and analyze their main features in a single table. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 3.5.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-woocommerce-compare
 * Domain Path: /languages/
 * WC requires at least: 10.1
 * WC tested up to: 10.3
 * Requires Plugins: woocommerce
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 3.5.0
 */

/*
Copyright 2013-2025 Your Inspiration Solutions (email : plugins@yithemes.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 *  === Define constants. ===
 */

defined( 'YITH_WOOCOMPARE_VERSION' ) || define( 'YITH_WOOCOMPARE_VERSION', '3.5.0' );
defined( 'YITH_WOOCOMPARE' ) || define( 'YITH_WOOCOMPARE', true );
defined( 'YITH_WOOCOMPARE_FREE_INIT' ) || define( 'YITH_WOOCOMPARE_FREE_INIT', plugin_basename( __FILE__ ) );
defined( 'YITH_WOOCOMPARE_SLUG' ) || define( 'YITH_WOOCOMPARE_SLUG', 'yith-woocommerce-compare' );
defined( 'YITH_WOOCOMPARE_URL' ) || define( 'YITH_WOOCOMPARE_URL', plugin_dir_url( __FILE__ ) );
defined( 'YITH_WOOCOMPARE_DIR' ) || define( 'YITH_WOOCOMPARE_DIR', plugin_dir_path( __FILE__ ) );
defined( 'YITH_WOOCOMPARE_FILE' ) || define( 'YITH_WOOCOMPARE_FILE', __FILE__ );
defined( 'YITH_WOOCOMPARE_ASSETS_URL' ) || define( 'YITH_WOOCOMPARE_ASSETS_URL', YITH_WOOCOMPARE_URL . 'assets/' );
defined( 'YITH_WOOCOMPARE_ASSETS_PATH' ) || define( 'YITH_WOOCOMPARE_ASSETS_PATH', YITH_WOOCOMPARE_DIR . 'assets/' );
defined( 'YITH_WOOCOMPARE_INCLUDE_PATH' ) || define( 'YITH_WOOCOMPARE_INCLUDE_PATH', YITH_WOOCOMPARE_DIR . 'includes/' );
defined( 'YITH_WOOCOMPARE_TEMPLATE_PATH' ) || define( 'YITH_WOOCOMPARE_TEMPLATE_PATH', YITH_WOOCOMPARE_DIR . 'templates/' );

/**
 * === Start the plugin up. ===
 */

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

// Plugin Framework Loader.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php';
}

/**
 * Prints error message if WooCommerce is not installed
 *
 * @since 1.0.0
 * @return void
 */
function yith_woocompare_install_woocommerce_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'YITH WooCommerce Compare is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-compare' ); ?></p>
	</div>
	<?php
}

/**
 * Error message if premium version is installed
 *
 * @since 1.0.0
 * @return void
 */
function yith_woocompare_install_free_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'You can\'t activate the free version of YITH WooCommerce Compare while you are using the premium one.', 'yith-woocommerce-compare' ); ?></p>
	</div>
	<?php
}

/**
 * Init plugin
 *
 * @since 1.0.0
 * @return void
 */
function yith_woocompare_constructor() {

	global $woocommerce;

	if ( ! isset( $woocommerce ) || ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'yith_woocompare_install_woocommerce_admin_notice' );
		return;
	} elseif ( defined( 'YITH_WOOCOMPARE_PREMIUM' ) ) {
		add_action( 'admin_notices', 'yith_woocompare_install_free_admin_notice' );
		deactivate_plugins( plugin_basename( __FILE__ ) );
		return;
	}

	if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
		yith_plugin_fw_load_plugin_textdomain( 'yith-woocommerce-compare', basename( __DIR__ ) . '/languages' );
	}

	// Load required classes and functions.
	require_once 'includes/functions.yith-woocompare.php';
	require_once 'includes/class-yith-woocompare-autoloader.php';

	// Let's start the game!
	global $yith_woocompare;
	$yith_woocompare = YITH_WooCompare::instance();
}
add_action( 'plugins_loaded', 'yith_woocompare_constructor', 11 );
