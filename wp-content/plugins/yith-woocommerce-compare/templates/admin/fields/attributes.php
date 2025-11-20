<?php
/**
 * Template for displaying the text field
 *
 * @var array $supported_field The field.
 * @package YITH\PluginFramework\Templates\Fields
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

$option_name      = str_replace( '_attrs', '', $field['id'] );
$option_value     = get_option( $option_name, array() );
$supported_fields = YITH_Woocompare_Helper::get_default_table_fields();
$all              = array();
$checkboxes       = $option_value;

if ( empty( $option_value ) && 'all' === $field['default'] ) {
	foreach ( array_keys( $supported_fields ) as $supported_field ) {
		$checkboxes[ $supported_field ] = true;
	}
} else {
	foreach ( array_keys( $supported_fields ) as $supported_field ) {
		if ( ! isset( $checkboxes[ $supported_field ] ) ) {
			$checkboxes[ $supported_field ] = false;
		}
	}
}

/**
 * APPLY_FILTERS: yith_woocompare_admin_fields_attributes
 *
 * Filters the fields attributes to show in the comparison table.
 *
 * @param array $attributes       Field attributes.
 * @param array $supported_fields Fields to show.
 * @param array $checked          Checked attributes to show.
 *
 * @return array
 */
$checkboxes = apply_filters( 'yith_woocompare_admin_fields_attributes', $checkboxes, $supported_fields, $option_value );

?>

<div class="attributes">
	<p class="description"><?php echo wp_kses_post( $field['desc'] ); ?></p>
	<ul class="fields">
		<?php
		foreach ( $checkboxes as $slug => $checked ) :
			if ( ! isset( $supported_fields[ $slug ] ) ) {
				continue;
			}
			$is_fixed = apply_filters( 'yith_woocompare_admin_fields_is_attribute_fixed', in_array( $slug, array( 'title', 'image', 'add_to_cart' ), true ) || str_ends_with( $slug, '_2' ), $slug );
			?>
			<li class="<?php echo $is_fixed ? 'fixed' : ''; ?>">
				<label>
					<?php echo esc_attr( $supported_fields[ $slug ] ); ?>
				</label>
				<div>
					<?php if ( ! $is_fixed ) : ?>
						<i class="yith-icon yith-icon-drag"></i>
					<?php endif; ?>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput
					echo yith_plugin_fw_get_field(
						array(
							'name'              => $field['id'] . '[]',
							'id'                => $field['id'] . '_' . $slug,
							'type'              => 'onoff',
							'yith-type'         => 'onoff',
							'custom_attributes' => array_merge(
								array(
									'data-value' => $slug,
								),
								$checked ? array(
									'checked' => true,
								) : array(),
							),
						)
					)
					?>
				</div>
			</li>
			<?php
		endforeach;
		?>
	</ul>
	<input type="hidden" name="<?php echo esc_attr( $field['id'] ); ?>_positions" value="<?php echo implode( ',', array_keys( $checkboxes ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" />
</div>

