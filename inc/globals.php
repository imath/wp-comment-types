<?php
/**
 * Functions about globals.
 *
 * @package WP\CommentTypes
 * @subpackage \inc\globals
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin globals
 *
 * @since 1.0.0
 */
function register_globals() {
	$wpct = instance();

	$wpct->version     = '1.0.0';
	$wpct->inc_path    = plugin_dir_path( __FILE__ );
	$wpct->wp_inc_path = plugin_dir_path( dirname( __FILE__ ) ) . 'wp-includes';

	$wpct->assets_url         = plugin_dir_url( dirname( __FILE__ ) ) . 'assets';
	$wpct->languages_path     = plugin_dir_path( dirname( __FILE__ ) ) . 'languages';
	$wpct->languages_basepath = trailingslashit( dirname( plugin_basename( dirname( __FILE__ ) ) ) ) . 'languages';

	$wpct->comment_types         = array();
	$wpct->comment_type_features = array();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\register_globals', 10 );
