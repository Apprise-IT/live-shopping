<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Install/Upgrade class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 3.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_Woocompare_Install' ) ) {
	/**
	 * Install class
	 * Takes care of installing/upgrading process
	 *
	 * @since 3.0.0
	 */
	class YITH_Woocompare_Install {
		/**
		 * Name of the option where system will store Compare page
		 *
		 * @const string
		 */
		const PAGE_OPTION_NAME = 'yith_woocompare_page_id';

		/**
		 * Version saved in database.
		 *
		 * @var string
		 */
		protected static $stored_version;

		/**
		 * Initialize function
		 */
		public static function init() {
			self::$stored_version = get_option( 'yith_woocompare_version', YITH_WOOCOMPARE_VERSION );

			add_action( 'init', array( self::class, 'maybe_do_upgrade' ) );
			add_action( 'init', array( self::class, 'maybe_install_page' ) );
			add_action( 'init', array( self::class, 'install_image_sizes' ) );
		}

		/**
		 * Register custom image size
		 */
		public static function install_image_sizes() {
			$size = get_option( 'yith_woocompare_image_size' );

			if ( ! $size ) {
				return;
			}

			list( $width, $height, $crop ) = yith_plugin_fw_extract( $size, 'width', 'height', 'crop' );

			add_image_size( 'yith-woocompare-image', $width, $height, yith_plugin_fw_is_true( $crop ) );

			// filter woocommerce image.
			add_filter( 'woocommerce_get_image_size_yith-woocompare-image', fn () => compact( 'width', 'height', 'crop' ) );
		}

		/**
		 * Install compare page if needed.
		 */
		public static function maybe_install_page() {
			global $wpdb;

			$option_value = (int) get_option( self::PAGE_OPTION_NAME );

			if ( $option_value > 0 && get_post( $option_value ) ) {
				return;
			}

			$page_slug = esc_sql( _x( 'yith-compare', 'page_slug', 'yith-woocommerce-compare' ) );
			$page_id   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_name` = '%s' LIMIT 1;", $page_slug ) ); // phpcs:ignore

			if ( ! $page_id ) {
				$page_data = array(
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_author'    => 1,
					'post_name'      => $page_slug,
					'post_title'     => __( 'Compare', 'yith-woocommerce-compare' ),
					'post_content'   => '<!-- wp:shortcode -->[yith_woocompare_table]<!-- /wp:shortcode -->',
					'post_parent'    => 0,
					'comment_status' => 'closed',
				);
				$page_id   = wp_insert_post( $page_data );
			}

			update_option( self::PAGE_OPTION_NAME, $page_id );
		}

		/**
		 * Process required actions to upgrade current version of the plugin to latest
		 */
		public static function maybe_do_upgrade() {
			if ( version_compare( self::$stored_version, YITH_WOOCOMPARE_VERSION, '<' ) ) {
				version_compare( self::$stored_version, '3.0.0', '<' ) && self::do_300_upgrade();
			}

			// update stored version after upgrade.
			update_option( 'yith_woocompare_version', YITH_WOOCOMPARE_VERSION );
		}

		/**
		 * Upgrade process for version 3.0.0
		 */
		protected static function do_300_upgrade() {
			self::do_300_options_upgrade();

			do_action( 'yith_woocompare_300_upgrade' );
		}

		/**
		 * Process upgrade ops to bring plugin to version 3.0.0
		 */
		protected static function do_300_options_upgrade() {
			/**
			 * An array of direct replacements.
			 * In the most basic form, this array contains value such as
			 *
			 * NAME_OF_THE_OPTION_REPLACING => NAME_OF_THE_OPTION_BEING_REPLACED
			 *
			 * Anyway, it can also contain an array as value, when more options needs to be
			 * unified in a single array value.
			 */
			$replacements = array(
				self::PAGE_OPTION_NAME          => 'yith-woocompare-page-id',
				'yith_woocompare_button_colors' => array(
					'text'             => 'yith-woocompare-table-button-text-color',
					'text_hover'       => 'yith-woocompare-table-button-text-color-hover',
					'background'       => 'yith-woocompare-table-button-color',
					'background_hover' => 'yith-woocompare-table-button-color-hover',
				),
				'yith_woocompare_table_colors'  => array(
					'title'        => 'yith-woocompare-table-title-color',
					'accent'       => 'yith-woocompare-table-accent-color',
					'highlight'    => 'yith-woocompare-highlights-color',
					'remove'       => 'yith-woocompare-table-remove-color',
					'remove_hover' => 'yith-woocompare-table-remove-color-hover',
					'star'         => 'yith-woocompare-table-star',
				),
			);

			foreach ( $replacements as $new_name => $current_option ) {
				if ( is_string( $current_option ) ) {
					$value = get_option( $current_option );
					delete_option( $current_option );
				} elseif ( is_array( $current_option ) ) {
					$value = array();

					foreach ( $current_option as $item_key => $item_name ) {
						$value[ $item_key ] = get_option( $item_name );
						delete_option( $item_name );
					}

					$value = array_filter( $value );
				}

				if ( $value ) {
					update_option( $new_name, $value );
				}
			}

			// show button in.
			$show_button_in_product = 'yes' === get_option( 'yith_woocompare_compare_button_in_product_page' );
			$show_button_in_shop    = 'yes' === get_option( 'yith_woocompare_compare_button_in_products_list' );

			if ( $show_button_in_product && $show_button_in_shop ) {
				$show_button_in = 'both';
			} elseif ( $show_button_in_product ) {
				$show_button_in = 'product';
			} else {
				$show_button_in = 'shop';
			}

			update_option( 'yith_woocompare_show_compare_button_in', $show_button_in );

			// open popup.
			$auto_open         = 'yes' === get_option( 'yith_woocompare_auto_open', 'yes' );
			$open_after_second = 'yes' === get_option( 'yith_woocompare_open_after_second', 'no' );

			if ( ! $auto_open ) {
				$show_table = 'manually';
			} elseif ( ! $open_after_second ) {
				$show_table = 'after_1st_product';
			} else {
				$show_table = 'after_2nd_product';
			}

			update_option( 'yith_woocompare_show_table', $show_table );

			// excluded categories.
			$excluded_categories = get_option( 'yith_woocompare_excluded_category' );

			if ( ! empty( $excluded_categories ) ) {
				$reverse_category_exclusion = yith_plugin_fw_is_true( get_option( 'yith_woocompare_excluded_category_inverse', 'no' ) );

				if ( $reverse_category_exclusion ) {
					$included_categories = $excluded_categories;
				} else {
					$all_categories = get_terms(
						array(
							'taxonomy' => 'product_cat',
							'number'   => 0,
							'fields'   => 'ids',
						)
					);

					$included_categories = array_diff( $all_categories, $excluded_categories );

				}

				update_option( 'yith_woocompare_include_by_category', 'yes' );
				update_option( 'yith_woocompare_included_categories', $included_categories );
			}

			// show table image.
			$show_image_in_popup = yith_plugin_fw_is_true( get_option( 'yith-woocompare-table-image-in-popup' ) );
			$show_image_in_page  = yith_plugin_fw_is_true( get_option( 'yith-woocompare-table-image-in-page' ) );

			if ( $show_image_in_popup && $show_image_in_page ) {
				$show_image_in = 'both';
			} elseif ( $show_image_in_popup ) {
				$show_image_in = 'popup';
			} else {
				$show_image_in = 'page';
			}

			update_option( 'yith_woocompare_show_image_table_in', $show_image_in );

			// show share section.
			$show_share_in_popup = yith_plugin_fw_is_true( get_option( 'yith-woocompare-share-in-popup' ) );
			$show_share_in_page  = yith_plugin_fw_is_true( get_option( 'yith-woocompare-share-in-page' ) );

			if ( $show_share_in_popup && $show_share_in_page ) {
				$show_share_in = 'both';
			} elseif ( $show_image_in_popup ) {
				$show_share_in = 'popup';
			} else {
				$show_share_in = 'page';
			}

			update_option( 'yith_woocompare_show_share_in', $show_share_in );

			// show related section.
			$show_related_in_popup = yith_plugin_fw_is_true( get_option( 'yith-woocompare-related-in-popup' ) );
			$show_related_in_page  = yith_plugin_fw_is_true( get_option( 'yith-woocompare-related-in-page' ) );

			if ( $show_related_in_popup && $show_related_in_page ) {
				$show_related_in = 'both';
			} elseif ( $show_related_in_popup ) {
				$show_related_in = 'popup';
			} else {
				$show_related_in = 'page';
			}

			update_option( 'yith_woocompare_show_related_in', $show_related_in );

			// has limit on comparison.
			$number_of_items = get_option( 'yith_woocompare_num_product_compared', 0 );
			$number_of_items && update_option( 'yith_woocompare_should_limit_comparison', 'yes' );

			// has fixed columns.
			$number_of_items = get_option( 'yith_woocompare_num_fixedcolumns', 1 );
			$number_of_items && update_option( 'yith_woocompare_has_fixed_columns', 'yes' );

			// slider options.
			$slider_autoplay   = yith_plugin_fw_is_true( get_option( 'yith-woocompare-related-autoplay', 'no' ) );
			$slider_navigation = yith_plugin_fw_is_true( get_option( 'yith-woocompare-related-navigation', 'yes' ) );

			$slider_features = array();
			if ( $slider_autoplay ) {
				$slider_features[] = 'autoplay';
			}
			if ( $slider_navigation ) {
				$slider_features[] = 'navigation';
			}

			update_option( 'yith_woocompare_related_slider_options', $slider_features );

			// table contents.
			$fields             = get_option( 'yith_woocompare_fields' );
			$show_title         = yith_plugin_fw_is_true( get_option( 'yith_woocompare_fields_product_info_title' ) );
			$show_image         = yith_plugin_fw_is_true( get_option( 'yith_woocompare_fields_product_info_image' ) );
			$show_add_to_cart   = yith_plugin_fw_is_true( get_option( 'yith_woocompare_fields_product_info_add_cart' ) );
			$repeat_price       = yith_plugin_fw_is_true( get_option( 'yith_woocompare_price_end', ) );
			$repeat_add_to_cart = yith_plugin_fw_is_true( get_option( 'yith_woocompare_add_to_cart_end' ) );
			$show_product_info  = ! empty( $fields['product_info'] );
			$show_price         = ! empty( $fields['price'] );

			unset( $fields['product_info'] );

			$fields = array_merge(
				array(
					'title'       => $show_product_info && $show_title,
					'image'       => $show_product_info && $show_image,
					'add_to_cart' => $show_product_info && $show_add_to_cart,
				),
				$fields,
				array(
					'price_2'       => $show_price && $repeat_price,
					'add_to_cart_2' => $show_add_to_cart && $repeat_add_to_cart,
				)
			);

			update_option( 'yith_woocompare_fields', $fields );
		}
	}
}
