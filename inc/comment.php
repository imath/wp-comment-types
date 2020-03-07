<?php
/**
 * Comment functions
 *
 * @package WP\CommentTypes
 * @subpackage \inc\comment
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers a comment type.
 *
 * @since 1.0.0
 *
 * @param string $comment_type Comment type key. Must not exceed 20 characters and may
 *                             only contain lowercase alphanumeric characters, dashes,
 *                             and underscores. See `sanitize_key()`.
 * @param array $args {
 *     Array or string of arguments for registering a comment type.
 * }
 * @return WP_Comment_Type|WP_Error The registered comment type object on success,
 *                                  WP_Error object on failure.
 */
function register_comment_type( $comment_type, $args = array() ) {
	$wp_comment_types = instance()->comment_types;

	if ( ! is_array( $wp_comment_types ) ) {
		$wp_comment_types = array();
	}

	// Sanitize post type name
	$comment_type = sanitize_key( $comment_type );

	if ( empty( $comment_type ) || strlen( $comment_type ) > 20 ) {
		_doing_it_wrong( __FUNCTION__, __( 'Comment type names must be between 1 and 20 characters in length.' ), '5.4.0' );
		return new WP_Error( 'comment_type_length_invalid', __( 'Comment type names must be between 1 and 20 characters in length.' ) );
	}

	$comment_type_object                = new WP_Comment_Type( $comment_type, $args );
	$wp_comment_types[ $comment_type ] = $comment_type_object;

	/**
	 * Fires after a comment type is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string           $comment_type        Comment type.
	 * @param WP_Comment_Type $comment_type_object Arguments used to register the comment type.
	 */
	do_action( 'registered_comment_type', $comment_type, $comment_type_object );

	return $comment_type_object;
}

/**
 * Creates the initial comment types when 'init' action is fired.
 *
 * See {@see 'init'}.
 *
 * @since 1.0.0
 */
function create_initial_comment_types() {
	register_comment_type(
		'comment',
		array(
			'label'                 => __( 'Comments' ),
			'labels'                => array(
				'singular_name'  => _x( 'Comment', 'Comment singular name' ),
				'name_admin_nav' => _x( 'Comments', 'Comment screen main nav' ),
			),
			'public'                => true,
			'_builtin'              => true, /* internal use only. don't use this when registering your own comment type. */
			'_edit_link'            => 'comment.php?comment=%d', /* internal use only. don't use this when registering your own comment type. */
			'nav_position'          => 0,
			'delete_with_user'      => false,
			'supports'              => array( 'editor' ),
			'show_in_rest'          => true,
			'rest_base'             => 'comments',
			'rest_controller_class' => 'WP_REST_Comments_Controller',
		)
	);

	register_comment_type(
		'pingback',
		array(
			'label'                 => __( 'Pings' ),
			'labels'                => array(
				'singular_name'  => _x( 'Ping', 'Comment singular name' ),
				'name_admin_nav' => _x( 'Pings', 'Comment screen main nav' ),
			),
			'public'                => true,
			'_builtin'              => true, /* internal use only. don't use this when registering your own comment type. */
			'_edit_link'            => 'comment.php?comment=%d', /* internal use only. don't use this when registering your own comment type. */
			'nav_position'          => 1,
			'delete_with_user'      => false,
			'supports'              => array(),
			'show_in_rest'          => true,
			'rest_base'             => 'comments',
			'rest_controller_class' => 'WP_REST_Comments_Controller',
		)
	);

	register_comment_type(
		'trackback',
		array(
			'label'                 => __( 'Trackbacks' ),
			'labels'                => array(
				'singular_name'  => _x( 'Trackback', 'Comment singular name' ),
				'name_admin_nav' => _x( 'Trackbacks', 'Comment screen main nav' ),
			),
			'public'                => true,
			'_builtin'              => true, /* internal use only. don't use this when registering your own comment type. */
			'_edit_link'            => 'comment.php?comment=%d', /* internal use only. don't use this when registering your own comment type. */
			'nav_position'          => 2,
			'delete_with_user'      => false,
			'supports'              => array(),
			'show_in_rest'          => true,
			'rest_base'             => 'comments',
			'rest_controller_class' => 'WP_REST_Comments_Controller',
		)
	);
}

function get_comment_type_object( $comment_type ) {
	$wp_comment_types = instance()->comment_types;

	if ( ! is_scalar( $comment_type ) || empty( $wp_comment_types[ $comment_type ] ) ) {
		return null;
	}

	return $wp_comment_types[ $comment_type ];
}

function get_comment_type_labels( $comment_type_object ) {
	$labels = (object) wp_parse_args( $comment_type_object->labels, array() );

	if ( ! isset( $labels->name ) ) {
		$labels->name = $comment_type_object->name;
	}

	if ( ! isset( $labels->name_admin_nav ) ) {
		$labels->name_admin_nav = $comment_type_object->name;
	}

	if ( ! isset( $labels->awaiting_mod_item ) ) {
		/* translators: %s: Singular for the number of comments awaiting moderation. */
		$labels->awaiting_mod_item = _x( '%s Comment in moderation', 'Comment Administration screen navigation' );
	}

	if ( ! isset( $labels->awaiting_mod_items ) ) {
		/* translators: %s: Plural for the number of comments awaiting moderation. */
		$labels->awaiting_mod_items = _x( '%s Comments in moderation', 'Comment Administration screen navigation' );
	}

	return $labels;
}

function comment_type_exists( $comment_type ) {
	return (bool) get_comment_type_object( $comment_type );
}

function get_comment_types( $args = array(), $output = 'names', $operator = 'and' ) {
	$wp_comment_types = instance()->comment_types;

	$field = false;
	if ( 'names' === $output ) {
		$field = 'name';
	}

	return wp_filter_object_list( $wp_comment_types, $args, $operator, $field );
}

/**
 * Retrieves the total comment counts for the given types.
 *
 * @since 1.0.0
 *
 * @param array $types The list of types needed to be counted.
 * @return array       An associative array of the count objects keyed according to their type.
 */
function wp_count_comments( $types = array() ) {
	if ( ! $types ) {
		$types = get_comment_types();
	}

	$counts = array();

	/**
	 * Filters the comment counts for the given types.
	 *
	 * @since 1.0.0
	 *
	 * @param array $counts An empty array or the array containing comment counts.
	 * @param array $types  The list of comment type's names.
	 */
	$filtered = apply_filters( 'wp_count_comments', $counts, $types );
	if ( ! empty( $filtered ) ) {
		return $filtered;
	}

	// Used to store the types to count.
	$to_count = array();

	// Try to get the count from cache.
	foreach ( $types as $name ) {
		$counts[ $name ] = wp_cache_get( "comments-0-{$name}", 'counts' );

		if ( ! $counts[ $name ] ) {
			$to_count[] = $name;
		}
	}

	// If some counts are not available perform the count.
	if ( $to_count ) {
		$uncache_counts = array();
		$comments      = get_comment_count( 0, $to_count );

		if ( 1 === count( $to_count ) ) {
			$type                    = reset( $to_count );
			$uncache_counts[ $type ] = $comments;
		} else {
			$uncache_counts = $comments;
		}

		foreach ( $uncache_counts as $t => $count ) {
			$count['moderated'] = $count['awaiting_moderation'];
			unset( $count['awaiting_moderation'] );

			$counts[ $t ] = (object) $count;
			wp_cache_set( "comments-0-{$t}", $counts[ $t ], 'counts' );
		}
	}

	return $counts;
}
