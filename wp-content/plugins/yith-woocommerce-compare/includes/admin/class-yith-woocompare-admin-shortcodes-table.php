<?php
/**
 * Admin Shortcodes table
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Adimn
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Admin_Shortcodes_Table' ) ) {
	/**
	 * Admin Shortcodes table class.
	 * Renders the table to show shortcodes
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Admin_Shortcodes_Table extends WP_List_Table {
		/**
		 * Returns columns available in table
		 *
		 * @return array Array of columns of the table
		 * @since 1.0.0
		 */
		public function get_columns() {
			$columns = array(
				'title'     => __( 'Title', 'yith-woocommerce-compare' ),
				'shortcode' => __( 'Shortcode', 'yith-woocommerce-compare' ),
				'actions'   => __( 'Actions', 'yith-woocommerce-compare' ),
			);

			return $columns;
		}

		/**
		 * Returns column to be sortable in table
		 *
		 * @return array Array of sortable columns
		 * @since 1.0.0
		 */
		public function get_sortable_columns() {
			return array();
		}

		/**
		 * Print table title
		 *
		 * @param WP_Post $item Current post.
		 * @return string Column content.
		 */
		public function column_title( $item ) {
			return $item->post_title;
		}

		/**
		 * Print shortcode field
		 *
		 * @param WP_Post $item Current post.
		 * @return string Column content.
		 */
		public function column_shortcode( $item ) {
			$layout      = get_post_meta( $item->ID, 'yith_woocompare_layout', true );
			$layout      = $layout ? $layout : 'wide';
			$product_ids = get_post_meta( $item->ID, 'yith_woocompare_product_ids', true );
			$product_ids = $product_ids ? $product_ids : array();

			if ( empty( $product_ids ) ) {
				return '';
			}

			ob_start();
			yith_plugin_fw_copy_to_clipboard( '[yith_woocompare_table products="' . implode( ',', $product_ids ) . '" layout="' . $layout . '"]' );

			return ob_get_clean();
		}

		/**
		 * Prints actions for each item
		 *
		 * @param WP_Post $item Current post.
		 * @return string Column content.
		 */
		public function column_actions( $item ) {
			$actions = array(
				'edit'   => array(
					'label' => __( 'Edit', 'yith-woocommerce-compare' ),
					'url'   => '#',
					'icon'  => 'edit',
				),
				'clone'  => array(
					'label' => __( 'Clone', 'yith-woocommerce-compare' ),
					'url'   => YITH_WooCompare_Admin_Shortcodes::get_action_url( 'clone', array( 'id' => $item->ID ) ),
					'icon'  => 'clone',
				),
				'delete' => array(
					'label' => __( 'Delete', 'yith-woocommerce-compare' ),
					'url'   => YITH_WooCompare_Admin_Shortcodes::get_action_url( 'delete', array( 'id' => $item->ID ) ),
					'icon'  => 'trash',
				),
			);
			$links   = '';

			if ( ! empty( $actions ) ) {
				foreach ( $actions as $action_id => $action_details ) {
					list( $label, $url, $icon ) = yith_plugin_fw_extract( $action_details, 'label', 'url', 'icon' );

					if ( ! $url || ! $label ) {
						continue;
					}

					$component_args = array(
						'name'   => $label,
						'url'    => $url,
						'class'  => $action_id,
						'action' => $action_id,
						'icon'   => $icon,
					);

					if ( 'delete' === $action_id ) {
						$component_args['confirm_data'] = array(
							'title'               => __( 'Confirm delete', 'yith-woocommerce-compare' ),
							'message'             => __( 'Are you sure you want to delete this item?', 'yith-woocommerce-compare' ),
							'confirm-button'      => __( 'Delete', 'yith-woocommerce-compare' ),
							'confirm-button-type' => 'delete',
						);
					}

					$links .= yith_plugin_fw_get_component(
						array_merge(
							array(
								'type' => 'action-button',
							),
							$component_args,
						),
						false
					);
				}
			}

			return $links;
		}

		/**
		 * Displays the table.
		 */
		public function display() {
			if ( empty( $this->items ) ) {
				$this->display_empty_state();
				return;
			}

			parent::display();
		}

		/**
		 * Displays empty state.
		 */
		protected function display_empty_state() {
			?>
			<div class="empty-table yith-woocompare-comparison-tables">
				<img src="<?php echo esc_url( YITH_WOOCOMPARE_ASSETS_URL ); ?>images/list-empty.svg">
				<p>
					<?php esc_html_e( 'You don\'t have any shortcodes yet.', 'yith-woocommerce-compare' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'But don\'t worry! You can create your first one here.', 'yith-woocommerce-compare' ); ?>
				</p>
				<a class="button button-primary add yith-add-button"><?php esc_html_e( 'Create a shortcode', 'yith-woocommerce-compare' ); ?></a>
			</div>
			<?php
		}

		/**
		 * Disable tablenav for this table
		 *
		 * @param string $which The location of the navigation: Either 'top' or 'bottom'.
		 */
		protected function display_tablenav( $which ) {
		}

		/**
		 * Generates content for a single row of the table.
		 *
		 * @since 3.1.0
		 *
		 * @param WP_Post $item The current item.
		 */
		public function single_row( $item ) {
			$layout      = get_post_meta( $item->ID, 'yith_woocompare_layout', true );
			$product_ids = get_post_meta( $item->ID, 'yith_woocompare_product_ids', true );
			$products    = array();

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );

				if ( ! $product ) {
					continue;
				}

				$products[ $product_id ] = $product->get_title();
			}

			$data = array(
				'id'       => $item->ID,
				'title'    => $item->post_title,
				'products' => $products,
				'layout'   => $layout,
			);

			?>
			<tr data-item="<?php echo wc_esc_json( wp_json_encode( $data ) ); // phpcs:ignore ?>">
				<?php $this->single_row_columns( $item ); ?>
			</tr>
			<?php
		}

		/**
		 * Gets a list of CSS classes for the WP_List_Table table tag.
		 *
		 * @since 3.1.0
		 *
		 * @return string[] Array of CSS classes for the table tag.
		 */
		protected function get_table_classes() {
			$classes   = parent::get_table_classes();
			$classes[] = 'yith-woocompare-comparison-tables';
			$classes[] = 'yith-plugin-fw__boxed-table';

			return $classes;
		}

		/**
		 * Prepare items to show in the table
		 */
		public function prepare_items() {
			$this->items = get_posts(
				array(
					'post_type'      => YITH_WooCompare_Admin_Shortcodes::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				)
			);
		}
	}
}
