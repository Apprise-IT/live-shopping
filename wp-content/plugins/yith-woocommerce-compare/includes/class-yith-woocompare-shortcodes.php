<?php
/**
 * Shortcodes handling class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Shortcodes' ) ) {
	/**
	 * Shortcodes handling class
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Shortcodes {
		/**
		 * List of supported shortcodes
		 *
		 * @var array
		 */
		protected static $supported = array(
			'button',
			'counter',
		);

		/**
		 * Initialize widget handling
		 */
		public static function init() {
			foreach ( static::$supported as $shortcode_id ) {
				$class = static::get_class( $shortcode_id );

				if ( ! $class || ! class_exists( $class ) || ! method_exists( $class, 'render' ) ) {
					continue;
				}

				add_shortcode( self::get_shortcode_tag( $shortcode_id ), array( $class, 'render' ) );
			}
		}

		/**
		 * Returns shortcode tag, given a specific shortcode id
		 *
		 * @param string $shortcode_id Shortcode id.
		 * @return string Shortcode tag.
		 */
		protected static function get_shortcode_tag( $shortcode_id ) {
			switch ( $shortcode_id ) {
				case 'button':
					return 'yith_compare_button';
				default:
					return "yith_woocompare_{$shortcode_id}";
			}
		}

		/**
		 * Retrieve class name for a specific shortcode id.
		 *
		 * @param string $shortcode_id Shortcode id.
		 * @return string|bool Class name, or false on failure.
		 */
		protected static function get_class( $shortcode_id ) {
			if ( ! in_array( $shortcode_id, static::$supported, true ) ) {
				return false;
			}

			return "YITH_WooCompare_{$shortcode_id}_Shortcode";
		}
	}
}
