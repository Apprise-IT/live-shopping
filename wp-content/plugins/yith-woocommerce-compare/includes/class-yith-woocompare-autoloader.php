<?php
/**
 * Autoloader.
 *
 * @package YITH\Compare\Classes
 * @version 2.0.0
 */

defined( 'YITH_WOOCOMPARE' ) || exit;

if ( ! class_exists( 'YITH_WooCompare_Autoloader' ) ) {
	/**
	 * Autoloader class.
	 */
	class YITH_WooCompare_Autoloader {

		/**
		 * Path to the includes directory.
		 *
		 * @var string
		 */
		private $include_path = '';

		/**
		 * The Constructor.
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );

			$this->include_path = YITH_WOOCOMPARE_INCLUDE_PATH;
		}

		/**
		 * Take a class name and turn it into a file name.
		 *
		 * @param string $class_name Class name.
		 *
		 * @return string
		 */
		private function get_file_name_from_class( $class_name ) {
			$filename = '';
			$base     = str_replace( '_', '-', $class_name );

			if ( false !== strpos( $class_name, 'interface' ) ) {
				$filename = 'interface-' . $base . '.php';
			} elseif ( false !== strpos( $class_name, 'trait' ) ) {
				$base     = str_replace( '-trait', '', $base );
				$filename = 'trait-' . $base . '.php';
			}

			if ( empty( $filename ) ) {
				$filename = 'class-' . $base . '.php';
			}

			return $filename;
		}

		/**
		 * Include a class file.
		 *
		 * @param string $path File path.
		 *
		 * @return bool Successful or not.
		 */
		private function load_file( $path ) {
			if ( $path && is_readable( $path ) ) {
				include_once $path;

				return true;
			}

			return false;
		}

		/**
		 * Auto-load plugins' classes on demand to reduce memory consumption.
		 *
		 * @param string $class_name Class name.
		 */
		public function autoload( $class_name ) {
			$class_name = strtolower( $class_name );

			if ( 0 !== strpos( $class_name, 'yith_woocompare' ) ) {
				return;
			}

			$file = $this->get_file_name_from_class( $class_name );
			$path = '';

			if ( false !== strpos( $class_name, 'interface' ) ) {
				$path = $this->include_path . 'interfaces/';
			} elseif ( false !== strpos( $class_name, 'trait' ) ) {
				$path = $this->include_path . 'traits/';
			} elseif ( false !== strpos( $class_name, 'abstract' ) ) {
				$path = $this->include_path . 'abstracts/';
			} elseif ( false !== strpos( $class_name, 'admin' ) ) {
				$path = $this->include_path . 'admin/';
			} elseif ( false !== strpos( $class_name, 'legacy' ) ) {
				$path = $this->include_path . 'legacy/';
			} elseif ( false !== strpos( $class_name, 'widget' ) && false === strpos( $class_name, 'widgets' ) ) {
				$path = $this->include_path . 'widgets/';
			} elseif ( false !== strpos( $class_name, 'shortcode' ) && false === strpos( $class_name, 'shortcodes' ) ) {
				$path = $this->include_path . 'shortcodes/';
			} elseif ( false !== strpos( $class_name, 'integration' ) && false === strpos( $class_name, 'integrations' ) ) {
				$path = $this->include_path . 'integrations/';
			}

			if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
				$this->load_file( $this->include_path . $file );
			}
		}
	}
}

new YITH_WooCompare_Autoloader();
