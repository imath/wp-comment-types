<?php
/**
 * Comment Type's example.
 *
 * @package WP\CommentTypes
 * @subpackage \inc\example
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a custom comment type.
 *
 * @since 1.0.0
 */
function custom_comment_type() {
	register_comment_type(
		'example',
		array(
			'label'            => __( 'Examples', 'wp-comment-types' ),
			'labels'           => array(
				'singular_name'   => _x( 'Example', 'Comment singular name', 'wp-comment-types' ),
				'admin_menu_name' => _x( 'Examples', 'Comment screen main nav', 'wp-comment-types' ),
			),
			'public'           => true,
			'delete_with_user' => false,
			'supports'         => array( 'editor' ),
			'show_in_rest'     => true,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\custom_comment_type' );
