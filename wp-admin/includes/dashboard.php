<?php
/**
 * Dashboard widgets.
 *
 * @package WP\CommentTypes
 * @subpackage \wp-admin\includes\dashboard
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show Comments section.
 *
 * @since 1.0.0
 *
 * @param int $total_items Optional. Number of comments to query. Default 5.
 * @return bool False if no comments were found. True otherwise.
 */
function wp_dashboard_recent_comments( $total_items = 5 ) {
	// Select all comment types and filter out spam later for better query performance.
	$comments = array();

	$comments_query = array(
		'number' => $total_items * 5,
		'offset' => 0,
		/**
		 * Makes sure only comment types supporting this feature
		 * will be includes into the comments query.
		 */
		'type'   => get_comment_types_by_support( 'dashboard-widget' ),
	);

	if ( ! current_user_can( 'edit_posts' ) ) {
		$comments_query['status'] = 'approve';
	}

	while ( count( $comments ) < $total_items && $possible = get_comments( $comments_query ) ) {
		if ( ! is_array( $possible ) ) {
			break;
		}
		foreach ( $possible as $comment ) {
			if ( ! current_user_can( 'read_post', $comment->comment_post_ID ) ) {
				continue;
			}
			$comments[] = $comment;
			if ( count( $comments ) == $total_items ) {
				break 2;
			}
		}
		$comments_query['offset'] += $comments_query['number'];
		$comments_query['number']  = $total_items * 10;
	}

	if ( $comments ) {
		echo '<div id="latest-comments" class="activity-block">';
		echo '<h3>' . __( 'Recent Comments' ) . '</h3>';

		echo '<ul id="the-comment-list" data-wp-lists="list:comment">';
		foreach ( $comments as $comment ) {
			_wp_dashboard_recent_comments_row( $comment );
		}
		echo '</ul>';

		if ( current_user_can( 'edit_posts' ) ) {
			echo '<h3 class="screen-reader-text">' . __( 'View more comments' ) . '</h3>';
			_get_list_table( 'WP_Comments_List_Table' )->views();
		}

		wp_comment_reply( -1, false, 'dashboard', false );
		wp_comment_trashnotice();

		echo '</div>';
	} else {
		return false;
	}
	return true;
}

/**
 * Callback function for Activity widget.
 *
 * @since 1.0.0
 */
function wp_dashboard_site_activity() {

	echo '<div id="activity-widget">';

	$future_posts = wp_dashboard_recent_posts(
		array(
			'max'    => 5,
			'status' => 'future',
			'order'  => 'ASC',
			'title'  => __( 'Publishing Soon', 'wp-comment-types' ),
			'id'     => 'future-posts',
		)
	);

	$recent_posts = wp_dashboard_recent_posts(
		array(
			'max'    => 5,
			'status' => 'publish',
			'order'  => 'DESC',
			'title'  => __( 'Recently Published', 'wp-comment-types' ),
			'id'     => 'published-posts',
		)
	);

	$recent_comments = wp_dashboard_recent_comments();

	if ( ! $future_posts && ! $recent_posts && ! $recent_comments ) {
		echo '<div class="no-activity">';
		echo '<p class="smiley" aria-hidden="true"></p>';
		echo '<p>' . __( 'No activity yet!', 'wp-comment-types' ) . '</p>';
		echo '</div>';
	}

	echo '</div>';
}

/**
 * Replaces the Activity widget's callback.
 *
 * @since 1.0.0
 *
 * @global array $wp_meta_boxes
 */
function wp_dashboard_setup() {
	if ( ! is_blog_admin() ) {
		return;
	}

	global $wp_meta_boxes;

	if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] ) ) {
		$wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']['callback'] = __NAMESPACE__ . '\wp_dashboard_site_activity';
	}
}
add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\wp_dashboard_setup' );
