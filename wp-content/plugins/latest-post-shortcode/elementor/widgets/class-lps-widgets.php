<?php // phpcs:ignore
/**
 * Elementor widget that inserts an embbedable content into the page, from any given URL.
 *
 * @since 8.7
 * @package lps
 */

/**
 * LPS Elementor Widget class.
 */
class Lps_Widget extends \Elementor\Widget_Base {

	/**
	 * Retrieve lps widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'lps';
	}

	/**
	 * Retrieve lps widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return __( 'Latest Post Shortcode', 'lps' );
	}

	/**
	 * Retrieve lps widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'fa fa-code fa-lps-icon';
	}

	/**
	 * Retrieve the list of categories the lps widget belongs to.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories(): array {
		return [ 'basic' ];
	}

	/**
	 * Retrieve the lps widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return [ 'lps', __( 'Latest Post Shortcode', 'lps' ) ];
	}

	/**
	 * Retrieve the list of script dependencies the element requires.
	 *
	 * @return array Widget dependencies.
	 */
	public function get_script_depends(): array {
		return [ 'lps-admin-style', 'lps-admin-shortcode-button' ];
	}

	/**
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$default = '[latest-selected-content ver="2" limit="3" display="title,excerpt" titletag="h3" url="yes" linktext="' . esc_html__( 'Read more', 'lps' ) . ' ➞" image="medium" elements="3" css="three-columns as-column has-radius has-shadow has-img-spacing content-space-between" type="post" status="publish" orderby="dateD"]';

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Latest Post Shortcode', 'lps' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'shortcode',
			[
				'label'       => sprintf(
					// Translators: %1$s - icon embed.
					__( 'To configure the shortcode, use the custom button %1$s from the Visual editor or the LPS button from the Text editor (see a sample below).', 'lps' ),
					file_get_contents( dirname( dirname( dirname( __FILE__ ) ) ) . '/assets/images/icon-lps-light.svg' ) // phpcs:ignore
				),
				'description' => sprintf(
					// Translators: %1$s - shorcode example.
					__( 'Sample of showing four columns of your posts: %1$s.', 'lps' ),
					$default
				),
				'type'        => \Elementor\Controls_Manager::WYSIWYG,
				'dynamic'     => [
					'active' => true,
				],
				'default'     => $default,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render lps widget output on the frontend, used to generate the final HTML.
	 *
	 * @return void
	 */
	protected function render() {
		$shortcode = $this->get_settings_for_display( 'shortcode' );
		$shortcode = str_replace(
			'[latest-selected-content ',
			'[latest-selected-content is_elementor="yes" ',
			$shortcode
		);
		$shortcode = do_shortcode( shortcode_unautop( $shortcode ) );
		?>
		<div class="elementor-shortcode"><?php echo $shortcode; // phpcs:ignore ?><div class="clear"></div></div>
		<?php
	}

	/**
	 * Override the default behavior by printing the shortcode instead of rendering it.
	 *
	 * @return void
	 */
	public function render_plain_content() {
		// In plain mode, render without shortcode.
		echo $this->get_settings( 'shortcode' ); // phpcs:ignore
	}

	/**
	 * Render shortcode widget output in the editor.
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @return void
	 */
	protected function content_template() {
	}
}
