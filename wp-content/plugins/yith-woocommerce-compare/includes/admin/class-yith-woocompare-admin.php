<?php
/**
 * Admin class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Admin {

		use YITH_WooCompare_Trait_Singleton;

		/**
		 * Panel Object
		 *
		 * @var YIT_Plugin_Panel_WooCommerce
		 */
		protected $panel;

		/**
		 * Premium tab template file name
		 *
		 * @var string
		 */
		protected $premium = 'premium.php';

		/**
		 * Premium version landing link
		 *
		 * @var string
		 */
		protected $premium_landing = 'https://yithemes.com/themes/plugins/yith-woocommerce-compare';

		/**
		 * Compare panel page
		 *
		 * @var string
		 */
		protected $panel_page = 'yith_woocompare_panel';

		/**
		 * Various links
		 *
		 * @since 1.0.0
		 * @var string
		 * @access public
		 */
		public $doc_url = 'http://yithemes.com/docs-plugins/yith-woocommerce-compare/';

		/**
		 * Constructor
		 *
		 * @access public
		 * @since 1.0.0
		 */
		public function __construct() {
			// register panel.
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

			// add action links.
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_WOOCOMPARE_DIR . '/' . basename( YITH_WOOCOMPARE_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );

			// premium tab.
			add_action( 'yith_woocompare_premium', array( $this, 'premium_tab' ) );

			// scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 20 );

			// custom fields.
			add_filter( 'yith_plugin_fw_get_field_template_path', array( $this, 'custom_fields' ), 10, 2 );
			add_filter( 'woocommerce_admin_settings_sanitize_option_yith_woocompare_fields_attrs', array( $this, 'admin_update_custom_option' ), 10, 3 );
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'admin_update_image_width' ), 10, 3 );

			// YITH WooCompare Loaded.
			/**
			 * DO_ACTION: yith_woocompare_loaded
			 *
			 * Allows to trigger some action when the plugin is loaded.
			 */
			do_action( 'yith_woocompare_loaded' );
		}

		/**
		 * Action Links: add the action links to plugin admin page
		 *
		 * @since    1.0
		 * @param array $links Links plugin array.
		 * @return mixed
		 * @use plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, $this->panel_page, true, YITH_WOOCOMPARE_SLUG );
			return $links;
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @since    1.0
		 * @use     /Yit_Plugin_Panel class
		 * @return   void
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {
			if ( ! empty( $this->panel ) ) {
				return;
			}

			$premium_tab = array(
				'landing_page_url' => $this->get_premium_landing_uri(),
				'features'         => array(
					array(
						'title'       => __( 'Customize the comparison table', 'yith-woocommerce-compare' ),
						'description' => __( 'Customize the table with advanced options: upload an image, replace the "In Stock" and "Out of Stock" texts with icons, decide whether to fix the first column, highlight differences with a background color, and much more.', 'yith-woocommerce-compare' ),
					),
					array(
						'title'       => __( 'Suggest products to compare', 'yith-woocommerce-compare' ),
						'description' => __( 'Help your customers explore your catalog by showing a recommended products section below the comparison table. This way, they can add the suggested products to the comparison with a single click and evaluate their purchase.', 'yith-woocommerce-compare' ),
					),
					array(
						'title'       => __( '100% mobile-friendly design', 'yith-woocommerce-compare' ),
						'description' => __( 'The premium version of the plugin includes a new mobile-friendly design inspired by the features of major e-commerce platforms. A must-have upgrade to ensure that product comparison works seamlessly for customers shopping on your store on smartphones or tablets.', 'yith-woocommerce-compare' ),
					),
					array(
						'title'       => __( 'Create custom comparison tables to display on any page of your shop', 'yith-woocommerce-compare' ),
						'description' => __( 'Create personalized comparison tables by selecting the products to include and placing them anywhere on your store using the dedicated shortcode. This allows you to highlight specific products on your home page or insert comparison tables within product detail pages to encourage customers to consider alternative products (just like Amazon).', 'yith-woocommerce-compare' ),
					),
				),
			);

			$help_tab = array(
				'hc_url'  => 'https://support.yithemes.com/hc/en-us/',
				'doc_url' => $this->get_doc_url(),
			);

			$args = apply_filters(
				'yith_woocompare_panel_args',
				array_merge(
					array(
						'create_menu_page'   => true,
						'parent_slug'        => '',
						'ui_version'         => 2,
						'page_title'         => 'YITH WooCommerce Compare',
						'menu_title'         => 'Compare',
						'plugin_description' => __( 'It allows you to compare in a simple and efficient way products on sale in your shop and analyze their main features in a single table.', 'yith-woocommerce-compare' ),
						'capability'         => 'manage_options',
						'parent'             => '',
						'class'              => yith_set_wrapper_class(),
						'parent_page'        => 'yith_plugin_panel',
						'admin-tabs'         => $this->get_available_panel_tabs(),
						'options-path'       => YITH_WOOCOMPARE_DIR . '/plugin-options',
						'plugin_slug'        => YITH_WOOCOMPARE_SLUG,
						'plugin_version'     => YITH_WOOCOMPARE_VERSION,
						'is_premium'         => defined( 'YITH_WOOCOMPARE_PREMIUM' ),
						'page'               => $this->panel_page,
						'help_tab'           => $help_tab,
					),
					! defined( 'YITH_WOOCOMPARE_PREMIUM' ) ? array( 'premium_tab' => $premium_tab ) : array()
				)
			);

			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once YITH_WOOCOMPARE_DIR . 'plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Returns url to a page of the panel
		 *
		 * @param string $tab     Tab to visit.
		 * @param string $sub_tab Sub tab to visit.
		 * @return string Panel url.
		 */
		public function get_panel_url( $tab = false, $sub_tab = false ) {
			return add_query_arg(
				array_merge(
					array( 'page' => $this->panel_page ),
					$tab ? array( 'tab' => $tab ) : array(),
					$sub_tab ? array( 'sub_tab' => $sub_tab ) : array(),
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Returns a list of available tabs for the admin panel
		 *
		 * @return array
		 */
		protected function get_available_panel_tabs() {
			$tabs = array(
				'general' => array(
					'title'       => __( 'General settings', 'yith-woocommerce-compare' ),
					'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
					'description' => __( 'Set the general behavior of the plugin.', 'yith-woocommerce-compare' ),
				),
				'table'   => array(
					'title'       => __( 'Comparison tables', 'yith-woocommerce-compare' ),
					'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" /></svg>',
					'description' => __( 'Configure the options for the comparison tables displayed in your shop.', 'yith-woocommerce-compare' ),
				),
			);

			/**
			 * APPLY_FILTERS: yith_woocompare_admin_tabs
			 *
			 * Filter the available tabs in the plugin panel.
			 *
			 * @param array $admin_tabs Admin tabs.
			 */
			return apply_filters( 'yith_woocompare_admin_tabs', $tabs );
		}

		/**
		 * Premium Tab Template
		 * Load the premium tab template on admin page
		 *
		 * @since    1.0
		 * @return void
		 */
		public function premium_tab() {
			$premium_tab_template = YITH_WOOCOMPARE_TEMPLATE_PATH . 'admin/' . $this->premium;

			if ( ! file_exists( $premium_tab_template ) ) {
				return;
			}

			include_once $premium_tab_template;
		}

		/**
		 * Add the action links to plugin admin page
		 *
		 * @since    1.0
		 * @param array    $new_row_meta_args An array of plugin row meta.
		 * @param string[] $plugin_meta An array of the plugin's metadata, including the version, author, author URI, and plugin URI.
		 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
		 * @return   array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file ) {

			if ( defined( 'YITH_WOOCOMPARE_INIT' ) && YITH_WOOCOMPARE_INIT === $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_WOOCOMPARE_SLUG;

				if ( defined( 'YITH_WOOCOMPARE_PREMIUM' ) ) {
					$new_row_meta_args['is_premium'] = true;
				}
			}

			return $new_row_meta_args;
		}

		/**
		 * Get the premium landing uri
		 *
		 * @since   1.0.0
		 * @return  string The premium landing link
		 */
		public function get_premium_landing_uri() {
			return $this->premium_landing;
		}

		/**
		 * Returns url to the doc
		 *
		 * @return string Documentation url.
		 */
		public function get_doc_url() {
			if ( defined( 'YITH_WOOCOMPARE_PREMIUM' ) ) {
				$doc_url = 'https://docs.yithemes.com/yith-woocommerce-compare/';
			} else {
				$doc_url = 'https://docs.yithemes.com/yith-woocommerce-compare/category/free-version-settings/';
			}

			return $doc_url;
		}

		/**
		 * Returns path to custom fields defined by this plugin
		 *
		 * @param string $path Template path.
		 * @param array  $field Field being rendered.
		 * @return string Filtered path.
		 */
		public function custom_fields( $path, $field ) {
			$type = $field['type'] ?? '';

			if ( ! in_array( $type, array( 'woocompare_image_width', 'woocompare_attributes' ), true ) ) {
				return $path;
			}

			$filename = str_replace( array( 'woocompare_', '_' ), array( '', '-' ), $type );

			return YITH_WOOCOMPARE_TEMPLATE_PATH . 'admin/fields/' . $filename . '.php';
		}

		/**
		 * Save the admin field: slider
		 *
		 * @access public
		 * @since 1.0.0
		 * @param mixed $value The option value.
		 * @param mixed $option The options array.
		 * @return mixed
		 */
		public function admin_update_custom_option( $value, $option ) {

			$val            = array();
			$checked_fields = isset( $_POST[ $option['id'] ] ) ? maybe_unserialize( wp_unslash( $_POST[ $option['id'] ] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$fields         = isset( $_POST[ $option['id'] . '_positions' ] ) ? array_map( 'wc_clean', explode( ',', wp_unslash( $_POST[ $option['id'] . '_positions' ] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			foreach ( $fields as $field ) {
				$val[ $field ] = in_array( $field, $checked_fields, true );
			}

			update_option( str_replace( '_attrs', '', $option['id'] ), $val );

			return $value;
		}

		/**
		 * Save the admin field: slider
		 *
		 * @access public
		 * @since 1.0.0
		 * @param mixed $value The option value.
		 * @param mixed $option The options array.
		 * @return mixed
		 */
		public function admin_update_image_width( $value, $option ) {
			if ( 'woocompare_image_width' !== $option['type'] ) {
				return $value;
			}

			$value         = (array) maybe_unserialize( $value );
			$value['crop'] = isset( $value['crop'] );
			$value         = wp_parse_args(
				$value,
				array(
					'width'  => 0,
					'height' => 0,
					'crop'   => true,
				)
			);

			return $value;
		}

		/**
		 * Enqueue admin styles and scripts
		 *
		 * @access public
		 * @since 1.0.0
		 * @return void
		 */
		public function enqueue_styles_scripts() {

			$min = ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '';

			if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === $this->panel_page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_script( 'jquery-ui' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-mouse' );
				wp_enqueue_script( 'jquery-ui-slider' );
				wp_enqueue_script( 'jquery-ui-sortable' );

				wp_enqueue_style( 'yith_woocompare_admin', YITH_WOOCOMPARE_URL . 'assets/css/admin.css', array(), YITH_WOOCOMPARE_VERSION );
				wp_enqueue_script( 'yith_woocompare', YITH_WOOCOMPARE_URL . 'assets/js/woocompare-admin' . $min . '.js', array( 'jquery', 'jquery-ui-sortable' ), YITH_WOOCOMPARE_VERSION, true );
			}

			/**
			 * DO_ACTION: yith_woocompare_enqueue_styles_scripts
			 *
			 * Allows to trigger some action when the styles and scripts are enqueued.
			 */
			do_action( 'yith_woocompare_enqueue_styles_scripts' );
		}
	}
}
