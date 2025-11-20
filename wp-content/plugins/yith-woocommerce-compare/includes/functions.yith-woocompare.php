<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Functions
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! function_exists( 'yith_woocompare_user_style' ) ) {
	/**
	 * Return custom style based on user options
	 *
	 * @since 2.1.0
	 * @return string
	 */
	function yith_woocompare_user_style() {
		$table_colors  = get_option(
			'yith_woocompare_table_colors',
			array(
				'title'        => yith_woocompare_get_theme_default( 'table_title', '#333333' ),
				'accent'       => yith_woocompare_get_theme_default( 'table_accent', '#333333' ),
				'highlight'    => yith_woocompare_get_theme_default( 'table_highlight', '#e4e4e4' ),
				'remove'       => yith_woocompare_get_theme_default( 'table_remove', '#777777' ),
				'remove_hover' => yith_woocompare_get_theme_default( 'table_remove_hover', '#333333' ),
				'star'         => yith_woocompare_get_theme_default( 'table_star', '#303030' ),
			)
		);
		$button_colors = get_option(
			'yith_woocompare_button_colors',
			array(
				'text'             => yith_woocompare_get_theme_default( 'button_text', '#ffffff' ),
				'text_hover'       => yith_woocompare_get_theme_default( 'button_text_hover', '#ffffff' ),
				'background'       => yith_woocompare_get_theme_default( 'button_background', '#b2b2b2' ),
				'background_hover' => yith_woocompare_get_theme_default( 'button_background_hover', '#303030' ),
			)
		);

		$custom_css = <<<EOCSS
			#yith-woocompare h1,
			#yith-woocompare h2,
			#yith-woocompare h3 {
				color: {$table_colors["title"]};
			}
			
			#yith-woocompare .remove a {
				color: {$table_colors["remove"]};
			}
			#yith-woocompare .remove a:hover {
				color: {$table_colors["remove_hover"]};
			}

			#yith-woocompare table.compare-list .product_info .button,
			#yith-woocompare table.compare-list .add-to-cart .button,
			#yith-woocompare table.compare-list .added_to_cart,
			#yith-woocompare-related .related-products .button,
			.compare.button {
				color: {$button_colors["text"]};
				background-color: {$button_colors["background"]};
			}

			#yith-woocompare table.compare-list .product_info .button:hover,
			#yith-woocompare table.compare-list .add-to-cart .button:hover,
			#yith-woocompare table.compare-list .added_to_cart:hover,
			#yith-woocompare-related .related-products .button:hover,
			.compare.button:hover {
				color: {$button_colors["text_hover"]};
				background-color: {$button_colors["background_hover"]};
			}
			
			#yith-woocompare table.compare-list .rating .star-rating {
				color: {$table_colors["star"]};
			}
			
			#yith-woocompare table.compare-list tr.different,
			#yith-woocompare table.compare-list tr.different th {
				background-color: {$table_colors["highlight"]} !important;
			}
			
			
			#yith-woocompare-share a:hover,
			#yith-woocompare-cat-nav li a:hover, #yith-woocompare-cat-nav li .active,
			.yith-woocompare-popup-close:hover {
				border-color: {$table_colors["accent"]};
				color: {$table_colors["accent"]};
			}
			EOCSS;

		/**
		 * APPLY_FILTERS: yith_woocompare_user_style_value
		 *
		 * Filters the custom CSS rules based on the options configured in the plugin settings.
		 *
		 * @param string $custom_css Custom CSS rules.
		 *
		 * @return string
		 */
		return apply_filters( 'yith_woocompare_user_style_value', $custom_css );
	}
}

if ( ! function_exists( 'yith_woocompare_get_vendor_name' ) ) {
	/**
	 * Print vendor name under product name in Compare Table. Needs YITH WooCommerce Multi Vendor active
	 *
	 * @since 2.2.0
	 * @param string $product The product object.
	 * @return string
	 */
	function yith_woocompare_get_vendor_name( $product ) {

		if ( ! function_exists( 'yith_get_vendor' ) || empty( $product ) || ! is_object( $product ) ) {
			return '';
		}

		$vendor = yith_get_vendor( $product, 'product' );

		if ( $vendor->is_valid() ) {
			$args = array(
				'vendor'      => $vendor,
				'label_color' => 'color: ' . get_option( 'yith_vendors_color_name' ),
			);

			$template_info = array(
				'name'    => 'vendor-name-title',
				'args'    => $args,
				'section' => 'woocommerce/loop',
			);

			/**
			 * APPLY_FILTERS: yith_woocommerce_vendor_name_template_info
			 *
			 * Filters the array with the vendor data to be sent to the template for the comparison table.
			 *
			 * @param array $template_info Template info.
			 *
			 * @return array
			 */
			$template_info = apply_filters( 'yith_woocommerce_vendor_name_template_info', $template_info );

			extract( $template_info ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

			ob_start();
			yith_wcpv_get_template( $name, $args, $section );

			return ob_get_clean();
		}

		return '';
	}
}

if ( ! function_exists( 'yith_woocompare_get_theme_default' ) ) {
	/**
	 * Retrieve default color values for supported themes
	 * Admin can override this defaults in the Settings panel.
	 *
	 * @since  1.5.1
	 * @param string $key           The option key.
	 * @param mixed  $default_value The option default value.
	 * @return string
	 */
	function yith_woocompare_get_theme_default( $key, $default_value = '' ) {
		$theme_presets = array(
			'yith-wonder' => array(
				'table_title'             => '#007565',
				'table_accent'            => '#007565',
				'table_highlight'         => '#e8e8e8',
				'table_remove'            => '#404040',
				'table_remove_hover'      => '#000000',
				'table_star'              => '#007565',
				'button_text'             => '#ffffff',
				'button_text_hover'       => '#ffffff',
				'button_background'       => '#007565',
				'button_background_hover' => '#007565',
			),
			'yith-proteo' => array(
				'table_title'             => get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' ),
				'table_accent'            => get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' ),
				'table_highlight'         => '#e8e8e8',
				'table_remove'            => get_theme_mod( 'yith_proteo_base_font_color', '#404040' ),
				'table_remove_hover'      => get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' ),
				'table_star'              => get_theme_mod( 'yith_proteo_base_font_color', '#404040' ),
				'button_text'             => get_theme_mod( 'yith_proteo_button_style_1_text_color', '#ffffff' ),
				'button_text_hover'       => get_theme_mod( 'yith_proteo_button_style_1_text_color_hover', '#ffffff' ),
				'button_background'       => get_theme_mod( 'yith_proteo_button_style_1_bg_color', '#448a85' ),
				'button_background_hover' => get_theme_mod( 'yith_proteo_button_style_1_bg_color_hover', '#448a85' ),
			),
		);

		$current_theme = false;

		if ( defined( 'YITH_PROTEO_VERSION' ) ) {
			$current_theme = 'yith-proteo';
		} elseif ( defined( 'YITH_WONDER_VERSION' ) ) {
			$current_theme = 'yith-wonder';
		}

		if ( ! $current_theme ) {
			return $default_value;
		}

		return $theme_presets[ $current_theme ][ $key ];
	}
}

if ( ! function_exists( 'yith_woocompare_get_page_id' ) ) {
	/**
	 * Get the standard compare page id
	 *
	 * @since 2.4.0
	 * @return string
	 */
	function yith_woocompare_get_page_id() {
		// Get page from option.
		$page = get_option( 'yith_woocompare_compare_page', '' );
		// Else get default.
		if ( ! $page ) {
			$page = get_option( 'yith-woocompare-page-id', '' );
		}

		if ( function_exists( 'wpml_object_id_filter' ) && $page ) {
			$page = wpml_object_id_filter( $page, 'page', true );
		}

		return $page;
	}
}

if ( ! function_exists( 'yith_woocompare_append_items' ) ) {
	/**
	 * Adds items inside set array, placing them after the item with the index specified
	 *
	 * @param array  $set      Array where we need to add items.
	 * @param string $index    Index we need to search inside $set.
	 * @param mixed  $items    Items that we need to add to $set.
	 * @param string $position Where to place the additional set of items.
	 *
	 * @return array Array with new items
	 */
	function yith_woocompare_append_items( $set, $index, $items, $position = 'after' ) {
		$index_position = array_search( $index, array_keys( $set ), true );

		if ( $index_position < 0 ) {
			return $set;
		}

		if ( 'after' === $position ) {
			$pivot_position = $index_position + 1;
		} else {
			$pivot_position = $index_position;
		}

		$settings_options_chunk_1 = array_slice( $set, 0, $pivot_position );
		$settings_options_chunk_2 = array_slice( $set, $pivot_position, count( $set ) );

		return array_merge(
			$settings_options_chunk_1,
			$items,
			$settings_options_chunk_2
		);
	}
}

if ( ! function_exists( 'yith_woocompare_recursive_array_merge' ) ) {
	/**
	 * Merges two (or more) arrays using the same logic as array_merge, except that, when it encounter two string index
	 * both containing sub array, will recursively merge those two arrays as well, instead of just overriding the former
	 * with the latter
	 *
	 * Note that this differs from array_merge_recursive in the sense that two scalar indexes aren't consolidated into
	 * a list of values, but the latter will always override the former as in default array_merge strategy.
	 *
	 * @param array $origin_array Destination array.
	 * @param array ...$to_merge  List of arrays to merge into destination.
	 *
	 * @return array
	 */
	function yith_woocompare_recursive_array_merge( $origin_array, ...$to_merge ) {
		if ( ! is_array( $origin_array ) ) {
			return null;
		}

		foreach ( $to_merge as $array_to_merge ) {
			if ( ! is_array( $array_to_merge ) ) {
				continue;
			}

			foreach ( $array_to_merge as $key_to_merge => $value_to_merge ) {
				if ( is_numeric( $key_to_merge ) ) {
					$origin_array[] = $value_to_merge;
				} elseif ( isset( $origin_array[ $key_to_merge ] ) && is_array( $origin_array[ $key_to_merge ] ) && is_array( $value_to_merge ) ) {
					$origin_array[ $key_to_merge ] = yith_woocompare_recursive_array_merge( $origin_array[ $key_to_merge ], $value_to_merge );
				} else {
					$origin_array[ $key_to_merge ] = $value_to_merge;
				}
			}
		}

		return $origin_array;
	}
}
