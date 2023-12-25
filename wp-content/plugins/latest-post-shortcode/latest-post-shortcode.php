<?php // phpcs:ignore
/**
 * Plugin Name: Latest Post Shortcode
 * Plugin URI: https://iuliacazan.ro/latest-post-shortcode/
 * Description: This plugin allows you to create a dynamic content selection from your posts, pages and custom post types that can be embedded with a UI configurable shortcode. When used with WordPress >= 5.0 + Gutenberg, the plugin shortcode can be configured from the LPS block or any Classic block, using the plugin button.
 * Text Domain: lps
 * Domain Path: /langs
 * Version: 11.6.0
 * Author: Iulia Cazan
 * Author URI: https://profiles.wordpress.org/iulia-cazan
 * Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ
 * License: GPL2
 *
 * @package LPS
 *
 * Copyright (C) 2015-2023 Iulia Cazan
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Define the plugin version.
define( 'LPS_PLUGIN_VERSION', 11.60 );
define( 'LPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LPS_PLUGIN_SLUG', 'lps' );

/**
 * Class for Latest Post Shortcode.
 */
class Latest_Post_Shortcode {

	const PLUGIN_NAME        = 'Latest Post Shortcode';
	const PLUGIN_SUPPORT_URL = 'https://wordpress.org/support/plugin/latest-post-shortcode/';
	const PLUGIN_TRANSIENT   = 'lps-plugin-notice';
	const ASSETS_VERSION     = 'lps_asset_version';

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Check that configurator wrapper was set or not.
	 *
	 * @var array
	 */
	public static $wrapper_was_set = false;

	/**
	 * Tile pattern.
	 *
	 * @var array
	 */
	public static $tile_pattern = [];

	/**
	 * Tile pattern for ver 2 only.
	 *
	 * @var array
	 */
	public static $tile_pattern_ver2 = [];

	/**
	 * Tile content.
	 *
	 * @var string
	 */
	public static $tile_content = '';

	/**
	 * Taxonomy positions.
	 *
	 * @var array
	 */
	public static $tax_positions = [];

	/**
	 * Pugin tags replaceable.
	 *
	 * @var array
	 */
	public static $replaceable_tags = [ 'date', 'title', 'text', 'image', 'read_more_text', 'author', 'category', 'tags', 'show_mime', 'caption' ];

	/**
	 * Pugin order by options.
	 *
	 * @var array
	 */
	public static $orderby_options = [];

	/**
	 * Tile pattern links.
	 *
	 * @var array
	 */
	public static $tile_pattern_links;

	/**
	 * Tile pattern with no links.
	 *
	 * @var array
	 */
	public static $tile_pattern_nolinks;

	/**
	 * Title tags.
	 *
	 * @var array
	 */
	public static $title_tags = [];

	/**
	 * Slider wrap tags.
	 *
	 * @var array
	 */
	public static $slider_wrap_tags = [];

	/**
	 * Date limit units.
	 *
	 * @var array
	 */
	public static $date_limit_units = [];

	/**
	 * Current query statuses list.
	 *
	 * @var array
	 */
	public static $current_query_statuses_list = [];

	/**
	 * True if the Elementor editor is active.
	 *
	 * @var boolean
	 */
	public static $is_elementor_editor = false;

	/**
	 * The current assets version.
	 *
	 * @var string
	 */
	public static $assets_version = '';

	/**
	 * Get active object instance
	 *
	 * @return object
	 */
	public static function get_instance(): object {
		if ( ! self::$instance ) {
			self::$instance = new Latest_Post_Shortcode();
		}
		return self::$instance;
	}

	/**
	 * Class constructor. Includes constants and init methods.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Run action and filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		$class = get_called_class();

		// Allow to hook into tile patterns.
		add_action( 'init', [ $class, 'tile_pattern_setup' ], 1 );

		// Text domain load.
		add_action( 'plugins_loaded', [ $class, 'load_textdomain' ] );

		// Apply the tiles shortcodes.
		add_shortcode( 'latest-selected-content', [ $class, 'latest_selected_content' ] );

		if ( is_admin() ) {
			add_action( 'admin_footer', [ $class, 'add_shortcode_popup_container' ] );
			add_action( 'admin_enqueue_scripts', [ $class, 'load_admin_assets' ] );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $class, 'plugin_action_links' ] );
		} else {
			add_action( 'wp_enqueue_scripts', [ $class, 'load_assets' ] );
			add_action( 'wp_enqueue_scripts', [ $class, 'load_slider_assets' ] );
		}

		add_action( 'wp_insert_post', [ $class, 'execute_lps_cache_reset' ] );
		add_action( 'post_updated', [ $class, 'execute_lps_cache_reset' ] );
		add_action( 'wp_trash_post', [ $class, 'execute_lps_cache_reset' ] );
		add_action( 'before_delete_post', [ $class, 'execute_lps_cache_reset' ] );

		add_action( 'wp_ajax_nopriv_lps_navigate_to_page', [ $class, 'lps_navigate_callback' ] );
		add_action( 'wp_ajax_lps_navigate_to_page', [ $class, 'lps_navigate_callback' ] );
		add_action( 'wp_ajax_lps_reset_cache', [ $class, 'lps_reset_cache' ] );

		add_action( 'admin_notices', [ $class, 'plugin_admin_notices' ] );
		add_action( 'wp_ajax_plugin-deactivate-notice-lps', [ $class, 'plugin_admin_notices_cleanup' ] );
		add_action( 'plugins_loaded', [ $class, 'plugin_ver_check' ] );

		// Attempt to do one last filter of the assets.
		add_action( 'admin_init', [ $class, 'lps_assets_options' ] );
		add_action( 'wp_enqueue_scripts', [ $class, 'lps_filter_plugin_assets' ], 999 );

		// Attempt to fix the pagination for single pages.
		add_action( 'parse_query', [ $class, 'fix_request_redirect' ] );
	}

	/**
	 * Set the assets version from the DB, if possible.
	 *
	 * @return void
	 */
	public static function get_assets_version() {
		self::$assets_version = get_option( self::ASSETS_VERSION, LPS_PLUGIN_VERSION );
	}

	/**
	 * Define the tile patterns.
	 *
	 * @return void
	 */
	public static function tile_pattern_setup() {
		self::get_assets_version();

		if ( class_exists( 'Latest_Post_Shortcode_Slider' ) ) {
			// Deactivate the extension, it is no longer supported.
			if ( function_exists( 'deactivate_plugins' ) ) {
				deactivate_plugins( '/latest-post-shortcode-slider-extension/latest-post-shortcode-slider.php' );
			}

			// Mention to the user that the extension is not supported anymore.
			add_action( 'admin_notices', function () {
				$class   = 'notice notice-error';
				$message = __( 'The Latest Post Shortcode Slider Extension is no longer supported, and it has been deactivated. If you need to display posts as a slider you can find the settings integrated in the Latest Post Shortcode plugin', 'lps' );

				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			}, 99 );
		}

		self::$tile_pattern = [
			// No link.
			0  => '[image][title][text][read_more_text]',
			1  => '[title][image][text][read_more_text]',
			2  => '[title][text][image][read_more_text]',
			18 => '[title][text][read_more_text][image]',

			// Full link.
			3  => '[a][image][title][text][read_more_text][/a]',
			11 => '[a][title][image][text][read_more_text][/a]',
			14 => '[a][title][text][image][read_more_text][/a]',
			19 => '[a][title][text][read_more_text][image][/a]',

			// Partial link.
			13 => '[title][image][text][a-r][read_more_text][/a]',
			17 => '[title][text][image][a-r][read_more_text][/a]',
			25 => '[image][a][title][/a][text][read_more_text]',
			26 => '[image][a][title][/a][text][a-r][read_more_text][/a]',
			27 => '[a][image][title][/a][text][read_more_text]',
			5  => '[image][title][text][a-r][read_more_text][/a]',
			28 => '[a][image][title][/a][text][a-r][read_more_text][/a]',
			22 => '[title][text][a-r][read_more_text][/a][image]',
		];

		// Allow to hook into tile patterns.
		self::$tile_pattern         = apply_filters( 'lps_filter_tile_patterns', self::$tile_pattern ); // Legacy.
		self::$tile_pattern         = apply_filters( 'lps/override_card_patterns', self::$tile_pattern );
		self::$tile_pattern_links   = [];
		self::$tile_pattern_nolinks = [];
		self::$tile_pattern_ver2    = [ 0, 5, 18, 22, 25, 26 ];
		self::$title_tags           = [ 'h3', 'h2', 'h1', 'h4', 'h5', 'h6', 'b', 'strong', 'em', 'p', 'div', 'span' ];
		self::$date_limit_units     = [
			'months' => esc_html__( 'months', 'lps' ),
			'weeks'  => esc_html__( 'weeks', 'lps' ),
			'days'   => esc_html__( 'days', 'lps' ),
			'hours'  => esc_html__( 'hours', 'lps' ),
		];
		self::$slider_wrap_tags     = [ 'div', 'p', 'span', 'section' ];

		foreach ( self::$tile_pattern as $k => $v ) {
			if ( substr_count( $v, '[a]' ) !== 0 || substr_count( $v, '[a-r]' ) !== 0 ) {
				array_push( self::$tile_pattern_links, $k );
			} else {
				array_push( self::$tile_pattern_nolinks, $k );
			}
		}

		self::$orderby_options = [
			'dateD'         => [
				'title'   => esc_html__( 'Date Descending', 'lps' ),
				'order'   => 'DESC',
				'orderby' => 'date',
			],
			'dateA'         => [
				'title'   => esc_html__( 'Date Ascending', 'lps' ),
				'order'   => 'ASC',
				'orderby' => 'date',
			],
			'menuD'         => [
				'title'   => esc_html__( 'Menu Order Descending', 'lps' ),
				'order'   => 'DESC',
				'orderby' => 'menu_order',
			],
			'menuA'         => [
				'title'   => esc_html__( 'Menu Order Ascending', 'lps' ),
				'order'   => 'ASC',
				'orderby' => 'menu_order',
			],
			'titleD'        => [
				'title'   => esc_html__( 'Title Descending', 'lps' ),
				'order'   => 'DESC',
				'orderby' => 'title',
			],
			'titleA'        => [
				'title'   => esc_html__( 'Title Ascending', 'lps' ),
				'order'   => 'ASC',
				'orderby' => 'title',
			],
			'idD'           => [
				'title'   => esc_html__( 'ID Descending', 'lps' ),
				'order'   => 'DESC',
				'orderby' => 'ID',
			],
			'idA'           => [
				'title'   => esc_html__( 'ID Ascending', 'lps' ),
				'order'   => 'ASC',
				'orderby' => 'ID',
			],
			'random'        => [
				'title'   => esc_html__( 'Random *', 'lps' ),
				'order'   => 'DESC',
				'orderby' => 'rand',
			],
			'metaValueD'    => [
				'title'   => esc_html__( 'Text Meta Value Descending', 'lps' ),
				'order'   => 'DESC',
				'orderby' => 'meta_value',
			],
			'metaValueA'    => [
				'title'   => esc_html__( 'Text Meta Value Ascending', 'lps' ),
				'order'   => 'ASC',
				'orderby' => 'meta_value',
			],
			'metaValueNumD' => [
				'title'   => esc_html__( 'Numeric Meta Value Descending', 'lps' ),
				'order'   => 'DESC',
				'orderby' => 'meta_value_num',
			],
			'metaValueNumA' => [
				'title'   => esc_html__( 'Numeric Meta Value Ascending', 'lps' ),
				'order'   => 'ASC',
				'orderby' => 'meta_value_num',
			],
			'relevance'     => [
				'title'   => esc_html__( 'Relevance *', 'lps' ),
				'order'   => 'DESC',
				'orderby' => 'relevance',
			],
		];

		self::$tax_positions = [
			'before-title'          => esc_html__( 'before title', 'lps' ),
			'after-title'           => esc_html__( 'after title', 'lps' ),
			'before-image'          => esc_html__( 'before image', 'lps' ),
			'after-image'           => esc_html__( 'after image', 'lps' ),
			'before-text'           => esc_html__( 'before text', 'lps' ),
			'after-text'            => esc_html__( 'after text', 'lps' ),
			'before-read_more_text' => esc_html__( 'before read more text', 'lps' ),
			'after-read_more_text'  => esc_html__( 'after read more text', 'lps' ),
			'before-date'           => esc_html__( 'before date', 'lps' ),
			'after-date'            => esc_html__( 'after date', 'lps' ),
		];

		$filtered_tax = self::filtered_taxonomies();
		if ( ! empty( $filtered_tax ) ) {
			foreach ( $filtered_tax as $key => $value ) {
				array_push( self::$replaceable_tags, $key );
			}
		}

		self::$replaceable_tags = array_unique( self::$replaceable_tags );
		$display_posts_list     = [
			'title'                     => esc_html__( 'Title', 'lps' ),
			'title,excerpt'             => esc_html__( 'Title + Post Excerpt', 'lps' ),
			'title,content'             => esc_html__( 'Title + Post Content', 'lps' ),
			'title,excerpt-small'       => esc_html__( 'Title + Few Chars From The Excerpt', 'lps' ),
			'title,content-small'       => esc_html__( 'Title + Few Chars From The Content', 'lps' ),
			'date'                      => esc_html__( 'Date', 'lps' ),
			'title,date'                => esc_html__( 'Title + Date', 'lps' ),
			'title,date,excerpt'        => esc_html__( 'Title + Date + Post Excerpt', 'lps' ),
			'title,date,content'        => esc_html__( 'Title + Date + Post Content', 'lps' ),
			'title,date,excerpt-small'  => esc_html__( 'Title + Date + Few Chars From The Excerpt', 'lps' ),
			'title,date,content-small'  => esc_html__( 'Title + Date + Few Chars From The Content', 'lps' ),
			'date,title'                => esc_html__( 'Date + Title', 'lps' ),
			'date,title,excerpt'        => esc_html__( 'Date + Title + Post Excerpt', 'lps' ),
			'date,title,content'        => esc_html__( 'Date + Title + Post Content', 'lps' ),
			'date,title,excerpt-small'  => esc_html__( 'Date + Title + Few Chars From The Excerpt', 'lps' ),
			'date,title,content-small'  => esc_html__( 'Date + Title + Few Chars From The Content', 'lps' ),
			'date,title,excerptcontent' => esc_html__( 'Date + Title + Post Excerpt + Post Content', 'lps' ),
			'date,title,contentexcerpt' => esc_html__( 'Date + Title + Post Content + Post Excerpt', 'lps' ),
		];

		// Maybe apply custom extra type.
		$display_posts_list = apply_filters( 'lps_filter_display_posts_list', $display_posts_list ); // Legacy.
		$display_posts_list = apply_filters( 'lps/override_card_display', $display_posts_list );
		self::$tile_content = $display_posts_list;
	}

	/**
	 * Load text domain for internalization.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'lps', false, basename( __DIR__ ) . '/langs' );
	}

	/**
	 * Load the plugin assets.
	 *
	 * @param bool $pagination Enqueue or not pagination styles.
	 * @return void
	 */
	public static function load_assets( $pagination = true ) { // phpcs:ignore
		wp_register_script( 'lps-frontend-variables', '', [], LPS_PLUGIN_VERSION . self::$assets_version, false );
		wp_enqueue_script( 'lps-frontend-variables' );
		wp_add_inline_script(
			'lps-frontend-variables',
			sprintf(
				'var lpsSettings = %s;',
				wp_json_encode( [
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				] )
			),
			'before'
		);

		wp_enqueue_style(
			'lps-style-legacy',
			plugins_url( '/assets/css/style-legacy.min.css', __FILE__ ),
			[],
			LPS_PLUGIN_VERSION . self::$assets_version,
			false
		);

		wp_enqueue_style(
			'lps-style',
			plugins_url( '/assets/css/style.min.css', __FILE__ ),
			[],
			LPS_PLUGIN_VERSION . self::$assets_version,
			false
		);

		if ( $pagination ) {
			wp_register_script(
				'lps-ajax-pagination-js',
				plugins_url( '/assets/js/custom-pagination.min.js', __FILE__ ),
				[],
				LPS_PLUGIN_VERSION . self::$assets_version,
				true
			);
			wp_localize_script(
				'lps-ajax-pagination-js',
				'LPS',
				[
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				]
			);
			wp_enqueue_script( 'lps-ajax-pagination-js' );
		}
	}

	/**
	 * Load the admin assets.
	 *
	 * @return void
	 */
	public static function load_admin_assets() {
		self::load_assets( false );

		wp_enqueue_style(
			'lps-admin-style',
			plugins_url( '/assets/css/admin-style.css', __FILE__ ),
			[],
			LPS_PLUGIN_VERSION . self::$assets_version,
			false
		);

		// Register the custom script first.
		wp_register_script(
			'lps-admin-shortcode-button',
			plugins_url( '/assets/js/custom.min.js', __FILE__ ),
			[ 'jquery' ],
			LPS_PLUGIN_VERSION . self::$assets_version,
			false
		);

		// Localize the custom module script with our data.
		wp_localize_script(
			'lps-admin-shortcode-button',
			'lpsGenVars',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'icon'        => plugins_url( '/lps-block/icon-lps.svg', __FILE__ ),
				'title'       => esc_html__( 'Latest Post Shortcode', 'lps' ),
				'outputTypes' => implode( ' ', array_filter( array_keys( self::get_card_output_types() ) ) ),
			]
		);

		// The script can be enqueued now.
		wp_enqueue_script( 'lps-admin-shortcode-button' );

		self::$is_elementor_editor = false;

		$maybe_elementor = filter_input( INPUT_GET, 'action', FILTER_DEFAULT );
		if ( ! empty( $maybe_elementor ) && 'elementor' === $maybe_elementor ) {
			self::$is_elementor_editor = true;
			self::load_slider_assets();
		}
	}

	/**
	 * Load the slider assets from local files instead of CDN, to make it faster, and available offline.
	 *
	 * @param  bool $forced Load the assets without checking if it's necessary.
	 * @return void
	 */
	public static function load_slider_assets( bool $forced = false ): void {
		if ( ! $forced ) {
			global $post;

			$text  = ( ! empty( $post->post_content ) ) ? $post->post_content : '';
			$text .= serialize( get_option( 'widget_text' ) ) . serialize( get_option( 'widget_custom_html' ) ); // phpcs:ignore

			if ( empty( $text ) ) {
				return;
			}
			if ( ! substr_count( $text, '[latest-selected-content' ) || ! substr_count( $text, 'output="slider"' ) ) {
				return;
			}
		}

		wp_enqueue_style(
			'lps-slick-style',
			plugins_url( '/assets/css/slick-custom-theme.min.css', __FILE__ ),
			[],
			LPS_PLUGIN_VERSION,
			false
		);

		// Slick style from //cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css.
		wp_enqueue_style(
			'lps-slick',
			plugins_url( '/assets/slick-1.8.1/slick.min.css', __FILE__ ),
			[],
			LPS_PLUGIN_VERSION,
			false
		);

		// Slick js from //cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js.
		wp_enqueue_script(
			'lps-slick',
			plugins_url( '/assets/slick-1.8.1/slick.min.js', __FILE__ ),
			[ 'jquery' ],
			LPS_PLUGIN_VERSION,
			false
		);
	}

	/**
	 * Return all private and all public statuses defined.
	 *
	 * @return array
	 */
	public static function get_statuses(): array {
		global $wp_post_statuses;
		$arr = [
			'public'  => [],
			'private' => [],
		];
		if ( ! empty( $wp_post_statuses ) ) {
			$exclude = [ 'auto-draft', 'request-confirmed', 'request-pending', 'request-failed', 'request-completed', 'trash', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed', 'wc-checkout-draft', 'flamingo-spam', 'in-progress', 'failed' ];
			foreach ( $wp_post_statuses as $t => $v ) {
				if ( $v->public ) {
					$arr['public'][] = $t;
				} elseif ( ! in_array( $t, $exclude, true ) ) {
						$arr['private'][] = $t;
				}
			}
		}
		self::get_ctps();

		return $arr;
	}

	/**
	 * Return the defined and filtered CPTs.
	 *
	 * @return array
	 */
	public static function get_ctps(): array {
		$cpts   = [];
		$ptypes = get_post_types( [], 'objects' );
		if ( ! empty( $ptypes ) ) {
			$exclude = [ 'revision', 'nav_menu_item', 'oembed_cache', 'custom_css', 'customize_changeset', 'user_request', 'wp_block', 'wpcf7_contact_form', 'amp_validated_url', 'scheduled-action', 'shop_order', 'shop_order_refund', 'shop_coupon', 'shop_order_placehold', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', 'e-landing-page', 'elementor_library' ];
			foreach ( $ptypes as $t => $v ) {
				if ( ! in_array( $t, $exclude, true ) ) {
					$cpts[ $t ] = $v->label;
				}
			}
		}
		return $cpts;
	}

	/**
	 * Return the available sites.
	 *
	 * @return array
	 */
	public static function get_sites(): array {
		if ( ! is_multisite() ) {
			return [];
		}

		$list = [];
		$args = \apply_filters( 'lps/filter_sites_list', [ 'public' => '1' ] );
		foreach ( \get_sites( $args ) as $site ) {
			if ( ! empty( $site->blog_id ) ) {
				$info = \get_blog_details( $site->blog_id );

				$list[ $site->blog_id ] = $info->blogname ?? $site->path;
			}
		}

		return $list;
	}

	/**
	 * The custom patterns start with _custom_.
	 *
	 * @param  string $tile_pattern A tile pattern.
	 * @return bool
	 */
	public static function tile_markup_is_custom( string $tile_pattern = '' ): bool {
		$use_custom_markup = false;
		if ( '_custom_' === substr( $tile_pattern, 1, 8 ) ) {
			$use_custom_markup = true;
		}
		return $use_custom_markup;
	}

	/**
	 * Get the filtered card output types.
	 *
	 * @return array
	 */
	public static function get_card_output_types() {
		$list = \apply_filters( 'lps/card_output_types', [
			''             => esc_html__( '-- unspecified --', 'lps' ),
			'as-column'    => esc_html__( 'vertical card', 'lps' ),
			'h-image-info' => esc_html__( 'horizontal card: image + info', 'lps' ),
			'h-info-image' => esc_html__( 'horizontal card: info + image', 'lps' ),
			'as-overlay'   => esc_html__( 'overlay', 'lps' ),
		] );

		if ( ! is_array( $list ) ) {
			return [];
		}

		return $list;
	}

	/**
	 * Get the filtered card output types.
	 *
	 * @param  array $args Shortcode arguments.
	 * @return string
	 */
	public static function get_card_output_type_from_args( array $args = [] ): string {
		if ( empty( $args['css'] ) ) {
			// Fail-fast, nothing to compare.
			return '';
		}

		$css     = explode( ' ', $args['css'] );
		$options = array_keys( self::get_card_output_types() );
		$match   = array_unique( array_intersect( $options, $css ) );
		return trim( implode( '', $match ) );
	}

	/**
	 * The list of filtered taxonomies.
	 *
	 * @return array
	 */
	public static function filtered_taxonomies(): array {
		$list = [];
		$tax  = get_taxonomies( [], 'objects' );
		if ( ! empty( $tax ) ) {
			$exclude = [ 'post_tag', 'nav_menu', 'link_category', 'post_format', 'amp_template', 'elementor_library_type', 'elementor_library_category', 'elementor_library', 'wp_theme' ];
			foreach ( $tax as $k => $v ) {
				if ( ! in_array( $k, $exclude, true ) ) {
					if ( ! empty( $v->public ) ) {
						$list[ $k ] = $v;
					}
				}
			}
		}
		return $list;
	}

	/**
	 * Add some content to the bottom of the page.
	 * This will be shown in the inline modal.
	 */
	public static function add_shortcode_popup_container() {
		if ( true === self::$wrapper_was_set ) {
			// Fail-fast, this was used.
			return;
		}

		self::$wrapper_was_set = true;
		$display_posts_list    = self::$tile_content;
		?>

		<div id="lps_shortcode_popup_container_bg" style="display:none;"></div>
		<div id="lps_shortcode_popup_container" style="display:none;">
			<div class="lps_maintable_buttons">
				<button type="button" id="lps-link-close" onclick="lpsClose();"><span class="dashicons dashicons-no"></span></button>
				<button type="button" id="lps-link-up" onclick="lpsMenu('#tabs-0');"><span class="dashicons dashicons-arrow-up-alt"></span></button>
				<button type="button" id="lps-link-up-mobile" onclick="lpsMenu('top');"><span class="dashicons dashicons-arrow-up-alt"></span></button>
				<table width="100%" cellpadding="0" cellspacing="0" class="lps_maintable">
					<tr>
						<td class="shortcode-preview">
							<div class="inner">
								<h1 class="lps_shortcode_popup_container_title">
								<span class="lps-lightbox-icon"><?php include __DIR__ . '/lps-block/icon-lps.svg'; ?></span> <?php esc_html_e( 'Latest Post Shortcode', 'lps' ); ?></h1>
								<div class="clear"></div>
								<hr class="sep">
								<a id="lps-embed-button" class="button button-primary float-right" onclick="lpsEmbed();"><?php esc_html_e( 'Embed Shortcode', 'lps' ); ?></a>
								<h3><?php esc_html_e( 'Preview', 'lps' ); ?></h3>
								<div id="lps-preview">
									<div id="lps_preview_embed_shortcode">[latest-selected-content ver="2" type="post" limit="1" tag="news"]</div>
								</div>
								<div class="clear"></div>
								<div id="lps_reset_cache-wrap"><a id="lps_reset_cache" href="#" onclick="lpsResetCache();"><span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Reset Cache', 'lps' ); ?></a></div>
								<hr class="sep">
								<h3><?php esc_html_e( 'Settings', 'lps' ); ?></h3>
								<ul class="lps-ui-menu">
									<li id="menu-tabs-0" class="selected"><a onclick="lpsMenu('#tabs-0');"><?php esc_html_e( 'Output Type', 'lps' ); ?></a></li>
									<li id="menu-tabs-1"><a onclick="lpsMenu('#tabs-1');"><?php esc_html_e( 'Content & Filters', 'lps' ); ?></a></li>
									<li id="menu-tabs-2"><a onclick="lpsMenu('#tabs-2');"><?php esc_html_e( 'Limit & Pagination', 'lps' ); ?></a></li>
									<li id="menu-tabs-3"><a onclick="lpsMenu('#tabs-3');"><?php esc_html_e( 'Display Settings', 'lps' ); ?></a></li>
									<li id="menu-tabs-4"><a onclick="lpsMenu('#tabs-4');"><?php esc_html_e( 'Extra Options', 'lps' ); ?></a></li>
								</ul>
								<input type="hidden" name="lps_shortcode_popup_container_current_menu" id="lps_shortcode_popup_container_current_menu" value="#menu-tabs-0">
								<div class="clear"></div>
								<?php self::show_donate_text(); ?>
								<div class="clear"></div>
							</div>
						</td>
						<td class="shortcode-settings">
							<div class="inner">
								<div id="tabs-0" class="settings-group">
									<h1>1. <?php esc_html_e( 'Output Type', 'lps' ); ?></h1>

									<div class="settings-block">
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td>
													<?php esc_html_e( 'Version', 'lps' ); ?>
												</td>
												<td>
													<select name="lps_ver" id="lps_ver" data-default="2" onchange="lpsRefresh()">
														<option value=""><?php esc_html_e( '1 (available for < 11.0.0 - will be deprecated in future versions)', 'lps' ); ?></option>
														<option value="2"><?php esc_html_e( '2 (recommended starting with 11.0.0)', 'lps' ); ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<td>
													<?php esc_html_e( 'Display as', 'lps' ); ?>
												</td>
												<td>
													<img src="<?php echo esc_url( plugins_url( '/assets/images/type-grid.jpg', __FILE__ ) ); ?>" onclick="selectTypeImg('');" width="48%">

													<img src="<?php echo esc_url( plugins_url( '/assets/images/type-slider.jpg', __FILE__ ) ); ?>" onclick="selectTypeImg('slider')" width="48%">

													<select name="lps_output" id="lps_output" data-default="" onchange="lpsRefresh()">
														<option value=""><?php esc_html_e( 'post grid/list/tiles', 'lps' ); ?></option>
														<option value="slider"><?php esc_html_e( 'slider', 'lps' ); ?></option>
													</select>
												</td>
											</tr>
										</table>
									</div>
								</div>
								<hr class="sep">
								<div id="tabs-1" class="settings-group">
									<h1>2. <?php esc_html_e( 'Content & Filters', 'lps' ); ?></h1>
									<div class="settings-block"><h3>
										<?php esc_html_e( 'Post Types, Status & Order', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<?php
											if ( is_multisite() ) {
												$sites = self::get_sites();
												if ( ! empty( $sites ) ) :
													?>
													<tr>
														<td><?php esc_html_e( 'Site ID', 'lps' ); ?></td>
														<td>
															<select name="lps_site_id" id="lps_site_id" data-default="" onchange="lpsRefresh()">
																<option value="">~ <?php esc_html_e( 'Current site', 'lps' ); ?>~ </option>
																<?php foreach ( $sites as $k => $v ) : ?>
																	<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option>
																<?php endforeach; ?>
															</select>
														</td>
													</tr>
													<?php
												endif;
											}
											?>
											<tr>
												<td><?php esc_html_e( 'Post Type', 'lps' ); ?></td>
												<td>
													<select name="lps_post_type" id="lps_post_type" data-default="" onchange="lpsRefresh()">
														<option value=""><?php esc_html_e( 'Any', 'lps' ); ?></option>
														<?php $post_types = self::get_ctps(); ?>
														<?php if ( ! empty( $post_types ) ) : ?>
															<?php foreach ( $post_types as $k => $v ) : ?>
																<?php if ( ! in_array( $k, [ 'revision', 'nav_menu_item', 'oembed_cache', 'custom_css', 'customize_changeset', 'user_request', 'wp_block', 'wpcf7_contact_form', 'amp_validated_url', 'scheduled-action', 'shop_order', 'shop_order_refund', 'shop_coupon' ], true ) ) : ?>
																	<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $k ); ?></option>
																<?php endif; ?>
															<?php endforeach; ?>
														<?php endif; ?>
													</select>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Post Status', 'lps' ); ?></td>
												<td>
													<?php $st = self::get_statuses(); ?>
													<?php foreach ( $st['public'] as $pu ) : ?>
														<label><input type="checkbox" name="lps_status[]" id="lps_status_<?php echo esc_attr( $pu ); ?>" value="<?php echo esc_attr( $pu ); ?>" onclick="lpsRefresh()" class="lps_status"><b><?php echo esc_html( $pu ); ?></b></label>
													<?php endforeach; ?>
													<?php foreach ( $st['private'] as $pr ) : ?>
														<label><input type="checkbox" name="lps_status[]" id="lps_status_<?php echo esc_attr( $pr ); ?>" value="<?php echo esc_attr( $pr ); ?>" onclick="lpsRefresh()" class="lps_status"><em><?php echo esc_html( $pr ); ?></em></label>
													<?php endforeach; ?>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Sticky Posts', 'lps' ); ?></td>
												<td>
													<label class="wide"><input type="radio" name="lps_show_extra_sticky[]" id="lps_show_extra_nosticky_restriction" value="" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'no restriction', 'lps' ); ?></label>

													<label class="wide"><input type="radio" name="lps_show_extra_sticky[]" id="lps_show_extra_sticky" value="sticky" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'only sticky posts', 'lps' ); ?></label>

													<label class="wide"><input type="radio" name="lps_show_extra_sticky[]" id="lps_show_extra_nosticky" value="nosticky" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'no sticky posts', 'lps' ); ?></label>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Order by', 'lps' ); ?></td>
												<td>
													<select name="lps_orderby" id="lps_orderby" data-default="dateD" onchange="lpsRefresh()">
														<?php foreach ( self::$orderby_options as $k => $v ) : ?>
															<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v['title'] ); ?></option>
														<?php endforeach; ?>
													</select>
													<div id="lps_orderby_meta_wrap" class="lps-update-blink">
														<input type="text" name="lps_orderby_meta" id="lps_orderby_meta" placeholder="<?php esc_attr_e( 'Post meta (ex: _price)', 'lps' ); ?>" onchange="lpsRefresh()" onkeyup="lpsRefresh()">
														<p class="comment lps-update-blink"><?php esc_html_e( '* Please note that ordering the items by post meta might present performance risks, please use this careful. Additionally, the output will be filtered only to the posts that have the specified post meta.', 'lps' ); ?></p>
													</div>
													<div id="lps_orderby_random_wrap">
														<p class="comment lps-update-blink"><?php esc_html_e( '* Please note that ordering the items by random might present performance risks, please use this careful.', 'lps' ); ?><span class="block-use available-for-tiles"> <?php esc_html_e( 'Also, using a random order and pagination will output unexpected and potentially redundant content.', 'lps' ); ?></span></p>
													</div>
												</td>
											</tr>
										</table>
									</div>

									<div id="lps_filter_tax_wrapper" class="settings-block">
										<h3><?php esc_html_e( 'Filter By Taxonomy', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td><?php esc_html_e( 'Taxonomy', 'lps' ); ?></td>
												<td>
													<select name="lps_taxonomy" id="lps_taxonomy" data-default="" onchange="lpsRefresh()">
														<option value=""><?php esc_html_e( 'Any', 'lps' ); ?></option>
														<?php $tax = self::filtered_taxonomies(); ?>
														<?php if ( ! empty( $tax ) ) : ?>
															<?php foreach ( $tax as $k => $v ) : ?>
																<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v->labels->name ); ?> (<?php echo esc_attr( $k ); ?>)</option>
															<?php endforeach; ?>
														<?php endif; ?>
													</select>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Term', 'lps' ); ?></td>
												<td>
													<input type="text" name="lps_term" id="lps_term" placeholder="<?php esc_attr_e( 'Term slug (ex: news)', 'lps' ); ?>" onchange="lpsRefresh()" onkeyup="lpsRefresh()">

													<label class="wide">
														<input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_term_strict" value="term_strict" onclick="lpsRefresh()" class="lps_show_extra">
														<?php esc_html_e( 'exclude children', 'lps' ); ?>
													</label>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Taxonomy', 'lps' ); ?> 2</td>
												<td>
													<select name="lps_taxonomy2" id="lps_taxonomy2" data-default="" onchange="lpsRefresh()">
														<option value=""><?php esc_html_e( 'Any', 'lps' ); ?></option>
														<?php if ( ! empty( $tax ) ) : ?>
															<?php foreach ( $tax as $k => $v ) : ?>
																<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v->labels->name ); ?> (<?php echo esc_attr( $k ); ?>)</option>
															<?php endforeach; ?>
														<?php endif; ?>
													</select>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Term', 'lps' ); ?> 2</td>
												<td>
													<input type="text" name="lps_term2" id="lps_term2"  placeholder="<?php esc_attr_e( 'Term slug (ex: news)', 'lps' ); ?>" onchange="lpsRefresh()" onkeyup="lpsRefresh()">

													<label class="wide">
														<input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_term2_strict" value="term2_strict" onclick="lpsRefresh()" class="lps_show_extra">
														<?php esc_html_e( 'exclude children', 'lps' ); ?>
													</label>
												</td>
											</tr>
										</table>
									</div>

									<div id="lps_filter_tag_wrapper" class="settings-block">
										<h3><?php esc_html_e( 'Filter By Tag', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td><?php esc_html_e( 'Tag', 'lps' ); ?></b></td>
												<td><input type="text" name="lps_tag" id="lps_tag" onchange="lpsRefresh()" onkeyup="lpsRefresh()"></td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Dynamic', 'lps' ); ?></td>
												<td>
													<select name="lps_dtag" id="lps_dtag" data-default="" onchange="lpsRefresh()">
														<option value=""><?php esc_html_e( 'No, use the selected ones', 'lps' ); ?></option>
														<option value="yes"><?php esc_html_e( 'Yes, use the current post tags', 'lps' ); ?></option>
													</select>
												</td>
											</tr>
										</table>
									</div>

									<div class="settings-block">
										<h3><?php esc_html_e( 'Search & Archive Filter', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr id="lps_search_wrapper" class="lps-update-blink">
												<td><?php esc_html_e( 'Search Key', 'lps' ); ?></b></td>
												<td><input type="text" name="lps_search" id="lps_search" onchange="lpsRefresh()" onkeyup="lpsRefresh()"></td>
											</tr>
											<tr id="lps_archive_wrapper" class="lps-update-blink">
												<td><?php esc_html_e( 'Use as Archive', 'lps' ); ?></td>
												<td>
													<select name="lps_archive" id="lps_archive" data-default="" onchange="lpsRefresh()">
														<option value=""><?php esc_html_e( 'No', 'lps' ); ?></option>
														<option value="yes"><?php esc_html_e( 'Yes, use the current search key or taxonomy term', 'lps' ); ?></option>
													</select>
													<div id="lps_archive_comment_wrapper">
														<p class="comment lps-update-blink"><?php esc_html_e( 'If you enable this option, the rest of taxonomies filters will not apply. This option is only intended to mimic the native archives (categories, tags, etc.) or the search result. If you are using pagination, the number of posts per page is inherited from the site reading settings.', 'lps' ); ?></span></p>
													</div>
												</td>
											</tr>
										</table>
									</div>

									<div class="settings-block">
										<h3><?php esc_html_e( 'Filter By Specific IDs', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td><?php esc_html_e( 'Post ID', 'lps' ); ?></td>
												<td>
													<input type="text" name="lps_post_id" id="lps_post_id" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'Separate IDs with comma', 'lps' ); ?>"><p class="comment"><?php esc_attr_e( 'Show only objects with the selected IDs.', 'lps' ); ?></p></td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Parent ID', 'lps' ); ?></td>
												<td>
													<select name="lps_dparent" id="lps_dparent" data-default="" onchange="lpsRefresh()" class="lps-update-blink">
														<option value=""><?php esc_html_e( 'Static, use the specified IDs', 'lps' ); ?></option>
														<option value="yes"><?php esc_html_e( 'Dynamic, use the current post attributes', 'lps' ); ?></option>
													</select>

													<input type="text" name="lps_parent_id" id="lps_parent_id" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'Separate IDs with comma', 'lps' ); ?>">

													<p class="comment"><?php esc_attr_e( 'Show only objects with specific parents.', 'lps' ); ?></p>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Author ID', 'lps' ); ?></td>
												<td>
													<select name="lps_dauthor" id="lps_dauthor" data-default="" onchange="lpsRefresh()" class="lps-update-blink">
														<option value=""><?php esc_html_e( 'Static, use the specified IDs', 'lps' ); ?></option>
														<option value="yes"><?php esc_html_e( 'Dynamic, use the current post attributes', 'lps' ); ?></option>
													</select>

													<input type="text" name="lps_author_id" id="lps_author_id" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'Separate IDs with comma', 'lps' ); ?>">
													<p class="comment"><?php esc_attr_e( 'Show only objects with specific authors.', 'lps' ); ?></p>
												</td>
											</tr>
										</table>
									</div>

									<div class="settings-block">
										<h3><?php esc_html_e( 'Exclude Content', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td><?php esc_html_e( 'Current', 'lps' ); ?></td>
												<td>
													<label class="wide"><input type="checkbox" name="lps_show_extra_current_id" id="lps_show_extra_current_id" value="current_id" checked="checked" disabled="disabled" readonly="readonly"> <?php esc_html_e( 'the current post', 'lps' ); ?></label>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Dynamic', 'lps' ); ?></td>
												<td>
													<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_exclude_previous_content" value="exclude_previous_content" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'previous shortcodes*', 'lps' ); ?></label>
													<p class="comment"><?php esc_html_e( '* The exclude content dynamic option will filter the content so that the posts that were already embedded by previous shortcodes on this page will not show up (so that the content does not repeat).', 'lps' ); ?></p>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'By Post ID', 'lps' ); ?></td>
												<td>
													<input type="text" name="lps_excludepost_id" id="lps_excludepost_id" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'Separate IDs with comma', 'lps' ); ?>">
													<p class="comment"><?php esc_attr_e( 'Exclude the objects with the selected IDs.', 'lps' ); ?></p>
												</td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'By Author ID', 'lps' ); ?></td>
												<td>
													<input type="text" name="lps_excludeauthor_id" id="lps_excludeauthor_id" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'Separate IDs with comma', 'lps' ); ?>">
													<p class="comment"><?php esc_attr_e( 'Exclude the objects with the selected author IDs.', 'lps' ); ?></p>
												</td>
											</tr>

											<tr>
												<td><?php esc_html_e( 'By Tags', 'lps' ); ?></td>
												<td>
													<input type="text" name="lps_exclude_tags" id="lps_exclude_tags" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'Separate slugs with comma', 'lps' ); ?>">
													<p class="comment"><?php esc_attr_e( 'Exclude the objects with the selected tags.', 'lps' ); ?></p>
												</td>
											</tr>

											<tr>
												<td><?php esc_html_e( 'By Categories', 'lps' ); ?></td>
												<td>
													<input type="text" name="lps_exclude_categories" id="lps_exclude_categories" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'Separate slugs with comma', 'lps' ); ?>">
													<p class="comment"><?php esc_attr_e( 'Exclude the objects with the selected categories.', 'lps' ); ?></p>
												</td>
											</tr>
										</table>
									</div>
								</div>
								<hr class="sep">
								<div id="tabs-2" class="settings-group">
									<h1>3. <?php esc_html_e( 'Limit & Pagination', 'lps' ); ?></h1>

									<div class="settings-block">
										<h3><?php esc_html_e( 'Posts Limit', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td><?php esc_html_e( 'Number of Posts', 'lps' ); ?></td>
												<td>
													<input type="text" name="lps_limit" id="lps_limit" value="" onchange="lpsRefresh()" onkeyup="lpsRefresh()" size="5">
													<p class="comment"><?php esc_html_e( 'This is the maximum number of posts the shortcode will expose.', 'lps' ); ?></p>
												</td>
											</tr>
										</table>

										<h3><?php esc_html_e( 'Date Limit', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td><?php esc_html_e( 'Date Limit Type', 'lps' ); ?></td>
												<td>
													<select name="lps_date_limit" id="lps_date_limit" data-default="" onchange="lpsRefresh()">
														<option value=""><?php esc_html_e( 'Date Range', 'lps' ); ?></option>
														<option value="1"><?php esc_html_e( 'Dynamic Date', 'lps' ); ?></option>
													</select>
												</td>
											</tr>
										</table>
										<div id="lps_date_limit_options_0" class="lps-update-blink">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td> </td>
													<td><?php esc_html_e( 'Published After', 'lps' ); ?></td>
													<td>
														<input type="date" name="lps_date_after" id="lps_date_after" value="" onchange="lpsRefresh()">
													</td>
												</tr>
												<tr>
													<td> </td>
													<td><?php esc_html_e( 'Published Before', 'lps' ); ?></td>
													<td>
														<input type="date" name="lps_date_before" id="lps_date_before" value="" onchange="lpsRefresh()">
													</td>
												</tr>
											</table>
										</div>
										<div id="lps_date_limit_options_1" class="lps-update-blink">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td> </td>
													<td><?php esc_html_e( 'Since', 'lps' ); ?></td>
													<td class="small-input">
														<input type="number" name="lps_date_start" id="lps_date_start" value="0" onchange="lpsRefresh()" onkeyup="lpsRefresh()" size="2">
													</td>
													<td>
														<select name="lps_date_start_type" id="lps_date_start_type" data-default="" onchange="lpsRefresh()">
															<?php foreach ( self::$date_limit_units as $k => $v ) : ?>
																<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?> </option>
															<?php endforeach; ?>
														</select>
													</td>
													<td><?php esc_html_e( 'ago', 'lps' ); ?></td>
												</tr>
											</table>
										</div>
									</div>

									<div class="settings-block">
										<h3><?php esc_html_e( 'Pagination Settings', 'lps' ); ?></h3><hr>
										<div class="block-use available-for-tiles">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Use Pagination', 'lps' ); ?></td>
													<td>
														<select name="lps_use_pagination" id="lps_use_pagination" data-default="" onchange="lpsRefresh()">
															<option value=""><?php esc_html_e( 'No', 'lps' ); ?></option>
															<option value="yes"><?php esc_html_e( 'Yes, paginate results', 'lps' ); ?></option>
														</select>
														<div id="lps_pagination_limit">
															<p class="comment lps-update-blink"><?php esc_html_e( 'Please note that paginated items are limited to the number of posts specified above. If you do not want to limit the result, just remove the value from the number of posts.', 'lps' ); ?></p>
														</div>
													</td>
												</tr>
											</table>

											<div id="lps_pagination_options">
												<table width="100%" cellspacing="0" cellpadding="2">
													<tr>
														<td><?php esc_html_e( 'Records Per Page', 'lps' ); ?></td>
														<td>
															<input type="text" name="lps_per_page" id="lps_per_page" value="0" onchange="lpsRefresh()" onkeyup="lpsRefresh()" size="5">
														</td>
													</tr>
													<tr>
														<td><?php esc_html_e( 'Offset', 'lps' ); ?></td>
														<td>
															<input type="text" name="lps_offset" id="lps_offset" value="0" onchange="lpsRefresh()" onkeyup="lpsRefresh()" size="5">
														</td>
													</tr>
													<tr>
														<td><?php esc_html_e( 'Visibility', 'lps' ); ?></td>
														<td>
															<select name="lps_showpages" id="lps_showpages" data-default="" onchange="lpsRefresh()">
																<option value=""><?php esc_html_e( 'Hide Navigation', 'lps' ); ?></option>
																<option value="4"><?php esc_html_e( 'Show Navigation (range of 4)', 'lps' ); ?></option>
																<option value="5"><?php esc_html_e( 'Show Navigation (range of 5)', 'lps' ); ?></option>
																<option value="10"><?php esc_html_e( 'Show Navigation (range of 10)', 'lps' ); ?></option>
																<option value="more"><?php esc_html_e( 'Show Navigation (load more button)', 'lps' ); ?></option>
																<option value="scroll"><?php esc_html_e( 'Infinite scroll (load more on scroll)', 'lps' ); ?></option>
															</select>
														</td>
													</tr>
													<tr id="lps_showpages_options">
														<td><?php esc_html_e( 'Load More Text', 'lps' ); ?></td>
														<td>
															<input type="text" name="lps_loadtext" id="lps_loadtext" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_html_e( 'Custom \'Load more\' button text', 'lps' ); ?>" value="<?php esc_html_e( 'Load more', 'lps' ); ?>" size="32">
															<p class="comment lps-update-blink"><?php esc_html_e( 'This is the text that will be displayed on the button on the front-end. Do not use brackets for the custom load more message, these are shortcodes delimiters.', 'lps' ); ?></p>
														</td>
													</tr>
													<tr>
														<td><?php esc_html_e( 'Position', 'lps' ); ?></td>
														<td>
															<select name="lps_showpages_pos" id="lps_showpages_pos" onchange="lpsRefresh()">
																<option value=""><?php esc_html_e( 'Above the results', 'lps' ); ?></option>
																<option value="1"><?php esc_html_e( 'Below the results', 'lps' ); ?></option>
																<option value="2"><?php esc_html_e( 'Above & below the result', 'lps' ); ?></option>
															</select>
														</td>
													</tr>
													<tr>
														<td><?php esc_html_e( 'AJAX Pagination', 'lps' ); ?></td>
														<td>
															<label><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_ajax_pagination" value="ajax_pagination" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'yes', 'lps' ); ?></label>
															<label><input type="radio" name="lps_show_extra[]" id="lps_show_extra_no_spinner" value="" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'no spinner', 'lps' ); ?></label>
															<br>
															<label><input type="radio" name="lps_show_extra[]" id="lps_show_extra_light_spinner" value="light_spinner" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'light spinner', 'lps' ); ?></label>
															<label><input type="radio" name="lps_show_extra[]" id="lps_show_extra_dark_spinner" value="dark_spinner" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'dark spinner', 'lps' ); ?></label>

														</td>
													</tr>
													<tr>
														<td><?php esc_html_e( 'Pagination Style', 'lps' ); ?></td>
														<td>
															<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_pagination_all" value="pagination_all" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'all pagination elements', 'lps' ); ?> </label>
															<p class="comment"><?php esc_html_e( 'Tick this option if you need to display the pagination elements all the time, including the disabled elements like: go to first, previous, next, and last page, even if these are disabled.', 'lps' ); ?></p>

															<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_show_total" value="show_total" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'show total items', 'lps' ); ?></label>
															<div id="lps_show_total_options">
																<input type="text" name="lps_total_text" id="lps_total_text" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php /* Translators: %d - total value. */ esc_html_e( 'Custom \'Total items: %d\' text', 'lps' ); ?>" value="<?php esc_html_e( 'Total items: %d', 'lps' ); ?>" size="32">
																<p class="comment lps-update-blink"><?php /* Translators: %d - total value. */ esc_html_e( 'Write the total items text, %d will be replaced by the total value. Leave empty for the default.', 'lps' ); ?></p>
															</div>
														</td>
													</tr>
												</table>
											</div>
										</div>
										<div class="block-use available-for-slider">
											<p class="comment lps-update-blink"><?php esc_html_e( 'The pagination is not available for sliders, only for list/tiles output.', 'lps' ); ?></p>
										</div>
									</div>
								</div>
								<hr class="sep">
								<div id="tabs-3" class="settings-group">
									<h1>4. <?php esc_html_e( 'Display Settings', 'lps' ); ?></h1>

									<?php
									// Introduce the slider extension options.
									self::output_slider_configuration();
									?>

									<div class="settings-block">
										<h3><?php esc_html_e( 'Post Appearance', 'lps' ); ?></h3><hr>
										<div class="block-use available-for-tiles">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Display Post', 'lps' ); ?></td>
													<td>
														<select name="lps_display" id="lps_display" data-default="title" onchange="lpsRefresh()">
															<?php foreach ( $display_posts_list as $k => $v ) : ?>
																<?php
																$key = array_keys( self::$tile_pattern, '[' . $k . ']', true );
																if ( ! empty( $key ) ) {
																	$key = reset( $key );
																} else {
																	$key = '';
																}
																?>
																<option value="<?php echo esc_attr( $k ); ?>" data-template-id="<?php echo esc_attr( $key ); ?>" <?php selected( 'title', $k ); ?>><?php echo esc_html( $v ); ?> </option>
															<?php endforeach; ?>
														</select>
													</td>
												</tr>
											</table>
										</div>
										<div id="lps_display_titletag">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Title Wrap', 'lps' ); ?></td>
													<td>
														<select name="lps_titletag" id="lps_titletag" data-default="h3" onchange="lpsRefresh()">
															<?php foreach ( self::$title_tags as $s ) : ?>
																<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
															<?php endforeach; ?>
														</select>
														<p class="comment lps-update-blink"><?php esc_html_e( 'This is the HTML tag used to wrap the post title in the output (defaults to h3).', 'lps' ); ?></p>
													</td>
												</tr>
											</table>
										</div>
										<div id="lps_display_limit">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Chars Limit', 'lps' ); ?></td>
													<td>
														<input type="text" name="lps_chrlimit" id="lps_chrlimit" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="Ex: 120" value="120" size="5">
														<p class="comment lps-update-blink"><?php esc_html_e( 'Maximum number of chars from excerpt / content to be displayed (the text will be truncated, but will not break words).', 'lps' ); ?></p>
													</td>
												</tr>
												<tr>
													<td><?php esc_html_e( 'Trim type', 'lps' ); ?></td>
													<td>
														<label class="wide"><input type="checkbox" name="lps_show_extra_trim[]" id="lps_show_extra_trim" value="trim" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'limit the title and text together', 'lps' ); ?></label>
														<p class="comment lps-update-blink"><?php esc_html_e( 'Apply the chars limit to title and excerpt/content together (the excerpt/content length will be computed by subtracting the title length from the chars limit).', 'lps' ); ?></p>
													</td>
												</tr>
												<tr>
													<td><?php esc_html_e( 'More Suffix', 'lps' ); ?></td>
													<td>
														<input type="text" name="lps_more" id="lps_more" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="Ex: …" value="">
														<p class="comment lps-update-blink"><?php esc_html_e( 'The extra chars to be appended at the end of the trimmed strings.', 'lps' ); ?></p>
													</td>
												</tr>
											</table>
										</div>
										<div id="lps_display_raw">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Raw Content', 'lps' ); ?></td>
													<td>
														<label><input type="checkbox" name="lps_show_extra_raw[]" id="lps_show_extra_raw" value="raw" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'show raw content', 'lps' ); ?></label>
														<p class="comment lps-update-blink"><?php esc_html_e( 'This option is forcing the content output without stripping the markup. This might produce unexpected content layout on the front end, use wisely.', 'lps' ); ?></p>
													</td>
												</tr>
											</table>
										</div>
										<div id="lps_display_date_diff">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Date Option', 'lps' ); ?></td>
													<td>
														<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_date_diff" value="date_diff" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'as date difference', 'lps' ); ?></label>
														<p class="comment lps-update-blink"><?php esc_html_e( 'If you check this option, the date for the tile (if that is included in the tile format) will display in date difference format (like 2 hours ago or 1 day ago, etc.).', 'lps' ); ?></p>
													</td>
												</tr>
											</table>
										</div>

										<div id="lps_url_wrap">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td class="lps_title_td"><?php esc_html_e( 'Use Post URL', 'lps' ); ?></td>
													<td>
														<select name="lps_url" id="lps_url" data-default="" onchange="lpsRefresh()">
															<option value=""><?php esc_html_e( 'No link to the post', 'lps' ); ?></option>
															<option value="yes"><?php esc_html_e( 'Link to the post', 'lps' ); ?></option>
															<option value="yes_blank"><?php esc_html_e( 'Link to the post (_blank)', 'lps' ); ?></option>
															<option value="yes_media"><?php esc_html_e( 'Link to the media file', 'lps' ); ?></option>
															<option value="yes_media_blank"><?php esc_html_e( 'Link to the media file (_blank)', 'lps' ); ?></option>
															<option value="yes_media_lightbox" disabled><?php esc_html_e( 'Link to the media file with lightbox', 'lps' ); ?></option>
														</select>
														<div id="lps_url_options">
															<p class="comment lps-update-blink"><?php esc_html_e( 'See below the available tile patterns and select to one you want.', 'lps' ); ?></p>
														</div>
													</td>
												</tr>
											</table>
											<div id="lps_url_options_read">
												<table width="100%" cellspacing="0" cellpadding="2">
													<tr>
														<td class="lps_title_td"><?php esc_html_e( 'Custom \'Read more\' message', 'lps' ); ?></td>
														<td>
															<input type="text" name="lps_linktext" id="lps_linktext" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_html_e( 'Custom \'Read more\' message', 'lps' ); ?>" size="32">
															<p class="comment lps-update-blink"><?php esc_html_e( 'Do not use brackets for the custom read more message, these are shortcodes delimiters.', 'lps' ); ?></p>
														</td>
													</tr>
												</table>
											</div>
											<div class="block-use available-for-tiles">
												<div id="lps_lightbox_options" class="lps-experimental lps-update-blink">
													<table width="100%" cellspacing="0" cellpadding="2">
														<tr>
															<td class="lps_title_td"><?php esc_html_e( 'Lightbox Attributes', 'lps' ); ?></td>
															<td colspan="2">
																<p class="comment"><?php esc_html_e( 'If you want to use a lightbox for the images, you can setup below the image size to be available in the lightbox and the selector.', 'lps' ); ?></p>
															</td>
														</tr>
														<tr>
															<td><?php esc_html_e( 'Lightbox Image', 'lps' ); ?></td>
															<td colspan="2">
																<select name="lps_lightbox_size" id="lps_lightbox_size" data-default="full" onchange="lpsRefresh()">
																	<option value="full"><?php esc_html_e( 'full (original size)', 'lps' ); ?></option>
																	<?php $app_sizes = get_intermediate_image_sizes(); ?>
																	<?php if ( ! empty( $app_sizes ) ) : ?>
																		<?php foreach ( $app_sizes as $s ) : ?>
																			<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
																		<?php endforeach; ?>
																	<?php endif; ?>
																</select>
															</td>
														</tr>
														<tr>
															<td><?php esc_html_e( 'Selector attribute and value', 'lps' ); ?></td>
															<td>
																<input type="text" name="lps_lightbox_attr" id="lps_lightbox_attr" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_html_e( 'Ex: class', 'lps' ); ?>" size="32">
															</td>
															<td>
																<input type="text" name="lps_lightbox_val" id="lps_lightbox_val" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_html_e( 'Ex: fancybox image', 'lps' ); ?>" size="32">
															</td>
														</tr>
														<tr>
															<td> </td>
															<td colspan="2">
																<p class="comment"><?php esc_html_e( 'This feature has been tested and is recommended to be used with Easy FancyBox plugin (>=1.8) or FooBox Image Lightbox plugin (>=2.6).', 'lps' ); ?></p>
															</td>
														</tr>
													</table>
												</div>
											</div>
										</div>
										<div id="lps_image_wrap">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Use Image', 'lps' ); ?></td>
													<td>
														<select name="lps_image" id="lps_image" data-default="" onchange="lpsRefresh()">
															<option value=""><?php esc_html_e( 'No Image', 'lps' ); ?></option>
															<?php $app_sizes = get_intermediate_image_sizes(); ?>
															<?php if ( ! empty( $app_sizes ) ) : ?>
																<?php foreach ( $app_sizes as $s ) : ?>
																	<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
																<?php endforeach; ?>
															<?php endif; ?>
															<option value="full"><?php esc_html_e( 'full (original size)', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
											</table>
											<div id="lps_image_placeholder_wrap" class="lps-update-blink">
												<table width="100%" cellspacing="0" cellpadding="2">
													<tr>
														<td><?php esc_html_e( 'Image Placeholder', 'lps' ); ?></td>
														<td>
															<input type="text" name="lps_image_placeholder" id="lps_image_placeholder" onchange="lpsRefresh()" onkeyup="lpsRefresh()">
															<p class="comment"><?php esc_html_e( 'Define an image to be used for the posts that do not have a featured image.', 'lps' ); ?> <?php esc_html_e( 'If you specify a list of images separated by comma, a random one from the list will be picked for each article that does not have a featured image.', 'lps' ); ?></p>
														</td>
													</tr>
												</table>
											</div>

											<div id="lps_fallback_wrap">
												<table width="100%" cellspacing="0" cellpadding="2">
													<tr>
														<td><?php esc_html_e( 'Content Fallback', 'lps' ); ?></td>
														<td>
															<input type="text" name="lps_fallback" id="lps_fallback" onchange="lpsRefresh()" onkeyup="lpsRefresh()">
															<p class="comment"><?php esc_html_e( 'Add a custom text to be displayed if no content matches the settings.', 'lps' ); ?></p>
														</td>
													</tr>
												</table>
											</div>
										</div>

										<div class="block-use available-for-tiles">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td colspan="2"><?php esc_html_e( 'Tile Pattern', 'lps' ); ?></td>
												</tr>
												<tr>
													<td colspan="2" class="normal">
														<input type="hidden" name="lps_elements" id="lps_elements" value="0" onchange="lpsRefresh()">
														<?php
														foreach ( self::$tile_pattern as $k => $p ) :
															$cl  = ( in_array( $k, self::$tile_pattern_links, true ) ) ? 'with-link' : 'without-link';
															$cl .= ( in_array( $k, self::$tile_pattern_ver2, true ) ) ? ' ver2' : '';
															$cl  = ( self::tile_markup_is_custom( $p ) ) ? 'custom-type wide' : $cl;
															?>
															<label class="<?php echo esc_attr( $cl ); ?> lps-update-blink" onclick="LPS_generator.updateElements('<?php echo esc_attr( $k ); ?>');">
																<?php if ( self::tile_markup_is_custom( $p ) ) : ?>
																	<input type="radio" name="lps_elements_img" id="lps_elements_img_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $k ); ?>" readonly="readonly">
																	<?php echo esc_html( $display_posts_list[ str_replace( ']', '', str_replace( '[', '', $p ) ) ] ); ?> <?php esc_html_e( 'markup', 'lps' ); ?>
																<?php else : ?>
																	<img src="<?php echo esc_url( plugins_url( '/assets/images/post_tiles' . esc_attr( $k ) . '.png', __FILE__ ) ); ?>" title="<?php echo esc_attr( str_replace( '[a-r]', '[a]', $p ) ); ?>">
																	<input type="radio" name="lps_elements_img" id="lps_elements_img_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $k ); ?>">
																<?php endif; ?>
															</label>
														<?php endforeach; ?>
														<div class="clear"></div>
														<p id="tile_description_wrap" class="comment"><?php esc_html_e( 'The icons represent the order of the HTML tags, the links are marked with red.', 'lps' ); ?></p>
														<p id="custom_tile_description_wrap" class="comment"><?php esc_html_e( 'You are using a custom output, the markup is handled programatically, in your custom code.', 'lps' ); ?></p>
													</td>
												</tr>
											</table>
										</div>
									</div>
								</div>
								<hr class="sep">
								<div id="tabs-4" class="settings-group">
									<h1>5. <?php esc_html_e( 'Extra Options', 'lps' ); ?></h1>
									<div class="block-use available-for-tiles">
										<div class="settings-block"><p class="comment"><?php esc_html_e( 'Please note that if you are using a custom output template defined in your theme, the author, the taxonomies and the tags extra options will not function, since your custom template is overriding the output and the default behavior.', 'lps' ); ?></p></div>


										<?php
										$tax    = self::filtered_taxonomies();
										$tax    = wp_list_pluck( $tax, 'label', 'name' );
										$alltax = [
											'author'      => esc_html__( 'Author', 'lps' ),
											'caption'     => esc_html__( 'Caption', 'lps' ) . ' ' . esc_html__( '(only for attachments)', 'lps' ),
											'show_mime'   => esc_html__( 'Mime Type', 'lps' ) . ' ' . esc_html__( '(only for attachments)', 'lps' ),
											'price'       => esc_html__( 'Price', 'lps' ) . ' ' . esc_html__( '(only for products)', 'lps' ),
											'add_to_cart' => esc_html__( 'Add to cart', 'lps' ) . ' ' . esc_html__( '(only for products)', 'lps' ),

											'price_add_to_cart' => esc_html__( 'Price + Add to cart', 'lps' ) . ' ' . esc_html__( '(only for products)', 'lps' ),
										];
										if ( ! empty( $tax ) ) {
											$alltax = array_merge( $alltax, $tax );
										}

										$alltax['tags'] = esc_html__( 'Tags', 'lps' );
										?>
										<?php if ( ! empty( $alltax ) ) : ?>
											<?php foreach ( $alltax as $slug => $name ) : ?>
												<?php
												$theslug = ( ! in_array( $slug, [ 'author', 'caption', 'show_mime', 'price', 'add_to_cart', 'price_add_to_cart' ], true ) ) ? '(' . $slug . ')' : '';
												?>
												<div id="lps-extra-<?php echo esc_html( $slug ); ?>" class="settings-block-item terms-options">
													<h3>
														<label class="title wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_attr( $slug ); ?>" onclick="lpsRefresh();" class="lps_show_extra lps-is-taxonomy">
														<?php echo esc_html( $name ); ?></label>
													</h3>
													<div id="lps_show_extra_<?php echo esc_attr( $slug ); ?>_pos_wrap" class="extra-options-wrap lps-update-blink">
														<?php if ( 'category' === $slug ) : ?>
															<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_hide_uncategorized_<?php echo esc_attr( $slug ); ?>" value="hide_uncategorized_<?php echo esc_attr( $slug ); ?>" onclick="lpsRefresh();" class="lps_show_extra"> <b><?php esc_html_e( 'Do not display Uncategorized term', 'lps' ); ?></b></label>
														<?php endif; ?>
														<?php if ( ! empty( $theslug ) ) : ?>
															<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_oneterm_<?php echo esc_attr( $slug ); ?>" value="oneterm_<?php echo esc_attr( $slug ); ?>" onclick="lpsRefresh();" class="lps_show_extra"> <b><?php esc_html_e( 'Show only one term', 'lps' ); ?></b></label>
															<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_nolabel_<?php echo esc_attr( $slug ); ?>" value="nolabel_<?php echo esc_attr( $slug ); ?>" onclick="lpsRefresh();" class="lps_show_extra"> <b><?php esc_html_e( 'Hide the taxonomy name from the list', 'lps' ); ?></b></label>
															<br><hr>
														<?php endif; ?>
														<b><?php esc_html_e( 'Display position', 'lps' ); ?></b>
														<br>
														<label class="show-options"><input type="radio" name="lps_show_extra_pos_<?php echo esc_attr( $slug ); ?>" id="lps_show_extra_taxpos_<?php echo esc_attr( $slug ); ?>_default" value="" checked="checked" onclick="lpsRefresh();" class="lps_show_extra"><?php esc_html_e( 'default', 'lps' ); ?>,</label>
														<?php foreach ( self::$tax_positions as $pos => $pos_title ) : ?>
															<label class="show-options"><input type="radio" name="lps_show_extra_pos_<?php echo esc_attr( $slug ); ?>" id="lps_show_extra_taxpos_<?php echo esc_attr( $slug ); ?>_<?php echo esc_attr( $pos ); ?>" value="taxpos_<?php echo esc_attr( $slug ); ?>_<?php echo esc_attr( $pos ); ?>" onclick="lpsRefresh();" class="lps_show_extra"><?php echo esc_html( $pos_title ); ?>,</label>
														<?php endforeach; ?>
													</div>
												</div>
											<?php endforeach; ?>

											<div class="settings-block">
												<table width="100%" cellspacing="0" cellpadding="2">
													<tr>
														<td><?php esc_html_e( 'Mime Type', 'lps' ); ?></td>
														<td>
															<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_show_mime_class" value="show_mime_class" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'Show mime type as CSS class', 'lps' ); ?> </label>
															<p class="comment"><?php esc_html_e( 'The extra options will apply only to attachment post type.', 'lps' ); ?></p>
														</td>
													</tr>
													<tr>
														<td><?php esc_html_e( 'Line Break', 'lps' ); ?></td>
														<td>
															<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_clearall" value="linebreak" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'clear the content below', 'lps' ); ?> </label>
															<p class="comment"><?php esc_html_e( 'The extra options will clear the content below by adding a line break after the shorcode.', 'lps' ); ?></p>
														</td>
													</tr>

												</table>
											</div>
										<?php endif; ?>
									</div>

									<div class="settings-block">
										<h3><?php esc_html_e( 'Cache', 'lps' ); ?></h3><hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td><?php esc_html_e( 'Shortcode Cache', 'lps' ); ?></td>
												<td>
													<label class="wide"><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_cache" value="cache" onclick="lpsRefresh()" class="lps_show_extra"> <?php esc_html_e( 'cache the shortcode result', 'lps' ); ?> </label>
													<p class="comment"><?php esc_html_e( 'The cache can help you speed up the page load. The default duration of the shortcode cache is 30 days. If you need to reset the shortcode cache, use the reset button.', 'lps' ); ?></p>
												</td>
											</tr>
										</table>
									</div>

									<div class="settings-block">
										<div class="block-use available-for-tiles">
											<h3><?php esc_html_e( 'Tiles Grid Options', 'lps' ); ?></h3><hr>
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td></td>
													<td><?php esc_html_e( 'Height', 'lps' ); ?></td>
													<td><?php esc_html_e( 'Padding', 'lps' ); ?></td>
													<td><?php esc_html_e( 'Spacing', 'lps' ); ?></td>
													<td><?php esc_html_e( 'Overlay Padding', 'lps' ); ?></td>
												</tr>
												<tr>
													<td><?php esc_html_e( 'Default/Desktop', 'lps' ); ?></td>
													<td>
														<input type="text" name="lps_default_height" id="lps_default_height" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
													<td>
														<input type="text" name="lps_default_padding" id="lps_default_padding" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
													<td>
														<input type="text" name="lps_default_gap" id="lps_default_gap" onchange="lpsRefresh()" placeholder="1rem" onkeyup="lpsRefresh()" size="32">
													</td>
													<td>
														<input type="text" name="lps_default_overlay_padding" id="lps_default_overlay_padding" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
												</tr>
												<tr>
													<td><?php esc_html_e( 'Tablet', 'lps' ); ?></td>
													<td>
														<input type="text" name="lps_tablet_height" id="lps_tablet_height" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
													<td>
														<input type="text" name="lps_tablet_padding" id="lps_tablet_padding" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
													<td>
														<input type="text" name="lps_tablet_gap" id="lps_tablet_gap" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
													<td>
														<input type="text" name="lps_tablet_overlay_padding" id="lps_tablet_overlay_padding" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
												</tr>
												<tr>
													<td><?php esc_html_e( 'Mobile', 'lps' ); ?></td>
													<td>
														<input type="text" name="lps_mobile_height" id="lps_mobile_height" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
													<td>
														<input type="text" name="lps_mobile_padding" id="lps_mobile_padding" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
													<td>
														<input type="text" name="lps_mobile_gap" id="lps_mobile_gap" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
													<td>
														<input type="text" name="lps_mobile_overlay_padding" id="lps_mobile_overlay_padding" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="1rem" size="32">
													</td>
												</tr>
												<tr>
													<td></td>
													<td colspan="5">
														<p class="comment"><?php esc_html_e( 'These options allow to specify the gap between tiles and the height for these. Use px, %, vw or vh as units.', 'lps' ); ?> <?php esc_html_e( 'Leave empty if you want to use the defaults.', 'lps' ); ?></p>
													</td>
												</tr>
											</table>
											<hr>
										</div>
										<h3><?php esc_html_e( 'Style', 'lps' ); ?></h3><hr>
										<p class="comment">
											<?php
											echo esc_html(
												sprintf(
													// Translators: %1$s - class, %2$s - class, %3$s - class, %4$s - class, %5$s - class, %6$s - class, %7$s - class, %8$s - class.
													__( 'Currently, the plugin offers out of the box support for two, three, four, five and six columns for the tiles and the overlay option. If, for example, you would like to display the tiles as four columns and the images as backgrounds, you can use `%1$s`. The content of the tiles can be aligned with `%6$s`, `%7$s` or `%8$s`. Extra options for the overlay usage: `%2$s` (for the overlay color), `%3$s` (to make the height of the tiles a bit bigger, so it fits more content). For the pagination alignment, there are two CSS classes that allow to center or align right the element: `%4$s` and `%5$s`.', 'lps' ),
													'four-columns as-overlay',
													'light',
													'tall',
													'pagination-center',
													'pagination-right',
													'align-left',
													'align-center',
													'align-right'
												)
											);
											?>
										</p>
										<hr>
										<table width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td><?php esc_html_e( 'CSS Class', 'lps' ); ?></td>
												<td>
													<input type="text" name="lps_css" id="lps_css" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'Ex: two-columns, three-columns, four-columns', 'lps' ); ?>" size="32">
													<p class="comment"><?php esc_html_e( 'The CSS class/classes you can use to customize the appearance of the shortcode output.', 'lps' ); ?></p>
												</td>
											</tr>
										</table>
										<div class="block-use available-for-tiles">
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Columns Helper', 'lps' ); ?></td>
													<td colspan="3">
														<select id="lps_style_helper_columns" data-default="" onchange="lpsStyleHelper()">
															<option value="one-column">1</option>
															<option value="two-columns">2</option>
															<option value="three-columns">3</option>
															<option value="four-columns">4</option>
															<option value="five-columns">5</option>
															<option value="six-columns">6</option>
														</select>
													</td>
												</tr>
												<tr>
													<?php $card_styles = self::get_card_output_types(); ?>
													<td><?php esc_html_e( 'Output Helper', 'lps' ); ?></td>
													<td colspan="3">
														<select id="lps_style_helper_overlay" data-default="" onchange="lpsStyleHelper()">
															<?php
															if ( ! empty( $card_styles ) ) {
																foreach ( $card_styles as $s => $n ) {
																	?>
																	<option value="<?php echo esc_attr( sanitize_title( $s ) ); ?>"><?php echo esc_html( $n ); ?></option>
																	<?php
																}
															}
															?>
														</select>
													</td>
												</tr>
												<tr id="lps_style_helper_pags_tr" class="lps-update-blink">
													<td><?php esc_html_e( 'Pagination horizontal alignment', 'lps' ); ?></td>
													<td colspan="2">
														<select id="lps_style_helper_pags" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'left', 'lps' ); ?></option>
															<option value="pagination-center"><?php esc_html_e( 'center', 'lps' ); ?></option>
															<option value="pagination-right"><?php esc_html_e( 'right', 'lps' ); ?></option>
															<option value="pagination-space-between"><?php esc_html_e( 'space between', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
											</table>
										</div>
									</div>

									<div class="settings-block">
										<div class="block-use available-for-tiles">
											<h3><?php esc_html_e( 'Card options', 'lps' ); ?></h3><hr>
											<table width="100%" cellspacing="0" cellpadding="2">
												<tr>
													<td><?php esc_html_e( 'Aignment', 'lps' ); ?></td>
													<td>
														<?php esc_html_e( 'horizontal', 'lps' ); ?>
														<select id="lps_style_helper_align" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'left', 'lps' ); ?></option>
															<option value="align-center"><?php esc_html_e( 'center', 'lps' ); ?></option>
															<option value="align-right"><?php esc_html_e( 'right', 'lps' ); ?></option>
														</select>
													</td>
													<td>
														<?php esc_html_e( 'vertical', 'lps' ); ?>
														<select id="lps_style_helper_valign" data-default="" onchange="lpsStyleHelper()">
															<option value="content-center"><?php esc_html_e( 'center', 'lps' ); ?></option>
															<option value="content-start"><?php esc_html_e( 'start', 'lps' ); ?></option>
															<option value="content-end"><?php esc_html_e( 'end', 'lps' ); ?></option>
															<option value="content-space-between"><?php esc_html_e( 'space between', 'lps' ); ?></option>
															<option value="content-auto"><?php esc_html_e( 'auto', 'lps' ); ?></option>
															<option value="content-first-top"><?php esc_html_e( 'first top', 'lps' ); ?></option>
															<option value="content-last-bottom"><?php esc_html_e( 'last bottom', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
												<tr>
													<td>
														<?php esc_html_e( 'Font size', 'lps' ); ?>
													</td>
													<td>
														<?php esc_html_e( 'card text', 'lps' ); ?>
														<input type="text" name="lps_size_text" id="lps_size_text" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'inherit' ); ?>" size="32">
													</td>
													<td>
														<?php esc_html_e( 'card title', 'lps' ); ?>
														<input type="text" name="lps_size_title" id="lps_size_title" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'inherit' ); ?>" size="32">
													</td>
												</tr>
												<tr>
													<td></td>
													<td colspan="2"><p class="comment"><?php esc_html_e( 'Ex: 1rem, 24px, 2em, clamp(1rem, 0.6rem + 1.25vw, 1.4rem), etc.', 'lps' ); ?>
														<?php esc_html_e( 'Use px, %, vw or vh as units.', 'lps' ); ?>
														<?php esc_html_e( 'Leave empty if you want to use the defaults.', 'lps' ); ?>
													</p></td>
												</tr>
												<tr>
													<td>
														<?php esc_html_e( 'Colors', 'lps' ); ?>
													</td>
													<td colspan="2">
														<table width="100%" class="table-fixed" cellspacing="0" cellpadding="2">
															<tr>
																<td class="default">
																	<?php esc_html_e( 'card text', 'lps' ); ?>
																	<div class="lps-color-wrapper">
																		<input type="text" name="lps_color_text" id="lps_color_text" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'inherit' ); ?>" size="32">
																		<input type="color" id="lps_color_text_field" onchange="lpsRefreshColor(this)">
																	</div>
																</td>
																<td>
																	<?php esc_html_e( 'card title', 'lps' ); ?>
																	<div class="lps-color-wrapper">
																		<input type="text" name="lps_color_title" id="lps_color_title" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'inherit' ); ?>" size="32">
																		<input type="color" id="lps_color_title_field" onchange="lpsRefreshColor(this)">
																	</div>
																</td>
																<td>
																	<?php esc_html_e( 'background', 'lps' ); ?>
																	<div class="lps-color-wrapper">
																		<input type="text" name="lps_color_bg" id="lps_color_bg" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'inherit' ); ?>" size="32">
																		<input type="color" id="lps_color_bg_field" onchange="lpsRefreshColor(this)">
																	</div>
																</td>
															</tr>
														</table>
													</td>
												</tr>
												<tr>
													<td></td>
													<td colspan="2">
														<p class="comment"><?php esc_html_e( 'Ex: #fff, rbga(255,255,255, 0.5), etc.', 'lps' ); ?>
														<?php esc_html_e( 'Leave empty if you want to use the defaults.', 'lps' ); ?> <?php esc_html_e( 'Also, if you want to apply the colors, you should remove the card generic aspect.', 'lps' ); ?></p>
													</td>
												</tr>
												<tr>
													<td>
														<?php esc_html_e( 'Shadow', 'lps' ); ?>
													</td>
													<td>
														<?php esc_html_e( 'card shadow', 'lps' ); ?>
														<select id="lps_style_has_shadow" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
															<option value="has-shadow"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
													<td>
														<?php esc_html_e( 'title shadow', 'lps' ); ?>
														<select id="lps_style_has_title_shadow" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
															<option value="has-title-shadow"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
												<tr>
													<td>
														<?php esc_html_e( 'Hover Effect', 'lps' ); ?>
													</td>
													<td>
														<?php esc_html_e( 'image zoom', 'lps' ); ?>
														<select id="lps_style_has_zoom" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
															<option value="hover-zoom"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
													<td>
														<?php esc_html_e( 'card highlight', 'lps' ); ?>
														<select id="lps_style_has_highlight" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
															<option value="hover-highlight"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
												<tr>
													<td>
														<?php esc_html_e( 'Title options', 'lps' ); ?>
													</td>
													<td>
														<?php esc_html_e( 'no decoration', 'lps' ); ?>
														<select id="lps_style_has_title_nodecoration" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'inherit', 'lps' ); ?></option>
															<option value="has-title-nodecoration"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
													<td>
														<?php esc_html_e( 'uppercase', 'lps' ); ?>
														<select id="lps_style_has_title_uppercase" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'inherit', 'lps' ); ?></option>
															<option value="has-title-uppercase"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
												<tr>
													<td>
														<?php esc_html_e( 'Aspect', 'lps' ); ?>
													</td>
													<td>
														<?php esc_html_e( 'generic aspect', 'lps' ); ?>
														<select id="lps_style_has_aspect" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( '-- unset --', 'lps' ); ?></option>
															<option value="dark"><?php esc_html_e( 'dark', 'lps' ); ?></option>
															<option value="light"><?php esc_html_e( 'light', 'lps' ); ?></option>
															<option id="lps-option-clear-image" value="clear-image"><?php esc_html_e( 'no overlay', 'lps' ); ?></option>
														</select>
													</td>
													<td>
														<?php esc_html_e( 'border radius', 'lps' ); ?>
														<select id="lps_style_has_radius" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
															<option value="has-radius"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
												<tr id="lps_image_opacity_tr" class="lps-update-blink">
													<td>
														<?php esc_html_e( 'Image opacity', 'lps' ); ?>
													</td>
													<td colspan="2">
														<select id="lps_image_opacity" data-default="" onchange="lpsStyleHelper()">
															<?php
															foreach ( range( 100, 0, -5 ) as $nr ) {
																$val = ( 0 === $nr ) ? 0 : $nr / 100;
																?>
																<option value="<?php echo esc_attr( $val ); ?>"><?php echo (int) $nr; ?>%</option>
																<?php
															}
															?>
														</select>
													</td>
												</tr>
												<tr id="lps_style_has_tall_tr" class="lps-update-blink">
													<td>
														<?php esc_html_e( 'Tall card', 'lps' ); ?>
													</td>
													<td colspan="2">
														<select id="lps_style_has_tall" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
															<option value="tall"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
												<tr id="lps_style_has_img_spacing_tr" class="lps-update-blink">
													<td>
														<?php esc_html_e( 'Image spacing', 'lps' ); ?>
													</td>
													<td colspan="2">
														<select id="lps_style_has_img_spacing" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
															<option value="has-img-spacing"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
												<tr id="lps_style_has_stacked_tr" class="lps-update-blink">
													<td>
														<?php esc_html_e( 'Stacked on mobile', 'lps' ); ?>
													</td>
													<td colspan="2">
														<select id="lps_style_has_stacked" data-default="" onchange="lpsStyleHelper()">
															<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
															<option value="has-stacked"><?php esc_html_e( 'yes', 'lps' ); ?></option>
														</select>
													</td>
												</tr>
												<tr id="lps_size_image_tr" class="lps-update-blink">
													<td>
														<?php esc_html_e( 'Image size', 'lps' ); ?>
													</td>
													<td colspan="2">
														<input type="text" name="lps_size_image" id="lps_size_image" onchange="lpsRefresh()" onkeyup="lpsRefresh()" placeholder="<?php esc_attr_e( 'inherit (50%)' ); ?>" size="32">
													</td>
												</tr>

												<tr id="lps_card_ratio_tr" class="lps-update-blink">
													<td>
														<?php esc_html_e( 'Card Aspect Ratio', 'lps' ); ?>
													</td>
													<td colspan="2">
														<select id="lps_card_ratio" onchange="lpsRefresh();">
															<option value=""><?php esc_html_e( 'auto', 'lps' ); ?></option>
															<option value="1"><?php esc_html_e( '1:1 (square)', 'lps' ); ?></option>
															<optgroup label="<?php esc_html_e( 'landscape', 'lps' ); ?>">
																<option value="16/9">16:9</option>
																<option value="4/3">4:3</option>
																<option value="3/2">3:2</option>
															</optgroup>
															<optgroup label="<?php esc_html_e( 'portrait', 'lps' ); ?>">
																<option value="5/9">5:9</option>
																<option value="4/5">4:5</option>
															</optgroup>
														</select>
													</td>
												</tr>

												<tr id="lps_image_ratio_tr" class="lps-update-blink">
													<td>
														<?php esc_html_e( 'Image Aspect Ratio', 'lps' ); ?>
													</td>
													<td colspan="2">
														<select id="lps_image_ratio" onchange="lpsRefresh();">
															<option value=""><?php esc_html_e( 'auto', 'lps' ); ?></option>
															<option value="contain"><?php esc_html_e( 'none', 'lps' ); ?></option>
															<option value="1"><?php esc_html_e( '1:1 (square)', 'lps' ); ?></option>
															<optgroup label="<?php esc_html_e( 'landscape', 'lps' ); ?>">
																<option value="16/9">16:9</option>
																<option value="4/3">4:3</option>
																<option value="3/2">3:2</option>
															</optgroup>
															<optgroup label="<?php esc_html_e( 'portrait', 'lps' ); ?>">
																<option value="5/9">5:9</option>
																<option value="4/5">4:5</option>
															</optgroup>
														</select>
													</td>
												</tr>
											</table>
										</div>
									</div>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Latest_Post_Shortcode_Slider::output_slider_shortcode()
	 */
	public static function output_slider_configuration() {
		?>
		<div id="lps_display_slider">
			<div class="settings-block">
				<h3><?php esc_html_e( 'Slider Settings', 'lps' ); ?></h3><hr>
				<table width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td><?php esc_html_e( 'Wrapper Element', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderwrap" id="lps_sliderwrap" data-default="div" onchange="lpsRefresh()">
								<?php foreach ( self::$slider_wrap_tags as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Slides Transition', 'lps' ); ?></td>
						<td>
							<select name="lps_slidermode" id="lps_slidermode" data-default="horizontal" onchange="lpsRefresh()">
								<option value="horizontal"><?php esc_html_e( 'horizontal', 'lps' ); ?></option>
								<option value="vertical"><?php esc_html_e( 'vertical', 'lps' ); ?></option>
								<option value="fade"><?php esc_html_e( 'fade', 'lps' ); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<td><?php esc_html_e( 'Center Mode', 'lps' ); ?></td>
						<td>
							<select name="lps_centermode" id="lps_centermode" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'center mode', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
					<tbody id="lps_centermode_options">
						<tr>
							<td colspan="2" class="normal comment">
								<p class="comment lps-update-blink"><?php esc_html_e( 'The center mode is intended to works for the cases when you are showing more slides per row, and works better when you use an odd number of slides (3, 5, etc.), so, if your slider is presenting one slide at a time, you might want to switch this option off.', 'lps' ); ?></p>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Center Mode Padding', 'lps' ); ?></td>
							<td>
								<input type="number" name="lps_centerpadd" id="lps_centerpadd" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="5" min="5" max="50">
								<p class="comment lps-update-blink"><?php esc_html_e( 'The value in pixels for the padding of the center slide.', 'lps' ); ?></p>
							</td>
						</tr>
					</tbody>

					<tr>
						<td><?php esc_html_e( 'Auto Play', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderauto" id="lps_sliderauto" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
					<tbody id="lps_sliderauto_options">
						<tr>
							<td><?php esc_html_e( 'Speed', 'lps' ); ?></td>
							<td>
								<input type="number" name="lps_sliderspeed" id="lps_sliderspeed" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="3000" min="1000" max="20000">
								<p class="comment lps-update-blink"><?php esc_html_e( 'Autoplay speed in milliseconds.', 'lps' ); ?></p>
							</td>
						</tr>
					</tbody>
					<tr>
						<td><?php esc_html_e( 'Height', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderheight" id="lps_sliderheight" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'adaptive', 'lps' ); ?></option>
								<option value="fixed"><?php esc_html_e( 'fixed', 'lps' ); ?></option>
							</select>
							<p class="comment"><?php esc_html_e( 'This depends on the image size you select', 'lps' ); ?></p>
						</td>
					</tr>
					<tbody id="lps_sliderheight_options">
						<tr>
							<td><?php esc_html_e( 'Maximum Height', 'lps' ); ?></td>
							<td>
								<input type="number" name="lps_slidermaxheight" id="lps_slidermaxheight" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="280" min="1" max="1200">
								<p class="comment lps-update-blink"><?php esc_html_e( 'Provide the value in pixels for the maximum height of the slider.', 'lps' ); ?></p>
							</td>
						</tr>
					</tbody>
					<tr>
						<td><?php esc_html_e( 'Show Slide Overlay', 'lps' ); ?></td>
						<td>
							<select name="lps_slideoverlay" id="lps_slideoverlay" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'title + few chars from the excerpt', 'lps' ); ?></option>
								<option value="title"><?php esc_html_e( 'only title', 'lps' ); ?></option>
								<option value="text"><?php esc_html_e( 'only few chars from the excerpt', 'lps' ); ?></option>
								<option value="no"><?php esc_html_e( 'no overlay, just the image', 'lps' ); ?></option>
							</select>
							<p class="comment"><?php esc_html_e( 'The slide will display over the image the title and few chars from the excerpt (what you define for the Post Appearance > Chars Limit below).', 'lps' ); ?></p>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Slides Gaps', 'lps' ); ?></td>
						<td>
							<select name="lps_slidegap" id="lps_slidegap" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no gaps', 'lps' ); ?></option>
								<option value="5"><?php esc_html_e( '5px', 'lps' ); ?></option>
								<option value="10"><?php esc_html_e( '10px', 'lps' ); ?></option>
								<option value="15"><?php esc_html_e( '15px', 'lps' ); ?></option>
								<option value="20"><?php esc_html_e( '20px', 'lps' ); ?></option>
								<option value="25"><?php esc_html_e( '25px', 'lps' ); ?></option>
								<option value="30"><?php esc_html_e( '30px', 'lps' ); ?></option>
								<option value="50"><?php esc_html_e( '50px', 'lps' ); ?></option>
							</select>
							<p class="comment"><?php esc_html_e( 'This is useful when you want to display more visible slides in the row.', 'lps' ); ?></p>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Slides to Show', 'lps' ); ?></td>
						<td>
							<input type="number" name="lps_slideslides" id="lps_slideslides" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="1" min="1" max="12">
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Slides to Scroll', 'lps' ); ?></td>
						<td>
							<input type="number" name="lps_slidescroll" id="lps_slidescroll" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="1" min="1" max="12">
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Show Dots', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderdots" id="lps_sliderdots" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Infinite Scroll', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderinfinite" id="lps_sliderinfinite" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Next/Previous', 'lps' ); ?></td>
						<td>
							<select name="lps_slidercontrols" id="lps_slidercontrols" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Responsive', 'lps' ); ?></td>
						<td>
							<select name="lps_slidersponsive" id="lps_slidersponsive" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="yes"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<div id="lps_slidersponsive_options" class="settings-block">
				<h3><?php esc_html_e( 'Responsive Slider Settings', 'lps' ); ?></h3><hr>
				<table width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td><?php esc_html_e( 'Respond to', 'lps' ); ?></td>
						<td>
							<select name="lps_respondto" id="lps_respondto" data-default="window" onchange="lpsRefresh()">
								<option value="window"><?php esc_html_e( 'window', 'lps' ); ?></option>
								<option value="slider"><?php esc_html_e( 'slider', 'lps' ); ?></option>
								<option value=""><?php esc_html_e( 'min', 'lps' ); ?></option>
							</select>
							<p class="comment lps-update-blink">
								<?php esc_html_e( 'Width that responsive object responds to, the min (default) option means the smaller of the window or slider.', 'lps' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td colspan="2"><?php esc_html_e( 'Tablet Settings', 'lps' ); ?></td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Breakpoint', 'lps' ); ?></td>
						<td>
							<input type="number" name="lps_sliderbreakpoint_tablet" id="lps_sliderbreakpoint_tablet" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="600" min="320" max="1200">
						</td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Slides to Show', 'lps' ); ?></td>
						<td>
							<input type="number" name="lps_slideslides_tablet" id="lps_slideslides_tablet" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="1" min="1" max="12">
						</td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Slides to Scroll', 'lps' ); ?></td>
						<td>
							<input type="number" name="lps_slidescroll_tablet" id="lps_slidescroll_tablet" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="1" min="1" max="12">
						</td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Show Dots', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderdots_tablet" id="lps_sliderdots_tablet" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Infinite Scroll', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderinfinite_tablet" id="lps_sliderinfinite_tablet" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<td colspan="2"><?php esc_html_e( 'Mobile Settings', 'lps' ); ?></td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Breakpoint', 'lps' ); ?></td>
						<td>
							<input type="number" name="lps_sliderbreakpoint_mobile" id="lps_sliderbreakpoint_mobile" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="480" min="320" max="1024">
						</td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Slides to Show', 'lps' ); ?></td>
						<td>
							<input type="number" name="lps_slideslides_mobile" id="lps_slideslides_mobile" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="1" min="1" max="12">
						</td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Slides to Scroll', 'lps' ); ?></td>
						<td>
							<input type="number" name="lps_slidescroll_mobile" id="lps_slidescroll_mobile" onchange="lpsRefresh()" onkeyup="lpsRefresh()" value="1" min="1" max="12">
						</td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Show Dots', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderdots_mobile" id="lps_sliderdots_mobile" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="left-padd">&bull; <?php esc_html_e( 'Infinite Scroll', 'lps' ); ?></td>
						<td>
							<select name="lps_sliderinfinite_mobile" id="lps_sliderinfinite_mobile" data-default="" onchange="lpsRefresh()">
								<option value=""><?php esc_html_e( 'no', 'lps' ); ?></option>
								<option value="true"><?php esc_html_e( 'yes', 'lps' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Get short text of maximum x chars.
	 *
	 * @param  string  $text       Text.
	 * @param  integer $limit      Limit of chars.
	 * @param  boolean $is_excerpt True if this represents an excerpt.
	 * @param  string  $trimmore   Maybe some trailing extra chars for truncated string.
	 * @return string
	 */
	public static function get_short_text( $text, $limit, $is_excerpt = false, $trimmore = '' ) { // phpcs:ignore
		if ( empty( $text ) ) {
			// Fail-fast.
			return '';
		}

		$filter = ( $is_excerpt ) ? 'the_excerpt' : 'the_content';

		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '~\[[^\]]+\]~', '', $text );
		$text = strip_shortcodes( $text );
		$text = apply_filters( $filter, strip_shortcodes( $text ) );
		$text = preg_replace( '~\[[^\]]+\]~', '', $text );
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '~\[[^\]]+\]~', '', $text );
		/** This is a trick to replace the unicode whitespace :) */
		$text = preg_replace( '/\xA0/u', ' ', $text );
		$text = str_replace( '&nbsp;', ' ', $text );
		$text = preg_replace( '/\s\s+/', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		if ( empty( $text ) ) {
			// Fail-fast.
			return '';
		}

		$init_len = mb_strlen( $text );
		if ( $init_len <= $limit ) {
			// The text length is smaller than the limit.
			$text = apply_filters( $filter, $text );
			$text = str_replace( ']]>', ']]&gt;', $text );
			return $text;
		}

		$content = explode( ' ', $text );

		$len  = 0;
		$i    = 0;
		$max  = count( $content );
		$text = '';
		while ( $len < $limit ) {
			$text .= $content[ $i ] . ' ';
			++$i;
			$len = mb_strlen( $text );
			if ( $i >= $max || $len >= $limit ) {
				break;
			}
		}

		if ( ! empty( $text ) ) {
			$text = trim( $text );
			$text = preg_replace( '/\[.+\]/', '', $text );
			$text = self::cleanup_tralining_punctuation( $text );
			$text = trim( $text );
			if ( ! empty( $trimmore ) && ! empty( $text ) && mb_strlen( $text ) !== $init_len ) {
				$text .= $trimmore;
				$text  = trim( $text );
			}

			$text = apply_filters( $filter, $text );
			$text = str_replace( ']]>', ']]&gt;', $text );
		}

		return $text;
	}

	/**
	 * Cleanup tralining punctuation.
	 *
	 * @param  string $text Initial string.
	 * @return string
	 */
	public static function cleanup_tralining_punctuation( $text = '' ) { //phpcs:ignore
		if ( ! empty( $text ) && is_string( $text ) ) {
			$text = trim( $text, " \t\n\r\0\x0B-.,:|?!-_`'…" );
		}
		return $text;
	}

	/**
	 * Execute the reset of shortcodes cache in the database.
	 *
	 * @return void
	 */
	public static function purge_site_lps_cache() {
		global $wpdb;
		// Remove all the transients records in one query.
		$tmp_query = $wpdb->prepare(
			' DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE %s OR option_name LIKE %s ',
			$wpdb->esc_like( '_transient_lps-' ) . '%',
			$wpdb->esc_like( '_transient_timeout_lps-' ) . '%'
		);
		$wpdb->query( $tmp_query ); // phpcs:ignore
	}

	/**
	 * Execute the reset of shortcodes cache.
	 *
	 * @return void
	 */
	public static function execute_lps_cache_reset() {
		self::purge_site_lps_cache();

		if ( is_multisite() ) {
			$sites = self::get_sites();
			if ( ! empty( $sites ) ) {
				foreach ( $sites as $id => $name ) {
					switch_to_blog( $id );
					self::purge_site_lps_cache();
					restore_current_blog();
				}
			}
		}

		remove_action( 'wp_insert_post', [ get_called_class(), 'execute_lps_cache_reset' ] );
		remove_action( 'post_updated', [ get_called_class(), 'execute_lps_cache_reset' ] );
		remove_action( 'wp_trash_post', [ get_called_class(), 'execute_lps_cache_reset' ] );
		remove_action( 'before_delete_post', [ get_called_class(), 'execute_lps_cache_reset' ] );
	}

	/**
	 * Reset the shortcodes cache.
	 *
	 * @return void
	 */
	public static function lps_reset_cache() {
		$get = filter_input( INPUT_GET, 'no-cache', FILTER_DEFAULT );
		if ( ! empty( $get ) ) {
			self::execute_lps_cache_reset();
			echo 'OK';
			die();
		}
	}

	/**
	 * Return the content generated after an ajax call for the pagination.
	 *
	 * @return void
	 */
	public static function lps_navigate_callback() {
		$args    = filter_input( INPUT_POST, 'args', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$current = filter_input( INPUT_POST, 'current', FILTER_DEFAULT );
		$shid    = filter_input( INPUT_POST, 'id', FILTER_DEFAULT );
		if ( ! empty( $args ) ) {
			if ( ! empty( $current ) ) {
				if ( empty( $args['archive'] ) ) {
					if ( empty( $args['excludeid'] ) ) {
						$args['excludeid'] = (int) $current;
					} else {
						$args['excludeid'] .= ',' . (int) $current;
					}
				}
			}
			header( 'Content-type: text/html; charset=utf-8' );
			$_args = $args;
			if ( is_array( $args ) ) {
				foreach ( $args as $key => $value ) {
					$args[ $key ] = sanitize_text_field( $value );
				}
			} else {
				$_args = stripslashes( stripslashes( $args ) );
				$args  = ( ! empty( $_args ) ) ? json_decode( $_args ) : false;
			}

			$ppage = filter_input( INPUT_POST, 'page', FILTER_DEFAULT );
			if ( ! empty( $ppage ) && $args ) {
				$args = (array) $args;
				if ( ! empty( $args['linktext'] ) ) {
					$args['linktext'] = preg_replace( '/u([0-9a-z]{4})+/', '&#x$1;', $args['linktext'] );
				}
				set_query_var( 'page', (int) $ppage );

				global $is_lps_ajax_call, $is_ajax_shortcode_id, $lps_current_queried_object_id;
				$is_lps_ajax_call              = true;
				$is_ajax_shortcode_id          = str_replace( '-wrap', '', $shid );
				$lps_current_queried_object_id = (int) $current;
				echo self::latest_selected_content( $args ); // phpcs:ignore
			}
		}
		die();
	}

	/**
	 * Return the content generated for plugin pagination with the specific arguments.
	 *
	 * @param  integer $total         Total of records.
	 * @param  integer $per_page      How many per page.
	 * @param  integer $range         Range size.
	 * @param  string  $shortcode_id  Shortcode id (element selector).
	 * @param  string  $class         Pagination CSS class.
	 * @param  array   $args          Load more text, total text, show total.
	 * @param  integer $maxpg         Maximum number of total pages (leave 0 for default).
	 * @param  integer $site_initial  Initial site.
	 * @param  integer $site_expected Expected/requested site.
	 * @return string
	 */
	public static function lps_pagination( $total = 1, $per_page = 10, $range = 4, $shortcode_id = '', $class = '', $args = [], $maxpg = 0, $site_initial = 0, $site_expected = 0 ) { // phpcs:ignore
		$current_page = self::get_current_page();
		wp_reset_postdata();

		if ( is_multisite() && $site_initial !== $site_expected ) {
			switch_to_blog( $site_initial );
		}

		$body     = '';
		$total    = (int) $total;
		$all      = $total;
		$per_page = ( ! empty( $per_page ) ) ? (int) $per_page : 1;
		$range    = abs( (int) $range );
		$range    = ( empty( $range ) ) ? 1 : $range;
		$total    = ceil( $total / $per_page );
		if ( ! empty( $maxpg ) && $maxpg < $total ) {
			$total = $maxpg;
		}

		if ( $total > 1 ) {
			if ( 0 === ( $current_page % $range ) ) {
				$start = $current_page - $range + 1;
			} else {
				$start = $current_page - $current_page % $range + 1;
			}
			$start = ( $start <= 1 ) ? 1 : $start;
			$end   = $start + $range - 1;
			if ( $end >= $total ) {
				$end = $total;
			}

			$more_text  = ! empty( $args['more_text'] ) ? $args['more_text'] : '';
			$total_text = ! empty( $args['total_text'] ) ? esc_attr( $args['total_text'] ) : '';
			$show_total = ! empty( $args['show_total'] ) && ! empty( $total_text ) && substr_count( $total_text, '%d' );

			if ( substr_count( $class, ' lps-load-more' ) ) {
				$body .= '<ul class="latest-post-selection pages ' . esc_attr( trim( $class ) ) . ' ' . esc_attr( $shortcode_id ) . '">';
				if ( $show_total ) {
					$body .= '<li class="pages-info">' . sprintf( $total_text, $all ) . '</li>';
				}

				if ( $current_page < $total ) {
					$text  = ( ! empty( $more_text ) ) ? $more_text : __( 'Load more', 'lps' );
					$body .= '<li class="go-to-next lps-load-more"><a class="page-item" href="' . get_pagenum_link( $current_page + 1 ) . '" data-page="' . ( $current_page + 1 ) . '" title="' . esc_attr( $text ) . '">' . esc_html( $text ) . '</a></li>';
				}
				$body .= '</ul>';
			} else {
				$root_url = get_pagenum_link( 0 );
				$body    .= '<ul class="latest-post-selection pages ' . esc_attr( trim( $class ) ) . ' ' . esc_attr( $shortcode_id ) . '">';

				if ( $show_total ) {
					$body .= '<li class="pages-info">' . sprintf( $total_text, $all ) . '</li>';
				}

				$body .= '<li class="pages-info">' . __( 'Page', 'lps' ) . ' ' . $current_page . ' ' . __( 'of', 'lps' ) . ' ' . $total . '</li>';

				if ( $total > $range && $start > $range ) {
					$body .= '<li class="go-to-first"><a class="page-item" href="' . $root_url . '" data-page="1" title="' . esc_attr__( 'First', 'lps' ) . '">&lsaquo;&nbsp;</a></li>';
				} elseif ( $total > $range ) {
						$body .= '<li class="go-to-first disabled"><a class="page-item" data-page="' . $current_page . '" title="' . esc_attr__( 'First', 'lps' ) . '">&lsaquo;&nbsp;</a></li>';
				}

				if ( $current_page > 1 ) {
					if ( 2 === $current_page ) {
						$body .= '<li class="go-to-prev"><a class="page-item" href="' . $root_url . '" data-page="1" title="' . esc_attr__( 'Previous', 'lps' ) . '">&laquo;</a></li>';
					} else {
						$body .= '<li class="go-to-prev"><a class="page-item" href="' . get_pagenum_link( $current_page - 1 ) . '" data-page="' . ( $current_page - 1 ) . '" title="' . esc_attr__( 'Previous', 'lps' ) . '">&laquo;</a></li>';
					}
				} else {
					$body .= '<li class="go-to-prev disabled"><a class="page-item" data-page="' . $current_page . '" title="' . esc_attr__( 'Previous', 'lps' ) . '">&laquo;</a></li>';
				}

				for ( $i = $start; $i <= $end; $i++ ) {
					if ( 1 === $i ) {
						if ( $current_page === $i ) {
							$body .= '<li class="current"><a class="page-item" href="' . $root_url . '" data-page="1" title="' . esc_attr__( 'First', 'lps' ) . '">' . $i . '</a></li>';
						} else {
							$body .= '<li><a class="page-item" href="' . $root_url . '" data-page="1" title="' . esc_attr__( 'First', 'lps' ) . '">' . $i . '</a></li>';
						}
					} elseif ( $current_page === $i ) {
							$body .= '<li class="current"><a class="page-item" data-page="' . $i . '" title="' . esc_attr__( 'Page', 'lps' ) . ' ' . $i . '">' . $i . '</a></li>';
					} else {
						$body .= '<li><a class="page-item" href="' . get_pagenum_link( $i ) . '" data-page="' . $i . '" title="' . esc_attr__( 'Page', 'lps' ) . ' ' . $i . '">' . $i . '</a></li>';
					}
				}

				if ( $current_page < $total ) {
					$body .= '<li class="go-to-next"><a class="page-item" href="' . get_pagenum_link( $current_page + 1 ) . '" data-page="' . ( $current_page + 1 ) . '" title="' . esc_attr__( 'Next', 'lps' ) . '">&raquo;</a></li>';
				} else {
					$body .= '<li class="go-to-next disabled"><a class="page-item" data-page="' . $current_page . '" title="' . esc_attr__( 'Next', 'lps' ) . '">&raquo;</a></li>';
				}
				if ( $end < $total ) {
					$body .= '<li class="go-to-last"><a class="page-item" href="' . get_pagenum_link( $total ) . '" data-page="' . $total . '" title="' . esc_attr__( 'Last', 'lps' ) . '">&nbsp;&rsaquo;</a></li>';
				} elseif ( $total > $range ) {
						$body .= '<li class="go-to-last disabled"><a class="page-item" data-page="' . $current_page . '" title="' . esc_attr__( 'Last', 'lps' ) . '">&nbsp;&rsaquo;</a></li>';
				}
				$body .= '</ul>';
			}

			if ( ! empty( $body ) ) {
				$body = '<div class="lps-pagination-wrap ' . trim( $class ) . '">' . $body . '</div>';
			}
		}

		if ( is_multisite() && $site_initial !== $site_expected ) {
			switch_to_blog( $site_expected );
		}

		return $body;
	}

	/**
	 * Dynamic relative time.
	 *
	 * @param integer $id The post ID.
	 * @return string
	 */
	public static function relative_time( $id = null ) { // phpcs:ignore
		if ( function_exists( 'current_datetime' ) ) {
			$date = current_datetime();
			$now  = ( ! empty( $date->date ) ) ? strtotime( $date->date ) : current_time( 'timestamp' ); // phpcs:ignore
		} else {
			$now = current_time( 'timestamp' ); // phpcs:ignore
		}

		return sprintf(
			// Translators: %s the date difference.
			_x( '%s ago', '%s = human-readable time difference', 'lps' ),
			strtolower( human_time_diff( get_the_time( 'U', $id ), (int) $now ) )
		);
	}

	/**
	 * Get the current page for pagination.
	 *
	 * @return int
	 */
	public static function get_current_page(): int {
		global $wp;

		$maybe_var   = get_query_var( 'paged' );
		$maybe_paged = filter_input( INPUT_GET, 'paged', FILTER_VALIDATE_INT );
		$maybe_page  = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );
		if ( empty( $maybe_paged ) && empty( $maybe_page ) ) {
			$maybe_paged = filter_input( INPUT_POST, 'paged', FILTER_VALIDATE_INT );
			$maybe_page  = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );
		}

		if ( empty( $maybe_paged ) && empty( $maybe_page ) && ! empty( $maybe_var ) ) {
			$maybe_paged = $maybe_var;
		}

		if ( empty( $maybe_paged ) && ! empty( $wp->query_vars['paged'] ) ) {
			$maybe_paged = (int) $wp->query_vars['paged'];
		}

		$paged = 1;
		if ( ! empty( $maybe_paged ) ) {
			$paged = $maybe_paged;
		} elseif ( ! empty( $maybe_page ) ) {
			$paged = $maybe_page;
		}
		$paged = abs( intval( $paged ) );

		return $paged;
	}

	/**
	 * Returns true if the execution is triggered in the editor.
	 *
	 * @return bool
	 */
	public static function is_in_the_editor(): bool {
		// phpcs:disable
		$context = $_REQUEST['context'] ?? '';
		$action  = $_REQUEST['action'] ?? '';
		// phpcs:enable

		$in_the_editor = defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $context ) && ( 'edit' === $context || 'edit' === $action ); // phpcs:ignore

		if ( empty( $in_the_editor ) ) {
			$pagination_link = get_pagenum_link( 1 );
			if ( substr_count( $pagination_link, '/post.php?' ) || substr_count( $pagination_link, 'autosaves?' ) ) {
				$in_the_editor = true;
			}
		}

		return $in_the_editor;
	}

	/**
	 * Return the content generated by a shortcode with the specific arguments.
	 *
	 * @param  array $args Array of shortcode arguments.
	 * @return string
	 */
	public static function latest_selected_content( $args ) { // phpcs:ignore
		if ( empty( $args ) ) {
			// Fail-fast, too bad, this is used wrong, there is no argument.
			return '';
		}

		$args = wp_parse_args( $args, [
			'lps_instance_id'         => '',
			'ver'                     => 1,
			'output'                  => '',
			'limit'                   => '',
			'perpage'                 => '',
			'id'                      => '',
			'excludeid'               => '',
			'parent'                  => '',
			'dparent'                 => '',
			'author'                  => '',
			'dauthor'                 => '',
			'excludeauthor'           => '',
			'type'                    => 'any',
			'site_id'                 => '',
			'titletag'                => '',
			'chrlimit'                => 120,
			'more'                    => '',
			'display'                 => '',
			'url'                     => '',
			'lightbox_size'           => '',
			'lightbox_attr'           => '',
			'lightbox_val'            => '',
			'linktext'                => '',
			'elements'                => '',
			'default_height'          => '',
			'default_padding'         => '',
			'default_gap'             => '',
			'default_overlay_padding' => '',
			'tablet_height'           => '',
			'tablet_padding'          => '',
			'tablet_gap'              => '',
			'tablet_overlay_padding'  => '',
			'mobile_height'           => '',
			'mobile_padding'          => '',
			'mobile_gap'              => '',
			'mobile_overlay_padding'  => '',
			'color_text'              => '',
			'color_title'             => '',
			'color_bg'                => '',
			'size_text'               => '',
			'size_title'              => '',
			'size_image'              => '',
			'image_opacity'           => '',
			'image_ratio'             => '',
			'card_ratio'              => '',
			'css'                     => '',
			'show_extra'              => '',
			'status'                  => '',
			'orderby'                 => '',
			'orderby_meta'            => '',
			'archive'                 => '',
			'archive_s'               => '',
			'archive_tax'             => '',
			'archive_id'              => '',
			'search'                  => '',
			'offset'                  => '',
			'tag'                     => '',
			'dtag'                    => '',
			'taxonomy'                => '',
			'term'                    => '',
			'taxonomy2'               => '',
			'term2'                   => '',
			'exclude_tags'            => '',
			'exclude_categories'      => '',
			'date_limit'              => '',
			'date_start'              => '',
			'date_start_type'         => '',
			'date_after'              => '',
			'date_before'             => '',
			'showpages'               => '',
			// Translators: %d - total value.
			'total_text'              => __( 'Total items: %d', 'lps' ),
			'loadtext'                => '',
			'pagespos'                => '',
			'fallback'                => '',
			'image'                   => '',
			'image_placeholder'       => '',
			'slidermode'              => '',
			'centermode'              => '',
			'centerpadd'              => '',
			'sliderauto'              => '',
			'sliderspeed'             => '',
			'slidersponsive'          => '',
			'respondto'               => '',
			'sliderwrap'              => '',
			'slideslides'             => '',
			'slidescroll'             => '',
			'sliderdots'              => '',
			'sliderinfinite'          => '',
			'slideoverlay'            => '',
			'slidegap'                => '',
			'sliderbreakpoint_tablet' => '',
			'slideslides_tablet'      => '',
			'slidescroll_tablet'      => '',
			'sliderdots_tablet'       => '',
			'sliderinfinite_tablet'   => '',
			'sliderbreakpoint_mobile' => '',
			'slideslides_mobile'      => '',
			'slidescroll_mobile'      => '',
			'sliderdots_mobile'       => '',
			'sliderinfinite_mobile'   => '',
			'sliderheight'            => '',
			'slidermaxheight'         => '',
			'slidercontrols'          => '',
		] );

		global $post, $lps_current_post_embedded_item_ids, $is_lps_ajax_call, $is_ajax_shortcode_id, $lps_current_queried_object_id;
		if ( empty( $is_lps_ajax_call ) ) {
			$is_lps_ajax_call = false;
		}

		if ( empty( $lps_current_post_embedded_item_ids ) ) {
			$lps_current_post_embedded_item_ids = [];
		}

		$lps_current_post_embedded_item_ids = apply_filters( 'lps_filter_exclude_previous_content_ids', $lps_current_post_embedded_item_ids );

		if ( ! empty( $lps_current_queried_object_id ) ) {
			$current_object = get_post( $lps_current_queried_object_id );
		} else {
			$current_object = get_queried_object();
		}

		// Maybe filter some more shortcode arguments.
		$args        = apply_filters( 'lps_filter_use_custom_shortcode_arguments', $args );
		$args['ver'] = isset( $args['ver'] ) ? abs( (int) $args['ver'] ) : 1;
		$args['ver'] = $args['ver'] >= 2 ? 2 : $args['ver'];

		// Maybe use the site id.
		$args['site_id'] = is_multisite() && ! empty( $args['site_id'] ) ? (int) $args['site_id'] : 0;

		$site_switched = false;
		$site_initial  = 0;
		$site_expected = 0;
		if ( ! empty( $args['site_id'] ) && \get_current_blog_id() !== $args['site_id'] ) {
			$site_initial  = \get_current_blog_id();
			$site_expected = $args['site_id'];
			$site_switched = true;
			\switch_to_blog( $args['site_id'] );
		}

		$maxpg = 0;
		if ( empty( $args['output'] ) && ! empty( $args['limit'] ) && ! empty( $args['perpage'] ) ) {
			// Limit pagination items.
			$paged = get_query_var( 'paged' ) ? abs( intval( get_query_var( 'paged' ) ) ) : 1;
			$maxpg = ceil( (int) $args['limit'] / (int) $args['perpage'] );
			if ( $paged > $maxpg ) {
				// No further computation, the pagination limit was reached.
				return false;
			}
		}

		// Get the post arguments from shortcode arguments.
		$ids         = ( ! empty( $args['id'] ) ) ? explode( ',', $args['id'] ) : [];
		$exclude_ids = ( ! empty( $args['excludeid'] ) ) ? explode( ',', $args['excludeid'] ) : [];
		if ( ! empty( $args['dparent'] ) ) {
			$parent = ! empty( $current_object->post_parent ) ? [ (int) $current_object->post_parent ] : [ -9999 ];
		} else {
			$parent = ( ! empty( $args['parent'] ) ) ? explode( ',', $args['parent'] ) : [];
		}

		if ( ! empty( $args['dauthor'] ) ) {
			$author = ! empty( $current_object->post_author ) ? [ (int) $current_object->post_author ] : [ -9999 ];
		} else {
			$author = ( ! empty( $args['author'] ) ) ? explode( ',', $args['author'] ) : [];
		}
		$exclude_authors = ( ! empty( $args['excludeauthor'] ) ) ? explode( ',', $args['excludeauthor'] ) : [];

		$type = ( ! empty( $args['type'] ) ) ? $args['type'] : 'post';
		if ( substr_count( $type, ',' ) ) {
			$type = explode( ',', $type );
		}

		$titletag      = ( ! empty( $args['titletag'] ) && in_array( $args['titletag'], self::$title_tags, true ) ) ? $args['titletag'] : 'h3';
		$chrlimit      = ( ! empty( $args['chrlimit'] ) ) ? intval( $args['chrlimit'] ) : 120;
		$trimmore      = ( ! empty( $args['more'] ) ) ? $args['more'] : '';
		$extra_display = ( ! empty( $args['display'] ) ) ? explode( ',', $args['display'] ) : [ 'title' ];
		$linkurl       = ( ! empty( $args['url'] ) && ( 'yes' === $args['url'] || 'yes_blank' === $args['url'] ) ) ? true : false;
		$linkmedia     = ( ! empty( $args['url'] ) && ( 'yes_media' === $args['url'] || 'yes_media_blank' === $args['url'] || 'yes_media_lightbox' === $args['url'] ) ) ? true : false;
		$lightbox_size = ( $linkmedia && ! empty( $args['lightbox_size'] ) ) ? $args['lightbox_size'] : '';
		$lightbox_attr = ( $linkmedia && ! empty( $args['lightbox_attr'] ) ) ? $args['lightbox_attr'] : '';
		$lightbox_val  = ( $linkmedia && ! empty( $args['lightbox_val'] ) ) ? $args['lightbox_val'] : '';
		$linkblank     = ( ! empty( $args['url'] ) && ( 'yes_blank' === $args['url'] || 'yes_media_blank' === $args['url'] || 'yes_media_lightbox' === $args['url'] ) ) ? true : false;

		$tile_type = 0;
		if ( $linkurl || $linkmedia ) {
			$linktext = ( ! empty( $args['linktext'] ) ) ? $args['linktext'] : '';
		}

		$tile_type = ( ! empty( $args['elements'] ) && ! empty( self::$tile_pattern[ $args['elements'] ] ) ) ? $args['elements'] : 0;

		if ( $args['ver'] >= 2 ) {
			// Version >= 2 markup.
			if ( ! substr_count( '_custom_', $tile_type )
				&& ! in_array( $tile_type, self::$tile_pattern_ver2, true ) ) {
				$tile_type = 0;
			}
		}

		$tile_pattern = ( ! empty( self::$tile_pattern[ $tile_type ] ) ) ? self::$tile_pattern[ $tile_type ] : 'title';

		$link_class      = ' class="main-link"';
		$read_more_class = ' class="read-more"';

		$tiles_custom_style_vars = '';
		if ( ! empty( $args['default_height'] ) ) {
			$tiles_custom_style_vars .= ' --default-tile-height: ' . esc_attr( $args['default_height'] ) . ';';
		}
		if ( ! empty( $args['default_padding'] ) ) {
			$tiles_custom_style_vars .= ' --default-tile-padding: ' . esc_attr( $args['default_padding'] ) . ';';
		}
		if ( ! empty( $args['default_gap'] ) ) {
			$tiles_custom_style_vars .= ' --default-tile-gap: ' . esc_attr( $args['default_gap'] ) . ';';
		}
		if ( ! empty( $args['default_overlay_padding'] ) ) {
			$tiles_custom_style_vars .= ' --default-overlay-padding: ' . esc_attr( $args['default_overlay_padding'] ) . ';';
		}

		if ( ! empty( $args['tablet_height'] ) ) {
			$tiles_custom_style_vars .= ' --tablet-tile-height: ' . esc_attr( $args['tablet_height'] ) . ';';
		}
		if ( ! empty( $args['tablet_padding'] ) ) {
			$tiles_custom_style_vars .= ' --tablet-tile-padding: ' . esc_attr( $args['tablet_padding'] ) . ';';
		}
		if ( ! empty( $args['tablet_gap'] ) ) {
			$tiles_custom_style_vars .= ' --tablet-tile-gap: ' . esc_attr( $args['tablet_gap'] ) . ';';
		}
		if ( ! empty( $args['tablet_overlay_padding'] ) ) {
			$tiles_custom_style_vars .= ' --tablet-overlay-padding: ' . esc_attr( $args['tablet_overlay_padding'] ) . ';';
		}

		if ( ! empty( $args['mobile_height'] ) ) {
			$tiles_custom_style_vars .= ' --mobile-tile-height: ' . esc_attr( $args['mobile_height'] ) . ';';
		}
		if ( ! empty( $args['mobile_padding'] ) ) {
			$tiles_custom_style_vars .= ' --mobile-tile-padding: ' . esc_attr( $args['mobile_padding'] ) . ';';
		}
		if ( ! empty( $args['mobile_gap'] ) ) {
			$tiles_custom_style_vars .= ' --mobile-tile-gap: ' . esc_attr( $args['mobile_gap'] ) . ';';
		}
		if ( ! empty( $args['mobile_overlay_padding'] ) ) {
			$tiles_custom_style_vars .= ' --mobile-overlay-padding: ' . esc_attr( $args['mobile_overlay_padding'] ) . ';';
		}

		if ( ! empty( $args['color_text'] ) ) {
			$tiles_custom_style_vars .= ' --article-text-color: ' . esc_attr( $args['color_text'] ) . ';';
		}
		if ( ! empty( $args['color_title'] ) ) {
			$tiles_custom_style_vars .= ' --article-title-color: ' . esc_attr( $args['color_title'] ) . ';';
		}
		if ( ! empty( $args['color_bg'] ) ) {
			$tiles_custom_style_vars .= ' --article-bg-color: ' . esc_attr( $args['color_bg'] ) . ';';
		}
		if ( ! empty( $args['size_text'] ) ) {
			$tiles_custom_style_vars .= ' --article-size-text: ' . esc_attr( $args['size_text'] ) . ';';
		}
		if ( ! empty( $args['size_title'] ) ) {
			$tiles_custom_style_vars .= ' --article-size-title: ' . esc_attr( $args['size_title'] ) . ';';
		}
		if ( ! empty( $args['image_opacity'] ) ) {
			$tiles_custom_style_vars .= ' --article-image-opacity: ' . esc_attr( $args['image_opacity'] ) . ';';
		}
		if ( ! empty( $args['size_image'] ) ) {
			$args['css'] .= ' has-image-size';
			$args['css']  = trim( $args['css'] );

			$tiles_custom_style_vars .= ' --article-image-size: ' . esc_attr( $args['size_image'] ) . ';';
		}
		if ( ! empty( $args['image_ratio'] ) ) {
			if ( 'contain' === $args['image_ratio'] ) {
				$args['css'] .= ' has-image-contain';
			} else {
				$args['css'] .= ' has-image-ratio';

				$tiles_custom_style_vars .= ' --article-image-ratio: ' . esc_attr( $args['image_ratio'] ) . ';';
			}

			$args['css'] = trim( $args['css'] );
		}
		if ( ! empty( $args['card_ratio'] ) ) {
			$tiles_custom_style_vars .= ' --article-ratio: ' . esc_attr( $args['card_ratio'] ) . ';';
		}

		$lightbox_extra = '';
		if ( in_array( (int) $tile_type, [ 3, 11, 14, 19 ], true ) ) {
			$link_class      = ' class="main-link read-more-wrap"';
			$read_more_class = '';
		}
		if ( ! empty( $lightbox_attr ) ) {
			if ( 'class' === $lightbox_attr ) {
				if ( $link_class ) {
					$link_class = str_replace( 'class="', 'class="' . esc_attr( $lightbox_val ) . ' ', $link_class );
				}
				if ( $read_more_class ) {
					$read_more_class = str_replace( 'class="', 'class="' . esc_attr( $lightbox_val ) . ' ', $read_more_class );
				}
			} else {
				$lightbox_extra = ' ' . esc_attr( $lightbox_attr ) . '="' . esc_attr( $lightbox_val ) . '"';
			}
		}

		$show_extra  = ( ! empty( $args['show_extra'] ) ) ? explode( ',', $args['show_extra'] ) : [];
		$raw_content = ( in_array( 'raw', $show_extra, true ) ) ? true : false;
		$trim_text   = ( in_array( 'trim', $show_extra, true ) ) ? true : false;

		$qargs = [
			'numberposts' => 1,
			'post_status' => 'publish',
		];

		if ( ! empty( $args['status'] ) ) {
			$qargs['post_status'] = explode( ',', trim( $args['status'] ) );
			if ( in_array( 'private', $qargs['post_status'], true ) ) {
				if ( ! is_user_logged_in() ) {
					$pkey = array_search( 'private', $qargs['post_status'], true );
					if ( false !== $pkey ) {
						unset( $qargs['post_status'][ $pkey ] );
					}
				}
			}
		}
		if ( empty( $qargs['post_status'] ) ) {
			return;
		}

		self::$current_query_statuses_list = $qargs['post_status'];

		$orderby          = ( ! empty( $args['orderby'] ) ) ? $args['orderby'] : 'dateD';
		$qargs['order']   = 'DESC';
		$qargs['orderby'] = 'date';
		if ( ! empty( $orderby ) && ! empty( self::$orderby_options[ $orderby ] ) ) {
			$qargs['order']   = self::$orderby_options[ $orderby ]['order'];
			$qargs['orderby'] = self::$orderby_options[ $orderby ]['orderby'];
			if ( substr_count( $qargs['orderby'], 'meta_value' ) ) {
				$qargs['meta_key'] = $args['orderby_meta']; // phpcs:ignore
			}
		}

		$is_lps_archive = ! empty( $args['archive'] ) || ! empty( $args['archive_s'] )
			|| ! empty( $args['archive_tax'] );
		$is_lps_search  = ! empty( $args['search'] );

		// Make sure we do not loop in the current page.
		if ( ! ( $is_lps_archive || $is_lps_search ) ) {
			if ( ! empty( $post->ID ) ) {
				$qargs['post__not_in'] = [ $post->ID ];
			}
		}

		// Exclude specified post IDs.
		if ( ! empty( $exclude_ids ) ) {
			if ( ! empty( $qargs['post__not_in'] ) ) {
				$qargs['post__not_in'] = array_merge( $qargs['post__not_in'], $exclude_ids );
			} else {
				$qargs['post__not_in'] = $exclude_ids;
			}
		}

		if ( ! empty( $show_extra ) && in_array( 'exclude_previous_content', $show_extra, true ) ) {
			// Exclude the previous ID embedded through the plugin shortcodes on this page.
			if ( is_scalar( $qargs['post__not_in'] ) ) {
				$qargs['post__not_in'] = [ $qargs['post__not_in'] ];
			}
			if ( empty( $lps_current_post_embedded_item_ids ) ) {
				$lps_current_post_embedded_item_ids = [];
			}
			$qargs['post__not_in'] = array_merge( $qargs['post__not_in'], $lps_current_post_embedded_item_ids );
		}

		if ( ! empty( $author ) ) {
			$qargs['author__in'] = $author;
		}

		if ( ! empty( $exclude_authors ) ) {
			$qargs['author__not_in'] = $exclude_authors;
		}

		if ( ! empty( $args['limit'] ) ) {
			$qargs['numberposts']    = ( ! empty( $args['limit'] ) ) ? intval( $args['limit'] ) : 1;
			$qargs['posts_per_page'] = ( ! empty( $args['limit'] ) ) ? intval( $args['limit'] ) : 1;
		}
		if ( empty( $args['output'] ) ) {
			$use_offset = true;
			if ( ! empty( $args['perpage'] ) ) {
				$qargs['posts_per_page'] = ( ! empty( $args['perpage'] ) ) ? intval( $args['perpage'] ) : 0;

				$current_page   = self::get_current_page();
				$qargs['paged'] = $current_page;
				$qargs['page']  = $current_page;

				$diff = $qargs['numberposts'] - $current_page * $qargs['posts_per_page'];
				if ( $diff <= 0 && $maxpg >= $current_page ) {
					// This is the forced limit on pagination.
					$qargs['posts_per_page'] = $qargs['posts_per_page'] - abs( $diff );
					$qargs['offset']         = abs( $qargs['numberposts'] - abs( $diff ) );
					if ( ! empty( $args['offset'] ) ) {
						$qargs['offset'] += intval( $args['offset'] );
					}
					$use_offset = false;
				}
			}
			if ( ! empty( $args['offset'] ) && true === $use_offset ) {
				$qargs['offset'] = ( ! empty( $args['offset'] ) ) ? intval( $args['offset'] ) : 0;
				if ( ! empty( $qargs['paged'] ) && $qargs['paged'] > 1 ) {
					$qargs['offset'] = abs( $current_page - 1 ) * $qargs['posts_per_page'] + $args['offset'];
				}
			}
		}

		$force_type = true;
		if ( ! empty( $ids ) && is_array( $ids ) ) {
			foreach ( $ids as $k => $v ) {
				$ids[ $k ] = intval( $v );
			}
			$qargs['post__in'] = $ids;
			$force_type        = false;
		}

		if ( $force_type ) {
			$qargs['post_type'] = $type;
		} elseif ( ! empty( $args['type'] ) ) {
				$qargs['post_type'] = $args['type'];
		}

		if ( $is_lps_archive || $is_lps_search ) {
			if ( ! empty( $args['type'] ) ) {
				$qargs['post_type'] = $args['type'];
			} else {
				$qargs['post_type'] = 'any';
			}
		}

		if ( empty( $qargs['post_type'] ) ) {
			$qargs['post_type'] = '';
		}

		if ( ! empty( $parent ) ) {
			$qargs['post_parent__in'] = $parent;
		}

		if ( ! empty( $args['search'] ) ) {
			$qargs['s'] = wp_strip_all_tags( esc_html( $args['search'] ) );
		}

		$qargs['tax_query'] = []; // phpcs:ignore

		if ( $is_lps_archive ) {
			$option_per_page         = get_option( 'posts_per_page' );
			$qargs['posts_per_page'] = (int) $option_per_page;
			$search_query            = get_search_query();
			if ( empty( $search_query ) ) {
				$maybe_args   = filter_input( INPUT_POST, 'args', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
				$search_query = $maybe_args['archive_s'] ?? '';
			}
			if ( ! empty( $search_query ) ) {
				$args['archive_s'] = wp_strip_all_tags( $search_query );
				$qargs['s']        = wp_strip_all_tags( $search_query );
			} else {
				$archive_object = \get_queried_object();
				if ( ! empty( $archive_object->taxonomy ) && ! empty( $archive_object->term_id ) ) {
					$archive_taxonomy = $archive_object->taxonomy;
					$archive_term_id  = $archive_object->term_id;
				} else {
					$maybe_args       = filter_input( INPUT_POST, 'args', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
					$archive_taxonomy = $maybe_args['archive_tax'] ?? '';
					$archive_term_id  = $maybe_args['archive_id'] ?? '';
				}

				if ( ! empty( $archive_taxonomy ) && ! empty( $archive_term_id ) ) {
					$args['archive_tax'] = wp_strip_all_tags( $archive_taxonomy );
					$args['archive_id']  = (int) $archive_term_id;

					array_push(
						$qargs['tax_query'],
						[
							'relation' => 'AND',
						]
					);

					array_push(
						$qargs['tax_query'],
						[
							'taxonomy' => $archive_taxonomy,
							'field'    => 'term_id',
							'terms'    => [ (int) $archive_term_id ],
						]
					);
				}
			}
		} else {
			if ( ! empty( $args['tag'] ) ) {
				array_push(
					$qargs['tax_query'],
					[
						'taxonomy' => 'post_tag',
						'field'    => 'slug',
						'terms'    => ( ! empty( $args['tag'] ) ) ? explode( ',', $args['tag'] ) : 'homenews',
					]
				);
			}
			if ( ! empty( $args['dtag'] ) && ! empty( $post->ID ) ) {
				$tag_ids = wp_get_post_tags(
					$post->ID,
					[
						'fields' => 'ids',
					]
				);
				if ( ! empty( $tag_ids ) && is_array( $tag_ids ) ) {
					if ( ! empty( $qargs['tax_query'] ) ) {
						array_push(
							$qargs['tax_query'],
							[
								'relation' => 'AND',
							]
						);
					}
					array_push(
						$qargs['tax_query'],
						[
							'taxonomy' => 'post_tag',
							'field'    => 'term_id',
							'terms'    => $tag_ids,
							'operator' => 'IN',
						]
					);
				}
			}
			if ( ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
				$include_children = true;
				if ( ! empty( $show_extra ) && in_array( 'term_strict', $show_extra, true ) ) {
					$include_children = false;
				}
				if ( ! empty( $qargs['tax_query'] ) ) {
					array_push(
						$qargs['tax_query'],
						[
							'relation' => 'AND',
						]
					);
				}
				array_push(
					$qargs['tax_query'],
					[
						'taxonomy'         => $args['taxonomy'],
						'field'            => 'slug',
						'terms'            => explode( ',', $args['term'] ),
						'include_children' => $include_children,
					]
				);
			}
			if ( ! empty( $args['taxonomy2'] ) && ! empty( $args['term2'] ) ) {
				$include_children = true;
				if ( ! empty( $show_extra ) && in_array( 'term2_strict', $show_extra, true ) ) {
					$include_children = false;
				}
				if ( ! empty( $qargs['tax_query'] ) ) {
					array_push(
						$qargs['tax_query'],
						[
							'relation' => 'AND',
						]
					);
				}
				array_push(
					$qargs['tax_query'],
					[
						'taxonomy'         => $args['taxonomy2'],
						'field'            => 'slug',
						'terms'            => explode( ',', $args['term2'] ),
						'include_children' => $include_children,
					]
				);
			}
		}

		if ( ! empty( $args['exclude_tags'] ) ) {
			if ( ! empty( $qargs['tax_query'] ) ) {
				array_push(
					$qargs['tax_query'],
					[
						'relation' => 'AND',
					]
				);
			}
			array_push(
				$qargs['tax_query'],
				[
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => explode( ',', $args['exclude_tags'] ),
					'operator' => 'NOT IN',
				]
			);
		}
		if ( ! empty( $args['exclude_categories'] ) ) {
			if ( ! empty( $qargs['tax_query'] ) ) {
				array_push(
					$qargs['tax_query'],
					[
						'relation' => 'AND',
					]
				);
			}
			array_push(
				$qargs['tax_query'],
				[
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => explode( ',', $args['exclude_categories'] ),
					'operator' => 'NOT IN',
				]
			);
		}

		if ( ! empty( $args['date_limit'] ) && ( ! empty( $args['date_start'] ) || ! empty( $args['date_start_type'] ) ) ) {
			$drange = ( ! empty( $args['date_start_type'] ) && in_array( $args['date_start_type'], self::$date_limit_units, true ) ) ? $args['date_start_type'] : 'months';
			$s_date = strtotime( gmdate( 'Y-m-d' ) . ' -' . abs( (int) $args['date_start'] ) . $drange );
			if ( ! empty( $s_date ) ) {
				$args['date_after'] = gmdate( 'Y-m-d', $s_date );
			}
		}
		if ( ! empty( $args['date_after'] ) || ! empty( $args['date_before'] ) ) {
			$drange = [];
			if ( ! empty( $args['date_after'] ) ) {
				$drange['after'] = esc_attr( $args['date_after'] );
			}
			if ( ! empty( $args['date_before'] ) ) {
				$drange['before'] = esc_attr( $args['date_before'] );
			}
			$qargs['date_query'] = [
				[
					array_merge(
						$drange,
						[
							'inclusive' => true,
						]
					),
				],
			];
		}

		if ( ! empty( $show_extra ) ) {
			if ( in_array( 'nosticky', $show_extra, true ) ) {
				$qargs['ignore_sticky_posts'] = true;

				$sticky_ids = get_option( 'sticky_posts' );
				if ( ! empty( $sticky_ids ) ) {
					if ( ! empty( $qargs['post__not_in'] ) ) {
						$qargs['post__not_in'] = array_merge( $qargs['post__not_in'], $sticky_ids );
					} else {
						$qargs['post__not_in'] = $sticky_ids;
					}
				}
			} elseif ( in_array( 'sticky', $show_extra, true ) ) {
				$qargs['ignore_sticky_posts'] = false;

				$sticky_ids = get_option( 'sticky_posts' );
				if ( ! empty( $sticky_ids ) ) {
					if ( ! empty( $qargs['post__in'] ) ) {
						$qargs['post__in'] = array_merge( $qargs['post__in'], $sticky_ids );
					} else {
						$qargs['post__in'] = $sticky_ids;
					}
				}
			}
		}

		$qargs = array_filter( $qargs );

		$qargs['post_type']        = $qargs['post_type'] ?? '';
		$qargs['suppress_filters'] = false;

		// Maybe use some custom query arguments.
		$qargs = apply_filters( 'lps_filter_use_custom_query_arguments', $qargs );
		if ( 'attachment' === $qargs['post_type'] ) {
			add_filter( 'posts_where', [ get_called_class(), 'attachment_custom_where' ], 50, 2 );
			add_filter( 'posts_join', [ get_called_class(), 'attachment_custom_join' ], 50, 2 );
		}

		$use_cache     = ( in_array( 'cache', $show_extra, true ) ) ? true : false;
		$in_the_editor = self::is_in_the_editor();
		if ( ! empty( $use_cache ) ) {
			// Maybe cache the results.
			$trans_id = 'lps-cache-' . ( $in_the_editor ? 'editor-' : '' ) . md5( self::get_assets_version() . wp_json_encode( $qargs ) . ( $args['lps_instance_id'] ?? '' ) );
			$lpstrans = get_transient( $trans_id );
			if ( false !== $lpstrans && ! empty( $lpstrans ) ) {
				return $lpstrans;
			}
		}

		$posts = get_posts( $qargs );

		// If the slider extension is enabled and the shortcode is configured to output the slider, let's do that and return.
		if ( ! empty( $posts ) && ! empty( $args['output'] ) && 'slider' === $args['output'] ) {
			ob_start();
			self::latest_selected_content_slider( $posts, $args, $use_cache );
			wp_reset_postdata();
			$result = ob_get_clean();
			if ( ! empty( $use_cache ) && empty( $in_the_editor ) ) {
				set_transient( $trans_id, $result, 30 * DAY_IN_SECONDS );
			}

			if ( $site_switched ) {
				restore_current_blog();
			}

			return $result;
		}

		$is_lps_ajax = (int) filter_input( INPUT_POST, 'lps_ajax', FILTER_DEFAULT );
		if ( empty( $is_lps_ajax ) ) {
			$is_lps_ajax = (int) filter_input( INPUT_GET, 'lps_ajax', FILTER_DEFAULT );
		}

		$shortcode_id = ! empty( $is_ajax_shortcode_id ) ? $is_ajax_shortcode_id : 'lps-' . md5( wp_json_encode( $args ) . microtime() );

		ob_start();
		$forced_end  = '';
		$closing_tag = '';
		if ( ! empty( $qargs['posts_per_page'] ) && ! empty( $args['showpages'] ) ) {
			$pagination_class  = in_array( 'pagination_all', $show_extra, true ) ? 'all-elements' : '';
			$pagination_class .= ( 'more' === $args['showpages'] ) ? ' lps-load-more' : '';
			$pagination_class .= ( 'scroll' === $args['showpages'] ) ? ' lps-load-more-scroll' : '';
			if ( ! empty( $args['css'] ) ) {
				if ( substr_count( $args['css'], 'pagination-center' ) ) {
					$pagination_class .= ' pagination-center';
				} elseif ( substr_count( $args['css'], 'pagination-right' ) ) {
					$pagination_class .= ' pagination-right';
				} elseif ( substr_count( $args['css'], 'pagination-space-between' ) ) {
					$pagination_class .= ' pagination-space-between';
				}
			}

			if ( ! empty( $lightbox_attr ) ) {
				$pagination_class .= ' lps-lightbox';
			}

			$counter     = new WP_Query( $qargs );
			$found_posts = ( ! empty( $counter->found_posts ) ) ? (int) $counter->found_posts : 0;
			if ( ! empty( $args['limit'] ) && $found_posts > $args['limit'] ) {
				$found_posts = (int) $args['limit'];
			}

			$pagination_html = self::lps_pagination(
				$found_posts,
				! empty( $qargs['posts_per_page'] ) ? $qargs['posts_per_page'] : 1,
				intval( $args['showpages'] ),
				$shortcode_id,
				$pagination_class,
				[
					'loadtext'   => ! empty( $args['loadtext'] ) ? $args['loadtext'] : '',
					'total_text' => ! empty( $args['total_text'] ) ? $args['total_text'] : '',
					'show_total' => in_array( 'show_total', $show_extra, true ),
				],
				$maxpg,
				$site_initial,
				$site_expected
			);

			if ( ! empty( $is_lps_ajax ) ) { // phpcs:ignore
				// No need to put again the top level.
			} else {
				$use_data_args = false;
				$closing_tag   = '</div><!-- lps/end -->';
				if ( ! empty( $args['showpages'] )
					&& ( 'more' === $args['showpages'] || 'scroll' === $args['showpages'] ) ) {
					$use_data_args = true;
				}
				if ( in_array( 'ajax_pagination', $show_extra, true ) && ! empty( $args ) && is_array( $args ) ) {
					$use_data_args = true;
				}

				if ( true === $use_data_args ) {
					$maybe_spinner = '';
					if ( in_array( 'light_spinner', $show_extra, true ) ) {
						$maybe_spinner = ' light_spinner';
					} elseif ( in_array( 'dark_spinner', $show_extra, true ) ) {
						$maybe_spinner = ' dark_spinner';
					}

					$min_args         = array_filter( $args );
					$data_args_string = wp_json_encode( $min_args, JSON_UNESCAPED_UNICODE );
					if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
						$data_args_string = wp_json_encode( $min_args );
					}

					if ( is_multisite() && $site_initial !== $site_expected ) {
						switch_to_blog( $site_initial );
					}

					echo '<!-- lps/start --><div id="' . esc_attr( $shortcode_id ) . '-wrap" data-args="' . esc_js( $data_args_string ) . '" data-current="' . get_the_ID() . '" class="lps-top-section-wrap' . $maybe_spinner . '" data-url="' . esc_url( \get_pagenum_link( 1 ) ) . '">'; // phpcs:ignore

					if ( is_multisite() && $site_initial !== $site_expected ) {
						switch_to_blog( $site_expected );
					}
				} else {
					echo '<!-- lps/start --><div id="' . esc_attr( $shortcode_id ) . '-wrap" class="lps-top-section-wrap">';
				}
			}

			if ( empty( $args['pagespos'] ) || ( ! empty( $args['pagespos'] ) && 2 === (int) $args['pagespos'] ) ) {
				echo str_replace( 'lps-pagination-wrap', 'before lps-pagination-wrap', $pagination_html ); // phpcs:ignore
			}
		}
		if ( ! empty( $posts ) ) {
			if ( in_array( 'date', $extra_display, true ) ) {
				$date_format = get_option( 'date_format' ) . ' \<\i\>' . get_option( 'time_format' ) . '\<\/\i\>';
			}

			$class  = ( ! empty( $args['css'] ) ) ? ' ' . $args['css'] : '';
			$class .= ( ! empty( $args['ver'] ) && 2 === $args['ver'] ) ? ' ver2' : '';
			if ( in_array( 'ajax_pagination', $show_extra, true ) ) {
				$class .= ' ajax_pagination';
			}

			$use_custom_markup   = false;
			$filter_element_type = 'elements-' . (int) $args['elements'];

			$maybe_custom = trim( str_replace( '[', '', str_replace( ']', '', str_replace( '][', '_', $args['display'] ) ) ) );
			if ( '_custom_' === substr( $maybe_custom, 0, 8 ) ) {
				$use_custom_markup   = true;
				$filter_element_type = $maybe_custom;
			}

			// Section start.
			$section_start = apply_filters(
				'lps/override_section_start',
				'<section class="latest-post-selection' . esc_attr( $class ) . '" id="' . esc_attr( $shortcode_id ) . '" style="' . $tiles_custom_style_vars . '">',
				$shortcode_id,
				$class,
				$filter_element_type,
				$is_lps_ajax_call,
				$args
			);

			if ( $use_custom_markup && $args['ver'] < 2 ) {
				// Legacy markup.
				$start = apply_filters( 'lps_filter_use_custom_section_markup_start', $tile_pattern, $shortcode_id, $class, $args );
				if ( ! substr_count( $start, esc_attr( $shortcode_id ) ) ) {
					$start       = '<div id="' . esc_attr( $shortcode_id ) . '" class="' . trim( esc_attr( $class ) ) . '">' . $start;
					$forced_end .= '</div>';
				}
				echo $start; // phpcs:ignore
			}

			if ( $is_lps_ajax_call ) {
				// Nothing to output for the section start.
				$section_start = '';
			}
			echo $section_start; // phpcs:ignore

			$tile_pattern      = self::positions_from_extra( $show_extra, $tile_pattern, $args, $extra_display );
			$tile_elements     = (int) $args['elements'];
			$markup_info_start = '#1$*#';
			$markup_info_end   = '#3$*#';
			if ( $args['ver'] >= 2 ) {

				// Version >= 2 markup.
				if ( substr_count( $tile_pattern, '[image][' ) ) {
					// Image first, info second.
					$tile_pattern = str_replace( '[image][', '[image]' . $markup_info_start . '[', $tile_pattern ) . $markup_info_end;
				} elseif ( substr_count( $tile_pattern, '][image]' ) ) {
					// Info first, image second.
					$tile_pattern = $markup_info_start . str_replace( '][image]', ']' . $markup_info_end . '[image]', $tile_pattern );
				}
			}

			$markup_sep     = '#7$*#';
			$tile_keep_tags = [];
			foreach ( self::$title_tags as $k ) {
				$tile_keep_tags[ $k ] = [
					'class' => 1,
					'id'    => 1,
				];
			}
			$tile_keep_tags[ $titletag ] = [
				'class' => 1,
				'id'    => 1,
			];

			$tile_keep_tags['br'] = [];

			$card_output_type_from_args = self::get_card_output_type_from_args( $args );

			global $last_tiles_img;
			foreach ( $posts as $postobj ) {
				$post = $postobj; // phpcs:ignore
				// Collect the IDs for the current page from the shortcode results.
				array_push( $lps_current_post_embedded_item_ids, $postobj->ID );
				$tile = $tile_pattern;

				if ( $use_custom_markup ) {
					if ( 1 === $args['ver'] ) {
						// Legacy markup.
						echo apply_filters( 'lps_filter_use_custom_tile_markup', $tile_pattern, $postobj, $args ); // phpcs:ignore
					} else {
						// Card markup.
						$card_markup = apply_filters(
							'lps/override_card',
							'',
							$filter_element_type,
							$postobj,
							$args,
							$card_output_type_from_args
						);

						echo $card_markup; // phpcs:ignore
					}
				} else {
					$a_start   = '';
					$ar_start  = '';
					$a_end     = '';
					$title_str = self::cleanup_title( $postobj->post_title );

					if ( $linkurl || $linkmedia || substr_count( $class, 'as-overlay' ) ) {
						$link_target = ( ! empty( $linkblank ) ) ? ' target="_blank"' : '';
						if ( $linkmedia ) {
							$mediaurl = wp_get_attachment_image_src( $postobj->ID, $lightbox_size );
							$mediaurl = ( ! empty( $mediaurl[0] ) ) ? $mediaurl[0] : '';
							$hr       = ( ! empty( $mediaurl ) ) ? ' href="' . esc_url( $mediaurl ) . '"' : '';
						} else {
							$hr = ( ! empty( $linkurl ) ) ? ' href="' . esc_url( get_permalink( $postobj->ID ) ) . '"' : '';
						}

						if ( ! empty( $lightbox_attr ) ) {
							$hr .= ' rel="' . $shortcode_id . '"';
						}

						$a_start  = '<a' . $hr . $link_class . $lightbox_extra . $link_target . ' title="' . esc_attr( $title_str ) . '">';
						$ar_start = '<a' . $hr . $read_more_class . $lightbox_extra . $link_target . ' title="' . esc_attr( $title_str ) . '">';
						$a_end    = '</a>';
					}

					if ( $args['ver'] < 2 ) {
						// Legacy markup.
						$tile = str_replace( '[a]', $a_start, $tile );
						$tile = str_replace( '[a-r]', $ar_start, $tile );
						$tile = str_replace( '[/a]', $a_end, $tile );
					}

					// Tile replace image markup.
					$tile = self::set_tile_image( $postobj, $args, $tile );

					// Tile date markup.
					if ( in_array( 'date', $extra_display, true ) ) {
						if ( in_array( 'date_diff', $show_extra, true ) ) {
							$date_value = self::relative_time( $postobj->ID );
						} else {
							$date_value = date_i18n( $date_format, strtotime( $postobj->post_date ), true );
						}
						$tile = str_replace( '[date]', $markup_sep . '<em class="item-date">' . $date_value . '</em>', $tile );
					}
					$tile = str_replace( '[date]', '', $tile );

					// Tile tags markup.
					if ( in_array( 'tags', $show_extra, true ) ) {
						$one_term = in_array( 'oneterm_tags', $show_extra, true );
						$no_label = in_array( 'nolabel_tags', $show_extra, true );
						$tags     = self::get_post_visible_term( (int) $postobj->ID, 'post_tag', $one_term, false, $no_label, $class );

						if ( ! empty( $tags ) ) {
							$tags = str_replace( 'post_tag', 'post_tag tags', $tags );
							$tags = $markup_sep . '<span class="lps-tags-wrap">' . $tags . '</span>';
							$tags = apply_filters( 'lps/override_card_terms', $tags, (int) $postobj->ID, 'post_tag', $shortcode_id );
							$tile = str_replace( '[tags]', $tags, $tile );
						}
					}
					$tile = str_replace( '[tags]', '', $tile );

					// Tile author markup.
					if ( in_array( 'author', $show_extra, true ) ) {
						$author = $markup_sep . '<div class="lps-author-wrap"><span class="lps-author">' . esc_html__( 'By', 'lps' ) . '</span> <a href="' . esc_url( get_author_posts_url( $postobj->post_author ) ) . '" class="lps-author-link">' . esc_html( get_the_author_meta( 'display_name', $postobj->post_author ) ) . '</a></div>';
						$tile   = str_replace( '[author]', $author, $tile );
					}
					$tile = str_replace( '[author]', '', $tile );

					if ( ( 'product' === $post->post_type || 'product_variation' === $post->post_type ) && function_exists( '\wc_get_product' ) ) {
						// Tile price markup.
						if ( in_array( 'price', $show_extra, true ) ) {
							$prod = \wc_get_product( (int) $post->ID );
							$info = $markup_sep . '<div class="lps-price-wrap">' . $prod->get_price_html() . '</div>';
							$tile = str_replace( '[price]', $info, $tile );
						}

						// Tile add to cart markup.
						if ( in_array( 'add_to_cart', $show_extra, true ) ) {
							$info = $markup_sep . '<div class="lps-add_to_cart-wrap">' . \do_shortcode( '[add_to_cart id="' . (int) $post->ID . '" style="" show_price="false"]' ) . '</div>';
							$tile = str_replace( '[add_to_cart]', $info, $tile );
						}

						// Tile price + add to cart markup.
						if ( in_array( 'price_add_to_cart', $show_extra, true ) ) {
							$info = $markup_sep . '<div class="lps-add_to_cart-wrap">' . \do_shortcode( '[add_to_cart id="' . (int) $post->ID . '" style=""]' ) . '</div>';
							$tile = str_replace( '[price_add_to_cart]', $info, $tile );
						}
					}
					$tile = str_replace( '[price]', '', $tile );
					$tile = str_replace( '[add_to_cart]', '', $tile );
					$tile = str_replace( '[price_add_to_cart]', '', $tile );

					$mime_css = '';
					if ( 'attachment' === $postobj->post_type ) {
						// Attachment tile mime type markup.
						if ( in_array( 'show_mime', $show_extra, true ) ) {
							$mime     = trim( strstr( $postobj->post_mime_type, '/' ), '/' );
							$mime_css = 'item-mime-type mime-' . esc_attr( $mime ) . ' mime-' . str_replace( '/', '-', esc_attr( $postobj->post_mime_type ) );

							$tile = str_replace( '[show_mime]', '<span class="' . $mime_css . '"><span>' . esc_html__( 'Mime Type', 'lps' ) . ':</span> ' . $mime . '</span>', $tile );
						}

						// Maybe prepare the mime type class.
						if ( in_array( 'show_mime_class', $show_extra, true ) ) {
							if ( empty( $mime_css ) ) {
								$mime     = trim( strstr( $postobj->post_mime_type, '/' ), '/' );
								$mime_css = 'item-mime-type mime-' . esc_attr( $mime ) . ' mime-' . str_replace( '/', '-', esc_attr( $postobj->post_mime_type ) );
							}
						} else {
							$mime_css = '';
						}

						// Attachment tile caption type markup.
						if ( in_array( 'caption', $show_extra, true ) ) {
							$caption = wp_get_attachment_caption( $postobj->ID );
							if ( ! empty( $caption ) ) {
								$caption = $markup_sep . '<div class="lps-caption-wrap"><span>' . esc_html__( 'Caption', 'lps' ) . ':</span> ' . esc_html( $caption ) . '</div>';
							}
							$tile = str_replace( '[caption]', $caption, $tile );
						}
					}
					$tile = str_replace( '[show_mime]', '', $tile );
					$tile = str_replace( '[caption]', '', $tile );

					// Tile taxonomies markup.
					$taxonomies = array_diff( $show_extra, [ 'tags', 'author', 'show_mime', 'caption', 'ajax_pagination', 'hide_uncategorized_category', 'show_total' ] );
					if ( ! empty( $taxonomies ) ) {
						foreach ( $taxonomies as $tax ) {
							$one_term = in_array( 'oneterm_' . $tax, $show_extra, true );
							$no_label = in_array( 'nolabel_' . $tax, $show_extra, true );
							$no_uncat = 'category' === $tax && in_array( 'hide_uncategorized_category', $show_extra, true );

							$terms = self::get_post_visible_term( (int) $postobj->ID, $tax, $one_term, $no_uncat, $no_label, $class );
							$terms = apply_filters( 'lps/override_card_terms', $terms, (int) $postobj->ID, $tax, $shortcode_id );
							$tile  = str_replace( '[' . $tax . ']', $markup_sep . $terms, $tile );
						}
					}

					// Tile title markup.
					if ( in_array( 'title', $extra_display, true ) ) {
						if ( $args['ver'] >= 2 ) {
							// Version >= 2 markup.

							$visible_title_str = $title_str;
							if ( $trim_text ) {
								$visible_title_str = self::get_short_text( $visible_title_str, $chrlimit, false, $trimmore );
							}

							$tile = empty( $args['url'] ) ? str_replace( '[a][title][/a]', '[title]', $tile ) : $tile;
							if ( ! empty( $args['url'] ) ) {
								if ( 5 !== (int) $tile_elements && 22 !== (int) $tile_elements ) {
									$tile = str_replace( '[title]', '<' . $titletag . ' class="item-title-tag">' . $a_start . $visible_title_str . $a_end . '</' . $titletag . '>', $tile );
								}
							}
							// Fallback to no link on title.
							$tile = str_replace( '[title]', '<' . $titletag . ' class="item-title-tag">' . $visible_title_str . '</' . $titletag . '>', $tile );
						} else {
							// Legacy markup.
							$tile = str_replace( '[title]', '<' . $titletag . ' class="item-title-tag">' . $title_str . '</' . $titletag . '>', $tile );
						}
					}
					$tile = str_replace( '[title]', '', $tile );

					// Tile text markup.
					$text = '';
					if ( ! empty( $args['display'] )
						&& ( substr_count( $args['display'], 'content' ) || substr_count( $args['display'], 'excerpt' ) ) ) {
						$lim = $chrlimit;
						if ( $trim_text ) {
							$lim -= mb_strlen( $title_str );
							if ( $lim < 0 ) {
								$lim = 0;
							}
						}
						$text = self::compute_tile_text( $postobj, $extra_display, $lim, $raw_content, $trimmore );
					}
					$tile = str_replace( '[text]', $markup_sep . $text, $tile );

					if ( ! empty( $linktext ) ) {
						if ( $args['ver'] >= 2 ) {
							// Version >= 2 markup.
							if ( ! empty( $args['url'] ) ) {
								if ( 5 === (int) $tile_elements
									|| 22 === (int) $tile_elements
									|| 26 === (int) $tile_elements ) {
									$tile = str_replace( '[read_more_text]', $markup_sep . '<span class="read-more">' . $a_start . $linktext . $a_end . '</span>', $tile );
								}
							}
							// Fallback to replacing just the string.
							$tile = str_replace( '[read_more_text]', $markup_sep . '<span class="read-more">' . $linktext . '</span>', $tile );
						} else {
							// Legacy markup.
							$tile = str_replace( '[read_more_text]', $markup_sep . '<span class="read-more">' . $linktext . '</span>', $tile );
						}
					} else {
						$tile = str_replace( '[read_more_text]', '', $tile );
					}

					// Cleanup the remanining tags.
					$maybe_tile    = str_replace( $markup_info_start, '<div class="article__info">', $tile );
					$maybe_tile    = str_replace( $markup_info_end, '</div>', $maybe_tile );
					$maybe_tile    = preg_replace( '/\[(.*)\]/', '', $maybe_tile );
					$card_markup   = '';
					$article_class = get_post_class( $mime_css, $postobj->ID );
					if ( substr_count( $maybe_tile, 'main-link' ) ) {
						$article_class[] = 'has-link';
					}
					$article_class = ( ! empty( $article_class ) ) ? ' class="' . implode( ' ', $article_class ) . '"' : '';
					if ( substr_count( $class, 'as-overlay' ) && $args['ver'] < 2 ) {
						// Legacy markup.
						if ( ! empty( $last_tiles_img ) ) {
							$last_tiles_img = esc_url( $last_tiles_img );
						}
						$maybe_tile = str_replace( $markup_sep, ' ', $maybe_tile );
						$maybe_tile = wp_kses( $maybe_tile, $tile_keep_tags );

						$card_markup = '<article' . $article_class . ' style="background-image:url(\'' . $last_tiles_img . '\')"><div class="lps-ontopof-overlay">' . $a_start . $maybe_tile . $a_end . '</div></article>';
					} else {
						$maybe_tile  = str_replace( $markup_sep, '', $maybe_tile );
						$card_markup = '<article' . $article_class . '>' . $maybe_tile . '</article>';
					}

					if ( $args['ver'] >= 2 && $a_start && ! substr_count( $tile, $a_start ) ) {
						// Version >= 2 markup.
						$card_markup = str_replace( '<div class="article__info">', '<div class="article__info">' . str_replace( 'main-link', 'main-link hidden', $a_start ) . $a_end, $card_markup );
					}

					// Card markup.
					$card_markup = apply_filters(
						'lps/override_card',
						$card_markup,
						$tile_pattern,
						$postobj,
						$args,
						$card_output_type_from_args
					);

					if ( substr_count( $class, 'content-first-top' ) ) {
						$card_markup = self::maybe_info_row_template( $card_markup, 'first' );
					} elseif ( substr_count( $class, 'content-last-bottom' ) ) {
						$card_markup = self::maybe_info_row_template( $card_markup, 'last' );
					}

					echo $card_markup; // phpcs:ignore
				}
			}

			// Section end.
			$section_end = apply_filters(
				'lps/override_section_end',
				'</section>',
				$shortcode_id,
				$class,
				$filter_element_type,
				$is_lps_ajax_call,
				$args
			);

			if ( $use_custom_markup && 1 === $args['ver'] ) {
				// Legacy markup.
				echo apply_filters( 'lps_filter_use_custom_section_markup_end', $tile_pattern, $shortcode_id, $class, $args ); // phpcs:ignore
				if ( ! empty( $forced_end ) ) {
					echo $forced_end; // phpcs:ignore
				}
			}

			if ( $is_lps_ajax_call ) {
				// Nothing to output for the section end.
				$section_end = '';
			}
			echo $section_end; // phpcs:ignore
		} elseif ( ! empty( $args['fallback'] ) ) {
				echo '<div class="lps-placeholder">' . wp_kses_post( $args['fallback'] ) . '</div>';
		}
		if ( ! empty( $qargs['posts_per_page'] ) && ! empty( $args['showpages'] ) ) {
			if ( ! empty( $args['pagespos'] ) && ( 1 === (int) $args['pagespos'] || 2 === (int) $args['pagespos'] ) ) {
				echo str_replace( 'lps-pagination-wrap', 'after lps-pagination-wrap', $pagination_html ); // phpcs:ignore
			}
		}

		echo $closing_tag; // phpcs:ignore

		$result = ob_get_clean();
		wp_reset_postdata(); // Previously wp_reset_query(), but new CS now.

		if ( ! empty( $use_cache ) && ! empty( $trans_id ) ) {
			if ( $in_the_editor ) {
				$result = str_replace( 'lps-top-section-wrap', 'lps-top-section-wrap lps-cached', $result );
				$result = str_replace( 'latest-post-selection-slider', 'latest-post-selection-slider lps-cached', $result );
			} else {
				set_transient( $trans_id, $result, 30 * DAY_IN_SECONDS );
			}
		}

		if ( $site_switched ) {
			restore_current_blog();
		}
		return $result;
	}

	/**
	 * Maybe append info row template.
	 *
	 * @param  string $html Initial card markup.
	 * @param  string $type Alignment type.
	 * @return string
	 */
	public static function maybe_info_row_template( $html, $type = 'first' ) {
		if ( empty( $html ) ) {
			// Fail-fast.
			return '';
		}

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_use_internal_errors( false );

		$changed  = false;
		$xpath    = new DOMXPath( $dom );
		$elements = $xpath->query( '//div[contains(@class, "article__info")]' );
		foreach ( $elements as $el ) {
			// Note: $el->childNodes->length does not work properly with … .
			$count = 0;
			if ( $el->childNodes ) { // phpcs:ignore
				foreach ( $el->childNodes as $child ) { // phpcs:ignore
					if ( ! empty( $child->tagName ) ) { // phpcs:ignore
						++$count;
					}
				}
			}

			if ( $count > 1 ) {
				if ( 'first' === $type ) {
					$template = '--info-rows-template: 1fr' . str_repeat( ' auto', $count - 1 );
				} else {
					$repeat    = 2 === $count ? 2 : $count - 2;
					$template  = '--info-rows-align: space-between;';
					$template .= '--info-rows-template: ' . str_repeat( 'auto ', $repeat ) . '1fr';
				}

				$changed = true;
				$el->setAttribute( 'style', $template );
			}
		}

		if ( $changed ) {
			// Get the modified HTML.
			$html = $dom->saveHTML();
		}

		return $html;
	}

	/**
	 * Alter the query where for attachment use.
	 *
	 * @param  string $where The where statement.
	 * @param  object $obj   The query object.
	 * @return string
	 */
	public static function attachment_custom_where( $where, $obj ) { // phpcs:ignore
		global $wpdb;
		if ( is_scalar( self::$current_query_statuses_list ) ) {
			$list = explode( ',', self::$current_query_statuses_list );
		} else {
			$list = self::$current_query_statuses_list;
		}
		$que = '';
		foreach ( $list as $k => $value ) {
			$que  .= ( ! empty( $que ) ) ? ' OR ' : '';
			$que  .= 'p2.post_status = \'' . $value . '\'';
			$where = str_replace( 'AND (p2.post_status = \'' . $value . '\')', '', $where );
			$where = str_replace( ' OR p2.post_status = \'' . $value . '\'', '', $where );
			$where = str_replace( 'p2.post_status = \'' . $value . '\'', '', $where );
		}
		$where = str_replace( ' AND ()', '', $where );
		$where = str_replace( 'AND (' . $que . ')', '', $where );

		return $where;
	}

	/**
	 * Alter the query join for attachment use.
	 *
	 * @param  string $join The join statement.
	 * @param  object $obj  The query object.
	 * @return string
	 */
	public static function attachment_custom_join( $join, $obj ) { // phpcs:ignore
		global $wpdb;
		$join = str_replace( 'LEFT JOIN ' . $wpdb->posts . ' AS p2 ON (' . $wpdb->posts . '.post_parent = p2.ID) ', '', $join );
		return $join;
	}

	/**
	 * Return empty for the attachment paragraph that embeds the image in the content.
	 *
	 * @param  string $p The paragraph.
	 * @return string
	 */
	public static function remove_attachment_content_p( $p ) { // phpcs:ignore
		return '';
	}

	/**
	 * Compute a post usable excerpt.
	 *
	 * @param  object  $post The post object.
	 * @param  boolean $raw  Use or not raw content.
	 * @return string
	 */
	public static function maybe_post_excerpt( $post, $raw = false ) { // phpcs:ignore
		if ( $raw ) {
			$excerpt = wp_kses_post( strip_shortcodes( $post->post_excerpt ) );
		} else {
			$excerpt = apply_filters( 'the_excerpt', strip_shortcodes( $post->post_excerpt ) );
		}
		return $excerpt;
	}

	/**
	 * Compute a post usable content.
	 *
	 * @param  object  $post The post object.
	 * @param  boolean $raw  Use or not raw content.
	 * @return string
	 */
	public static function maybe_post_content( $post, $raw = false ) { // phpcs:ignore
		if ( $raw ) {
			$content = wp_kses_post( $post->post_content );
		} else {
			$content = apply_filters( 'the_content', $post->post_content );
		}

		return $content;
	}

	/**
	 * Compute a item text.
	 *
	 * @param  object  $post     The post object.
	 * @param  array   $extra    The elements display list.
	 * @param  integer $limit    Chars limit.
	 * @param  boolean $raw      Use or not raw content.
	 * @param  string  $trimmore Maybe some trailing extra chars for truncated string.
	 * @return string
	 */
	public static function compute_tile_text( $post, $extra = [], $limit = 120, $raw = false, $trimmore = '' ) { // phpcs:ignore
		if ( 'attachment' === $post->post_type ) {
			add_filter( 'prepend_attachment', [ get_called_class(), 'remove_attachment_content_p' ] );
		}

		if ( in_array( 'excerpt-small', $extra, true ) ) {
			return self::get_short_text( $post->post_excerpt, $limit, true, $trimmore );
		} elseif ( in_array( 'excerpt', $extra, true ) ) {
			return self::maybe_post_excerpt( $post );
		} elseif ( in_array( 'content', $extra, true ) ) {
			return self::maybe_post_content( $post, $raw );
		} elseif ( in_array( 'content-small', $extra, true ) ) {
			if ( $raw ) {
				return wp_kses_post( self::trim_html_to_length( $post->post_content, $limit ) );
			} else {
				return self::get_short_text( $post->post_content, $limit, false, $trimmore );
			}
		} elseif ( in_array( 'excerptcontent', $extra, true ) ) {
			return '<div class="lps-excerpt">' . self::maybe_post_excerpt( $post, $raw ) . '</div><div class="lps-content">' . self::maybe_post_content( $post, $raw ) . '</div>';
		} elseif ( in_array( 'contentexcerpt', $extra, true ) ) {
			return '<div class="lps-content">' . self::maybe_post_content( $post, $raw ) . '</div><div class="lps-excerpt">' . self::maybe_post_excerpt( $post, $raw ) . '</div>';
		}
		return '';
	}

	/**
	 * Trim a HTML string to length, keeping the tags.
	 *
	 * @param  string  $title   String to be trimmed.
	 * @param  integer $max_len Max chars.
	 * @param  string  $end     The ending string.
	 * @return string
	 */
	public static function trim_html_to_length( $title, $max_len = 30, $end = '...' ) { // phpcs:ignore
		$current_len = 0;

		$title = strip_shortcodes( $title );
		$title = str_replace( '&nbsp;', ' ', $title );
		$title = preg_replace( '/\s\s+/', ' ', trim( $title ) );
		$title = preg_replace( '/<!--(.*?)-->/', '', $title );
		$title = html_entity_decode( $title );
		if ( strlen( $title ) <= $max_len ) {
			return $title;
		}

		$words_tags = preg_split( '/(<[^>]*[^\/]>)|(<[^>]*\/>)/i', $title, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		$new_title  = '';
		$text       = '';
		$starts     = [];
		$ends       = [];
		if ( ! empty( $words_tags ) ) {
			foreach ( $words_tags as $elem ) {
				$current_len = strlen( $new_title );
				$remaining   = $max_len - $current_len;
				if ( $remaining <= 0 ) {
					// Stop here, do not iterate further.
					break;
				}

				if ( '</' === substr( $elem, 0, 2 ) ) {
					// Tag ending.
					array_unshift( $ends, $elem );
					$text  .= $elem;
					$maybes = rtrim( ltrim( $elem, '</' ), '>' );
					if ( ! empty( $starts ) ) {
						if ( substr( reset( $starts ), 0, strlen( $maybes ) + 1 ) === '<' . $maybes ) {
							// This is closed now, remove the most recent starting match.
							array_shift( $starts );
						}
					}
				} elseif ( '<' === substr( $elem, 0, 1 ) ) {
					// Tag start, append it at the beginning of stack.
					array_unshift( $starts, $elem );
					$text .= $elem;
				} else {
					// Text.
					if ( strlen( $elem ) > $remaining ) {
						$pos = stripos( $elem, ' ', $remaining );
						if ( ! empty( $pos ) ) {
							$elem = trim( substr( $elem, 0, $pos ) );
						} else {
							$w  = explode( ' ', $elem );
							$el = '';
							foreach ( $w as $wk ) {
								if ( strlen( $el ) >= $remaining ) {
									break;
								}
								$el .= ' ' . $wk;
							}
							$elem = trim( $el );
						}
					}
					$new_title .= $elem . ' ';
					$text      .= $elem . ' ';
				}
			}
		}

		// Remove the trailing punctuation if possible.
		$text = trim( $text );
		$text = preg_replace( '/\PL\z/', '', $text );
		$text = trim( $text );

		if ( ! empty( $end ) ) {
			$text .= $end;
		}
		if ( ! empty( $starts ) ) {
			$starts = implode( '', $starts );
			$text  .= str_replace( '<', '</', $starts );
		}

		return $text;
	}

	/**
	 * Get the post terms list.
	 *
	 * @param  integer $post_id The post ID.
	 * @param  string  $tax     Taxonomy slug.
	 * @param  boolean $one     Get only one term.
	 * @param  boolean $uncat   Exclude the uncategorizes term.
	 * @param  boolean $label   Use the taxonomy name in front of the list.
	 * @param  string  $css     Styles.
	 * @return string
	 */
	public static function get_post_visible_term( int $post_id = 0, string $tax = '', bool $one = false, bool $uncat = false, bool $label = true, string $css = '' ): string {
		if ( empty( $post_id ) || empty( $tax ) ) {
			return '';
		}

		$count = 1;
		if ( ! empty( $css ) ) {
			switch ( $tax ) {
				case 'post_tag':
					if ( substr_count( $css, 'two-tags' ) ) {
						$count = 2;
					} elseif ( substr_count( $css, 'three-tags' ) ) {
						$count = 3;
					}
					break;

				case 'category':
					if ( substr_count( $css, 'two-categories' ) ) {
						$count = 2;
					} elseif ( substr_count( $css, 'three-categories' ) ) {
						$count = 3;
					}
					break;

				default:
					break;
			}
		}

		$tax_obj = get_taxonomy( $tax );
		if ( ! empty( $tax_obj ) && ! is_wp_error( $tax_obj ) ) {
			$terms_list = get_the_term_list( $post_id, $tax, '<span class="lps-terms ' . esc_attr( $tax ) . '">', ', ', '</span>' );

			$maybe_one = false;
			if ( ! empty( $uncat ) ) {
				$list = explode( ', ', $terms_list );
				foreach ( $list as $k => $term ) {
					if ( substr_count( $term, 'uncategorized' ) ) {
						unset( $list[ $k ] );
					}
				}
				if ( ! empty( $one ) ) {
					if ( ! empty( $list ) ) {
						$terms_list = implode( ', ', array_slice( $list, 0, $count ) );
					}
					$maybe_one = true;
				} else {
					$terms_list = implode( ', ', $list );
				}
			}

			if ( $one && ! $maybe_one && ! empty( $terms_list ) ) {
				$list       = explode( ', ', $terms_list );
				$terms_list = implode( ', ', array_slice( $list, 0, $count ) );
			}

			if ( ! empty( $terms_list ) ) {
				$before = empty( $label ) ? '<span class="lps-taxonomy ' . esc_attr( $tax ) . '">' . esc_html( $tax_obj->label ) . ':</span> ' : '';

				return '<div class="lps-taxonomy-wrap ' . esc_attr( $tax ) . ( $one ? ' one-term' : '' ) . ( ! $before ? ' no-label' : '' ) . '">' . $before . $terms_list . '</div>';
			}
		}

		return '';
	}

	/**
	 * Compute the position for the extra elements.
	 *
	 * @param string $show_extra    The extra element list.
	 * @param string $tile_pattern  The tile pattern.
	 * @param array  $args          The shortcode arguments.
	 * @param array  $extra_display The extra elements to be shown.
	 * @return string
	 */
	public static function positions_from_extra( $show_extra = '', $tile_pattern = '', $args = [], $extra_display = [] ) { // phpcs:ignore
		if ( in_array( 'date', $extra_display, true ) ) {
			if ( in_array( 'title', $extra_display, true ) ) {
				if ( ! empty( $args['display'] ) && substr_count( $args['display'], 'date,title' ) ) {
					$tile_pattern = str_replace( '[title]', '[date][title]', $tile_pattern );
				} else {
					$tile_pattern = str_replace( '[title]', '[title][date]', $tile_pattern );
				}
			} else {
				$tile_pattern = str_replace( '[title]', '[date]', $tile_pattern );
			}
		}

		if ( ! is_array( $show_extra ) ) {
			$show_extra = explode( ',', $show_extra );
		}
		if ( ! empty( $show_extra ) ) {
			foreach ( $show_extra as $extra_tag ) {
				if ( substr_count( $extra_tag, 'taxpos_' ) ) {
					preg_match_all( '/taxpos\_(.*)\_(before|after)\-(.*)/', $extra_tag, $matches );
					if ( ! empty( $matches[1][0] ) && in_array( $matches[1][0], $show_extra, true )
						&& ! empty( $matches[2][0] ) ) {
						if ( 'before' === $matches[2][0] ) {
							$tile_pattern = str_replace( '[' . $matches[3][0] . ']', '[' . $matches[1][0] . '][' . $matches[3][0] . ']', $tile_pattern );
						} else {
							$tile_pattern = str_replace( '[' . $matches[3][0] . ']', '[' . $matches[3][0] . '][' . $matches[1][0] . ']', $tile_pattern );
						}
					}
				}
			}
		}

		// Set the default positions for.
		foreach ( self::$replaceable_tags as $tag ) {
			if ( ! substr_count( $tile_pattern, '[' . $tag . ']' ) ) {
				if ( in_array( $tag, $show_extra, true ) ) {
					$tile_pattern = str_replace( '[text]', '[text][' . $tag . ']', $tile_pattern );
				}
			}
		}

		return $tile_pattern;
	}

	/**
	 * Clean the tile title.
	 *
	 * @param  string $str The string.
	 * @return string
	 */
	public static function cleanup_title( $str ) { // phpcs:ignore
		return ( ! empty( $str ) ) ? str_replace( ']', '', str_replace( '[', '', $str ) ) : '';
	}

	/**
	 * Select a random placeholder.
	 *
	 * @param  string $string The list of placeholders separated by comma.
	 * @return string
	 */
	public static function select_random_placeholder( $string = '' ) { // phpcs:ignore
		if ( empty( $string ) ) {
			return '';
		}
		global $select_random_placeholder;
		$list   = ( ! is_array( $string ) ) ? explode( ',', $string ) : $string;
		$usable = $list;
		if ( empty( $select_random_placeholder ) ) {
			$select_random_placeholder = [];
		} else {
			$diff = array_diff( $list, $select_random_placeholder );
			if ( ! empty( $diff ) ) {
				$list = array_values( $diff );
			} else {
				$list = $usable;

				$select_random_placeholder = [];
			}
		}
		$index = array_rand( $list, 1 );
		$item  = ( ! empty( $list[ $index ] ) ) ? $list[ $index ] : $usable[0];

		$select_random_placeholder[] = $item;
		return $item;
	}

	/**
	 * Compute the tile image for a post, based on the arguments.
	 *
	 * @param object $post The WP_Post object.
	 * @param array  $args The shortcode arguments.
	 * @param string $tile The tile pattern.
	 * @return string
	 */
	public static function set_tile_image( $post, $args, $tile ) { // phpcs:ignore
		global $last_tiles_img;
		if ( empty( $post ) ) {
			return;
		}
		$last_tiles_img = '';

		// Tile image markup.
		if ( ! empty( $args['image'] ) ) {
			$img_html = '';
			$attr     = [
				'class'   => 'lps-custom-' . $args['image'],
				'loading' => 'lazy',
			];

			if ( 'attachment' === $post->post_type ) {
				$th_id       = $post->ID;
				$attr['alt'] = get_post_meta( $th_id, '_wp_attachment_image_alt', true );
				if ( empty( $attr['alt'] ) ) {
					$attr['alt'] = self::cleanup_title( $post->post_title );
				}
			} else {
				$th_id       = get_post_thumbnail_id( (int) $post->ID );
				$attr['alt'] = self::cleanup_title( $post->post_title );
			}

			$image     = wp_get_attachment_image_src( $th_id, $args['image'] );
			$img_url   = '';
			$is_native = false;
			if ( ! empty( $image[0] ) ) {
				$img_url        = $image[0];
				$is_native      = true;
				$attr['width']  = $image[1];
				$attr['height'] = $image[2];
			} elseif ( ! empty( $args['image_placeholder'] ) ) {
				$img_url = self::select_random_placeholder( $args['image_placeholder'] );
			}

			if ( ! empty( $img_url ) ) {
				if ( true === $is_native ) {
					$srcset = wp_get_attachment_image_srcset( $th_id, $args['image'] );
					if ( ! empty( $srcset ) ) {
						$attr['srcset'] = $srcset;
					}
				}

				$attributes = '';
				foreach ( $attr as $k => $v ) {
					if ( 'class' === $k ) {
						$v = 'lps-tile-main-image ' . $v;
					}
					$attributes .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
				}

				if ( $args['ver'] < 2 ) {
					// Legacy markup.
					$img_html = '<img src="' . esc_url( $img_url ) . '"' . $attributes . '>';
				} else {
					// Ver >= 2 markup.
					$img_html = '<figure class="article__image"><img src="' . esc_url( $img_url ) . '"' . $attributes . '></figure>';
				}

				$last_tiles_img = $img_url;
			}
			$tile = str_replace( '[image]', $img_html, $tile );
		}
		$tile = str_replace( '[image]', '', $tile );
		return $tile;
	}

	/**
	 * Generate the slider output from the selected posts and shortcode settings.
	 *
	 * @param array   $posts     List of WP_Post objects.
	 * @param array   $args      Shortcode settings.
	 * @param boolean $use_cache Use cache for the slider shortcode.
	 * @return void
	 */
	public static function latest_selected_content_slider( $posts, $args, $use_cache = false ) { // phpcs:ignore
		if ( empty( $posts ) ) {
			return;
		}
		// Enqueue one time the slider assets.
		self::load_slider_assets();
		$shortcode_id = md5( wp_json_encode( $args ) . microtime() );
		$show_extra   = ( ! empty( $args['show_extra'] ) ) ? explode( ',', $args['show_extra'] ) : [];
		$use_trim     = ( in_array( 'trim', $show_extra, true ) ) ? true : false;

		$css     = ( ! empty( $args['css'] ) ) ? $args['css'] : '';
		$imgsize = ( empty( $args['image'] ) ) ? 'none' : $args['image'];
		$url     = ( ! empty( $args['url'] ) && substr_count( $args['url'], 'yes' ) ) ? 'true' : 'false';
		$height  = ( ! empty( $args['slidermaxheight'] ) ) ? (int) $args['slidermaxheight'] : 0;
		if ( empty( $height ) && 'none' === $imgsize ) {
			$height = 100;
		}
		$wrap     = ( ! empty( $args['sliderwrap'] ) && in_array( $args['sliderwrap'], self::$slider_wrap_tags, true ) ) ? $args['sliderwrap'] : 'div';
		$mode     = ( ! empty( $args['slidermode'] ) ) ? $args['slidermode'] : 'horizontal';
		$auto     = ( ! empty( $args['sliderauto'] ) ) ? 'true' : 'false';
		$speed    = ( ! empty( $args['sliderspeed'] ) ) ? (int) $args['sliderspeed'] : 1000;
		$ctrl     = ( ! empty( $args['slidercontrols'] ) ) ? 'true' : 'false';
		$slides   = ( ! empty( $args['slideslides'] ) ) ? (int) $args['slideslides'] : 1;
		$scroll   = ( ! empty( $args['slidescroll'] ) ) ? (int) $args['slidescroll'] : 1;
		$dots     = ( ! empty( $args['sliderdots'] ) ) ? 'true' : 'false';
		$inf      = ( ! empty( $args['sliderinfinite'] ) ) ? 'true' : 'false';
		$t_bp     = ( ! empty( $args['sliderbreakpoint_tablet'] ) ) ? (int) $args['sliderbreakpoint_tablet'] : 600;
		$t_slides = ( ! empty( $args['slideslides_tablet'] ) ) ? (int) $args['slideslides_tablet'] : 1;
		$t_scroll = ( ! empty( $args['slidescroll_tablet'] ) ) ? (int) $args['slidescroll_tablet'] : 1;
		$t_dots   = ( ! empty( $args['sliderdots_tablet'] ) ) ? 'true' : 'false';
		$t_inf    = ( ! empty( $args['sliderinfinite_tablet'] ) ) ? 'true' : 'false';
		$m_bp     = ( ! empty( $args['sliderbreakpoint_mobile'] ) ) ? (int) $args['sliderbreakpoint_mobile'] : 460;
		$m_slides = ( ! empty( $args['slideslides_mobile'] ) ) ? (int) $args['slideslides_mobile'] : 1;
		$m_scroll = ( ! empty( $args['slidescroll_mobile'] ) ) ? (int) $args['slidescroll_mobile'] : 1;
		$m_dots   = ( ! empty( $args['sliderdots_mobile'] ) ) ? 'true' : 'false';
		$m_inf    = ( ! empty( $args['sliderinfinite_mobile'] ) ) ? 'true' : 'false';
		$extra    = ( ! empty( $args['display'] ) ) ? explode( ',', $args['display'] ) : [ 'title' ];
		$chrlimit = ( ! empty( $args['chrlimit'] ) ) ? intval( $args['chrlimit'] ) : 120;
		$trimmore = ( ! empty( $args['more'] ) ) ? $args['more'] : '';
		$overlay  = ( ! empty( $args['slideoverlay'] ) && 'no' === $args['slideoverlay'] ) ? 'false' : 'true';
		$otype    = '';
		if ( 'true' === $overlay ) {
			$otype = ( ! empty( $args['slideoverlay'] ) ) ? $args['slideoverlay'] : 'all';
		}
		$gaps   = ( ! empty( $args['slidegap'] ) ) ? (int) $args['slidegap'] : 0;
		$center = ( ! empty( $args['centermode'] ) ) ? 'true' : 'false';
		$padd   = ( ! empty( $args['centerpadd'] ) ) ? (int) $args['centerpadd'] : 0;
		$resp   = ( ! empty( $args['slidersponsive'] ) && 'yes' === $args['slidersponsive'] ) ? 'true' : 'false';
		$respto = ( ! empty( $args['respondto'] ) && in_array( $args['respondto'], [ 'window', 'slider' ], true ) ) ? $args['respondto'] : 'min';

		$is_block_rendering = ( defined( 'REST_REQUEST' ) && REST_REQUEST );

		$max_height = ( ! empty( $height ) ) ? $height . 'px' : 'unset';
		ob_start();
		?>

		<?php if ( ! $is_block_rendering ) : ?>
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> {
				display: none;
			}
		<?php else : ?>
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> {
				display: block;
				max-height: <?php echo esc_attr( $max_height ); ?>;
				overflow: hidden;
			}
		<?php endif; ?>
		<?php if ( ! empty( $height ) ) : ?>
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> > div,
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .img-wrap,
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-slide {
				max-height: <?php echo esc_attr( $max_height ); ?>;
				overflow: hidden;
			}
		<?php endif; ?>
		<?php if ( ! empty( $gaps ) ) : ?>
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-slide {
				margin: 0 <?php echo (int) $gaps; ?>px;
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-list {
				margin: 0 -<?php echo (int) $gaps; ?>px;
			}
		<?php endif; ?>
		<?php if ( 'true' === $center ) : ?>
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-slide {
				margin: 0px;
				padding: <?php echo (int) $padd; ?>px;
				position: relative;
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-slide .overlay {
				max-width: calc(100% - <?php echo 2 * (int) $padd; ?>px);
				margin-left: 0px;
				bottom: <?php echo (int) $padd; ?>px;
				display: none;
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-center .overlay {
				max-width: calc(100%);
				margin-left: -<?php echo (int) $padd; ?>px;
				bottom: <?php echo (int) $padd; ?>px;
				display: block;
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-center .img-wrap {
				min-width: calc(100% + <?php echo 2 * (int) $padd; ?>px);
				max-height: auto;
				height: auto;
				margin-left: -<?php echo (int) $padd; ?>px;
				margin-top: -<?php echo (int) $padd; ?>px;
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-prev {
				left: <?php echo 2 * (int) $padd; ?>px;
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-next {
				right: <?php echo 2 * (int) $padd; ?>px;
			}
		<?php endif; ?>
		<?php if ( 'true' === $dots ) : ?>
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .slick-dots {
				bottom: -30px;
			}
		<?php endif; ?>

		<?php
		$in_the_editor = self::is_in_the_editor();
		if ( $in_the_editor ) {
			?>
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> {
				display: grid;
				gap: <?php echo (int) $gaps; ?>px;
				grid-template-columns: repeat(<?php echo (int) $slides; ?>, 1fr);
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> > div {
				display: none;
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> > div:nth-child(-n+<?php echo (int) $slides; ?>) {
				display: block;
			}
			#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> > div img {
				display: block;
			}
			<?php
		}

		$sliderstyle = ob_get_clean();

		// Normalize newlines.
		$sliderstyle = preg_replace( '/(\r\n|\r|\n)+/', ' ', $sliderstyle );

		// Replace whitespace characters with a single space.
		$sliderstyle = preg_replace( '/\s+/', ' ', $sliderstyle );

		if ( self::$is_elementor_editor || true === $use_cache || $is_block_rendering ) {
			// Output the inline styles.
			echo '<style id="lps-slider-' . $shortcode_id . '">' . $sliderstyle . '</style>'; // phpcs:ignore
		} else {
			// Enqueue the static styles.
			wp_enqueue_style(
				'lps-slider-' . $shortcode_id,
				plugin_dir_url( __FILE__ ) . 'assets/css/lps-fed-css-slider.css',
				[],
				LPS_PLUGIN_VERSION
			);
			wp_add_inline_style( 'lps-slider-' . $shortcode_id, $sliderstyle );
		}

		ob_start();
		?>

		<<?php echo esc_attr( $wrap ); ?> class="latest-post-selection-slider-wrap">
			<div class="latest-post-selection-slider <?php echo esc_attr( $css ); ?>" id="latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?>">
				<?php
				foreach ( $posts as $post ) :
					setup_postdata( $post );
					if ( ! empty( $imgsize ) ) :
						if ( 'none' === $imgsize ) {
							$image[0] = plugins_url( '/assets/images/slider-default.png', __FILE__ );
						} else {
							$th_id = ( 'attachment' === $post->post_type ) ? (int) $post->ID : get_post_thumbnail_id( (int) $post->ID );
							$image = wp_get_attachment_image_src( $th_id, $imgsize );
						}
						if ( empty( $image[0] ) && ! empty( $args['image_placeholder'] ) ) {
							$image[0] = esc_attr( self::select_random_placeholder( $args['image_placeholder'] ) );
						}
						if ( ! empty( $image[0] ) ) :
							$a_start   = '';
							$a_end     = '';
							$title_str = self::cleanup_title( $post->post_title );
							if ( $url ) {
								$link_target = ( 'yes_blank' === $args['url'] ) ? ' target="_blank"' : '';
								$a_start     = '<a href="' . get_permalink( $post->ID ) . '"' . $link_target . ' title="' . esc_attr( $title_str ) . '">';
								$a_end       = '</a>';
							}
							?>
							<div>
								<?php echo $a_start; // phpcs:ignore ?>
								<div class="img-wrap"><img src="<?php echo esc_url( $image[0] ); ?>" alt="<?php echo esc_attr( $title_str ); ?>"></div>

								<?php if ( ! empty( $otype ) ) : ?>
									<div class="overlay">
										<?php if ( 'all' === $otype || 'title' === $otype ) : ?>
											<h3><?php echo esc_html( $title_str ); ?></h3>
										<?php endif; ?>
										<?php
										if ( 'all' === $otype || 'text' === $otype ) :
											$text = '';
											$lim  = $chrlimit;
											if ( $use_trim ) {
												$lim = (int) $chrlimit - mb_strlen( $title_str );
												$lim = $lim < 0 ? 0 : $lim;
											}
											if ( in_array( 'excerpt', $extra, true )
												|| in_array( 'content', $extra, true )
												|| in_array( 'content-small', $extra, true )
												|| in_array( 'excerpt-small', $extra, true )
												|| 'all' === $otype ) :
												if ( in_array( 'excerpt', $extra, true ) ) {
													$text = apply_filters( 'the_excerpt', strip_shortcodes( get_the_excerpt( $post ) ) );
												} elseif ( in_array( 'excerpt-small', $extra, true )
													|| 'all' === $otype ) {
													$text = self::get_short_text( get_the_excerpt( $post ), $lim, true, $trimmore );
												} elseif ( in_array( 'content', $extra, true ) ) {
													$text = apply_filters( 'the_content', $post->post_content );
												} elseif ( in_array( 'content-small', $extra, true ) ) {
													$text = self::get_short_text( $post->post_content, $lim, false, $trimmore );
												}
												echo esc_html( wp_strip_all_tags( $text ) );
											endif;
										endif;
										?>
									</div>
								<?php endif; ?>
								<?php echo $a_end; // phpcs:ignore ?>
							</div>
							<?php
						endif;
					endif;
				endforeach;
				?>
			</div>
		</<?php echo esc_attr( $wrap ); ?>>
		<?php
		$slider = ob_get_clean();

		// Normalize newlines.
		$slider = preg_replace( '/(\r\n|\r|\n)+/', ' ', $slider );

		// Replace whitespace characters with a single space.
		$slider = preg_replace( '/\s+/', ' ', $slider );

		echo $slider; // phpcs:ignore

		$script = '';
		ob_start();
		?>

		jQuery(document).ready(function(){
			jQuery('#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?>').slick({
				<?php if ( 'vertical' === $mode ) : ?>
					vertical: true,
				<?php elseif ( 'horizontal' === $mode ) : ?>
					vertical: false,
				<?php elseif ( 'fade' === $mode ) : ?>
					fade: true,
				<?php endif; ?>
				lazyLoad: 'progress',
				<?php if ( empty( $height ) ) : ?>
					adaptiveHeight: true,
				<?php else : ?>
					adaptiveHeight: false,
				<?php endif; ?>
				rows: 1,
				draggable: true,
				accessibility: true,
				autoplay: <?php echo esc_attr( $auto ); ?>,
				autoplaySpeed: <?php echo (int) $speed; ?>,
				speed: 300,
				pauseOnFocus: true,
				pauseOnHover: true,
				pauseOnDotsHover: true,
				slidesToShow: <?php echo (int) $slides; ?>,
				slidesToScroll: <?php echo (int) $scroll; ?>,
				infinite: <?php echo esc_attr( $inf ); ?>,
				dots: <?php echo esc_attr( $dots ); ?>,
				arrows: <?php echo esc_attr( $ctrl ); ?>,
				<?php if ( 'true' === $resp ) : ?>
					respondTo: '<?php echo esc_attr( $respto ); ?>',
					responsive: [{
						breakpoint: 1200,
						settings: {
							slidesToShow: <?php echo (int) $slides; ?>,
							slidesToScroll: <?php echo (int) $scroll; ?>,
							infinite: <?php echo esc_attr( $inf ); ?>,
							dots: <?php echo esc_attr( $dots ); ?>
						}
					}, {
						breakpoint: <?php echo (int) $t_bp; ?>,
						settings: {
							slidesToShow: <?php echo (int) $t_slides; ?>,
							slidesToScroll: <?php echo (int) $t_scroll; ?>,
							infinite: <?php echo esc_attr( $t_inf ); ?>,
							dots: <?php echo esc_attr( $t_dots ); ?>
						}
					},{
						breakpoint: <?php echo (int) $m_bp; ?>,
						settings: {
							slidesToShow: <?php echo (int) $m_slides; ?>,
							slidesToScroll: <?php echo (int) $m_scroll; ?>,
							infinite: <?php echo esc_attr( $m_inf ); ?>,
							dots: <?php echo esc_attr( $m_dots ); ?>
						}
					}],
				<?php endif; ?>
				<?php if ( 'true' === $center ) : ?>
					centerMode: true,
					centerPadding: '<?php echo (int) $padd; ?>px',
				<?php endif; ?>
				zIndex: 1000
			});
			<?php if ( 'none' === $imgsize ) : ?>
				jQuery('#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?> .overlay').css({'height': <?php echo (int) $height; ?>});
			<?php endif; ?>
			<?php if ( 'true' === $auto ) : ?>
				jQuery('#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?>').on('mouseleave', function() {
					jQuery(this).slick('play');
				});
			<?php endif; ?>
			jQuery('#latest-post-selection-slider-<?php echo esc_attr( $shortcode_id ); ?>').show();
		});
		<?php
		$script = ob_get_clean();

		// Normalize newlines.
		$script = preg_replace( '/(\r\n|\r|\n)+/', ' ', $script );

		// Replace whitespace characters with a single space.
		$script = preg_replace( '/\s+/', ' ', $script );

		if ( self::$is_elementor_editor || true === $use_cache ) {
			// Output the inline script.
			echo '<script id="lps-slider-' . $shortcode_id . '-script">' . $script . '</script>'; // phpcs:ignore
		} else {
			// Enqueue the static script.
			wp_enqueue_script(
				'lps-slider-' . $shortcode_id,
				plugin_dir_url( __FILE__ ) . 'assets/js/lps-fed-js-slider.js',
				[ 'jquery' ],
				LPS_PLUGIN_VERSION,
				true
			);
			wp_add_inline_script( 'lps-slider-' . $shortcode_id, $script );
		}

		wp_reset_postdata();
	}

	/**
	 * Custom minify content.
	 *
	 * @param  string  $content String to be minified.
	 * @param  boolean $is_css  String to CSS or not.
	 * @return string
	 */
	public static function custom_minify( $content, $is_css = false ) { // phpcs:ignore
		// Minify the output.
		$content = trim( $content );

		// Remove space after colons.
		$content = str_replace( ': ', ':', $content );
		$content = str_replace( ': ', ':', $content );

		// Remove whitespace.
		$content = str_replace( [ "\r\n", "\r", "\n", "\t" ], '', $content );

		// Remove spaces that might still be left where we know they aren't needed.
		$content = preg_replace( '/\s*([\{\}>~:;,])\s*/', '$1', $content );

		if ( true === $is_css ) {
			// Remove last semi-colon in a block.
			$content = preg_replace( '/;\}/', '}', $content );
		} else {
			$content = str_replace( '","', '", "', $content );
			$content = str_replace( '":"', '": "', $content );
		}

		return $content;
	}

	/**
	 * Maybe rebuild the front end assets.
	 *
	 * @param  boolean $rebuild True to rebuild.
	 * @return void
	 */
	public static function maybe_rebuild_assets( $rebuild ) { // phpcs:ignore
		if ( true === $rebuild ) {
			update_option( self::ASSETS_VERSION, gmdate( 'Ymd.Hi' ) );
		}

		$original = __DIR__ . '/assets/js/custom.js';
		$script1  = __DIR__ . '/assets/js/custom.min.js';
		if ( ( true === $rebuild && file_exists( $original ) ) || ! file_exists( $script1 ) ) {
			$content = @file_get_contents( dirname( __FILE__ ) . '/assets/js/custom.js' ); // phpcs:ignore
			$content = self::custom_minify( $content );
			@file_put_contents( $script1, $content ); // phpcs:ignore
		}
	}

	/**
	 * Plugin action link.
	 *
	 * @param  array $links Plugin links.
	 * @return array
	 */
	public static function plugin_action_links( $links ) { // phpcs:ignore
		$all   = [];
		$all[] = '<a href="https://iuliacazan.ro/latest-post-shortcode">' . esc_html__( 'Plugin URL', 'lps' ) . '</a>';
		$all   = array_merge( $all, $links );
		return $all;
	}

	/**
	 * The actions to be executed when the plugin is updated.
	 *
	 * @return void
	 */
	public static function plugin_ver_check() {
		$opt = str_replace( '-', '_', self::PLUGIN_TRANSIENT ) . '_db_ver';
		$dbv = get_option( $opt, 0 );
		if ( LPS_PLUGIN_VERSION !== (float) $dbv ) {
			update_option( $opt, LPS_PLUGIN_VERSION );
			self::activate_plugin();
		}
	}

	/**
	 * The actions to be executed when the plugin is activated.
	 *
	 * @return void
	 */
	public static function activate_plugin() {
		set_transient( self::PLUGIN_TRANSIENT, true );
		self::maybe_rebuild_assets( true );
	}

	/**
	 * The actions to be executed when the plugin is deactivated.
	 *
	 * @return void
	 */
	public static function deactivate_plugin() {
		self::plugin_admin_notices_cleanup( false );
	}

	/**
	 * Execute notices cleanup.
	 *
	 * @param  boolean $ajax Is AJAX call.
	 * @return void
	 */
	public static function plugin_admin_notices_cleanup( $ajax = true ) { // phpcs:ignore
		// Delete transient, only display this notice once.
		delete_transient( self::PLUGIN_TRANSIENT );

		if ( true === $ajax ) {
			// No need to continue.
			wp_die();
		}
	}

	/**
	 * Admin notices.
	 *
	 * @return void
	 */
	public static function plugin_admin_notices() {
		if ( apply_filters( 'lps_filter_remove_update_info', false ) ) {
			return;
		}

		$maybe_trans = get_transient( self::PLUGIN_TRANSIENT );
		if ( ! empty( $maybe_trans ) ) {
			$slug         = md5( LPS_PLUGIN_SLUG );
			$title        = __( 'Latest Post Shortcode', 'lps' );
			$donate       = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( self::PLUGIN_NAME ) . ')';
			$thanks       = __( 'A huge thanks in advance!', 'lps' );
			$maybe_pro    = '';
			$other_notice = sprintf(
				// Translators: %1$s - plugins URL, %2$s - heart icon, %3$s - extensions URL, %4$s - star icon, %5$s - maybe PRO details.
				__( '%5$sCheck out my other <a href="%1$s" target="_blank" rel="noreferrer">%2$s free plugins</a> on WordPress.org and the <a href="%3$s" target="_blank" rel="noreferrer">%4$s other extensions</a> available!', 'lps' ),
				'https://profiles.wordpress.org/iulia-cazan/#content-plugins',
				'<span class="dashicons dashicons-heart"></span>',
				'https://iuliacazan.ro/shop/',
				'<span class="dashicons dashicons-star-filled"></span>',
				$maybe_pro
			);
			?>
			<div id="item-<?php echo esc_attr( $slug ); ?>" class="updated notice">
				<div class="icon">
					<img src="<?php echo esc_url( LPS_PLUGIN_URL . 'assets/images/icon-128x128.gif' ); ?>">
				</div>
				<div class="content">
					<div>
						<h3>
							<?php
							echo wp_kses_post( sprintf(
								// Translators: %1$s - plugin name.
								__( '%1$s plugin was activated!', 'lps' ),
								'<b>' . $title . '</b>'
							) );
							?>
						</h3>
						<div class="notice-other-items"><div><?php echo wp_kses_post( $other_notice ); ?></div></div>
					</div>
					<div>
						<?php
						echo wp_kses_post( sprintf(
								// Translators: %1$s - donate URL, %2$s - rating, %3$s - thanks.
							__( 'If you find the plugin useful and would like to support my work, please consider making a <a href="%1$s" target="_blank">donation</a>. It would make me very happy if you would leave a %2$s rating. %3$s', 'lps' ),
							$donate,
							'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" rel="noreferrer" title="' . esc_attr( $thanks ) . '">★★★★★</a>',
							$thanks
						) );
						?>
					</div>
					<a class="notice-plugin-donate" href="<?php echo esc_url( $donate ); ?>" target="_blank"><img src="<?php echo esc_url( LPS_PLUGIN_URL . 'assets/images/buy-me-a-coffee.png?v=' . LPS_PLUGIN_VERSION ); ?>" width="200"></a>
				</div>
				<div class="action">
					<div class="dashicons dashicons-no" onclick="dismiss_notice_for_<?php echo esc_attr( $slug ); ?>()"></div>
				</div>
			</div>
			<?php
			$style = '
			#trans123super{--color-bg:rgba(63,77,183,0.1); --color-border:rgb(63,77,183); display:grid; padding:0; gap:0; grid-template-columns:6rem auto 3rem; max-width:100%; width:100%; border-left-color: var(--color-border); box-sizing:border-box;} #trans123super .dashicons-no{font-size:2rem; cursor:pointer;} #trans123super .icon{ display:grid; align-content:start; background-color:var(--color-bg); padding: 1rem} #trans123super .icon img{object-fit:cover; object-position:center; width:100%;} #trans123super .action{ display:grid; align-content:start; padding: 1rem 0.5rem} #trans123super .content{ align-items: center; display: grid; gap: 1rem; grid-template-columns: 1fr 1fr 12rem; padding: 1rem;} #trans123super .content .dashicons{color:var(--color-border);} #trans123super .content > div{color:#666;} #trans123super h3{margin:0 0 0.1rem 0;color:#666} #trans123super h3 b{color:#000} #trans123super a{color:#000;text-decoration:none;} #trans123super .notice-plugin-donate img{max-width: 100%;} @media all and (max-width: 1024px) {#trans123super .content{grid-template-columns:100%;}}';
			$style = str_replace( '#trans123super', '#item-' . esc_attr( $slug ), $style );
			echo '<style>' . $style . '</style>'; //phpcs:ignore
			?>
			<script>function dismiss_notice_for_<?php echo esc_attr( $slug ); ?>() { document.getElementById( 'item-<?php echo esc_attr( $slug ); ?>' ).style='display:none'; fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=plugin-deactivate-notice-<?php echo esc_attr( LPS_PLUGIN_SLUG ); ?>' ); }</script>
			<?php
		}
	}

	/**
	 * Maybe donate or rate.
	 *
	 * @return void
	 */
	public static function show_donate_text() {
		?>
		<hr>
		<table class="inline-donate-notice">
			<tbody><tr>
				<td valign="middle">
					<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '/assets/images/icon-128x128.png' ); ?>" width="38" height="38">
					<?php
					echo wp_kses_post(
						sprintf(
							// Translators: %1$s - donate URL, %2$s - rating, %3$s - thanks.
							__( 'If you find the plugin useful and would like to support my work, please consider making a <a href="%1$s" target="_blank">donation</a>. It would make me very happy if you would leave a %2$s rating. %3$s', 'lps' ),
							'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( self::PLUGIN_NAME ) . ')',
							'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" title="' . esc_attr__( 'A huge thanks in advance!', 'lps' ) . '">★★★★★</a>',
							__( 'A huge thanks in advance!', 'lps' )
						)
					);
					?>
					<br><em>Iulia</em>
				</td>
			</tr></tbody>
		</table>
		<hr class="sep">
		<?php
	}

	/**
	 * Dequeue the scripts and styles, and additionally for the front-end pages that
	 * do not use the LPS functionality.
	 *
	 * @return void
	 */
	public static function lps_filter_plugin_assets() {
		$always_load = get_option( 'lps-assets-all', '' );
		$always_load = apply_filters( 'lps/load_assets_on_page', $always_load );
		if ( 'yes' === $always_load || \is_admin() || self::$is_elementor_editor ) {
			// Fail-fast.
			return;
		}

		if ( ! self::lps_current_page_contains( 'latest-selected-content' )
			&& ! self::lps_current_page_contains( 'latest-post-selection' )
			&& ! self::lps_current_page_contains( 'wp:latest-post-shortcode' ) ) {

			// Dequeue the styles.
			\wp_dequeue_style( 'lps-style-legacy' );
			\wp_dequeue_style( 'lps-style' );
			\wp_dequeue_style( 'lps-slick-style' );

			// Dequeue the scripts.
			\wp_dequeue_script( 'lps-frontend-variables' );
			\wp_dequeue_script( 'lps-ajax-pagination-js' );
			\wp_dequeue_script( 'lps-slick' );
		}
	}

	/**
	 * Assess the current rendering page content.
	 *
	 * @return void
	 */
	public static function lps_assess_page_content() {
		global $lps_assess_cpa, $_wp_current_template_content;
		if ( empty( $lps_assess_cpa ) ) {
			$the_object      = \get_queried_object();
			$lps_assess_cpa  = $the_object->post_content ?? '';
			$lps_assess_cpa .= $the_object->description ?? '';
			$lps_assess_cpa .= $_wp_current_template_content ?? '';
			$lps_assess_cpa .= \wp_json_encode( \get_option( 'widget_text' ) );
			$lps_assess_cpa .= \wp_json_encode( \get_option( 'widget_custom_html' ) );
		}
	}

	/**
	 * Assess if the current rendering page contains a specific string.
	 *
	 * @param  string $something What to check.
	 * @return bool
	 */
	public static function lps_current_page_contains( string $something = '' ): bool {
		global $lps_assess_cpa;
		if ( empty( $something ) ) {
			return false;
		}
		if ( empty( $lps_assess_cpa ) ) {
			self::lps_assess_page_content();
		}

		$text = $lps_assess_cpa;
		if ( empty( $text ) ) {
			return false;
		}
		if ( substr_count( $text, $something ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Register a custom setting for the plugin.
	 *
	 * @return void
	 */
	public static function lps_assets_options() {
		// Add an option to Settings -> Reading.
		register_setting( 'reading', 'lps-assets-all', [
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$allowed_options = [ 'reading' => [ 'lps-assets-all' ] ];
		if ( function_exists( 'add_allowed_options' ) ) {
			add_allowed_options( $allowed_options );
		} else {
			// Fallback to old function.
			add_option_whitelist( $allowed_options ); // phpcs:ignore
		}

		add_settings_field(
			'lps-assets-all',
			__( 'Latest Post Shortcode', 'lps' ),
			[ get_called_class(), 'lps_assets_all' ],
			'reading'
		);
	}

	/**
	 * Custom setting output callback handler.
	 *
	 * @return void
	 */
	public static function lps_assets_all() {
		$value = get_option( 'lps-assets-all', '' );
		?>
		<div class="lps-assets-all-options">
			<p>
				<input type="radio" name="lps-assets-all" id="lps_assets_all_no" value=""
					<?php checked( empty( $value ), true ); ?> />
				<label for="lps_assets_all_no"><?php \esc_html_e( 'let WordPress decide when to load the LPS assets', 'lps' ); ?></label>
			</p>
			<p>
				<input type="radio" name="lps-assets-all" id="lps_assets_all_yes" value="yes"
					<?php checked( 'yes' === $value, true ); ?> />
				<label for="lps_assets_all_yes"><?php \esc_html_e( 'always load the LPS assets', 'lps' ); ?></label>
			</p>
		</div>
		<?php
	}

	/**
	 * Fix the pagination in the single pages.
	 *
	 * @param  WP_Query $request Current request object.
	 * @return WP_Query
	 */
	public static function fix_request_redirect( $request ) {
		if ( ( true === $request->is_singular || true === $request->is_single )
			&& - 1 === $request->current_post && true === $request->is_paged ) {
			add_filter( 'redirect_canonical', '__return_false' );
		}
		return $request;
	}
}

// Instantiate the class.
$lps_instance = Latest_Post_Shortcode::get_instance();

// Register activation and deactivation actions.
register_activation_hook( __FILE__, [ $lps_instance, 'activate_plugin' ] );
register_deactivation_hook( __FILE__, [ $lps_instance, 'deactivate_plugin' ] );

// Allow the text widget to render the Latest Post Shortcode.
add_filter( 'widget_text', 'do_shortcode', 11 );

if ( class_exists( 'Elementor\\Plugin' ) ) {
	// Add Elementor support.
	require_once 'elementor/class-elementor-lps-extension.php';
}

if ( function_exists( 'register_block_type' ) ) {
	// Gutenberg is active.
	require_once 'lps-block/lps-block.php';
}

if ( file_exists( __DIR__ . '/lps-tests.php' ) ) {
	// Maybe shortcode tests.
	include_once __DIR__ . '/lps-tests.php';
}
