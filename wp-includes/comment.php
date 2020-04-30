<?php
/**
 * Comment functions.
 *
 * @package WP\CommentTypes
 * @subpackage \wp-includes\comment
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers support of certain features for a comment type.
 *
 * @since 1.0.0
 *
 * @param string       $comment_type The comment type for which to add the feature.
 * @param string|array $feature      The feature being added, accepts an array of
 *                                   feature strings or a single string.
 * @param mixed        ...$args      Optional extra arguments to pass along with certain features.
 */
function add_comment_type_support( $comment_type, $feature, ...$args ) {
	$wp_comment_type_features = instance()->comment_type_features;

	$features = (array) $feature;
	foreach ( $features as $feature ) {
		if ( $args ) {
			$wp_comment_type_features[ $comment_type ][ $feature ] = $args;
		} else {
			$wp_comment_type_features[ $comment_type ][ $feature ] = true;
		}
	}

	instance()->comment_type_features = $wp_comment_type_features;
}

/**
 * Remove support for a feature from a comment type.
 *
 * @since 1.0.0
 *
 * @param string $comment_type The comment type for which to remove the feature.
 * @param string $feature      The feature being removed.
 */
function remove_comment_type_support( $comment_type, $feature ) {
	$wp_comment_type_features = instance()->comment_type_features;

	unset( $wp_comment_type_features[ $comment_type ][ $feature ] );

	instance()->comment_type_features = $wp_comment_type_features;
}

/**
 * Get all the comment type features
 *
 * @since 1.0.0
 *
 * @param string $comment_type The comment type.
 * @return array Comment type supports list.
 */
function get_all_comment_type_supports( $comment_type ) {
	$wp_comment_type_features = instance()->comment_type_features;

	if ( isset( $wp_comment_type_features[ $comment_type ] ) ) {
		return $wp_comment_type_features[ $comment_type ];
	}

	return array();
}

/**
 * Check a comment type's support for a given feature.
 *
 * @since 1.0.0
 *
 * @param string $comment_type The comment type being checked.
 * @param string $feature   The feature being checked.
 * @return bool Whether the comment type supports the given feature.
 */
function comment_type_supports( $comment_type, $feature ) {
	$wp_comment_type_features = instance()->comment_type_features;

	return ( isset( $wp_comment_type_features[ $comment_type ][ $feature ] ) );
}

/**
 * Retrieves a list of comment type names that support a specific feature.
 *
 * @since 1.0.0
 *
 * @param array|string $feature  Single feature or an array of features the comment types should support.
 * @param string       $operator Optional. The logical operation to perform. 'or' means
 *                               only one element from the array needs to match; 'and'
 *                               means all elements must match; 'not' means no elements may
 *                               match. Default 'and'.
 * @return string[] A list of comment type names.
 */
function get_comment_types_by_support( $feature, $operator = 'and' ) {
	$wp_comment_type_features = instance()->comment_type_features;

	$features = array_fill_keys( (array) $feature, true );

	return array_keys( wp_filter_object_list( $wp_comment_type_features, $features, $operator ) );
}

/**
 * Registers a comment type.
 *
 * @since 1.0.0
 *
 * @param string $comment_type Comment type key. Must not exceed 20 characters and may
 *                             only contain lowercase alphanumeric characters, dashes,
 *                             and underscores. See `sanitize_key()`.
 * @param array  $args         Array or string of arguments for registering a comment type.
 *
 * @return WP_Comment_Type|WP_Error The registered comment type object on success,
 *                                  WP_Error object on failure.
 */
function register_comment_type( $comment_type, $args = array() ) {
	$wp_comment_types = instance()->comment_types;

	if ( ! is_array( $wp_comment_types ) ) {
		$wp_comment_types = array();
	}

	// Sanitize post type name.
	$comment_type = sanitize_key( $comment_type );

	if ( empty( $comment_type ) || strlen( $comment_type ) > 20 ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Comment type names must be between 1 and 20 characters in length.', 'wp-comment-types' ), '1.0.0' );
		return new WP_Error( 'comment_type_length_invalid', esc_html__( 'Comment type names must be between 1 and 20 characters in length.', 'wp-comment-types' ) );
	}

	$comment_type_object               = new WP_Comment_Type( $comment_type, $args );
	$wp_comment_types[ $comment_type ] = $comment_type_object;

	// Add comment's type supports.
	$comment_type_object->add_supports();

	// Update the global.
	instance()->comment_types = $wp_comment_types;

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
			'label'                     => __( 'Comments', 'wp-comment-types' ),
			'public'                    => true,
			'show_in_comments_dropdown' => true,
			'_builtin'                  => true, /* internal use only. don't use this when registering your own comment type. */
			'_edit_link'                => 'comment.php?comment=%d', /* internal use only. don't use this when registering your own comment type. */
			'delete_with_user'          => false,
			'supports'                  => array( 'avatar', 'editor', 'dashboard-widget' ),
			'show_in_rest'              => true,
			'rest_base'                 => 'comments',
			'rest_controller_class'     => 'WP_REST_Comments_Controller',
		)
	);

	register_comment_type(
		'pings',
		array(
			'label'                     => __( 'Pings' ),
			'labels'                    => array(
				'singular_name'   => _x( 'Ping', 'Comment singular name' ),
				'admin_menu_name' => _x( 'Pings', 'Comment screen main nav' ),
			),
			'public'                    => true,
			'show_in_comments_dropdown' => true,
			'_builtin'                  => true, /* internal use only. don't use this when registering your own comment type. */
			'_edit_link'                => 'comment.php?comment=%d', /* internal use only. don't use this when registering your own comment type. */
			'delete_with_user'          => false,
			'supports'                  => array( 'dashboard-widget' ),
			'show_in_rest'              => true,
			'rest_base'                 => 'comments',
			'rest_controller_class'     => 'WP_REST_Comments_Controller',
		)
	);

	register_comment_type(
		'trackback',
		array(
			'label'                     => __( 'Trackbacks', 'wp-comment-types' ),
			'labels'                    => array(
				'singular_name'   => _x( 'Trackback', 'Comment singular name', 'wp-comment-types' ),
				'admin_menu_name' => _x( 'Trackbacks', 'Comment screen main nav', 'wp-comment-types' ),
			),
			'public'                    => true,
			'show_in_comments_dropdown' => true,
			'_builtin'                  => true, /* internal use only. don't use this when registering your own comment type. */
			'_edit_link'                => 'comment.php?comment=%d', /* internal use only. don't use this when registering your own comment type. */
			'delete_with_user'          => false,
			'supports'                  => array( 'dashboard-widget' ),
			'show_in_rest'              => true,
			'rest_base'                 => 'comments',
			'rest_controller_class'     => 'WP_REST_Comments_Controller',
		)
	);
}

/**
 * Gets the comment type object.
 *
 * @since 1.0.0
 *
 * @param string $comment_type The comment type name.
 * @return WP_Comment_Type     The comment type object.
 */
function get_comment_type_object( $comment_type ) {
	$wp_comment_types = instance()->comment_types;

	if ( ! is_scalar( $comment_type ) || empty( $wp_comment_types[ $comment_type ] ) ) {
		return null;
	}

	return $wp_comment_types[ $comment_type ];
}

/**
 * Gets the comment type labels.
 *
 * @since 1.0.0
 *
 * @param WP_Comment_Type $comment_type_object The comment type object.
 * @return object                              The comment type labels.
 */
function get_comment_type_labels( $comment_type_object ) {
	$labels = (object) wp_parse_args(
		$comment_type_object->labels,
		array(
			'singular_name'      => _x( 'Comment', '`singular_name` comment type label', 'wp-comment-types' ),
			'admin_menu_name'    => _x( 'Comments', '`admin_menu_name` comment type label', 'wp-comment-types' ),
			/* translators: %s: Singular for the number of comments awaiting moderation. */
			'awaiting_mod_item'  => _x( '%s Comment in moderation', '`awaiting_mod_item` comment type label', 'wp-comment-types' ),
			/* translators: %s: Plural for the number of comments awaiting moderation. */
			'awaiting_mod_items' => _x( '%s Comments in moderation', '`awaiting_mod_items` comment type label', 'wp-comment-types' ),
			'not_found'          => _x( 'No comments found', '`not_found` comment type label', 'wp-comment-types' ),
			'no_awaiting_mod'    => _x( 'No comments awaiting moderation.', '`no_awaiting_mod` comment type label', 'wp-comment-types' ),
			'not_found_in_trash' => _x( 'No comments found in Trash.', '`not_found_in_trash` comment type label', 'wp-comment-types' ),
			'search_items'       => _x( 'Search Comments', '`search_items` comment type label', 'wp-comment-types' ),
		)
	);

	if ( ! isset( $labels->name ) ) {
		$labels->name = $comment_type_object->label;
	}

	if ( ! $labels->admin_menu_name ) {
		$labels->admin_menu_name = $comment_type_object->label;
	}

	return $labels;
}

/**
 * Checks if a comment type exists.
 *
 * @since 1.0.0
 *
 * @param string $comment_type The comment type name.
 * @return boolean             True if the comment type exists. False otherwise.
 */
function comment_type_exists( $comment_type ) {
	return (bool) get_comment_type_object( $comment_type );
}

/**
 * Gets all registered comment types.
 *
 * @since 1.0.0
 *
 * @see register_comment_type().
 *
 * @param array  $args     Optional. An array of key => value arguments to match against
 *                         the post type objects. Default empty array.
 * @param string $output   Optional. The type of output to return. Accepts post type 'names'
 *                         or 'objects'. Default 'names'.
 * @param string $operator Optional. The logical operation to perform. 'or' means only one
 *                         element from the array needs to match; 'and' means all elements
 *                         must match; 'not' means no elements may match. Default 'and'.
 * @return array           An array of post type names or objects.
 */
function get_comment_types( $args = array(), $output = 'names', $operator = 'and' ) {
	$wp_comment_types = instance()->comment_types;

	$field = false;
	if ( 'names' === $output ) {
		$field = 'name';
	}

	return wp_filter_object_list( $wp_comment_types, $args, $operator, $field );
}

/**
 * Retrieves the total comment counts for the whole site or a single post.
 *
 * Unlike wp_count_comments(), this function always returns the live comment counts without caching.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int   $post_id Optional. Restrict the comment counts to the given post. Default 0, which indicates that
 *                       comment counts for the whole site will be retrieved.
 * @param array $types Optional. Restrict comment counts to one or more specific types.
 *
 * @return array() {
 *     The number of comments keyed by their status.
 *
 *     @type int|string $approved            The number of approved comments.
 *     @type int|string $awaiting_moderation The number of comments awaiting moderation (a.k.a. pending).
 *     @type int|string $spam                The number of spam comments.
 *     @type int|string $trash               The number of trashed comments.
 *     @type int|string $post-trashed        The number of comments for posts that are in the trash.
 *     @type int        $total_comments      The total number of non-trashed comments, including spam.
 *     @type int        $all                 The total number of pending or approved comments.
 * }
 */
function get_comment_count( $post_id = 0, $types = array() ) {
	global $wpdb;

	$post_id = (int) $post_id;

	$sql_select   = array( 'comment_approved', 'COUNT( * ) AS total' );
	$select       = '';
	$sql_where    = array();
	$where        = '';
	$sql_group_by = array( 'comment_approved' );
	$group_by     = '';
	$in_types     = array();

	if ( $post_id > 0 ) {
		$sql_where[] = $wpdb->prepare( 'comment_post_ID = %d', $post_id );
	}

	if ( ! is_array( $types ) ) {
		$types = (array) $types;
	}

	if ( array_filter( $types ) ) {
		$in_types = array_intersect( $types, get_comment_types() );

		if ( $in_types ) {
			if ( 1 < count( $in_types ) ) {
				array_unshift( $sql_select, 'comment_type AS type' );
				$sql_group_by[] = 'comment_type';
			}

			$sql_where[] = sprintf( 'comment_type IN ( "%s" )', implode( '", "', array_map( 'sanitize_key', $in_types ) ) );
		}
	} else {
		$show_in_comments_dropdown_types = get_comment_types(
			array(
				'show_in_comments_dropdown' => true,
			)
		);

		$sql_where[] = sprintf( 'comment_type IN ( "%s" )', implode( '", "', array_map( 'sanitize_key', $show_in_comments_dropdown_types ) ) );
	}

	$select = implode( ', ', $sql_select );

	if ( $sql_where ) {
		$where = 'WHERE ' . implode( ' AND ', $sql_where );
	}

	$group_by = implode( ', ', $sql_group_by );

	// phpcs:disable
	$totals = (array) $wpdb->get_results(
		"
		SELECT {$select}
		FROM {$wpdb->comments}
		{$where}
		GROUP BY {$group_by}
	",
		ARRAY_A
	);
	// phpcs:enable

	$comment_count = array(
		'approved'            => 0,
		'awaiting_moderation' => 0,
		'spam'                => 0,
		'trash'               => 0,
		'post-trashed'        => 0,
		'total_comments'      => 0,
		'all'                 => 0,
	);

	if ( $types ) {
		$comment_types_count = array_fill_keys( $types, $comment_count );

		if ( 1 === count( $types ) ) {
			$type = reset( $types );
		}
	} else {
		$type                = 'any';
		$comment_types_count = array(
			$type => $comment_count,
		);
	}

	foreach ( $totals as $row ) {
		if ( isset( $row['type'] ) ) {
			$type = $row['type'];

			if ( '' === $type ) {
				$type = 'comment';
			}
		}

		switch ( $row['comment_approved'] ) {
			case 'trash':
				$comment_types_count[ $type ]['trash'] = $row['total'];
				break;
			case 'post-trashed':
				$comment_types_count[ $type ]['post-trashed'] = $row['total'];
				break;
			case 'spam':
				$comment_types_count[ $type ]['spam']            = $row['total'];
				$comment_types_count[ $type ]['total_comments'] += $row['total'];
				break;
			case '1':
				$comment_types_count[ $type ]['approved']        = $row['total'];
				$comment_types_count[ $type ]['total_comments'] += $row['total'];
				$comment_types_count[ $type ]['all']            += $row['total'];
				break;
			case '0':
				$comment_types_count[ $type ]['awaiting_moderation'] = $row['total'];
				$comment_types_count[ $type ]['total_comments']     += $row['total'];
				$comment_types_count[ $type ]['all']                += $row['total'];
				break;
			default:
				break;
		}
	}

	if ( 3 !== count( $sql_select ) ) {
		$comment_types_count = reset( $comment_types_count );
	}

	return $comment_types_count;
}

/**
 * Retrieves the total comment counts for the whole site or a single post.
 *
 * The comment stats are cached and then retrieved, if they already exist in the
 * cache.
 *
 * @see get_comment_count() Which handles fetching the live comment counts.
 *
 * @since 1.0.0
 *
 * @param int    $post_id Optional. Restrict the comment counts to the given post. Default 0, which indicates that
 *                        comment counts for the whole site will be retrieved.
 * @param string $type Optional. Restrict comment counts to a specific type.
 *
 * @return stdClass {
 *     The number of comments keyed by their status.
 *
 *     @type int|string $approved       The number of approved comments.
 *     @type int|string $moderated      The number of comments awaiting moderation (a.k.a. pending).
 *     @type int|string $spam           The number of spam comments.
 *     @type int|string $trash          The number of trashed comments.
 *     @type int|string $post-trashed   The number of comments for posts that are in the trash.
 *     @type int        $total_comments The total number of non-trashed comments, including spam.
 *     @type int        $all            The total number of pending or approved comments.
 * }
 */
function wp_count_comments( $post_id = 0, $type = '' ) {
	$post_id = (int) $post_id;

	/**
	 * Filters the comments count for a given post or the whole site.
	 *
	 * @since 2.7.0
	 * @since ? Adds the $type parameter.
	 *
	 * @param array|stdClass $count   An empty array or an object containing comment counts.
	 * @param int            $post_id The post ID. Can be 0 to represent the whole site.
	 * @param string         $type    The comment type. Empty string for all types.
	 */
	// phpcs:disable
	/*$filtered = apply_filters( 'wp_count_comments', array(), $post_id, $type );
	if ( ! empty( $filtered ) ) {
		return $filtered;
	}*/
	// phpcs:enable

	$cache_suffix = $post_id;
	if ( $type && in_array( $type, get_comment_types(), true ) ) {
		$cache_suffix .= '-' . $type;
	}

	$count = wp_cache_get( "comments-{$cache_suffix}", 'counts' );
	if ( false !== $count ) {
		return $count;
	}

	$stats              = get_comment_count( $post_id, $type );
	$stats['moderated'] = $stats['awaiting_moderation'];
	unset( $stats['awaiting_moderation'] );

	$stats_object = (object) $stats;
	wp_cache_set( "comments-{$cache_suffix}", $stats_object, 'counts' );

	return $stats_object;
}

/**
 * Retrieves the total comment counts for the given types.
 *
 * @since 1.0.0
 *
 * @param array $types The list of types needed to be counted.
 * @return array       An associative array of the count objects keyed according to their type.
 */
function wp_count_comment_types( $types = array() ) {
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
	$filtered = apply_filters( 'wp_count_comment_types', $counts, $types );
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
		$comments       = get_comment_count( 0, $to_count );

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
