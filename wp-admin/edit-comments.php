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

function admin_comment_types() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Comment Types Admin', 'wp-comment-types' ); ?></h1>
	</div>
	<?php
}
