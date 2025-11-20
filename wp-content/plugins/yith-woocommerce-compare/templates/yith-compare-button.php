<?php
/**
 * Woocommerce Compare button
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.1.4
 */

/**
 * Template variables:
 *
 * @var $style string
 * @var $added bool
 * @var $product_id int
 * @var $button_target string
 * @var $compare_url string
 * @var $compare_classes string
 * @var $compare_label string
 */
defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.
?>
	<a
		href="<?php echo esc_url( $compare_url ); ?>"
		class="<?php echo esc_attr( $compare_classes ); ?>"
		data-product_id="<?php echo (int) $product_id; ?>"
		target="<?php echo esc_attr( $button_target ); ?>"
		rel="nofollow"
	>
		<?php if ( 'checkbox' === $style ) : ?>
			<input type="checkbox" <?php checked( $added ); ?>>
		<?php endif; ?>
		<span class="label">
			<?php echo esc_html( $compare_label ); ?>
		</span>
	</a>
<?php
wp_enqueue_script( 'yith-woocompare-main' );