<?php
/**
 * Trait that implements requests handling behaviour on a class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Compare\Traits
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! trait_exists( 'YITH_WooCompare_Trait_Requests_Handler' ) ) {
	/**
	 * This class implements a series of methods that lets an implementer define and manage a series of actions coming from different sources.
	 * Implementing this trait requires the extending class to define
	 * * get_handlers() to retrieve a list of supported handler
	 * * get_current_action() to retrieve action being executed at any given time
	 * * deny_service() to define an action to execute when system can't run an handler, even if it finds the action
	 *
	 * @since 2.0.0
	 */
	trait YITH_WooCompare_Trait_Requests_Handler {
		/**
		 * Handlers
		 *
		 * @var array
		 */
		protected static $handlers = array();

		/**
		 * Constructor method
		 *
		 * @since 2.0.0
		 */
		abstract public static function init();

		/**
		 * Returns available handlers
		 *
		 * @param string $context Context of the operation.
		 *
		 * @return array
		 */
		abstract public static function get_handlers( $context = 'view' );

		/**
		 * Returns action for an handler
		 *
		 * @param string $handler_key Handler to listen.
		 * @return string Action that triggers the execution of the handler
		 */
		public static function get_handler_action( $handler_key ) {
			/**
			 * APPLY_FILTERS: yith_woocompare_request_handler_action
			 *
			 * Filters the request handler action.
			 *
			 * @param string $action      form handler action.
			 * @param string $handler_key form handler.
			 */
			return apply_filters( 'yith_woocompare_request_handler_action', "yith-woocompare-$handler_key", $handler_key );
		}

		/**
		 * Returns default callback for an handler
		 *
		 * @param string $handler_key Handler to listen.
		 * @return callable Callable that handles request.
		 */
		public static function get_handler_callback( $handler_key ) {
			$class = static::class;

			if ( class_exists( "{$class}_Premium" ) ) {
				$class = "{$class}_Premium";
			}

			$callback = str_replace( '-', '_', $handler_key );

			return array( $class, $callback );
		}

		/**
		 * Return current action being executed; false if none is found.
		 */
		abstract public static function get_current_action();

		/**
		 * Handle all ajax calls, and execute specific callbacks when they satisfy minimum requirements.
		 */
		public static function handle() {
			$action = static::get_current_action();

			if ( ! $action ) {
				static::deny_service();
				return;
			}

			$handler     = false;
			$handlers    = static::get_handlers();
			$handler_key = str_replace( 'yith-woocompare-', '', $action );

			// retrieve handler from key.
			if ( isset( $handlers[ $handler_key ] ) ) {
				$handler = $handlers[ $handler_key ];
			} elseif ( in_array( $handler_key, $handlers, true ) ) {
				$handler = $handler_key;
			}

			// double checks that handler is one of the registered one.
			if ( ! $handler ) {
				static::deny_service();
				return;
			}

			// retrieve callbacks.
			$callback = is_array( $handler ) && isset( $handler['callback'] ) ? $handler['callback'] : self::get_handler_callback( $handler_key );

			// checks that callback is callable.
			if ( ! is_callable( $callback ) ) {
				static::deny_service();
				return;
			}

			// verify security nonce.
			if ( isset( $handler['nonce'] ) && ( ! isset( $_REQUEST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'yith_woocompare_' . $handler['nonce'] ) ) ) {
				static::deny_service();
				return;
			}

			// finally, call handler to process ajax call.
			call_user_func( $callback );
		}

		/* === UTILS === */

		/**
		 * Method called when a request shouldn't be processed
		 */
		abstract protected static function deny_service();
	}
}
