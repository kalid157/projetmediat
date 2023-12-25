<?php // phpcs:ignore
/**
 * Latest Post Shortcode Elementor custom extension.
 *
 * @since 8.7
 * @package lps
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Elementor LPS extension class
 * The main class that initiates and runs the plugin.
 */
final class Elementor_LPS_Extension {

	/**
	 * Plugin Version
	 *
	 * @var string The plugin version.
	 */
	const VERSION = '8.7';

	/**
	 * Minimum Elementor Version
	 *
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '2.4.0';

	/**
	 * Minimum PHP Version
	 *
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.0';

	/**
	 * Instance.
	 *
	 * @var Elementor_LPS_Extension The single instance of the class.
	 */
	private static $_instance = null; // phpcs:ignore

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return Elementor_LPS_Extension An instance of the class.
	 */
	public static function instance(): object {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', [ get_called_class(), 'i18n' ] );
		add_action( 'plugins_loaded', [ get_called_class(), 'init' ] );
	}

	/**
	 * Load plugin localization files.
	 *
	 * @return void
	 */
	public static function i18n() {
		load_plugin_textdomain( 'lps', false, basename( dirname( __DIR__ ) ) . '/langs' );
	}

	/**
	 * Load the plugin only after Elementor (and other plugins) are loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed load the files required to run the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		// Check if Elementor installed and activated.
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ get_called_class(), 'admin_notice_missing_main_plugin' ] );
			return;
		}

		// Check for required Elementor version.
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', [ get_called_class(), 'admin_notice_minimum_elementor_version' ] );
			return;
		}

		// Check for required PHP version.
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', [ get_called_class(), 'admin_notice_minimum_php_version' ] );
			return;
		}

		// Hook up the custom assets.
		$lps = new Latest_Post_Shortcode();
		add_action( 'elementor/editor/before_enqueue_scripts', [ $lps, 'add_shortcode_popup_container' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $lps, 'load_admin_assets' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $lps, 'load_slider_assets' ] );

		// Add Plugin actions.
		add_action( 'elementor/widgets/widgets_registered', [ get_called_class(), 'init_widgets' ] );
		add_action( 'elementor/controls/controls_registered', [ get_called_class(), 'init_controls' ] );
	}

	/**
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @return void
	 */
	public function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore
			unset( $_GET['activate'] ); // phpcs:ignore
		}

		$message = sprintf(
			/* Translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'lps' ),
			'<strong>' . esc_html__( 'LPS Elementor Extension', 'lps' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'lps' ) . '</strong>'
		);
		echo wp_kses_post( sprintf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message ) );
	}

	/**
	 * Warning when the site doesn't have a minimum required Elementor version.
	 *
	 * @return void
	 */
	public function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore
			unset( $_GET['activate'] ); // phpcs:ignore
		}

		$message = sprintf(
			// Translators: %1$s - plugin name, %2$s - required, %3$s - required version.
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'lps' ),
			'<strong>' . esc_html__( 'LPS Elementor Extension', 'lps' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'lps' ) . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);
		echo wp_kses_post( sprintf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message ) );
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function admin_notice_minimum_php_version() {
		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore
			unset( $_GET['activate'] ); // phpcs:ignore
		}

		$message = sprintf(
			// Translators: %1$s - plugin name, %2$s - required, %3$s - required version.
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'lps' ),
			'<strong>' . esc_html__( 'LPS Elementor Extension', 'lps' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'lps' ) . '</strong>',
			self::MINIMUM_PHP_VERSION
		);
		echo wp_kses_post( sprintf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message ) );
	}

	/**
	 * Include widgets files and register them.
	 *
	 * @return void
	 */
	public static function init_widgets() {
		// Include Widget files.
		require_once __DIR__ . '/widgets/class-lps-widgets.php';

		// Register widget.
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Lps_Widget() );
	}

	/**
	 * Include controls files and register them.
	 *
	 * @return void
	 */
	public static function init_controls() {
		// Include Control files.
		require_once __DIR__ . '/controls/class-lps-control.php';

		// Register control.
		\Elementor\Plugin::$instance->controls_manager->register_control( 'control-type-', new \Lps_Control() );
	}
}

Elementor_LPS_Extension::instance();
