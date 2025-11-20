<?php
/**
 * Legacy Frontend class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Frontend_Legacy' ) ) {
	/**
	 * YITH Custom Login Frontend
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Frontend_Legacy {

		/**
		 * Backward compatibility
		 * Allows third party code to retrieve legacy properties of this class
		 *
		 * @param string $key Key to retrieve.
		 * @return mixed Value of the property retrieved
		 */
		public function __get( $key ) {
			switch ( $key ) {
				case 'use_wc_ajax':
					return YITH_WooCompare_Ajax_Handler::should_use_wc_ajax();
				default:
					return $this->$key ?? false;
			}
		}

		/* === RENAMED METHODS === */

		/**
		 * Prints or return compare button
		 *
		 * @param int   $product_id    Product id.
		 * @param array $args          Additional arguments for rendering.
		 * @param bool  $should_return Whether to return or output the content.
		 * @return string|null Template of the button, or nothing if method output the button.
		 */
		public function add_compare_link( $product_id = false, $args = array(), $should_return = false ) {
			if ( ! method_exists( $this, 'output_compare_button' ) ) {
				return null;
			}

			return $this->output_compare_button( $product_id, $args, $should_return );
		}

		/**
		 * Render the compare page
		 *
		 * @since 1.0.0
		 */
		public function compare_table_html() {
			$this->output_page();
		}

		/**
		 * The URL of product comparison table
		 *
		 * @since 1.4.0
		 * @param  bool | integer $product_id The product ID.
		 * @return string The url to add the product in the comparison table.
		 */
		public function view_table_url( $product_id = false ) {
			return $this->get_table_url( $product_id );
		}

		/* === METHODS MOVED TO LIST HANDLER === */

		/**
		 * Get cookie name
		 *
		 * @since 2.3.2
		 * @return string
		 */
		public function get_cookie_name() {
			return YITH_WooCompare_Products_List::get_cookie_name();
		}

		/**
		 * Add a product in the products comparison table
		 *
		 * @since 1.0.0
		 * @param int $product_id product ID to add in the comparison table.
		 * @return boolean
		 */
		public function add_product_to_compare( $product_id ) {
			$list = YITH_WooCompare_Products_List::instance();

			return $list->add( $product_id );
		}

		/**
		 * Remove a product from the comparison table
		 *
		 * @since 1.0.0
		 * @param integer $product_id The product ID to remove from the comparison table.
		 */
		public function remove_product_from_compare( $product_id ) {
			$list = YITH_WooCompare_Products_List::instance();

			if ( 'all' === $product_id ) {
				$list->empty();
			} else {
				$list->remove( $product_id );
			}
		}

		/* === METHODS MOVED TO FORM HANDLER CLASS === */

		/**
		 * The URL to add the product into the comparison table
		 *
		 * @since 1.0.0
		 * @param integer $product_id ID of the product to add.
		 * @return string The url to add the product in the comparison table
		 */
		public function add_product_url( $product_id ) {
			return YITH_WooCompare_Form_Handler::get_add_action_url( $product_id );
		}

		/**
		 * The URL to remove the product into the comparison table
		 *
		 * @since 1.0.0
		 * @param integer $product_id The ID of the product to remove.
		 * @return string The url to remove the product in the comparison table
		 */
		public function remove_product_url( $product_id ) {
			return YITH_WooCompare_Form_Handler::get_remove_action_url( $product_id );
		}

		/* === METHODS MOVED TO HELPER CLASS === */

		/**
		 * Get product categories
		 *
		 * @param integer $product_ids The product ID.
		 * @return mixed
		 */
		public function get_product_categories( $product_ids ) {
			return YITH_WooCompare_Helper::get_product_categories( $product_ids );
		}
	}
}
