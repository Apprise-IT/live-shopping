<?php
/**
 * Compare button shortcode handling
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Button_Shortcode' ) ) {
	/**
	 * Compare button shortcode handling class
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Button_Shortcode {
		/**
		 * Renders the compare button
		 *
		 * @param array  $atts    Array of additional attributes for the shortcode rendering.
		 * @param string $content Shortcode content.
		 * @return string Shortcode template.
		 */
		public static function render( $atts = array(), $content = null ) {
			$atts = shortcode_atts(
				array(
					'product'     => false,
					'type'        => 'default',
					'container'   => 'yes',
					'button_text' => false,
				),
				$atts
			);

			$product_id = self::get_product_id( $atts );

			// couldn't find a product, return.
			if ( empty( $product_id ) ) {
				return '';
			}

			$content = isset( $atts['button_text'] ) ? $atts['button_text'] : $content;
			$button  = YITH_WooCompare_Frontend::instance()->output_button(
				$product_id,
				array(
					'button_or_link' => ( 'default' === $atts['type'] ? false : $atts['type'] ),
					'button_text'    => empty( $content ) ? 'default' : $content,
				),
				true
			);

			if ( yith_plugin_fw_is_true( $atts['container'] ) ) {
				$button = <<<EOHTML
					<div class="woocommerce product compare-button">
						$button
					</div>
				EOHTML;

			}

			return $button;
		}

		/**
		 * Returns the product id to use for shortcode rendering, basing on attributes
		 *
		 * @param array $atts Attributes passed for shortcode rendering.
		 * @return int Product id found; 0 on error.
		 */
		protected static function get_product_id( $atts ) {
			$product_id = 0;

			/**
			 * Retrieve the product ID in these steps:
			 * - If "product" attribute is not set, get the product ID of current product loop
			 * - If "product" contains an integer, use product with that ID
			 * - If "product" attribute contains a string, search for products with that slug or post title
			 */
			if ( ! $atts['product'] ) {
				global $product;
				$product_id = ! is_null( $product ) && $product instanceof WC_Product ? $product->get_id() : 0;
			} elseif ( is_numeric( $atts['product'] ) ) {
				$product_id = (int) $atts['product'];
			} elseif ( is_string( $atts['product'] ) ) {
				global $wpdb;
				$product_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND ( post_name = %s OR post_title = %s ) LIMIT 1", 'product', $atts['product'], $atts['product'] ) ); // phpcs:ignore
			}

			return apply_filters( 'yith_woocompare_button_shortcode_product_id', $product_id, $atts );
		}
	}
}
