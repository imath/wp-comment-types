<?php
/**
 * Compat functions.
 *
 * @package WP\CommentTypes
 * @subpackage \inc\compat
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Adds a hook to shortcircuit the wp-admin/edit-comments.php page if needed.
add_action( 'load-edit-comments.php', __NAMESPACE__ . '\admin_comment_types_load', 0 );

/**
 * Overrides the parent file of the displayed screen if needed.
 *
 * @since 1.0.0
 *
 * @param string $parent_file The parent file.
 * @return string             The parent file.
 */
function admin_parent_file( $parent_file ) {
	$current_screen = get_current_screen();

	if ( ! isset( $current_screen->comment_type ) ) {
		return $parent_file;
	}

	$custom_comment_types = get_comment_types(
		array(
			'_builtin' => false,
			'show_ui'  => true,
		)
	);

	if ( in_array( $current_screen->comment_type, $custom_comment_types, true ) ) {
		$parent_file = add_query_arg( 'comment_type', $current_screen->comment_type, $parent_file );
	}

	return $parent_file;
}
add_filter( 'parent_file', __NAMESPACE__ . '\admin_parent_file', 0 );
