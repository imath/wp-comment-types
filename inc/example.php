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
				'singular_name'      => _x( 'Example', 'Comment singular name', 'wp-comment-types' ),
				'admin_menu_name'    => _x( 'Examples', 'Comment screen main nav', 'wp-comment-types' ),
				'not_found'          => _x( 'No examples found', '`not_found` comment type label', 'wp-comment-types' ),
				'no_awaiting_mod'    => _x( 'No examples awaiting moderation.', '`no_awaiting_mod` comment type label', 'wp-comment-types' ),
				'not_found_in_trash' => _x( 'No examples found in Trash.', '`not_found_in_trash` comment type label', 'wp-comment-types' ),
				'search_items'       => _x( 'Search Examples', '`search_items` comment type label', 'wp-comment-types' ),
			),
			'public'           => true,
			'delete_with_user' => false,
			'supports'         => array( 'avatar', 'editor' ),
			'show_in_rest'     => true,
		)
	);

	register_comment_type(
		'sample',
		array(
			'label'                     => __( 'Samples', 'wp-comment-types' ),
			'labels'                    => array(
				'singular_name'   => _x( 'Sample', 'Comment singular name', 'wp-comment-types' ),
				'admin_menu_name' => _x( 'Samples', 'Comment screen main nav', 'wp-comment-types' ),
			),
			'public'                    => true,
			'show_in_comments_dropdown' => true,
			'delete_with_user'          => false,
			'supports'                  => array( 'editor' ),
			'show_in_rest'              => true,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\custom_comment_type' );
