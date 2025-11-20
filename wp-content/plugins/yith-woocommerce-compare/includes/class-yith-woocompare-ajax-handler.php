<?php
/**
 * Static class that will handle all ajax calls for the plugin
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Ajax_Handler' ) ) {
	/**
	 * Ajax Handler
	 *
	 * @since 2.0.0
	 */
	class YITH_WooCompare_Ajax_Handler {

		use YITH_WooCompare_Trait_Requests_Handler;

		/**
		 * Use WC Ajax instead of WP Ajax for call handling.
		 *
		 * @var boolean
		 */
		protected static $use_wc_ajax;

		/**
		 * Performs all required add_actions to handle forms
		 *
		 * @return void
		 */
		public static function init() {
			// should execute only during ajax calls.
			if ( ! wp_doing_ajax() ) {
				return;
			}

			$handlers = static::get_handlers();
			$prefix   = self::should_use_wc_ajax() ? 'wc_ajax_' : 'wp_ajax_';

			if ( empty( $handlers ) ) {
				return;
			}

			foreach ( $handlers as $handler_key => $handler ) {
				if ( is_string( $handler ) ) {
					$action = self::get_handler_action( $handler );

					add_action( $prefix . $action, array( self::class, 'handle' ) );
				} elseif ( is_array( $handler ) ) {
					$action = isset( $handler['action'] ) ? $handler['action'] : self::get_handler_action( $handler_key );

					add_action( $prefix . $action, array( static::class, 'handle' ) );

					if ( ! empty( $handler['nopriv'] ) && ! self::should_use_wc_ajax() ) {
						add_action( 'wp_ajax_nopriv_' . $action, array( static::class, 'handle' ) );
					}
				} else {
					continue;
				}
			}
		}

		/**
		 * Returns true if we should use WC Ajax instead of WP Ajax, for ajax calls handling
		 *
		 * @return bool
		 */
		public static function should_use_wc_ajax() {
			if ( is_null( self::$use_wc_ajax ) ) {
				self::$use_wc_ajax = apply_filters( 'yith_woocompare_use_wc_ajax', true );
			}

			return self::$use_wc_ajax;
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
					'add-product'    => array(
						'nopriv' => true,
						'nonce'  => 'add_action',
					),
					'remove-product' => array(
						'nopriv' => true,
						'nonce'  => 'remove_action',
					),
					'reload-compare' => array(
						'nopriv' => true,
						'nonce'  => 'reload_action',
					),
				);
			}

			if ( 'view' === $context ) {
				/**
				 * APPLY_FILTERS: yith_woocompare_ajax_handlers
				 *
				 * Filters the AJAX handlers.
				 *
				 * @param array $ajax_handlers AJAX handlers.
				 */
				return apply_filters( 'yith_woocompare_ajax_handlers', self::$handlers );
			}

			return self::$handlers;
		}

		/**
		 * Return current action being executed; false if none is found.
		 *
		 * @return string Action being processed
		 */
		public static function get_current_action() {
			return preg_replace( '/^(wp|wc)_ajax_(nopriv_)?/', '', current_action() );
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
			$response   = array_merge(
				static::maybe_add_product( $product_id ),
				static::get_updated_templates()
			);

			wp_send_json( apply_filters( 'yith_woocompare_add_product_action_json', $response ) );
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

			$product_id = is_numeric( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : ( 'all' === $_REQUEST['id'] ? 'all' : 0 );

			$response = array_merge(
				self::maybe_remove_product( $product_id ),
				static::get_updated_templates()
			);

			wp_send_json( apply_filters( 'yith_woocompare_remove_product_action_json', $response ) );
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 * AJAX handler that provides updated HTML of the list
		 */
		public static function reload_compare() {
			$response = static::get_updated_templates();

			wp_send_json( apply_filters( 'yith_woocompare_reload_compare_action_json', $response ) );
		}

		/* === UTILS === */

		/**
		 * Performs additions to the list and returns an array describing the result
		 *
		 * @param int $product_id Product to add.
		 * @return array Details of the operation.
		 */
		protected static function maybe_add_product( $product_id ) {
			$list  = YITH_WooCompare_Products_List::instance();
			$added = $list->add( $product_id );

			$added && $list->maybe_save();

			return array(
				'table_url' => YITH_WooCompare_Frontend::instance()->get_table_url( $product_id ),
				'added'     => $added,
			);
		}

		/**
		 * Performs removal from the list and returns an array describing the result
		 *
		 * @param int $product_id Product to remove.
		 * @return array Details of the operation.
		 */
		protected static function maybe_remove_product( $product_id ) {
			$list    = YITH_WooCompare_Products_List::instance();
			$removed = 'all' === $product_id ? $list->empty() : $list->remove( $product_id );

			$removed && $list->maybe_save();

			return array(
				'removed' => $removed,
			);
		}

		/**
		 * Method called when a request shouldn't be processed
		 */
		protected static function deny_service() {
			wp_die( -1 );
		}

		/**
		 * Returns updated template to show up on frontend
		 *
		 * @return array Array containing update templates for table and widget.
		 */
		protected static function get_updated_templates() {
			$rendering_args = array(
				'iframe' => true,
			);

			return array(
				'table_html'       => YITH_WooCompare_Table::instance( $rendering_args )->get_template( 'table' ),
				'widget_html'      => YITH_WooCompare_Compare_Widget::output_content( $rendering_args, true ),
				'preview_bar_html' => YITH_WooCompare_Table::instance()->get_template( 'preview_bar' ),
			);
		}
	}
}
