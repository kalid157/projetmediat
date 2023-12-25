<?php
/**
 * Plugin Name: Gosign Advanced Separator Block
 * Plugin URI: https://www.gosign.de/
 * Description: Gosign Advanced Separator Block — is a Gutenberg plugin, Which Add white spaces, Seperator and borders with custom icons.
 * Author: Gosign.de
 * Author URI: https://www.gosign.de/wordpress-agentur/
 * Version: 2.0.2
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Initializer.
 */
require_once plugin_dir_path( __FILE__ ) . 'src/init.php';
