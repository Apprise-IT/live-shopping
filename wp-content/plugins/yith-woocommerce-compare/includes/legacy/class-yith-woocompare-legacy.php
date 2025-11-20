<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Main legacy class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Legacy' ) ) {
	/**
	 * YITH Woocommerce Compare
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Legacy {

		/**
		 * Backward compatibility
		 * Allows third party code to retrieve legacy properties of this class
		 *
		 * @param string $key Key to retrieve.
		 * @return mixed Value of the property retrieved
		 */
		public function __get( $key ) {
			switch ( $key ) {
				case 'obj':
					if ( method_exists( $this, 'is_frontend' ) && $this->is_frontend() ) {
						return YITH_WooCompare_Frontend::instance();
					}
					if ( method_exists( $this, 'is_admin' ) && $this->is_admin() ) {
						return YITH_WooCompare_Admin::instance();
					}

					return $this;
				default:
					return $this->$key ?? false;
			}
		}
	}
}
