<?php // phpcs:ignore
/**
 * LPS Elementor widget control.
 *
 * @since 8.7
 * @package lps
 */

/**
 * LPS Elementor Control class.
 */
class Lps_Control extends \Elementor\Base_Data_Control {

	/**
	 * Retrieve the control type.
	 *
	 * @return string Control type.
	 */
	public function get_type(): string {
		return 'lps';
	}

	/**
	 * Used to register and enqueue custom scripts and styles used by the lps control.
	 *
	 * @return void
	 */
	public function enqueue() {
	}

	/**
	 * Retrieve the default settings of the lps control. Used to return
	 * the default settings while initializing the control.
	 *
	 * @return array Control default settings.
	 */
	protected function get_default_settings(): array {
		return [
			'label_block' => true,
			'rows'        => 3,
			'lps_options' => [],
		];
	}

	/**
	 * Used to generate the control HTML in the editor using Underscore JS
	 * template. The variables for the class are available using `data` JS
	 * object.
	 *
	 * @return void
	 */
	public function content_template() {
	}
}
