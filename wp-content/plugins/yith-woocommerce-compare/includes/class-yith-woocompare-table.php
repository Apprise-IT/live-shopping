<?php
/**
 * Compare Table class
 * Offers methods to output comparison table
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Classes
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Table' ) ) {
	/**
	 * Compare table, including a selection of products specified in the constructor,
	 * or retrieved from the cookie.
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Table {

		/**
		 * Name of the template being used to render table template
		 *
		 * @var string
		 */
		public $table_template = 'yith-compare-table.php';

		/**
		 * Name of the template being used to render template table
		 *
		 * @var string
		 */
		public $page_template = 'yith-compare-popup.php';

		/**
		 * Name of the template being used to render preview bar template
		 *
		 * @var string
		 */
		public $preview_bar_template = 'yith-compare-preview-bar.php';

		/**
		 * Array of arguments for table rendering.
		 *
		 * @var array
		 */
		protected $args = array();

		/**
		 * Whether the table is fixed or not.
		 *
		 * @var bool $fixed
		 */
		protected $fixed = false;

		/**
		 * Array of instances of this class, organized by hash of the given params
		 *
		 * @var YITH_WooCompare_Table[]|YITH_WooCompare_Table_Premium[]
		 */
		protected static $instances = array();

		/**
		 * Returns a specific instance of this class, depending on the argument passed
		 *
		 * @param array $args Array of arguments for the table rendering.
		 * @return YITH_WooCompare_Table|YITH_WooCompare_Table_Premium
		 */
		public static function instance( $args = array() ) {
			$instance_hash = md5( http_build_query( $args ) );
			$class         = static::class;
			$premium       = "{$class}_Premium";

			if ( class_exists( $premium ) ) {
				return $premium::instance( $args );
			}

			if ( ! isset( static::$instances[ $instance_hash ] ) ) {
				static::$instances[ $instance_hash ] = new $class( $args );
			}

			return static::$instances[ $instance_hash ];
		}

		/**
		 * Construct the object by accepting a set of parameters for table rendering.
		 *
		 * @param array $args Array of arguments for the table rendering.
		 */
		protected function __construct( $args = array() ) {
			$this->prepare_args( $args );
		}

		/**
		 * Returns a list of product ids included in the table
		 *
		 * @return int[]
		 */
		public function get_product_ids() {
			return $this->args['products'] ?? array();
		}

		/**
		 * Returns a list of product objects formatted and ready to appear in the table
		 *
		 * @param array $product_ids Optional array of product ids, submitted to be formatted.
		 * @return \WC_Product[]
		 */
		public function get_products( $product_ids = array() ) {
			$products = $product_ids ? $product_ids : $this->get_product_ids();

			if ( empty( $products ) ) {
				return array();
			}

			/**
			 * APPLY_FILTERS: yith_woocompare_support_show_single_variations
			 *
			 * Filters whether to show the single variations in the comparison table.
			 *
			 * @param bool $show_single_variations Whether to show the single variations or not.
			 *
			 * @return bool
			 */
			$show_variations = apply_filters( 'yith_woocompare_support_show_single_variations', true );

			/**
			 * APPLY_FILTERS: yith_woocompare_exclude_products_from_list
			 *
			 * Filters the products to exclude some from the comparison table.
			 *
			 * @param array $products Products to filter.
			 * @return array
			 */
			$products  = apply_filters( 'yith_woocompare_exclude_products_from_list', $products );
			$fields    = $this->get_fields( $product_ids );
			$formatted = array();

			foreach ( $products as $product_id ) {
				$product = wc_get_product( $product_id );

				if ( ! $product ) {
					continue;
				}

				$product->fields = array();
				$attributes      = $product->get_attributes();

				foreach ( $fields as $field => $name ) {
					switch ( $field ) {
						case 'title':
							$product->fields[ $field ] = $product->get_title();
							break;
						case 'price':
							$product->fields[ $field ] = $product->get_price_html();
							break;
						case 'image':
							$product->fields[ $field ] = absint( $product->get_image_id() );
							break;
						case 'description':
							// Get description.
							if ( 'yes' === get_option( 'yith_woocompare_use_full_description', 'no' ) ) {
								$description = $product->get_description();
							} else {
								$description = apply_filters( 'woocommerce_short_description', $product->get_short_description() );
							}

							// make sure that we strip any shortcode from description.
							$description = strip_shortcodes( $description );

							/**
							 * APPLY_FILTERS: yith_woocompare_products_description
							 *
							 * Filters the product description in the comparison table.
							 *
							 * @param string $description Product description.
							 *
							 * @return string
							 */
							$product->fields[ $field ] = apply_filters( 'yith_woocompare_products_description', $description );
							break;
						case 'stock':
							$availability = $product->get_availability();
							if ( empty( $availability['availability'] ) ) {
								$availability['availability'] = __( 'In stock', 'yith-woocommerce-compare' );
							}
							$product->fields[ $field ] = sprintf( '<span class="availability-label">%s</span>', esc_html( $availability['availability'] ) );
							break;
						case 'sku':
							$product->fields[ $field ] = $product->get_sku() ? $product->get_sku() : '-';
							break;
						case 'weight':
							$weight = $product->get_weight();
							$weight = $weight ? wc_format_localized_decimal( $weight ) . ' ' . esc_attr( get_option( 'woocommerce_weight_unit' ) ) : '-';

							$product->fields[ $field ] = sprintf( '<span>%s</span>', esc_html( $weight ) );
							break;
						case 'dimensions':
							$dimensions = wc_format_dimensions( $product->get_dimensions( false ) );
							if ( ! $dimensions ) {
								$dimensions = '-';
							}

							$product->fields[ $field ] = sprintf( '<span>%s</span>', esc_html( $dimensions ) );
							break;
						default:
							/**
							 * APPLY_FILTERS: yith_woocompare_field_separator
							 *
							 * Filters the field separator to show the products attributes in the comparison table.
							 *
							 * @param string     $separator Field separator.
							 * @param string     $field     Field to show.
							 * @param WC_Product $product   Product object.
							 *
							 * @return string
							 */
							$separator = apply_filters( 'yith_woocompare_field_separator', ', ', $field, $product );

							if ( taxonomy_exists( $field ) ) {
								$_product_id   = $product instanceof WC_Product_Variation && ! $show_variations ? $product->get_parent_id() : $product->get_id();
								$taxonomy      = $field;
								$product_terms = wp_get_post_terms( $_product_id, $taxonomy, array( 'fields' => 'id=>name' ) );

								$product->fields[ $taxonomy ] = implode( $separator, $product_terms );
							} elseif ( isset( $attributes[ $field ] ) ) {
								$product->fields[ $field ] = implode( $separator, $attributes[ $field ]->get_options() );
							} else {
								do_action_ref_array( 'yith_woocompare_field_' . $field, array( $product, &$product->fields ) );
							}
							break;
					}
				}

				$formatted[ $product_id ] = $product;
			}

			return $formatted;
		}

		/**
		 * Returns a list of fields that will appear in the table
		 *
		 * @param array $product_ids Optional array of product ids, submitted to retrieve related fields.
		 * @return array An array of fields, in the format [ 'key' => 'label' ].
		 */
		public function get_fields( $product_ids = array() ) {
			$products = $product_ids ? $product_ids : $this->get_product_ids();
			$defaults = YITH_WooCompare_Helper::get_default_table_fields( true );
			$fields   = get_option( 'yith_woocompare_fields', array() );
			$fields   = array_filter( $fields );

			foreach ( array_keys( $fields ) as $field ) {
				if ( isset( $defaults[ $field ] ) ) {
					$fields[ $field ] = $defaults[ $field ];
				}
			}

			/**
			 * APPLY_FILTERS: yith_woocompare_filter_table_fields
			 *
			 * Filters the fields to show in the comparison table.
			 *
			 * @param array $fields   Fields to show.
			 * @param array $products Products to show.
			 */
			return apply_filters( 'yith_woocompare_filter_table_fields', $fields, $products );
		}

		/**
		 * Prepare arguments for table output, and saves them in a class property
		 *
		 * @param array $args Additional args, used to override default in any rendering of this table.
		 * @return array Array of merged arguments.
		 */
		protected function prepare_args( $args = array() ) {
			$products           = ! empty( $args['products'] ) ? $args['products'] : YITH_WooCompare_Products_List::instance()->get();
			$fields             = $this->get_fields( $products );
			$default_fields     = YITH_WooCompare_Helper::get_default_table_fields();
			$repeat_price       = 'yes' === get_option( 'yith_woocompare_price_end', 'no' );
			$repeat_add_to_cart = 'yes' === get_option( 'yith_woocompare_add_to_cart_end', 'no' );
			$stock_icons        = 'yes' === get_option( 'yith_woocompare_replace_stock_labels_with_icons', 'no' );
			$show_product_info  = isset( $fields['title'] ) || isset( $fields['image'] ) || isset( $fields['add_to_cart'] );
			$iframe             = isset( $_REQUEST['iframe'] ) && 'yes' === $_REQUEST['iframe'] ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification
			$table_title        = get_option( 'yith_woocompare_table_text', __( 'Compare products', 'yith-woocommerce-compare' ) );
			$table_desc         = get_option( 'yith_woocompare_table_description' );
			$image_format       = get_option( 'yith_woocompare_table_image_format', 'thumb' );
			$image_size         = 'thumb' === $image_format ? 'yith-woocompare-image' : 'woocommerce_single';
			$different          = array();
			$fixed              = false;
			$layout             = 'wide';

			$this->args = apply_filters(
				'yith_woocompare_table_args',
				array_merge(
					array(
						'table' => $this,
					),
					compact( 'products', 'fields', 'default_fields', 'show_product_info', 'repeat_price', 'repeat_add_to_cart', 'stock_icons', 'fixed', 'iframe', 'table_title', 'table_desc', 'image_size', 'different', 'layout' ),
					$args
				)
			);

			return $this->args;
		}

		/**
		 * Returns html content of a specific template
		 *
		 * @param string $template Template to render (table|page).
		 * @return string Template HTML.
		 */
		public function get_template( $template ) {
			$available_templates = array(
				'table',
				'page',
				'preview_bar',
			);

			if ( ! in_array( $template, $available_templates, true ) ) {
				$template = current( $available_templates );
			}

			ob_start();
			$this->{"output_$template"}();
			return ob_get_clean();
		}

		/**
		 * Render comparison table
		 */
		public function output_table() {
			$args = array_merge(
				$this->args,
				array(
					'products' => $this->get_products(),
				)
			);

			wc_get_template( $this->table_template, $args, '', YITH_WOOCOMPARE_TEMPLATE_PATH );
		}

		/**
		 * Render comparison table
		 */
		public function output_page() {
			$args = array_merge(
				$this->args,
				array(
					'products' => $this->get_products(),
				)
			);

			wc_get_template( $this->page_template, $args, '', YITH_WOOCOMPARE_TEMPLATE_PATH );
		}

		/**
		 * Output table heading (title and description)
		 */
		public function output_table_heading() {
			list( $layout, $table_title, $table_desc ) = yith_plugin_fw_extract( $this->args, 'layout', 'table_title', 'table_desc' );

			if ( 'compact' === $layout ) {
				return;
			}

			if ( $table_title ) :
				?>
				<h2><?php echo wp_kses_post( $table_title ); ?></h2>
				<?php
			endif;

			if ( $table_desc ) :
				?>
				<p><?php echo wp_kses_post( $table_desc ); ?></p>
				<?php
			endif;
		}

		/**
		 * Render preview bar
		 */
		public function output_preview_bar() {
			$products               = $this->get_products();
			$has_more               = 5 < count( $products );
			$compare_button_text    = get_option( 'yith_woocompare_button_text_added', __( 'View comparison', 'yith-woocommerce-compare' ) );
			$compare_button_classes = array( 'yith-woocompare-open', 'button', wc_wp_theme_get_element_class_name( 'button' ) );
			$compare_url            = YITH_WooCompare_Frontend::instance()->get_table_url();

			$args = array_merge(
				$this->args,
				array(
					'has_more'               => $has_more,
					'remaining'              => $has_more ? count( $products ) - 4 : 0,
					'products'               => $has_more ? array_slice( $products, 0, 4, true ) : $products,
					'compare_button_text'    => $compare_button_text,
					'compare_button_classes' => implode( ' ', $compare_button_classes ),
					'compare_url'            => $compare_url,
				)
			);

			wc_get_template( $this->preview_bar_template, $args, '', YITH_WOOCOMPARE_TEMPLATE_PATH );
		}

		/**
		 * Output remove button
		 *
		 * @param int $product_id Product id.
		 */
		public function output_remove_anchor( $product_id ) {
			if ( $this->args['fixed'] ) {
				return;
			}

			$remove_url   = YITH_WooCompare_Form_Handler::get_remove_action_url( $product_id );
			$remove_icon  = apply_filters( 'yith_woocompare_remove_icon', 'x' );
			$remove_label = apply_filters( 'yith_woocompare_remove_label', esc_html__( 'Remove', 'yith-woocommerce-compare' ) );

			$button = sprintf(
				'<div class="remove"><a href="%1$s" data-iframe="%2$s" data-product_id="%3$d"><span class="remove">%4$s</span>%5$s</a></div>',
				esc_url( $remove_url ),
				esc_attr( $this->args['iframe'] ),
				esc_attr( $product_id ),
				wp_kses_post( $remove_icon ),
				wp_kses_post( $remove_label ),
			);

			echo wp_kses_post( $button );
		}

		/**
		 * Output Add to Cart button
		 */
		public function output_product_add_to_cart() {
			?>
				<div class="add_to_cart_wrap">
					<?php woocommerce_template_loop_add_to_cart(); ?>
				</div>
			<?php
		}
	}
}
