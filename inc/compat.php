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

// Adds a hook to shortcircuit the `wp-admin/edit-comments.php` page if needed.
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

/**
 * Overrides the items to list into the `wp-admin/edit-comments.php` comment types dropdown.
 *
 * @since 1.0.0
 *
 * @return array The comment types dropdown options.
 */
function admin_comment_types_dropdown() {
	$comment_types = get_comment_types(
		array(
			'show_in_comments_dropdown' => true,
		),
		'objects'
	);

	return wp_list_pluck( $comment_types, 'label', 'name' );
}
add_filter( 'admin_comment_types_dropdown', __NAMESPACE__ . '\admin_comment_types_dropdown', 0 );

/**
 * Restrict the queried comment types to the one showing into the dropdown menu
 *
 * NB: this filter is used in WP_Comments_List_Table::prepare_items()
 *
 * @since 1.0.0
 *
 * @param array $args The comments query arguments used into the Comments Admin screen.
 * @return array The comments query arguments used into the Comments Admin screen.
 */
function comments_list_table_query_args( $args = array() ) {
	/**
	 * The comment types to query as listed into the dropdown of the legacy
	 * comments administration screen.
	 */
	if ( ! $args['type'] ) {
		$args['type'] = get_comment_types(
			array(
				'show_in_comments_dropdown' => true,
			)
		);
	}

	return $args;
}
add_filter( 'comments_list_table_query_args', __NAMESPACE__ . '\comments_list_table_query_args', 0, 1 );

// Adds a hook to shortcircuit the `wp_count_comments()` function.
add_filter( 'wp_count_comments', __NAMESPACE__ . '\wp_count_comments', 0, 1 );
