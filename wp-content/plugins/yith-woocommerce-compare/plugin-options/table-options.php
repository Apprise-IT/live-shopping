<?php
/**
 * STYLE OPTIONS ARRAY
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 2.0.0
 */

if ( ! defined( 'YITH_WOOCOMPARE' ) ) {
	exit;
} // Exit if accessed directly

$options = array(
	'table' => array(
		array(
			'name' => __( 'Table options', 'yith-woocommerce-compare' ),
			'type' => 'title',
			'desc' => '',
			'id'   => 'yith_woocompare_table_heading',
		),
		array(
			'title'     => __( 'Table title', 'yith-woocommerce-compare' ),
			'desc'      => __( 'Enter the text for the table title.', 'yith-woocommerce-compare' ),
			'id'        => 'yith_woocompare_table_text',
			'default'   => __( 'Compare products', 'yith-woocommerce-compare' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'yith_woocompare_table_image_end',
		),
		array(
			'name' => __( 'Table content', 'yith-woocommerce-compare' ),
			'type' => 'title',
			'desc' => '',
			'id'   => 'yith_woocompare_table_content',
		),
		array(
			'name'      => __( 'In comparison table show:', 'yith-woocommerce-compare' ),
			'desc'      => __( 'Select the fields to be displayed in the comparison table and arrange them by dragging and dropping (WooCommerce attributes are also included).', 'yith-woocommerce-compare' ),
			'id'        => 'yith_woocompare_fields_attrs',
			'default'   => 'all',
			'type'      => 'yith-field',
			'yith-type' => 'woocompare_attributes',
		),
		array(
			'title'     => __( 'Image format', 'yith-woocommerce-compare' ),
			'desc'      => __( 'Choose whether you want to use the original image format or create a thumbnail of a specified size.', 'yith-woocommerce-compare' ),
			'id'        => 'yith_woocompare_table_image_format',
			'default'   => 'thumb',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'original' => __( 'Use original format', 'yith-woocommerce-compare' ),
				'thumb'    => __( 'Use thumbnail', 'yith-woocommerce-compare' ),
			),
		),
		array(
			'name'      => __( 'Image size', 'yith-woocommerce-compare' ),
			// translators: %s stand for the link to a suggest plugin to install.
			'desc'      => sprintf( __( 'Set image size (in px). After changing these settings you may need to %s.', 'yith-woocommerce-compare' ), '<a href="http://wordpress.org/extend/plugins/regenerate-thumbnails/">' . __( 'regenerate your thumbnails', 'yith-woocommerce-compare' ) . '</a>' ),
			'id'        => 'yith_woocompare_image_size',
			'type'      => 'yith-field',
			'yith-type' => 'woocompare_image_width',
			'default'   => array(
				'width'  => 220,
				'height' => 154,
				'crop'   => 1,
			),
			'deps'      => array(
				'id'    => 'yith_woocompare_table_image_format',
				'value' => 'thumb',
			),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'yith_woocompare_table_content_end',
		),
	),
);

/**
 * APPLY_FILTERS: yith_woocompare_table_settings
 *
 * Filter the options available in the 'Comparison Table' tab.
 *
 * @param array $options Array of options.
 *
 * @return array
 */
return apply_filters( 'yith_woocompare_table_settings', $options );
