<?php
/**
 * Woocommerce Compare page
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

?>

<li>
	<a href="<?php echo esc_url( YITH_WooCompare_Form_Handler::get_remove_action_url( $product_id ) ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>" class="remove" title="<?php esc_html_e( 'Remove', 'yith-woocommerce-compare' ); ?>">×</a>
	<?php echo wp_kses_post( $product->get_image( 'shop_thumbnail' ) ); ?>
	<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="product-info">
		<?php echo esc_html( $product->get_title() ); ?>
	</a>
</li>
