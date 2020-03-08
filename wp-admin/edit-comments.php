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
 * Displays the Comment Types admin screen.
 *
 * @since 1.0.0
 */
function admin_comment_types() {
	$title        = _x( 'Comment Type', 'default admin screen title', 'wp-comment-types' );
	$comment_type = null;

	if ( isset( $_GET['page'] ) ) { // phpcs:ignore
		$get_page = sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore
		$get_type = str_replace( 'wpct-', '', $get_page );

		$comment_type = get_comment_type_object( $get_type );
	}

	if ( null !== $comment_type ) {
		$title = $comment_type->label;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( $title ); ?></h1>
	</div>
	<?php
}
