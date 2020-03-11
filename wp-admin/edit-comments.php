<?php
/**
 * Comment Types Admin.
 *
 * @package WP\CommentTypes
 * @subpackage \wp-admin\edit-comments
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the Comment Type Admin screen.
 *
 * @since 1.0.0
 */
function admin_comment_types_load() {
	$current_screen = get_current_screen();

	if ( ! isset( $current_screen->id ) || ! isset( $_GET['comment_type'] ) ) { // phpcs:ignore
		return;
	}

	$comment_type = sanitize_key( wp_unslash( $_GET['comment_type'] ) ); // phpcs:ignore

	if ( ! comment_type_exists( $comment_type ) ) {
		wp_die( esc_html__( 'Invalid comment type.', 'wp-comment-types' ) );
	}

	$current_screen->comment_type = $comment_type;
	$builtin_comment_types        = get_comment_types( array( '_builtin' => true ) );

	if ( in_array( $comment_type, $builtin_comment_types, true ) ) {
		return;
	}

	$comment_type_object = get_comment_type_object( $comment_type );

	// The comment type is using the comments dropdown.
	if ( isset( $comment_type_object->show_in_comments_dropdown ) && true === $comment_type_object->show_in_comments_dropdown ) {
		return;

		// The comment type has its own Admin UI.
	} elseif ( isset( $comment_type_object->label ) && $comment_type_object->label ) {
		$page_title = $comment_type_object->label;
	} else {
		$page_title = _x( 'Comment Type', 'default admin screen title', 'wp-comment-types' );
	}

	require_once ABSPATH . 'wp-admin/admin-header.php';
	?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo esc_html( $page_title ); ?></h1>
		<hr class="wp-header-end">
	</div>

	<?php
	require_once ABSPATH . 'wp-admin/admin-footer.php';

	// Prevents the rest of the admin to load.
	exit();
}
