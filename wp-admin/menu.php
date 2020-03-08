<?php
/**
 * Comment Type menus.
 *
 * @package WP\CommentTypes
 * @subpackage \wp-admin\menu
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds top levels comment type menus.
 *
 * @since 1.0.0
 */
function admin_menu() {
	$comment_types = get_comment_types(
		array(
			'_builtin' => false,
			'show_ui'  => true,
		),
		'objects'
	);

	if ( ! $comment_types ) {
		return;
	}

	foreach ( $comment_types as $comment_type ) {
		add_menu_page(
			$comment_type->labels->admin_menu_name,
			$comment_type->labels->admin_menu_name,
			$comment_type->capabilities['list_comment_type_items'],
			'wpct-' . $comment_type->name,
			__NAMESPACE__ . '\admin_comment_types',
			$comment_type->menu_icon,
			(int) $comment_type->menu_position
		);
	}
}
add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu', 0 ); // highest priority.
