<?php
/**
 * Frontend class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Frontend' ) ) {
	/**
	 * YITH Custom Login Frontend
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Frontend extends YITH_WooCompare_Frontend_Legacy {

		use YITH_WooCompare_Trait_Singleton;

		/**
		 * Stylesheet file
		 *
		 * @since 2.1.0
		 * @var string
		 */
		public $stylesheet_file = 'compare.css';

		/**
		 * The action used to view the table comparison
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $action_view = 'yith-woocompare-view-table';

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// show Compare/View compare button on frontend.
			add_action( 'init', array( $this, 'add_compare_button' ) );

			// show Compare page.
			add_action( 'template_redirect', array( $this, 'output_page' ) );

			// enqueue required assets.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Enqueue the scripts and styles in the page
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {
			$min = ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '';

			// register needed dependencies.
			wp_register_script( 'jquery-fixedheadertable', YITH_WOOCOMPARE_ASSETS_URL . 'js/jquery.dataTables.min.js', array( 'jquery' ), '1.10.18', true );
			wp_register_script( 'jquery-fixedcolumns', YITH_WOOCOMPARE_ASSETS_URL . 'js/FixedColumns.min.js', array( 'jquery', 'jquery-fixedheadertable' ), '3.2.6', true );
			wp_register_script( 'jquery-imagesloaded', YITH_WOOCOMPARE_ASSETS_URL . 'js/imagesloaded.pkgd.min.js', array( 'jquery' ), '3.1.8', true );

			// Enqueue and add localize.
			wp_register_script( 'yith-woocompare-main', YITH_WOOCOMPARE_ASSETS_URL . 'js/woocompare' . $min . '.js', array( 'jquery', 'jquery-fixedheadertable', 'jquery-fixedcolumns', 'jquery-imagesloaded' ), YITH_WOOCOMPARE_VERSION, true );
			wp_localize_script( 'yith-woocompare-main', 'yith_woocompare', $this->get_script_localize() );

			// compare style.
			wp_register_style( 'jquery-fixedheadertable-style', YITH_WOOCOMPARE_ASSETS_URL . 'css/jquery.dataTables.css', array(), '1.10.18', 'all' );
			wp_enqueue_style( 'yith_woocompare_page', $this->stylesheet_url(), array( 'jquery-fixedheadertable-style' ), YITH_WOOCOMPARE_VERSION, 'all' );

			// Widget.
			wp_enqueue_style( 'yith-woocompare-widget', YITH_WOOCOMPARE_ASSETS_URL . 'css/widget.css', array(), YITH_WOOCOMPARE_VERSION );
		}

		/**
		 * Returns list of arguments used to localize plugin's main script
		 *
		 * @return array
		 */
		protected function get_script_localize() {
			// Localize script args.
			$args = array(
				'actions'                                  => array(
					'view'   => $this->action_view,
					'add'    => YITH_WooCompare_Ajax_Handler::get_handler_action( 'add-product' ),
					'remove' => YITH_WooCompare_Ajax_Handler::get_handler_action( 'remove-product' ),
					'reload' => YITH_WooCompare_Ajax_Handler::get_handler_action( 'reload-compare' ),
				),
				'nonces'                                   => array(
					'add'    => wp_create_nonce( 'yith_woocompare_add_action' ),
					'remove' => wp_create_nonce( 'yith_woocompare_remove_action' ),
					'reload' => wp_create_nonce( 'yith_woocompare_reload_action' ),
				),
				'ajaxurl'                                  => YITH_WooCompare_Ajax_Handler::should_use_wc_ajax() ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : admin_url( 'admin-ajax.php', 'relative' ),
				/**
				 * APPLY_FILTERS: yith_woocompare_compare_added_label
				 *
				 * Filters the label to use when the product has been added to the comparison table.
				 *
				 * @param string $label Label.
				 *
				 * @return string
				 */
				'added_label'                              => apply_filters( 'yith_woocompare_compare_added_label', __( 'Added to compare', 'yith-woocommerce-compare' ) ),
				/**
				 * APPLY_FILTERS: yith_woocompare_compare_table_title
				 *
				 * Filters the title of the comparison table.
				 *
				 * @param string $table_title Table title.
				 *
				 * @return string
				 */
				'table_title'                              => apply_filters( 'yith_woocompare_compare_table_title', __( 'Product Comparison', 'yith-woocommerce-compare' ) ),
				'auto_open'                                => 'manually' !== get_option( 'yith_woocompare_show_table', 'after_1st_product' ),
				'loader'                                   => YITH_WOOCOMPARE_ASSETS_URL . 'images/loader.gif',
				'button_text'                              => get_option( 'yith_woocompare_button_text', __( 'Compare', 'yith-woocommerce-compare' ) ),
				'cookie_name'                              => YITH_WooCompare_Products_List::get_cookie_name(),
				'close_label'                              => _x( 'Close', 'Label for popup close icon', 'yith-woocommerce-compare' ),
				/**
				 * APPLY_FILTERS: yith_woocompare_selector_for_custom_label_compare_button
				 *
				 * Filters the selector to use for the custom compare button.
				 *
				 * @param string $selector Selector.
				 *
				 * @return string
				 */
				'selector_for_custom_label_compare_button' => apply_filters( 'yith_woocompare_selector_for_custom_label_compare_button', '.product_title' ),
				/**
				 * APPLY_FILTERS: yith_woocompare_custom_label_for_compare_button
				 *
				 * Filters whether to use a custom label for the compare button.
				 *
				 * @param bool $use_custom_label Whether to use a custom label for the compare button or not.
				 *
				 * @return bool
				 */
				'custom_label_for_compare_button'          => apply_filters( 'yith_woocompare_custom_label_for_compare_button', false ),
				/**
				 * APPLY_FILTERS: yith_woocompare_force_showing_popup
				 *
				 * Filters whether to force showing the compare popup.
				 *
				 * @param bool $force_popup Whether to force showing the compare popup.
				 *
				 * @return bool
				 */
				'force_showing_popup'                      => apply_filters( 'yith_woocompare_force_showing_popup', false ),
				/**
				 * APPLY_FILTERS: yith_woocompare_popup_settings
				 *
				 * Filters the settings of the compare initial popup.
				 *
				 * @param array $settings Array of settings for the popup.
				 *
				 * @return array
				 */
				'settings'                                 => apply_filters(
					'yith_woocompare_popup_settings',
					array(
						'width'  => '80%',
						'height' => '80%',
					)
				),
			);

			/**
			 * APPLY_FILTERS: yith_woocompare_main_script_localize_array
			 *
			 * Filters the array with the variables to localize into the plugin script.
			 *
			 * @param array $localize Array with variables to localize.
			 */
			return apply_filters( 'yith_woocompare_main_script_localize_array', $args );
		}

		/* === COMPARE BUTTON === */

		/**
		 * Check if the plugin use WC Blocks for display the compare button.
		 *
		 * @return void
		 */
		public function add_compare_button() {
			$show_button_in = get_option( 'yith_woocompare_show_compare_button_in', 'product' );

			// Add link or button in the products list.
			if ( in_array( $show_button_in, array( 'product', 'both' ), true ) ) {
				add_filter( 'render_block_woocommerce/add-to-cart-form', array( $this, 'append_button_to_block' ), 10, 3 );

				if ( ! yith_plugin_fw_wc_is_using_block_template_in_single_product() ) {
					add_action( 'woocommerce_single_product_summary', array( $this, 'output_button' ), 35 );
				}
			}

			if ( in_array( $show_button_in, array( 'shop', 'both' ), true ) ) {
				add_filter( 'render_block_woocommerce/product-button', array( $this, 'append_button_to_block' ), 10, 3 );
				add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'inject_button_to_product_grid_item' ), 10, 3 );

				if ( ! yith_plugin_fw_wc_is_using_block_template_in_product_catalogue() ) {
					add_action( 'woocommerce_after_shop_loop_item', array( $this, 'output_button' ), 20 );
				}
			}
		}

		/**
		 * Add compare button after add to cart button in case Woo Blocks are used.
		 *
		 * @param string   $html Block content.
		 * @param array    $pars_block The full block, including name and attributes.
		 * @param WP_Block $block The block instance.
		 *
		 * @return string
		 */
		public function append_button_to_block( $html, $pars_block, $block ) {
			$product_id = $block->context['postId'];
			$button     = $this->output_button( $product_id, array(), true );

			if ( $button ) {
				$html = <<<EOHTML
					$html
					<div class="yith-wccp-compare">
						$button
					</div>
				EOHTML;
			}

			return $html;
		}

		/**
		 * Add compare button after add to cart button for each item of a Product Grid.
		 *
		 * @param string     $html Grid item.
		 * @param array      $data HTML components used to build the grid item.
		 * @param WC_Product $product Product object.
		 *
		 * @return string
		 */
		public function inject_button_to_product_grid_item( $html, $data, $product ) {
			$button = $this->output_button( $product->get_id(), array(), true );

			if ( $button ) {
				$html = str_replace( '</li>', $button . '</li>', $html );
			}

			return $html;
		}

		/**
		 * Checks whether we should show the compare button for a specific product
		 *
		 * @param int $product_id Product id.
		 * @return bool Whether to show the button or not.
		 */
		public function should_show_button( $product_id ) {
			$show = ! empty( $product_id );

			// backward compatibility.
			/**
			 * APPLY_FILTERS: yith_woocompare_remove_compare_link_by_cat
			 *
			 * Filters whether to remove the link to add to the comparison table.
			 *
			 * @param bool $remove_link Whether to remove the link or not.
			 * @param int  $product_id  Product ID.
			 *
			 * @return bool
			 */
			$show = $show && ! apply_filters( 'yith_woocompare_remove_compare_link_by_cat', false, $product_id );

			/**
			 * APPLY_FILTERS: yith_woocompare_skip_display_button
			 *
			 * Filters whether to skip display the button to add to the comparison table.
			 *
			 * @param bool $skip_display_button Whether to skip display the button or not.
			 *
			 * @return bool
			 */
			$show = $show && ! apply_filters( 'yith_woocompare_skip_display_button', false );

			return $show;
		}

		/**
		 * Returns classes to show in the Add to Compare button
		 *
		 * @param int $product_id Id of the product we're printing button for.
		 * @return array.
		 */
		public function get_button_classes( $product_id ) {
			$added          = YITH_WooCompare_Products_List::instance()->has( $product_id );
			$style          = empty( $button_or_link ) ? get_option( 'yith_woocompare_is_button', 'button' ) : $button_or_link;
			$anchor_classes = array( 'compare', $style, $added ? 'added' : '' );

			if ( 'button' === $style ) {
				$anchor_classes[] = wc_wp_theme_get_element_class_name( 'button' );
			}

			return apply_filters( 'yith_woocompare_button_classes', $anchor_classes, $product_id );
		}

		/**
		 * Returns the label to use for the button
		 *
		 * @param int    $product_id    Product id.
		 * @param string $default_label Default value to use.
		 *
		 * @return string
		 */
		public function get_button_label( $product_id, $default_label = '' ) {
			$button_label = get_option( 'yith_woocompare_button_text', __( 'Compare', 'yith-woocommerce-compare' ) );

			if ( ! empty( $default_label ) && 'default' !== $default_label ) {
				$button_label = $default_label;
			}

			return $button_label;
		}

		/**
		 * Prints or return compare button
		 *
		 * @param int   $product_id    Product id.
		 * @param array $args          Additional arguments for rendering.
		 * @param bool  $should_return Whether to return or output the content.
		 * @return string|null Template of the button, or nothing if method output the button.
		 */
		public function output_button( $product_id = false, $args = array(), $should_return = false ) {
			global $product;

			if ( ! $product_id ) {
				$product_id = $product instanceof WC_Product ? $product->get_id() : 0;
			}

			if ( ! $this->should_show_button( $product_id ) ) {
				return null;
			}

			// extract additional params.
			list( $button_text, $button_or_link ) = yith_plugin_fw_extract( $args, 'button_text', 'button_or_link' );

			ob_start();
			wc_get_template(
				'yith-compare-button.php',
				array(
					'button_target'   => '_self',
					'product_id'      => $product_id,
					'style'           => empty( $button_or_link ) ? get_option( 'yith_woocompare_is_button', 'button' ) : $button_or_link,
					'added'           => YITH_WooCompare_Products_List::instance()->has( $product_id ),
					'compare_url'     => YITH_WooCompare_Form_handler::get_add_action_url( $product_id ),
					'compare_classes' => implode( ' ', $this->get_button_classes( $product_id ) ),
					'compare_label'   => $this->get_button_label( $product_id, $button_text ),
				),
				'',
				YITH_WOOCOMPARE_TEMPLATE_PATH
			);
			$button = ob_get_clean();

			// if there's a compare button, make sure to output preview bar (will do just once anyway).
			add_action( 'wp_footer', array( $this, 'output_preview_bar' ) );

			if ( $should_return ) {
				return $button;
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $button;
		}

		/* === COMPARE PAGE === */

		/**
		 * The URL of product comparison table
		 *
		 * @since 1.0.0
		 * @param integer $product_id The product ID.
		 * @return string The url to add the product in the comparison table
		 */
		public function get_table_url( $product_id = false ) {
			$url_args = array(
				'action' => $this->action_view,
				'iframe' => 'yes',
			);

			/**
			 * APPLY_FILTERS: yith_woocompare_view_table_url
			 *
			 * Filters the URL to view the comparison table.
			 *
			 * @param string $url        URL to view the comparison table.
			 * @param int    $product_id Product ID.
			 *
			 * @return string
			 */
			return apply_filters( 'yith_woocompare_view_table_url', esc_url_raw( add_query_arg( $url_args, site_url() ) ), $product_id );
		}

		/**
		 * Render the compare page
		 *
		 * @since 1.0.0
		 */
		public function output_page() {

			if ( ! wp_doing_ajax() && ! YITH_WooCompare_Helper::is_action( $this->action_view ) ) {
				return;
			}

			// Set no cache headers.
			nocache_headers();

			// Check if is add to cart.
			$product_id = isset( $_REQUEST['add-to-cart'] ) ? (int) $_REQUEST['add-to-cart'] : null; // phpcs:ignore

			if ( $product_id ) {
				wp_safe_redirect( get_permalink( $product_id ) );
				exit;
			}

			$args           = array();
			$args['fixed']  = false;
			$args['iframe'] = 'yes';

			// Remove admin bar.
			remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
			remove_action( 'wp_head', '_admin_bar_bump_cb' );

			YITH_WooCompare_Table::instance( $args )->output_page();
			die;
		}

		/**
		 * Return the url of stylesheet position
		 *
		 * @since 1.0.0
		 */
		public function stylesheet_url() {
			$asset_path = wp_normalize_path( wc_locate_template( $this->stylesheet_file, '', YITH_WOOCOMPARE_DIR . 'assets/css/' ) );
			$asset_path = str_replace( array( wp_normalize_path( ABSPATH ), PATH_SEPARATOR ), array( '', '/' ), $asset_path );
			/**
			 * APPLY_FILTERS: yith_woocompare_asset_url
			 *
			 * Filters the URL to return the stylesheet position.
			 *
			 * @param string $url        URL of stylesheet position.
			 * @param string $asset_path Asset path.
			 *
			 * @return string
			 */
			$asset_url  = apply_filters( 'yith_woocompare_asset_url', home_url( $asset_path ), $asset_path );

			return $asset_url;
		}

		/* === PREVIEW BAR === */

		/**
		 * Output preview bar
		 */
		public function output_preview_bar() {
			static $processed = false;

			if ( $processed ) {
				return;
			}

			$processed = true;

			if ( ! apply_filters( 'yith_woocompare_should_show_preview_bar', true ) ) {
				return;
			}

			YITH_WooCompare_Table::instance()->output_preview_bar();
		}
	}
}
