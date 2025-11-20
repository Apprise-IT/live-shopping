<?php
/**
 * Main admin class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.1.1
 */

if ( ! defined( 'YITH_WOOCOMPARE' ) ) {
	exit;
} // Exit if accessed directly

$options = array(
	'general' => array(

		'yith_woocompare_general'                => array(
			'name' => __( '"Compare" button', 'yith-woocommerce-compare' ),
			'type' => 'title',
			'desc' => '',
			'id'   => 'yith_woocompare_general',
		),
		'yith_woocompare_is_button'              => array(
			'title'     => __( 'Compare style', 'yith-woocommerce-compare' ),
			'desc'      => __( 'Choose if you want to use a link or a button for the compare anchor.', 'yith-woocommerce-compare' ),
			'id'        => 'yith_woocompare_is_button',
			'default'   => 'button',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'button'   => __( 'Button', 'yith-woocommerce-compare' ),
				'link'     => __( 'Textual link', 'yith-woocommerce-compare' ),
				'checkbox' => __( 'Checkbox', 'yith-woocommerce-compare' ),
			),
		),
		'yith_woocompare_button_text'            => array(
			'title'     => __( 'Link/Button label', 'yith-woocommerce-compare' ),
			'desc'      => __( 'Label to use in the compare anchor.', 'yith-woocommerce-compare' ),
			'id'        => 'yith_woocompare_button_text',
			'default'   => __( 'Compare', 'yith-woocommerce-compare' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
		),
		'yith_woocompare_show_compare_button_in' => array(
			'title'     => __( 'Show compare in:', 'yith-woocommerce-compare' ),
			'id'        => 'yith_woocompare_show_compare_button_in',
			'default'   => 'shop',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'shop'    => __( 'Shop page', 'yith-woocommerce-compare' ),
				'product' => __( 'Product pages', 'yith-woocommerce-compare' ),
				'both'    => __( 'Both', 'yith-woocommerce-compare' ),
			),
		),
		'yith_woocompare_show_table'             => array(
			'title'     => __( 'Show table when:', 'yith-woocommerce-compare' ),
			'id'        => 'yith_woocompare_show_table',
			'default'   => 'after_1st_product',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'manually'          => __( 'The user manually clicks on view anchor', 'yith-woocommerce-compare' ),
				'after_1st_product' => __( 'The user adds a product to the list', 'yith-woocommerce-compare' ),
			),
			'deps'      => array(
				'id'    => 'yith_woocompare_use_page_popup',
				'value' => 'popup',
			),
		),
		'yith_woocompare_general_end'            => array(
			'type' => 'sectionend',
			'id'   => 'yith_woocompare_general_end',
		),
	),
);

/**
 * APPLY_FILTERS: yith_woocompare_general_settings
 *
 * Filter the options available in the 'Settings' tab.
 *
 * @param array $options Array of options.
 *
 * @return array
 */
return apply_filters( 'yith_woocompare_general_settings', $options );
