<?php
/**
 * Action and filter hooks.
 *
 * @package WP\CommentTypes
 * @subpackage \wp-includes\default-filters
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Comment types.
add_action( 'init', __NAMESPACE__ . '\create_initial_comment_types', 0 ); // highest priority.
