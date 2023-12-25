<?php // phpcs:ignore
/**
 * Latest Post Shortcode Block.
 * Text Domain: lps
 *
 * @package lps
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'init', 'latest_post_shortcode_lps_block_block_init' );
add_action( 'after_setup_theme', 'lps_add_theme_support' );
add_action( 'init', 'lps_set_script_translations', 30 );
add_filter( 'load_script_translation_file', 'lps_fix_translation_location', 10, 3 );

if ( ! function_exists( 'lps_set_script_translations' ) ) {
	/**
	 * Set script translations.
	 *
	 * @return void
	 */
	function lps_set_script_translations() {
		wp_set_script_translations( 'lps-block-editor-script', 'lps', dirname( __DIR__ ) . '/langs' );
	}
}

if ( ! function_exists( 'lps_fix_translation_location' ) ) {
	/**
	 * Fix translation location for the editor.
	 *
	 * @param  string $file   File.
	 * @param  string $handle Handle.
	 * @param  string $domain Text domain.
	 *
	 * @return string
	 */
	function lps_fix_translation_location( string $file, string $handle, string $domain ): string {
		if ( 'lps' !== $domain ) {
			return $file;
		}

		if ( strpos( $handle, 'lps-block-editor-script' ) !== false ) {
			$file = str_replace(
				WP_LANG_DIR . '/plugins',
				plugin_dir_path( __DIR__ ) . 'langs',
				$file
			);
		}
		return $file;
	}
}

if ( ! function_exists( 'latest_post_shortcode_lps_block_block_init' ) ) {
	/**
	 * Registers all block assets so that they can be enqueued through the block editor in the corresponding context.
	 *
	 * @return void
	 */
	function latest_post_shortcode_lps_block_block_init() {
		global $lps_instance;
		if ( empty( $lps_instance ) ) {
			return;
		}

		$ver = LPS_PLUGIN_VERSION . $lps_instance::$assets_version;

		// Load shortcode related assets.
		wp_enqueue_style(
			'lps-style-legacy',
			plugins_url( '/assets/css/style-legacy.min.css', __DIR__ ),
			[],
			$ver,
			false
		);

		wp_enqueue_style(
			'lps-style',
			plugins_url( '/assets/css/style.min.css', __DIR__ ),
			[],
			$ver,
			false
		);
		wp_register_script(
			'lps-ajax-pagination-js',
			plugins_url( '/assets/js/custom-pagination.min.js', __DIR__ ),
			[],
			$ver,
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

		$script_asset_path = __DIR__ . '/build/index.asset.php';
		if ( file_exists( $script_asset_path ) ) {
			$index_js     = 'build/index.js';
			$script_asset = require $script_asset_path;
			wp_register_script(
				'lps-block-editor-script',
				plugins_url( $index_js, __FILE__ ),
				$script_asset['dependencies'],
				$script_asset['version'],
				false
			);
		}

		$editor_css = 'build/editor.css';
		wp_register_style(
			'lps-block-editor-style',
			plugins_url( $editor_css, __FILE__ ),
			[],
			filemtime( __DIR__ . '/' . $editor_css )
		);

		register_block_type(
			'latest-post-shortcode/lps-block',
			[
				'editor_script'   => 'lps-block-editor-script',
				'editor_style'    => 'lps-block-editor-style',
				'style'           => 'lps-style',
				'attributes'      => [
					'lpsBlockName' => [
						'type'    => 'string',
						'default' => 'latest-post-shortcode/lps-block',
					],
					'lpsContent'   => [
						'type'    => 'string',
						'default' => '[latest-selected-content ver="2" limit="4" display="title" titletag="h3" url="yes" image="medium" elements="3" css="four-columns align-left content-end as-overlay tall dark hover-zoom" type="post" status="publish" orderby="dateD"]',
					],
					'postId'       => [
						'type'    => 'string',
						'default' => '',
					],
					'clientId'     => [
						'type' => 'string',
					],
					'nthOfType'    => [
						'type' => 'string',
					],
				],
				'supports'        => [
					'html'   => false,
					'anchor' => true,
					'align'  => [ 'full', 'wide', 'center' ],
				],
				'render_callback' => 'lps_render_block',
			]
		);
	}
}

if ( ! function_exists( 'lps_add_theme_support' ) ) {
	/**
	 * Add theme support.
	 *
	 * @return void
	 */
	function lps_add_theme_support() {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'align-wide' );
	}
}

if ( ! function_exists( 'lps_render_block' ) ) {
	/**
	 * Server-side rendering handler.
	 *
	 * @param  array  $block         Block attributes.
	 * @param  string $block_content Block content.
	 * @return string
	 */
	function lps_render_block( array $block, string $block_content ): string {
		$instance_id = wp_unique_id( 'lps-block-' );

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			if ( ! empty( $block['lpsBlockName'] ) && 'latest-post-shortcode/lps-block' === $block['lpsBlockName'] ) {
				$post_id    = ( ! empty( $block['postId'] ) ) ? $block['postId'] : 0;
				$client_id  = ( ! empty( $block['clientId'] ) ) ? $block['clientId'] : '';
				$block_ord  = ( ! empty( $block['nthOfType'] ) ) ? (int) $block['nthOfType'] : 0;
				$rendered   = [];
				$collection = [];

				if ( empty( $block_ord ) ) {
					update_post_meta( $post_id, '_lps-block-ids', [] );
				} else {
					$collection = get_post_meta( $post_id, '_lps-block-ids', true );
					if ( ! empty( $collection ) ) {
						foreach ( $collection as $key => $value ) {
							if ( $key === $client_id ) {
								break;
							}
							$rendered = array_merge( $rendered, $value );
						}
						$rendered = array_unique( $rendered );
					}
				}

				global $lps_current_post_embedded_item_ids, $lps_instance, $lps_current_queried_object_id;
				$lps_current_post_embedded_item_ids = $rendered;
				$lps_current_queried_object_id      = $post_id;

				// Compute here the content.
				$block['lpsContent'] = str_replace( '[latest-selected-content ', '[latest-selected-content lps_instance_id="' . $instance_id . '" ', $block['lpsContent'] );

				$content = do_shortcode( $block['lpsContent'] );
				if ( $lps_instance ) {
					$lps_instance::execute_lps_cache_reset();
				}

				$collection[ $client_id ] = $lps_current_post_embedded_item_ids;
				update_post_meta( $post_id, '_lps-block-ids', $collection );

				if ( empty( $content )
					|| ( ! substr_count( $content, '<section ' ) && ! substr_count( $content, 'lps-slider' ) ) ) {
					$content = '<div class="lps-placeholder">' . wp_kses_post( __( 'The shortcode found no results. If you provided a fallback message that will be shown on the front-end.', 'lps' ) ) . '</div>';
				}

				return '<div class="lps-block-preview">' . $content . '</div>';
			}
		}

		$block_content = str_replace( '[latest-selected-content ', '[latest-selected-content lps_instance_id="' . $instance_id . '" ', $block_content );

		return '<div ' . get_block_wrapper_attributes() . '>' . $block_content . '</div>';
	}
}
