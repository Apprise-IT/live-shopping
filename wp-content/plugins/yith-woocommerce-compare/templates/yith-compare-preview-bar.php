<?php
/**
 * Compare Preview Bar template
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.0.0
 */

/**
 * Template variables:
 *
 * @var $products WC_Product[]
 * @var $has_more bool
 * @var $remaining int
 * @var $compare_button_text string
 * @var $compare_button_classes string
 * @var $compare_url string
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.
?>

<div id="yith-woocompare-preview-bar" class="<?php echo ! empty( $products ) ? 'shown' : ''; ?>">
	<div class="container">
		<header>
			<p><?php echo wp_kses_post( __( 'Select at least 2 products<br/>to compare', 'yith-woocommerce-compare' ) ); ?></p>
		</header>
		<div class="content">
			<?php if ( ! empty( $products ) ) : ?>
			<ul class="compare-list">
				<?php foreach ( $products as $product_id => $product ) : ?>
					<li>
						<div class="image-wrap">
							<?php YITH_WooCompare_Table::instance()->output_remove_anchor( $product_id ); ?>
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<?php echo $product->get_image( 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						</div>
					</li>
				<?php endforeach; ?>

				<?php if ( ! $has_more ) : ?>
					<?php $placeholders_to_show = 5 - count( $products ); ?>
					<?php for ( $i = 0; $i < $placeholders_to_show; $i++ ) : ?>
						<li class="product-placeholder"></li>
					<?php endfor; ?>
				<?php endif; ?>

				<?php if ( $has_more ) : ?>
					<li class="product-placeholder">
						<span>
							<?php
							// translators: 1. Number of products in the comparison that exceed 5 previewed.
							echo esc_html( sprintf( esc_html__( '+%d more', 'yith-woocommerce-compare' ), $remaining ) );
							?>
						</span>
					</li>
				<?php endif; ?>
			</ul>
			<?php endif; ?>
		</div>
		<footer>
			<a href="<?php echo esc_attr( $compare_url ); ?>" class="<?php echo esc_attr( $compare_button_classes ); ?>">
				<?php echo esc_html( $compare_button_text ); ?>
			</a>
		</footer>
	</div>
</div>
