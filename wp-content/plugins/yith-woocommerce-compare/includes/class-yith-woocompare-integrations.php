<?php
/**
 * Static class that will handle integrations with third party plugins
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Integrations' ) ) {
	/**
	 * Compare Integrations Handler
	 *
	 * @since 2.0.0
	 */
	class YITH_WooCompare_Integrations {

		/**
		 * Available Integrations
		 *
		 * @var array
		 */
		protected static $integrations = array();

		/**
		 * Performs all required add_actions to handle integrations
		 *
		 * @return void
		 */
		public static function init() {
			$integrations = self::get_integrations();

			if ( empty( $integrations ) ) {
				return;
			}

			foreach ( $integrations as $plugin_id => $integration_details ) {
				if ( isset( $integration_details['condition'] ) && ! call_user_func( $integration_details['condition'] ) ) {
					continue;
				}
				if ( isset( $integration_details['class'] ) ) {
					$class_name = $integration_details['class'];
				} else {
					$plugin_name = str_replace( '-', '_', $plugin_id );
					$class_name  = "YITH_WooCompare_{$plugin_name}_Integration";
				}

				if ( ! class_exists( $class_name ) ) {
					continue;
				}

				$class_name::init();
			}
		}

		/**
		 * Returns available integrations
		 *
		 * @return array
		 */
		public static function get_integrations() {
			if ( empty( self::$integrations ) ) {
				self::$integrations = array(
					'wpml'                  => array(
						'condition' => fn () => defined( 'ICL_PLUGIN_PATH' ),
					),
					'elementor'             => array(
						'condition' => fn () => defined( 'ELEMENTOR_VERSION' ),
					),
					'yith-badge-management' => array(
						'condition' => fn () => defined( 'YITH_WCBM_VERSION' ),
					),
					'yith-request-quote'    => array(
						'condition' => fn () => defined( 'YITH_YWRAQ_VERSION' ),
					),
					'yith-color-labels'     => array(
						'condition' => fn () => defined( 'YITH_WCCL_PREMIUM' ),
					),
					'yith-multi-vendor'     => array(
						'condition' => fn () => defined( 'YITH_WPV_PREMIUM' ),
					),
					'woocommerce-filters'   => array(
						'condition' => fn () => class_exists( 'WooCommerce_Product_Filters' ),
					),
					'woocommerce-bundles'   => array(
						'condition' => fn () => function_exists( 'WC_PB' ),
					),
				);
			}

			/**
			 * APPLY_FILTERS: yith_woocompare_integrations
			 *
			 * Filters the available plugin integrations.
			 *
			 * @param array $integrations Available integrations.
			 */
			return apply_filters( 'yith_woocompare_integrations', self::$integrations );
		}
	}
}
