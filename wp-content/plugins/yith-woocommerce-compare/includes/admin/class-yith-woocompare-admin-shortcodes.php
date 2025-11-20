<?php
/**
 * Admin Shortcodes class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Adimn
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Admin_Shortcodes' ) ) {
	/**
	 * Admin Shortcodes class.
	 * Register the post type and shows the panel
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Admin_Shortcodes {
		/**
		 * Post type that will hold comparison table
		 *
		 * @const string
		 */
		const POST_TYPE = 'comparison_table';

		/**
		 * Init this class
		 */
		public static function init() {
			self::register_post_type();

			// Add admin tabs.
			add_action( 'yith_woocompare_shortcode_tab', array( self::class, 'print_panel' ) );
			add_filter( 'manage_yith-plugins_page_yith_woocompare_panel_columns', array( self::class, 'add_screen_columns' ) );

			// Admin actions.
			add_action( 'admin_action_save_comparison_table', array( self::class, 'save_item' ) );
			add_action( 'admin_action_clone_comparison_table', array( self::class, 'clone_item' ) );
			add_action( 'admin_action_delete_comparison_table', array( self::class, 'delete_item' ) );
		}

		/**
		 * Register post_type for Comparison Tables
		 */
		protected static function register_post_type() {
			register_post_type(
				self::POST_TYPE,
				array(

					'labels'           => array(
						'name'          => __( 'Comparison tables', 'woocommerce' ),
						'singular_name' => __( 'Comparison table', 'woocommerce' ),
					),
					'public'           => false,
					'hierarchical'     => false,
					'rewrite'          => false,
					'query_var'        => false,
					'delete_with_user' => false,
					'can_export'       => true,
				)
			);
		}

		/**
		 * Add columns for Comparison Tables screen
		 *
		 * @param array $columns Coulmns for current screen.
		 * @return array FIltered list of columns
		 */
		public static function add_screen_columns( $columns ) {
			return array_merge(
				$columns,
				array(
					'title'     => __( 'Title', 'yith-woocommerce-compare' ),
					'shortcode' => __( 'Shortcode', 'yith-woocommerce-compare' ),
					'actions'   => __( 'Actions', 'yith-woocommerce-compare' ),
				)
			);
		}

		/**
		 * Print panel to show available Coparison Table
		 */
		public static function print_panel() {
			$table = new YITH_WooCompare_Admin_Shortcodes_Table();

			$table->prepare_items();
			$table->display();

			self::print_edit_modal();
		}

		/**
		 * Print modal to create/update a Comparison Table
		 */
		protected static function print_edit_modal() {
			$product_categories = get_terms(
				array(
					'id'       => 0,
					'title'    => '',
					'products' => array(),
				)
			);

			include YITH_WOOCOMPARE_TEMPLATE_PATH . 'admin/yith-woocompare-add-comparison-table-modal.php';
		}

		/* === ADMIN ACTIONS === */

		/**
		 * Returns url to the page where we show Comparison Tables.
		 *
		 * @return string
		 */
		public static function get_page_url() {
			return YITH_WooCompare_Admin::instance()->get_panel_url( 'shortcodes' );
		}

		/**
		 * Returns url to execute a specific action over a comparison table
		 *
		 * @param string $action_id Action to execute.
		 * @param array  $args      Array of additional arguments.
		 * @return string Url for the action
		 */
		public static function get_action_url( $action_id, $args = array() ) {
			$url = add_query_arg(
				array_merge(
					array( 'action' => "{$action_id}_comparison_table" ),
					$args
				),
				admin_url( 'admin.php' )
			);

			return wp_nonce_url( $url, $action_id, 'security' );
		}

		/**
		 * Save an item
		 */
		public static function save_item() {
			$post_id     = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : false;
			$title       = isset( $_REQUEST['title'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['title'] ) ) : false;
			$product_ids = isset( $_REQUEST['product_ids'] ) ? array_map( 'intval', (array) $_REQUEST['product_ids'] ) : false;
			$layout      = isset( $_REQUEST['layout'] ) && in_array( $_REQUEST['layout'], array( 'wide', 'compact' ), true ) ? sanitize_text_field( wp_unslash( $_REQUEST['layout'] ) ) : 'wide';

			if (
				! current_user_can( 'manage_options' ) ||
				! isset( $_REQUEST['security'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'save' )
			) {
				wp_safe_redirect( self::get_page_url() );
				die;
			}

			if ( $post_id ) {
				$post_data             = get_post( $post_id );
				$post_data->post_title = $title;
			} else {
				$post_data = array(
					'post_title'  => $title,
					'post_status' => 'publish',
					'post_type'   => self::POST_TYPE,
				);
			}

			$new_post_id = wp_insert_post( $post_data );

			if ( $new_post_id ) {
				update_post_meta( $new_post_id, 'yith_woocompare_product_ids', $product_ids );
				update_post_meta( $new_post_id, 'yith_woocompare_layout', $layout );
			}

			wp_safe_redirect( self::get_page_url() );
			die;
		}

		/**
		 * Clone an item
		 */
		public static function clone_item() {
			$post_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : false;
			$post    = $post_id ? get_post( $post_id ) : false;

			if (
				! current_user_can( 'manage_options' ) ||
				! isset( $_REQUEST['security'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'clone' )
			) {
				wp_safe_redirect( self::get_page_url() );
				die;
			}

			if ( $post ) {
				$products    = get_post_meta( $post->ID, 'yith_woocompare_product_ids', true );
				$layout      = get_post_meta( $post->ID, 'yith_woocompare_layout', true );
				$post->ID    = null;
				$new_post_id = wp_insert_post( $post );

				if ( ! is_wp_error( $new_post_id ) ) {
					update_post_meta( $new_post_id, 'yith_woocompare_product_ids', $products );
					update_post_meta( $new_post_id, 'yith_woocompare_layout', $layout );
				}
			}

			wp_safe_redirect( self::get_page_url() );
			die;
		}

		/**
		 * Clone an item
		 */
		public static function delete_item() {
			$post_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : false;
			$post    = $post_id ? get_post( $post_id ) : false;

			if (
				! current_user_can( 'manage_options' ) ||
				! isset( $_REQUEST['security'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'delete' )
			) {
				wp_safe_redirect( self::get_page_url() );
				die;
			}

			if ( $post ) {
				wp_delete_post( $post->ID, true );
			}

			wp_safe_redirect( self::get_page_url() );
			die;
		}
	}
}
