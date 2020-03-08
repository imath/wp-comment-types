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

	if ( isset( $current_screen->id ) ) {
		$comment_type = str_replace( 'toplevel_page_wpct-', '', $current_screen->id );

		if ( comment_type_exists( $comment_type ) ) {
			$current_screen->comment_type = $comment_type;
		}
	}

	if ( ! isset( $current_screen->comment_type ) ) {
		wp_die( esc_html__( 'Invalid comment type.', 'wp-comment-types' ) );
	}
}

/**
 * Displays the Comment Types admin screen.
 *
 * @since 1.0.0
 */
function admin_comment_types() {
	$current_screen = get_current_screen();
	$comment_type   = get_comment_type_object( $current_screen->comment_type );

	if ( null !== $comment_type ) {
		$title = $comment_type->label;
	} else {
		$title = _x( 'Comment Type', 'default admin screen title', 'wp-comment-types' );
	}

	?>
	<div class="wrap">
		<h1><?php echo esc_html( $title ); ?></h1>
	</div>
	<?php
}
