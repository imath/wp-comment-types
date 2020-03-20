<?php
/**
 * WordPress comment types feature (as a plugin).
 *
 * @package   WP\CommentTypes
 * @author    imath
 * @license   GPL-2.0+
 * @link      https://imathi.eu
 *
 * @wordpress-plugin
 * Plugin Name:       WP Comment Types
 * Plugin URI:        https://github.com/imath/wp-comment-types
 * Description:       WordPress comment types feature (as a plugin).
 * Version:           1.0.0
 * Author:            imath
 * Author URI:        https://imathi.eu
 * Text Domain:       wp-comment-types
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Class.
 *
 * @since 1.0.0
 */
final class WP_Comment_Types {
	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Plugin globals.
	 *
	 * @since 1.0.0
	 *
	 * @see inc/register_globals()
	 * @var array
	 */
	private $globals;

	/**
	 * Checks the existence of a specific global.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key to check the set status for.
	 * @return boolean
	 */
	public function __isset( $key ) {
		return isset( $this->globals[ $key ] );
	}

	/**
	 * Gets a specific global.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key to return the value for.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->globals[ $key ] ) ) {
			return $this->globals[ $key ];
		}

		return null;
	}

	/**
	 * Sets a specific global.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Key to set a value for.
	 * @param mixed  $value Value to set.
	 */
	public function __set( $key, $value ) {
		$this->globals[ $key ] = $value;
	}

	/**
	 * Unsets a specific global.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key to unset a value for.
	 */
	public function __unset( $key ) {
		if ( isset( $this->globals[ $key ] ) ) {
			unset( $this->globals[ $key ] );
		}
	}

	/**
	 * Prevents notices and errors from invalid method calls.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of the method.
	 * @param array  $args The arguments of the method.
	 * @return null
	 */
	public function __call( $name = '', $args = array() ) {
		unset( $name, $args );
		return null;
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->inc();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 */
	public static function start() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load needed files.
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		// Classes.
		spl_autoload_register( array( $this, 'autoload' ) );

		// Pathes.
		$inc_path    = plugin_dir_path( __FILE__ ) . 'inc/';
		$wp_inc_path = plugin_dir_path( __FILE__ ) . 'wp-includes/';

		if ( is_admin() ) {
			$wp_admin_path = plugin_dir_path( __FILE__ ) . 'wp-admin/';
		}

		// Includes.
		require $inc_path . 'globals.php';
		require $wp_inc_path . 'comment.php';

		if ( is_admin() ) {
			require $wp_admin_path . 'edit-comments.php';
			require $wp_admin_path . 'menu.php';
			require $wp_admin_path . 'includes/dashboard.php';
		}

		require $wp_inc_path . 'default-filters.php';
		require $inc_path . 'compat.php';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			require $inc_path . 'example.php';
		}
	}

	/**
	 * Class Autoloader.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $class The class name.
	 */
	public function autoload( $class ) {
		if ( 0 !== strpos( $class, 'WP\\CommentTypes\\' ) ) {
			return;
		}

		$name = str_replace(
			array( 'wp\\commenttypes\\', '_' ),
			array( '', '-' ),
			strtolower( $class )
		);

		$directories = array(
			'wp-comment-type'        => 'wp-includes',
			'wp-comments-list-table' => 'wp-admin/includes',
		);

		$directory = '';
		if ( isset( $directories[ $name ] ) ) {
			$directory = $directories[ $name ];
		} else {
			return;
		}

		$path = plugin_dir_path( __FILE__ ) . "{$directory}/class-{$name}.php";

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		require $path;
	}
}

/**
 * Starts the plugin.
 *
 * @since 1.0.0
 *
 * @return WP_Comment_Types The main instance of the plugin.
 */
function instance() {
	return WP_Comment_Types::start();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\instance', 9 );
