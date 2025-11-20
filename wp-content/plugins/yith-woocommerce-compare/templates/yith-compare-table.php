<?php
/**
 * Woocommerce Compare page
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Compare
 * @version 1.1.4
 */

/**
 * Available variables:
 *
 * @var $table YITH_WooCompare_Table
 * @var $products array
 * @var $fields array
 * @var $default_fields array
 * @var $fixed bool
 * @var $show_product_info bool
 * @var $stock_icons bool
 * @var $layout string
 * @var $image_size string
 * @var $different array
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

global $product;
?>

	<div id="yith-woocompare" class="woocommerce <?php echo $fixed ? esc_attr( 'fixed-compare-table' ) : ''; ?>">

		<?php
		if ( empty( $products ) || ! $show_product_info ) {
			$table->output_table_heading();
		}
		?>

		<?php if ( empty( $products ) ) : ?>
			<p class="empty-comparison">
				<?php
				/**
				 * APPLY_FILTERS: yith_woocompare_empty_compare_message
				 *
				 * Filters the message shown when the comparison table is emtpy.
				 *
				 * @param string $message Message.
				 *
				 * @return string
				 */
				echo wp_kses_post( apply_filters( 'yith_woocompare_empty_compare_message', __( 'No products added in the comparison table.', 'yith-woocommerce-compare' ) ) );
				?>
			</p>
		<?php else : ?>
			<?php
			/**
			 * DO_ACTION: yith_woocompare_before_main_table
			 *
			 * Allows to render some content before the comparison table.
			 *
			 * @param array $products Products to show.
			 * @param bool  $fixed    Whether are products to show or not.
			 */
			do_action( 'yith_woocompare_before_main_table', $products, $fixed );
			?>
			<table id="yith-woocompare-table" class="compare-list has-background <?php echo $stock_icons ? 'with-stock-icons' : ''; ?> <?php echo esc_attr( $layout ); ?>">
				<thead>
				<tr>
					<th class="fields"></th>
					<?php echo str_repeat( '<td></td>', count( $products ) ); // phpcs:ignore ?>
					<td class="filler"></td>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<th class="fields"></th>
					<?php echo str_repeat( '<td></td>', count( $products ) ); // phpcs:ignore ?>
					<td class="filler"></td>
				</tr>
				</tfoot>

				<tbody>

				<?php if ( ! $show_product_info && ! $fixed ) : ?>
					<tr class="remove">
						<th></th>
						<?php
						$index = 0;
						foreach ( $products as $product_id => $product ) :
							$product_class = ( $index % 2 ? 'even' : 'odd' ) . ' product_' . $product_id
							?>
							<td class="<?php echo esc_attr( $product_class ); ?>">
								<?php $table->output_remove_anchor( $product_id ); ?>
							</td>
							<?php
							++$index;
						endforeach;
						?>
						<td class="filler"></td>
					</tr>
				<?php endif; ?>

				<?php if ( $show_product_info ) : ?>
					<tr class="product_info">
						<th>
							<?php $table->output_table_heading(); ?>
						</th>

						<?php
						$index = 0;
						foreach ( $products as $product_id => $product ) :
							$product_class = ( $index % 2 ? 'even' : 'odd' ) . ' product_' . $product_id;
							?>
							<td class="<?php echo esc_attr( $product_class ); ?>">
								<?php
								empty( $fields['image'] ) && ! $fixed && $table->output_remove_anchor( $product_id );

								if ( ! empty( $fields['image'] ) || empty( $fields['title'] ) ) {
									if ( ! empty( $fields['image'] ) ) :
										?>
										<div class="image-wrap">
											<?php if ( ! $fixed ) : ?>
												<div class="image-overlay">
													<?php $table->output_remove_anchor( $product_id ); ?>
												</div>
											<?php endif; ?>
											<?php echo $product->get_image( $image_size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</div>
									<?php
									endif;

									if ( ! empty( $fields['title'] ) ) :
										?>
										<a class="product-anchor" href="<?php echo esc_attr( $product->get_permalink() ); ?>">
											<h4 class="product_title">
												<?php echo esc_html( $product->get_title() ); ?>
											</h4>
										</a>
									<?php
									endif;
								}

								do_action( 'yith_woocompare_before_product_info_add_to_cart', $product, $fields );

								if ( ! empty( $fields['add_to_cart'] ) ) {
									$table->output_product_add_to_cart();
								}

								do_action( 'yith_woocompare_after_product_info_add_to_cart', $product, $fields );
								?>
							</td>
							<?php
							++$index;
						endforeach;
						?>
						<td class="filler"></td>
					</tr>
				<?php endif; ?>

				<?php foreach ( $fields as $field => $name ) : ?>
					<?php
					if ( apply_filters( 'yith_woocompare_table_should_skip_field', false, $field ) ) {
						continue;
					}
					$row_classes = array();

					if ( in_array( $field, array( 'title', 'image', 'add_to_cart' ), true ) ) {
						continue;
					}

					if ( in_array( $field, array( 'price_2', 'add_to_cart_2' ), true ) ) {
						$field         = str_replace( '_2', '', $field );
						$name          = $default_fields[ $field ];
						$row_classes[] = 'repeated';
					}

					if ( in_array( $field, $different, true ) ) {
						$row_classes[] = 'different';
					}

					$row_classes[] = $field;
					?>

					<tr class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
						<th><?php echo esc_html( $name ); ?></th>

						<?php
						$index = 0;
						foreach ( $products as $product_id => $product ) :
							// Set td class.
							$product_class = ( $index % 2 ? 'even' : 'odd' ) . ' product_' . $product_id;

							if ( 'stock' === $field ) {
								$availability   = $product->get_availability();
								$product_class .= ' ' . ( empty( $availability['class'] ) ? 'in-stock' : $availability['class'] );
							}
							?>

							<td class="<?php echo esc_attr( $product_class ); ?>">
								<?php
								switch ( $field ) {
									case 'add_to_cart':
										$table->output_product_add_to_cart();
										break;
									case 'rating':
										$rating = function_exists( 'wc_get_rating_html' ) ? wc_get_rating_html( $product->get_average_rating() ) : $product->get_rating_html();
										echo $rating ? '<div class="woocommerce-product-rating">' . wp_kses_post( $rating ) . '</div>' : '-';
										break;
									default:
										/**
										 * APPLY_FILTERS: yith_woocompare_value_default_field
										 *
										 * Filters the default value for the field in the comparison table.
										 *
										 * @param string     $value   Field value.
										 * @param WC_Product $product Product object.
										 * @param string     $field   Field id to show.
										 *
										 * @return string
										 */
										echo wp_kses_post( apply_filters( 'yith_woocompare_value_default_field', empty( $product->fields[ $field ] ) ? '-' : do_shortcode( $product->fields[ $field ] ), $product, $field ) );
										break;
								}
								?>
							</td>
							<?php
							++$index;
						endforeach
						?>
						<td class="filler"></td>
					</tr>
				<?php endforeach; ?>

				</tbody>
			</table>

			<?php
			/**
			 * DO_ACTION: yith_woocompare_after_main_table
			 *
			 * Allows to render some content after the comparison table.
			 *
			 * @param array $products Products to show.
			 * @param bool  $fixed    Whether are products to show or not.
			 */
			do_action( 'yith_woocompare_after_main_table', $products, $fixed );
			?>

		<?php endif; ?>
	</div>

<?php
wp_enqueue_script( 'yith-woocompare-main' );