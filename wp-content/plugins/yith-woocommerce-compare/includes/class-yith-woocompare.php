<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Main class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare' ) ) {
	/**
	 * YITH Woocommerce Compare
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare extends YITH_WooCompare_Legacy {

		use YITH_WooCompare_Trait_Singleton;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// init plugin.
			add_action( 'init', array( $this, 'init' ), 0 );

			// Load Plugin Framework.
			add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );
		}

		/**
		 * Init plugin
		 *
		 * @since 2.0.0
		 */
		public function init() {
			// let's create instance of the correct class, depending on the context.
			$this->is_frontend() && YITH_WooCompare_Frontend::instance();
			$this->is_admin() && YITH_WooCompare_Admin::instance();

			// let's init common classes.
			YITH_WooCompare_Install::init();
			YITH_WooCompare_Shortcodes::init();
			YITH_WooCompare_Form_Handler::init();
			YITH_WooCompare_Ajax_Handler::init();
			YITH_WooCompare_Widgets::init();
			YITH_WooCompare_Integrations::init();
		}

		/**
		 * Detect if is frontend
		 *
		 * @return bool
		 */
		public function is_frontend() {
			/**
			 * APPLY_FILTERS: yith_woocompare_actions_to_check_frontend
			 *
			 * Filters the actions to check to load the required files, for better compatibility with third-party software.
			 *
			 * @param array $actions Actions to check.
			 *
			 * @return array
			 */
			$actions_to_check = apply_filters( 'yith_woocompare_actions_to_check_frontend', array( 'woof_draw_products', 'prdctfltr_respond_550', 'wbmz_get_products', 'jet_smart_filters', 'productfilter' ) );

			$is_frontend =
				! is_admin() ||
				( class_exists( 'YITH_WooCompare_Elementor_Integration' ) && YITH_WooCompare_Elementor_Integration::is_elementor_editor() ) ||
				( is_ajax() && YITH_Woocompare_Helper::is_action( $actions_to_check ) ) ||
				( is_ajax() && YITH_Woocompare_Helper::is_context( 'frontend' ) );

			/**
			 * APPLY_FILTERS: yith_woocompare_check_is_frontend
			 *
			 * Filters whether the current request is made for a frontend page/request.
			 *
			 * @param bool $is_frontend Whether the request is made for a frontend page or not.
			 * @return bool
			 */
			return apply_filters( 'yith_woocompare_check_is_frontend', $is_frontend );
		}

		/**
		 * Detect if is admin
		 *
		 * @return bool
		 */
		public function is_admin() {
			$is_admin =
				is_admin() ||
				( is_ajax() && YITH_Woocompare_Helper::is_context( 'admin' ) );

			/**
			 * APPLY_FILTERS: yith_woocompare_check_is_admin
			 *
			 * Filters whether the current request is made for an admin page.
			 *
			 * @param bool $is_admin Whether the request is made for an admin page or not.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_woocompare_check_is_admin', $is_admin );
		}

		/**
		 * Declare support for WooCommerce features.
		 *
		 * @since 2.26.0
		 */
		public function declare_wc_features_support() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				$init = defined( 'YITH_WOOCOMPARE_FREE_INIT' ) ? YITH_WOOCOMPARE_FREE_INIT : false;
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $init, true );
			}
		}
	}
}
