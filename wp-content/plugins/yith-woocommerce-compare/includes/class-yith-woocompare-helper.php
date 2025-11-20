<?php
/**
 * Helper class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Helper' ) ) {
	/**
	 * YITH Woocommerce Compare helper
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Helper {

		/**
		 * Get product categories
		 *
		 * @param integer $product_ids The product ID.
		 * @return mixed
		 */
		public static function get_product_categories( $product_ids ) {
			$formatted   = array();
			$categories  = array();
			$product_ids = (array) $product_ids;

			foreach ( $product_ids as $id ) {
				$single_cat = get_the_terms( $id, 'product_cat' );

				if ( empty( $single_cat ) ) {
					continue;
				}
				// Get values.
				$categories = array_merge( $categories, array_values( $single_cat ) );
			}

			if ( empty( $categories ) ) {
				return $formatted;
			}

			foreach ( $categories as $category ) {
				if ( ! $category ) {
					continue;
				}
				$formatted[ $category->term_id ] = $category->name;
			}

			/**
			 * APPLY_FILTERS: yith_woocompare_get_product_categories
			 *
			 * Filters the product categories of the products in the comparison table.
			 *
			 * @param array $formatted        Array with the category data formatted.
			 * @param array $categories Product categories.
			 * @param int   $product_id Product ID.
			 *
			 * @return array
			 */
			return apply_filters( 'yith_woocompare_get_product_categories', $formatted, $categories, $product_ids );
		}

		/* === ATTRIBUTES METHODS ==== */

		/**
		 * Get Woocommerce Attribute Taxonomies
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public static function get_attribute_taxonomies() {
			$attributes           = array();
			$attribute_taxonomies = wc_get_attribute_taxonomies();

			if ( ! empty( $attribute_taxonomies ) ) {
				foreach ( $attribute_taxonomies as $attribute ) {
					$tax = wc_attribute_taxonomy_name( $attribute->attribute_name );
					if ( taxonomy_exists( $tax ) ) {
						$attributes[ $tax ] = ucfirst( $attribute->attribute_label );
					}
				}
			}

			return $attributes;
		}

		/**
		 * The list of standard fields
		 *
		 * @since 1.0.0
		 * @access public
		 * @param boolean $with_attr Include attribute taxonomies.
		 * @return array
		 */
		public static function get_default_table_fields( $with_attr = true ) {

			$fields = array(
				'title'         => __( 'Title', 'yith-woocommerce-compare' ),
				'image'         => __( 'Image', 'yith-woocommerce-compare' ),
				'add_to_cart'   => __( 'Add to cart', 'yith-woocommerce-compare' ),
				'price'         => __( 'Price', 'yith-woocommerce-compare' ),
				'rating'        => __( 'Rating', 'yith-woocommerce-compare' ),
				'description'   => __( 'Description', 'yith-woocommerce-compare' ),
				'sku'           => __( 'SKU', 'yith-woocommerce-compare' ),
				'stock'         => __( 'Availability', 'yith-woocommerce-compare' ),
				'weight'        => __( 'Weight', 'yith-woocommerce-compare' ),
				'dimensions'    => __( 'Dimensions', 'yith-woocommerce-compare' ),
				'price_2'       => __( 'Repeat price', 'yith-woocommerce-compare' ),
				'add_to_cart_2' => __( 'Repeat Add to cart', 'yith-woocommerce-compare' ),
			);

			if ( $with_attr ) {
				$fields = array_merge( $fields, self::get_attribute_taxonomies() );
			}

			/**
			 * APPLY_FILTERS: yith_woocompare_standard_fields_array
			 *
			 * Filters the list of standard fields to use in the comparison table.
			 *
			 * @param array $fields Array of fields.
			 *
			 * @return array
			 */
			return apply_filters( 'yith_woocompare_standard_fields_array', $fields );
		}

		/* === TEST METHODS === */

		/**
		 * Checks if we're in a specific context, by searching for context param in the request
		 *
		 * @param string|string[] $context_to_check Context(s) to match against request.
		 * @return bool Whether request context isset and matches the one passed.
		 */
		public static function is_context( $context_to_check ) {
			$context = isset( $_REQUEST['context'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['context'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification

			return $context && in_array( $context, (array) $context_to_check, true );
		}

		/**
		 * Checks if we're in a specific action, by searching for action param in the request
		 *
		 * @param string|string[] $action_to_check Action(s) to match against request.
		 * @return bool Whether request action isset and matches the one passed.
		 */
		public static function is_action( $action_to_check ) {
			$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification

			return $action && in_array( $action, (array) $action_to_check, true );
		}
	}
}
