<?php
/**
 * Comment API: WP_Comment_Type class.
 *
 * @package WP\CommentTypes
 * @subpackage \wp-includes\WP_Comment_Type
 * @since 1.0.0
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class used for interacting with comment types.
 *
 * @since 1.0.0
 *
 * @see register_comment_type()
 */
final class WP_Comment_Type {
	/**
	 * Comment type key.
	 *
	 * @since 1.0.0
	 * @var string $name
	 */
	public $name;

	/**
	 * Name of the comment type shown in the navigation. Usually plural.
	 *
	 * @since 1.0.0
	 * @var string $label
	 */
	public $label;

	/**
	 * Labels object for this comment type.
	 *
	 * @see get_comment_type_labels()
	 *
	 * @since 1.0.0
	 * @var object $labels
	 */
	public $labels;

	/**
	 * Comment type plural key name.
	 *
	 * @since 1.0.0
	 * @var string $plural
	 */
	public $plural;

	/**
	 * Comment type singular key name.
	 *
	 * @since 1.0.0
	 * @var string $singular
	 */
	public $singular;

	/**
	 * A short descriptive summary of what the comment type is.
	 *
	 * Default empty.
	 *
	 * @since 1.0.0
	 * @var string $description
	 */
	public $description = '';

	/**
	 * Whether a comment type is intended for use publicly either via the admin interface or by front-end users.
	 *
	 * Default false.
	 *
	 * @since 1.0.0
	 * @var boolean $public
	 */
	public $public = false;

	/**
	 * Whether to generate and allow a UI for managing this comment type in the admin.
	 *
	 * Default is the value of $public.
	 *
	 * @since 1.0.0
	 * @var boolean $show_ui
	 */
	public $show_ui;

	/**
	 * The position in the menu order the comment type should appear.
	 *
	 * Default is `26` (under the Comments menu).
	 *
	 * @since 1.0.0
	 * @var integer $menu_position
	 */
	public $menu_position = 26;

	/**
	 * The url to the icon, a base64-encoded SVG using a data URI, or a Dashicons helper class to be used for this menu.
	 *
	 * Default is `dashicons-admin-comments`.
	 *
	 * @since 1.0.0
	 * @var string $menu_icon
	 */
	public $menu_icon = 'dashicons-admin-comments';

	/**
	 * Whether to include the comment type into the /wp-admin/edit-comments.php dropdown filter.
	 *
	 * Default is the value of $public.
	 *
	 * @since 1.0.0
	 * @var boolean $show_in_comments_dropdown
	 */
	public $show_in_comments_dropdown;

	/**
	 * Whether to delete comments of this type when deleting a user.
	 *
	 * @since 1.0.0
	 * @var boolean $delete_with_user
	 */
	public $delete_with_user = null;

	/**
	 * Whether this comment type is a native or "built-in" comment_type.
	 *
	 * Default false.
	 *
	 * @since 1.0.0
	 * @var boolean $_builtin
	 */
	public $_builtin = false; // phpcs:ignore

	/**
	 * URL segment to use for edit link of this comment type.
	 *
	 * Default 'comment.php?comment=%d'.
	 *
	 * @since 1.0.0
	 * @var string $_edit_link
	 */
	public $_edit_link = 'comment.php?comment=%d'; // phpcs:ignore

	/**
	 * Comment type capabilities.
	 *
	 * @since 1.0.0
	 * @var object $capabilities
	 */
	public $capabilities;

	/**
	 * The features supported by the comment type.
	 *
	 * @since 1.0.0
	 * @var array|boolean $supports
	 */
	public $supports;

	/**
	 * Whether this comment type should appear in the REST API.
	 *
	 * @since 1.0.0
	 * @var boolean $show_in_rest
	 */
	public $show_in_rest;

	/**
	 * The base path for this comment type's REST API endpoints.
	 *
	 * @since 1.0.0
	 * @var string|boolean $rest_base
	 */
	public $rest_base;

	/**
	 * The controller for this comment type's REST API endpoints.
	 *
	 * Custom controllers must extend WP_REST_Controller.
	 *
	 * @since 1.0.0
	 * @var string|boolean $rest_controller_class
	 */
	public $rest_controller_class;

	/**
	 * The controller instance for this comment type's REST API endpoints.
	 *
	 * Lazily computed. Should be accessed using {@see WP_Comment_Type::get_rest_controller()}.
	 *
	 * @since 1.0.0
	 * @var WP_REST_Controller $rest_controller
	 */
	public $rest_controller;

	/**
	 * Constructor.
	 *
	 * Will populate object properties from the provided arguments and assign other
	 * default properties based on that information.
	 *
	 * @since 1.0.0
	 *
	 * @see register_comment_type()
	 *
	 * @param string       $comment_type Comment type key.
	 * @param array|string $args         Optional. Array or string of arguments for registering a comment type.
	 *                                   Default empty array.
	 */
	public function __construct( $comment_type, $args = array() ) {
		$this->name = $comment_type;

		$this->set_props( $args );
	}

	/**
	 * Sets comment type properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $args Array or string of arguments for registering a comment type.
	 */
	public function set_props( $args ) {
		$args = wp_parse_args( $args );

		/**
		 * Filters the arguments for registering a comment type.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $args         Array of arguments for registering a comment type.
		 * @param string $comment_type Comment type key.
		 */
		$args = apply_filters( 'register_comment_type_args', $args, $this->name );

		$has_edit_link = ! empty( $args['_edit_link'] );

		// Args prefixed with an underscore are reserved for internal use.
		$defaults = array(
			'label'                     => '',
			'labels'                    => array(),
			'plural'                    => null,
			'singular'                  => null,
			'description'               => '',
			'public'                    => true,
			'show_ui'                   => null,
			'menu_position'             => null,
			'menu_icon'                 => null,
			'show_in_comments_dropdown' => false,
			'capabilities'              => array(),
			'supports'                  => array(),
			'delete_with_user'          => false,
			'show_in_rest'              => false,
			'rest_base'                 => false,
			'rest_controller_class'     => false,
			'_builtin'                  => false,
			'_edit_link'                => 'comment.php?comment=%d',
		);

		$args = array_merge( $defaults, $args );

		$args['name'] = $this->name;

		// If not set, default to the uppercased comment type key.
		if ( null === $args['label'] ) {
			$args['label'] = ucfirst( $this->name );
		}

		// If not set, default to the comment type key.
		if ( null === $args['singular'] ) {
			$args['singular'] = $this->name;
		}

		// If not set, default to the plural of the comment singular key name.
		if ( null === $args['plural'] ) {
			$args['plural'] = $args['singular'] . 's';
		}

		// If the comment type is included into the comment dropdown, do not generate a specific UI for it.
		if ( true === $args['show_in_comments_dropdown'] && 'comment' !== $this->name ) {
			$args['show_ui'] = false;
		}

		// If not set, default to the setting for public.
		if ( null === $args['show_ui'] ) {
			$args['show_ui'] = $args['public'];
		}

		if ( $args['show_ui'] ) {
			if ( null === $args['menu_position'] ) {
				$args['menu_position'] = 26;
			}

			if ( null === $args['menu_icon'] ) {
				$args['menu_icon'] = 'dashicons-admin-comments';
			}
		}

		// If there's no specified edit link and no UI, remove the edit link.
		if ( ! $args['show_ui'] && ! $has_edit_link ) {
			$args['_edit_link'] = '';
		}

		if ( ! $args['capabilities'] || ! is_array( $args['capabilities'] ) ) {
			$args['capabilities'] = array(
				'list_comment_type_items' => 'edit_posts',
			);
		}

		foreach ( $args as $property_name => $property_value ) {
			$this->$property_name = $property_value;
		}

		$this->labels = get_comment_type_labels( $this );
		$this->label  = $this->labels->name;
	}

	/**
	 * Sets the features support for the comment type.
	 *
	 * @since 1.0.0
	 */
	public function add_supports() {
		if ( ! empty( $this->supports ) ) {
			foreach ( $this->supports as $feature => $args ) {
				if ( is_array( $args ) ) {
					add_comment_type_support( $this->name, $feature, $args );
				} else {
					add_comment_type_support( $this->name, $args );
				}
			}

			unset( $this->supports );

		} elseif ( false !== $this->supports ) {
			// Add default features.
			add_comment_type_support( $this->name, array( 'avatar' ) );
		}
	}

	/**
	 * Gets the REST API controller for this comment type.
	 *
	 * Will only instantiate the controller class once per request.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_REST_Controller|null The controller instance, or null if the comment type
	 *                                 is set not to show in rest.
	 */
	public function get_rest_controller() {
		if ( ! $this->show_in_rest ) {
			return null;
		}

		$class = $this->rest_controller_class ? $this->rest_controller_class : WP_REST_Comments_Controller::class;

		if ( ! class_exists( $class ) ) {
			return null;
		}

		if ( ! is_subclass_of( $class, WP_REST_Controller::class ) ) {
			return null;
		}

		if ( ! $this->rest_controller ) {
			$this->rest_controller = new $class( $this->name );
		}

		if ( ! ( $this->rest_controller instanceof $class ) ) {
			return null;
		}

		return $this->rest_controller;
	}
}
