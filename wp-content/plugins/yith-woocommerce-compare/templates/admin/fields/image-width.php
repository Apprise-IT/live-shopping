<?php
/**
 * Template for displaying the text field
 *
 * @var array $field The field.
 * @package YITH\PluginFramework\Templates\Fields
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

list ( $field_id, $class, $name, $value, $default, $custom_attributes, $data ) = yith_plugin_fw_extract( $field, 'id', 'class', 'name', 'value', 'default', 'custom_attributes', 'data' );

$default = wp_parse_args(
	$default,
	array(
		'width'  => 0,
		'height' => 0,
	)
);

list( $width, $height, $crop ) = yith_plugin_fw_extract(
	! empty( $value ) ? $value : $default,
	'width',
	'height',
	'crop'
);

?>

<div id="<?php echo esc_attr( $field_id ); ?>" style="display: flex; align-items: center; gap: 5px;">
	<input style="max-width: 100px;" name="<?php echo esc_attr( $field_id ); ?>[width]" id="<?php echo esc_attr( $field_id ); ?>-width" type="text" size="3" value="<?php echo esc_attr( $width ); ?>" /> &times;
	<input style="max-width: 100px;" name="<?php echo esc_attr( $field_id ); ?>[height]" id="<?php echo esc_attr( $field_id ); ?>-height" type="text" size="3" value="<?php echo esc_attr( $height ); ?>" /> px

	<label>
		<input name="<?php echo esc_attr( $field_id ); ?>[crop]" id="<?php echo esc_attr( $field_id ); ?>-crop" type="checkbox" value="yes" <?php echo checked( yith_plugin_fw_is_true( $crop ) || 'on' === $crop ); ?> />
		<?php esc_html_e( 'Force crop the image to this thumbnail size.', 'yith-woocommerce-compare' ); ?>
	</label>
</div>