<?php
/**
 * Compare list class
 * stores and offer methods to manipulate list of products registered in the compare for current unser
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Products_List' ) ) {
	/**
	 * YITH Custom Login Frontend
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Products_List {

		use YITH_WooCompare_Trait_Singleton;

		/**
		 * The name of cookie name
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $cookie_name = 'YITH_WooCompare_Products_List';

		/**
		 * Query var where plugin will search for products to add to comparison list
		 *
		 * @var string
		 */
		protected static $query_var = 'yith_compare_prod';

		/**
		 * The list of products inside the comparison table
		 *
		 * @var int[]
		 */
		protected $products_list;

		/**
		 * Flag set when list needs to be saved inside the cookie
		 *
		 * @var bool
		 */
		protected $dirty = false;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'template_redirect', array( $this, 'maybe_save' ), 99 );
		}

		/* === STATIC PRODUCTS HANDLING === */

		/**
		 * Get cookie name
		 *
		 * @since 2.3.2
		 * @return string
		 */
		public static function get_cookie_name() {
			$suffix = '';

			if ( is_multisite() ) {
				$suffix = '_' . get_current_blog_id();
			} elseif ( '/' !== COOKIEPATH ) {
				$suffix = '_' . sanitize_title( COOKIEPATH );
			}

			return self::$cookie_name . $suffix;
		}

		/**
		 * Reads value stored in the cookie
		 *
		 * @return array Array of values stored for comparison.
		 */
		protected static function get_cookie_value() {
			$cookie_name = self::get_cookie_name();

			if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
				return array();
			}

			$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
			$cookie_value = (array) json_decode( $cookie_value );
			$cookie_value = array_filter( array_map( 'absint', $cookie_value ) );

			return apply_filters( 'yith_woocompare_cookie_value', $cookie_value );
		}

		/**
		 * Saves array of products to compare in the cookie
		 *
		 * @param array $value Array of product ids to store.
		 */
		public static function set_cookie( $value ) {
			setcookie( self::get_cookie_name(), wp_json_encode( $value ), 0, COOKIEPATH, COOKIE_DOMAIN, false, false );
		}

		/**
		 * Retrieves a list of product ids from query string
		 *
		 * @return array Array of values for comparison.
		 */
		public static function get_query_string_value() {
			$query_value = isset( $_REQUEST[ self::$query_var ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ self::$query_var ] ) ) : false; // phpcs:ignore

			if ( ! $query_value ) {
				return array();
			}

			return array_filter( array_map( 'absint', explode( ',', $query_value ) ) );
		}

		/* === LIST HANDLING === */

		/**
		 * Reads list of products saved for comparison, if needed
		 */
		public function maybe_read() {
			if ( ! is_null( $this->products_list ) ) {
				return;
			}

			$this->read();

			/**
			 * DO_ACTION: yith_woocompare_after_populate_product_list
			 *
			 * Allows to trigger some action after adding products to the compare list.
			 *
			 * @param array $products_list Products list.
			 */
			do_action( 'yith_woocompare_after_populate_product_list', $this->products_list );
		}

		/**
		 * Reads list of products saved for comparison, if needed
		 */
		public function read() {
			$product_ids = self::get_cookie_value();

			$valid_ids = array();

			// validate and verify ids read from cookie.
			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );

				if ( ! $product || ! $product->is_visible() ) {
					continue;
				}

				$valid_ids[] = $product_id;
			}

			// store valid ids inside class.
			/**
			 * APPLY_FILTERS: yith_woocompare_products_list
			 *
			 * Allows third party code (or integrations) to modify the list of products saved for comparison.
			 *
			 * @param array $products_list Products list.
			 */
			$this->products_list = apply_filters( 'yith_woocompare_products_list', $valid_ids );
		}

		/**
		 * Checks if a specific product is in list or not
		 *
		 * @param int $product_id Product id to test.
		 * @return bool Whether the product is in list or not
		 */
		public function has( $product_id ) {
			$this->maybe_read();

			return in_array( (int) $product_id, $this->products_list, true );
		}

		/**
		 * Returns the list of products saved for comparison
		 * Reads it from cookie when necessary
		 *
		 * @return array
		 */
		public function get() {
			$this->maybe_read();
			return $this->products_list;
		}

		/**
		 * Returns count of items in the list
		 * Reads it from cookie when necessary
		 *
		 * @return int
		 */
		public function count() {
			$this->maybe_read();
			return count( $this->products_list );
		}

		/**
		 * Adds a new item to the list
		 *
		 * @param int $product_id Product to add to the list.
		 * @return bool Status of the operation.
		 */
		public function add( $product_id ) {
			if ( headers_sent() ) {
				wc_doing_it_wrong( __METHOD__, 'It should be invoked before sending output headers, so that system can still set the cookie', '2.0.0' );
			}

			$this->maybe_read();

			$product_id = (int) $product_id;

			if ( in_array( $product_id, $this->products_list, true ) ) {
				return false;
			}

			$this->products_list[] = $product_id;
			$this->dirty           = true;

			/**
			 * DO_ACTION: yith_woocompare_after_add_product
			 *
			 * Allows to trigger some action after the product has been added to the comparison table.
			 *
			 * @param int $product_id Product ID.
			 */
			do_action( 'yith_woocompare_after_add_product', $product_id );

			$this->maybe_save();
			return true;
		}

		/**
		 * Removes a product from the list
		 *
		 * @param int $product_id Product to remove from comparison list.
		 * @return bool status of the operation.
		 */
		public function remove( $product_id ) {
			if ( headers_sent() ) {
				wc_doing_it_wrong( __METHOD__, 'It should be invoked before sending output headers, so that system can still set the cookie', '2.0.0' );
			}

			$this->maybe_read();

			$product_id = (int) $product_id;

			if ( ! in_array( $product_id, $this->products_list, true ) ) {
				return false;
			}

			$item_index = array_search( $product_id, $this->products_list, true );

			unset( $this->products_list[ $item_index ] );
			$this->dirty = true;

			/**
			 * DO_ACTION: yith_woocompare_after_remove_product
			 *
			 * Allows to trigger some action after the product has been removed from the comparison table.
			 *
			 * @param int $product_id Product ID.
			 */
			do_action( 'yith_woocompare_after_remove_product', $product_id );

			return true;
		}

		/**
		 * Removes all products from the list
		 *
		 * @return bool status of the operation
		 */
		public function empty() {
			$this->maybe_read();

			if ( empty( $this->products_list ) ) {
				return false;
			}

			$this->products_list = array();
			$this->dirty         = true;

			/**
			 * DO_ACTION: yith_woocompare_after_remove_product
			 *
			 * Allows to trigger some action after the product has been removed from the comparison table.
			 *
			 * @param int $product_id Product ID.
			 */
			do_action( 'yith_woocompare_after_remove_product', 'all' );

			return true;
		}

		/**
		 * Performed at shutdown, saves list of roducts added to comparison list when a change happened
		 */
		public function maybe_save() {
			if ( ! $this->dirty ) {
				return;
			}

			// save new array of items inside the cookie.
			self::set_cookie( $this->products_list );

			// reset status flag.
			$this->dirty = false;
		}
	}
}
