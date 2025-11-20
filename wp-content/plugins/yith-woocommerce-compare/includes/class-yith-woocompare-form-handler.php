<?php
/**
 * Static class that will handle all requests coming from forms or query-strings
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Form_Handler' ) ) {
	/**
	 * Form Handler
	 *
	 * @since 2.0.0
	 */
	class YITH_WooCompare_Form_Handler {

		use YITH_WooCompare_Trait_Requests_Handler;

		/**
		 * Constructor method
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			// shouldn't execute during ajax calls.
			if ( wp_doing_ajax() ) {
				return;
			}

			// handle form requests.
			add_action( 'wp_loaded', array( static::class, 'handle' ) );
		}

		/**
		 * Returns available AJAX call handlers
		 *
		 * @param string $context Context of the operation.
		 *
		 * @return array
		 */
		public static function get_handlers( $context = 'view' ) {
			if ( empty( self::$handlers ) ) {
				self::$handlers = array(
					'add-product',
					'remove-product',
				);
			}

			if ( 'view' === $context ) {
				/**
				 * APPLY_FILTERS: yith_woocompare_form_handlers
				 *
				 * Filters the form handlers.
				 *
				 * @param array $form_handlers Form handlers.
				 */
				return apply_filters( 'yith_woocompare_form_handlers', self::$handlers );
			}

			return self::$handlers;
		}

		/**
		 * Return current action being executed; false if none is found.
		 *
		 * @return string Action being processed
		 */
		public static function get_current_action() {
			return isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : false; // phpcs:ignore
		}

		/* === CALLBACKS === */

		/**
		 * AJAX handler that adds something to the compare list
		 */
		public static function add_product() {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['id'] ) ) {
				wp_die( -1 );
			}

			$product_id = (int) $_REQUEST['id'];
			$list       = YITH_WooCompare_Products_List::instance();
			$added      = $list->add( $product_id );

			$added && $list->maybe_save();

			wp_safe_redirect( esc_url( remove_query_arg( array( 'id', 'action' ) ) ) );
			die;
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 * AJAX handler that removes something to the compare list
		 */
		public static function remove_product() {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['id'] ) ) {
				wp_die( -1 );
			}

			$product_id = (int) $_REQUEST['id'];
			$list       = YITH_WooCompare_Products_List::instance();
			$removed    = $list->remove( $product_id );

			$removed && $list->maybe_save();

			wp_safe_redirect( esc_url( remove_query_arg( array( 'id', 'action' ) ) ) );
			die;
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/* === UTILS === */

		/**
		 * Returns an url to add a product to comparison table
		 *
		 * @param int $product_id Product to add to list.
		 * @return string Action url.
		 */
		public static function get_add_action_url( $product_id ) {
			return self::get_action_url( 'add-product', array( 'id' => $product_id ) );
		}

		/**
		 * Returns an url to remove a product to comparison table
		 *
		 * @param int $product_id Product to remove from list.
		 * @return string Action url.
		 */
		public static function get_remove_action_url( $product_id ) {
			return self::get_action_url( 'remove-product', array( 'id' => $product_id ) );
		}

		/**
		 * Returns action url for one of the registered handles
		 *
		 * @param string $handler_key Handler for which we need to create url.
		 * @param array  $params      Additional parameters to append to the url.
		 * @return string Action url.
		 */
		public static function get_action_url( $handler_key, $params = array() ) {
			$action      = self::get_handler_action( $handler_key );
			$filter_name = str_replace( '-', '_', $handler_key );
			$url_args    = array_merge(
				array(
					'action' => $action,
				),
				$params
			);

			/**
			 * APPLY_FILTERS: yith_woocompare_{$filter_name}_url
			 *
			 * Filters the URL for a specific action in the plugin.
			 *
			 * @param string $filter_name Dynamic part of the url, that depends on the action to perform.
			 * @param string $url         Action url.
			 * @param string $action      Action parameter in the query string.
			 * @param array  $url_args    URL arguments.
			 *
			 * @return string
			 */
			return apply_filters( "yith_woocompare_{$filter_name}_url", esc_url_raw( add_query_arg( $url_args, site_url() ) ), $action, $url_args );
		}

		/**
		 * Method called when a request shouldn't be processed for any reason
		 */
		protected static function deny_service() {
		}
	}
}
