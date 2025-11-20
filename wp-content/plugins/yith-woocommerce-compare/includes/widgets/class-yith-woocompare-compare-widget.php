<?php
/**
 * Compare widget class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Compare\Widgets
 * @version 1.1.4
 */

defined( 'YITH_WOOCOMPARE' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WooCompare_Compare_Widget' ) ) {
	/**
	 * YITH_WooCompare_Compare_Widget Widget
	 *
	 * @since 1.0.0
	 */
	class YITH_WooCompare_Compare_Widget extends WP_Widget {

		/**
		 * Sets up the widgets
		 */
		public function __construct() {
			$widget_ops = array(
				'classname'   => 'yith-woocompare-widget',
				'description' => __(
					'The widget shows the list of products added to the comparison table.',
					'yith-woocommerce-compare'
				),
			);

			parent::__construct( 'yith-woocompare-widget', __( 'YITH WooCommerce Compare Widget', 'yith-woocommerce-compare' ), $widget_ops );
		}

		/**
		 * Outputs the content of the widget
		 *
		 * @param array $args General widgets argumetns.
		 * @param array $instance Widget specific instance.
		 */
		public function widget( $args, $instance ) {
			global $yith_woocompare;

			/**
			 * WPML Support
			 */
			$lang = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : false;

			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

			do_action( 'wpml_register_single_string', 'Widget', 'widget_yit_compare_title_text', $instance['title'] );
			$localized_widget_title = apply_filters( 'wpml_translate_single_string', $instance['title'], 'Widget', 'widget_yit_compare_title_text' );

			echo wp_kses_post( $before_widget . $before_title . $localized_widget_title . $after_title );

			self::output_content(
				array(
					'hide_empty' => isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : false,
				)
			);

			echo wp_kses_post( $after_widget );
		}

		/**
		 * Output template of this widget
		 *
		 * @param array   $args          An additional arguments array.
		 * @param boolean $should_return True to return, false otherwise.
		 */
		public static function output_content( $args = array(), $should_return = false ) {
			$args = array_merge(
				$args,
				array(
					'products_list' => YITH_WooCompare_Products_List::instance()->get(),
					'remove_url'    => YITH_WooCompare_Form_Handler::get_remove_action_url( 'all' ),
					'view_url'      => YITH_WooCompare_Frontend::instance()->get_table_url(),
				)
			);

			// Let's filter template arguments.
			/**
			 * APPLY_FILTERS: yith_woocompare_widget_template_args
			 *
			 * Filters the array with the arguments needed for the widget template.
			 *
			 * @param array $args Array of arguments.
			 *
			 * @return array
			 */
			$args = apply_filters( 'yith_woocompare_widget_template_args', $args );

			ob_start();
			wc_get_template( 'yith-compare-widget.php', $args, '', YITH_WOOCOMPARE_TEMPLATE_PATH );
			$template = ob_get_clean();

			if ( $should_return ) {
				return $template;
			}

			echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Outputs the options form on admin
		 *
		 * @param array $instance The widget options.
		 */
		public function form( $instance ) {
			global $woocommerce;

			$defaults = array(
				'title'      => '',
				'hide_empty' => 0,
			);

			$instance = wp_parse_args( (array) $instance, $defaults );

			?>
			<p>
				<label>
					<?php esc_html_e( 'Title', 'yith-woocommerce-compare' ); ?>:<br />
					<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" />
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_empty' ) ); ?>" value="yes" <?php checked( 'yes', $instance['hide_empty'] ); ?> />
					<?php esc_html_e( 'Hide if the comparison table is empty', 'yith-woocommerce-compare' ); ?>
				</label>
			</p>
			<?php
		}

		/**
		 * Processing widget options on save
		 *
		 * @param array $new_instance The new options.
		 * @param array $old_instance The previous options.
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			$instance['title']      = wp_strip_all_tags( $new_instance['title'] );
			$instance['hide_empty'] = $new_instance['hide_empty'];

			return $instance;
		}
	}
}

class_alias( 'YITH_WooCompare_Compare_Widget', 'YITH_Woocompare_Widget' );
