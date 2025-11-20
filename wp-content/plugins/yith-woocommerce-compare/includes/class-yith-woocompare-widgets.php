<?php
/**
 * Widgets handling class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Widgets' ) ) {
	/**
	 * Widgets handling class
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Widgets {
		/**
		 * List of supported widgets
		 *
		 * @var array
		 */
		protected static $supported = array(
			'compare',
			'counter',
		);

		/**
		 * Initialize widget handling
		 */
		public static function init() {
			add_action( 'widgets_init', array( __CLASS__, 'register' ) );
		}

		/**
		 * Register available widgets for this plugin
		 */
		public static function register() {
			foreach ( self::$supported as $widget_id ) {
				register_widget( self::get_class( $widget_id ) );
			}
		}

		/**
		 * Retrieve class name for a specific widget id.
		 *
		 * @param string $widget_id Widget id.
		 * @return string|bool Class name, or false on failure.
		 */
		protected static function get_class( $widget_id ) {
			if ( ! in_array( $widget_id, self::$supported, true ) ) {
				return false;
			}

			return "YITH_WooCompare_{$widget_id}_Widget";
		}
	}
}
