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

	if ( ! isset( $current_screen->id ) || ! isset( $_GET['comment_type'] ) || ! $_GET['comment_type'] ) { // phpcs:ignore
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

	$wp_list_table = new WP_Comments_List_Table(
		array(
			'plural'   => $comment_type_object->plural,
			'singular' => $comment_type_object->singular,
			'ajax'     => false,
			'screen'   => $current_screen,
		)
	);

	$pagenum  = $wp_list_table->get_pagenum();
	$doaction = $wp_list_table->current_action();

	$wp_list_table->prepare_items();
	add_screen_option( 'per_page' );

	require_once ABSPATH . 'wp-admin/admin-header.php';
	?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo esc_html( $page_title ); ?></h1>
		<?php
		if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) { // phpcs:ignore
			echo '<span class="subtitle">';
			printf(
				/* translators: %s: Search query. */
				__( 'Search results for &#8220;%s&#8221;', 'wp-comment-types' ), // phpcs:ignore
				wp_html_excerpt( esc_html( wp_unslash( $_REQUEST['s'] ) ), 50, '&hellip;' ) // phpcs:ignore
			);
			echo '</span>';
		}
		?>
		<hr class="wp-header-end">

		<?php $wp_list_table->views(); ?>

		<form id="comments-form" method="get">
			<?php $wp_list_table->search_box( $comment_type_object->labels->search_items, 'comment', 'wp-comment-types' ); ?>
			<input type="hidden" name="pagegen_timestamp" value="<?php echo esc_attr( current_time( 'mysql', 1 ) ); ?>" />

			<input type="hidden" name="_total" value="<?php echo esc_attr( $wp_list_table->get_pagination_arg( 'total_items' ) ); ?>" />
			<input type="hidden" name="_per_page" value="<?php echo esc_attr( $wp_list_table->get_pagination_arg( 'per_page' ) ); ?>" />
			<input type="hidden" name="_page" value="<?php echo esc_attr( $wp_list_table->get_pagination_arg( 'page' ) ); ?>" />

			<?php if ( isset( $_REQUEST['paged'] ) ) { // phpcs:ignore ?>
				<input type="hidden" name="paged" value="<?php echo esc_attr( absint( $_REQUEST['paged'] ) ); // phpcs:ignore ?>" />
			<?php } ?>

			<?php $wp_list_table->display(); ?>
		</form>
	</div>

	<?php
	require_once ABSPATH . 'wp-admin/admin-footer.php';

	// Prevents the rest of the admin to load.
	exit();
}
