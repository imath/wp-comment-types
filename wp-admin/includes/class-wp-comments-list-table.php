<?php
/**
 * List Table API: WP_Comments_List_Table class
 *
 * @package WP\CommentTypes
 * @subpackage \wp-admin\includes
 * @since 1.0.0
 */

namespace WP\CommentTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core class used to implement displaying comment types in a list table.
 *
 * @since 1.0.0
 * @access private
 *
 * @see WP_List_Table
 */
class WP_Comments_List_Table extends \WP_List_Table {
	/**
	 * Comment type key.
	 *
	 * @since 1.0.0
	 * @var string $type
	 */
	public $type;

	public $checkbox = true;

	/**
	 * Number of comments per type and stati.
	 *
	 * @since 1.0.0
	 * @var object $num_comments
	 */
	public $num_comments = null;

	public $pending_count = array();

	public $extra_items;

	private $user_can;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @see WP_List_Table::__construct() for more information on default arguments.
	 *
	 * @global int $post_id
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		global $post_id;

		$post_id = 0;
		if ( isset( $_REQUEST['p'] ) ) {
			$post_id = absint( $_REQUEST['p'] );
		}

		$args = wp_parse_args(
			$args,
			array(
				'plural'   => 'comments',
				'singular' => 'comment',
				'ajax'     => true,
				'screen'   => null,
			)
		);

		parent::__construct( $args );

		$this->type = $this->screen->comment_type;
		if ( 'comment' === $this->type && get_option( 'show_avatars' ) ) {
			add_filter( 'comment_author', array( $this, 'floated_admin_avatar' ), 10, 2 );
		}
	}

	public function floated_admin_avatar( $name, $comment_ID ) {
		$comment = get_comment( $comment_ID );
		$avatar  = get_avatar( $comment, 32, 'mystery' );
		return "$avatar $name";
	}

	/**
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * @global int    $post_id
	 * @global string $comment_status
	 * @global string $search
	 * @global string $comment_type
	 */
	public function prepare_items() {
		global $post_id, $comment_status, $search;

		$comment_status = isset( $_REQUEST['comment_status'] ) ? $_REQUEST['comment_status'] : 'all';
		if ( ! in_array( $comment_status, array( 'all', 'mine', 'moderated', 'approved', 'spam', 'trash' ) ) ) {
			$comment_status = 'all';
		}

		$search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';

		$post_type = ( isset( $_REQUEST['post_type'] ) ) ? sanitize_key( $_REQUEST['post_type'] ) : '';

		$user_id = ( isset( $_REQUEST['user_id'] ) ) ? $_REQUEST['user_id'] : '';

		$orderby = ( isset( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : '';
		$order   = ( isset( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : '';

		$comments_per_page = $this->get_per_page( $comment_status );

		$doing_ajax = wp_doing_ajax();

		if ( isset( $_REQUEST['number'] ) ) {
			$number = (int) $_REQUEST['number'];
		} else {
			$number = $comments_per_page + min( 8, $comments_per_page ); // Grab a few extra
		}

		$page = $this->get_pagenum();

		if ( isset( $_REQUEST['start'] ) ) {
			$start = $_REQUEST['start'];
		} else {
			$start = ( $page - 1 ) * $comments_per_page;
		}

		if ( $doing_ajax && isset( $_REQUEST['offset'] ) ) {
			$start += $_REQUEST['offset'];
		}

		$status_map = array(
			'mine'      => '',
			'moderated' => 'hold',
			'approved'  => 'approve',
			'all'       => '',
		);

		$args = array(
			'status'    => isset( $status_map[ $comment_status ] ) ? $status_map[ $comment_status ] : $comment_status,
			'search'    => $search,
			'user_id'   => $user_id,
			'offset'    => $start,
			'number'    => $number,
			'post_id'   => $post_id,
			'type'      => $this->type,
			'orderby'   => $orderby,
			'order'     => $order,
			'post_type' => $post_type,
		);

		/**
		 * Filters the arguments for the comment query in the comments list table.
		 *
		 * @since 5.1.0
		 *
		 * @param array $args An array of get_comments() arguments.
		 */
		$args = apply_filters( 'comments_list_table_query_args', $args );

		$_comments = get_comments( $args );
		if ( is_array( $_comments ) ) {
			update_comment_cache( $_comments );

			$this->items       = array_slice( $_comments, 0, $comments_per_page );
			$this->extra_items = array_slice( $_comments, $comments_per_page );
		}

		$total_comments = get_comments(
			array_merge(
				$args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_comments,
				'per_page'    => $comments_per_page,
			)
		);
	}

	/**
	 * @param string $comment_status
	 * @return int
	 */
	public function get_per_page( $comment_status = 'all' ) {
		$comments_per_page = $this->get_items_per_page( 'edit_' . $this->_args['plural'] . '_per_page' );
		/**
		 * Filters the number of comments listed per page in the comments list table.
		 *
		 * @since 2.6.0
		 *
		 * @param int    $comments_per_page The number of comments to list per page.
		 * @param string $comment_status    The comment status name. Default 'All'.
		 */
		return apply_filters( 'comments_per_page', $comments_per_page, $comment_status );
	}

	/**
	 * @global string $comment_status
	 */
	public function no_items() {
		global $comment_status;

		if ( 'moderated' === $comment_status ) {
			printf( esc_html__( 'No %s awaiting moderation.', 'wp-comment-types' ), $this->_args['plural'] );
		} else {
			printf( esc_html__( 'No %s found.', 'wp-comment-types' ), $this->_args['plural'] );
		}
	}

	/**
	 * @global int $post_id
	 * @global string $comment_status
	 * @global string $comment_type
	 */
	protected function get_views() {
		global $post_id, $comment_status;

		$status_links = array();

		if ( $post_id ) {
			$this->num_comments = wp_count_comments( $post_id, $this->type );
		} else {
			$this->num_comments = wp_count_comments( 0, $this->type );
		}

		$stati = array(
			/* translators: %s: Number of comments. */
			'all'       => _nx_noop(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				'comments',
				'wp-comment-types'
			), // singular not used

			/* translators: %s: Number of comments. */
			'mine'      => _nx_noop(
				'Mine <span class="count">(%s)</span>',
				'Mine <span class="count">(%s)</span>',
				'comments',
				'wp-comment-types'
			),

			/* translators: %s: Number of comments. */
			'moderated' => _nx_noop(
				'Pending <span class="count">(%s)</span>',
				'Pending <span class="count">(%s)</span>',
				'comments',
				'wp-comment-types'
			),

			/* translators: %s: Number of comments. */
			'approved'  => _nx_noop(
				'Approved <span class="count">(%s)</span>',
				'Approved <span class="count">(%s)</span>',
				'comments',
				'wp-comment-types'
			),

			/* translators: %s: Number of comments. */
			'spam'      => _nx_noop(
				'Spam <span class="count">(%s)</span>',
				'Spam <span class="count">(%s)</span>',
				'comments',
				'wp-comment-types'
			),

			/* translators: %s: Number of comments. */
			'trash'     => _nx_noop(
				'Trash <span class="count">(%s)</span>',
				'Trash <span class="count">(%s)</span>',
				'comments',
				'wp-comment-types'
			),
		);

		if ( ! EMPTY_TRASH_DAYS ) {
			unset( $stati['trash'] );
		}

		$link = admin_url( 'edit-comments.php' );
		if ( $this->type ) {
			$link = add_query_arg( 'comment_type', $this->type, $link );
		}

		foreach ( $stati as $status => $label ) {
			$current_link_attributes = '';

			if ( $status === $comment_status ) {
				$current_link_attributes = ' class="current" aria-current="page"';
			}

			if ( 'mine' === $status ) {
				$current_user_id          = get_current_user_id();
				$this->num_comments->mine = get_comments(
					array(
						'post_id' => $post_id ? $post_id : 0,
						'user_id' => $current_user_id,
						'count'   => true,
					)
				);
				$link                     = add_query_arg( 'user_id', $current_user_id, $link );
			} else {
				$link = remove_query_arg( 'user_id', $link );
			}

			if ( ! isset( $this->num_comments->$status ) ) {
				$this->num_comments->$status = 10;
			}
			$link = add_query_arg( 'comment_status', $status, $link );
			if ( $post_id ) {
				$link = add_query_arg( 'p', absint( $post_id ), $link );
			}
			/*
			// I toyed with this, but decided against it. Leaving it in here in case anyone thinks it is a good idea. ~ Mark
			if ( !empty( $_REQUEST['s'] ) )
				$link = add_query_arg( 's', esc_attr( wp_unslash( $_REQUEST['s'] ) ), $link );
			*/
			$status_links[ $status ] = "<a href='$link'$current_link_attributes>" . sprintf(
				translate_nooped_plural( $label, $this->num_comments->$status ),
				sprintf(
					'<span class="%s-count">%s</span>',
					( 'moderated' === $status ) ? 'pending' : $status,
					number_format_i18n( $this->num_comments->$status )
				)
			) . '</a>';
		}

		// If some comments are awaiting moderation, get the number of pending comment types per post.
		if ( isset( $this->num_comments->moderated ) && $this->num_comments->moderated ) {
			$_comment_post_ids = array_unique( wp_list_pluck( $this->items, 'comment_post_ID' ) );

			$this->pending_count = get_pending_comments_num( $_comment_post_ids, $this->type );
		}

		/**
		 * Filters the comment status links.
		 *
		 * @since 2.5.0
		 * @since 5.1.0 The 'Mine' link was added.
		 *
		 * @param string[] $status_links An associative array of fully-formed comment status links. Includes 'All', 'Mine',
		 *                              'Pending', 'Approved', 'Spam', and 'Trash'.
		 */
		return apply_filters( 'comment_status_links', $status_links );
	}

	/**
	 * @global string $comment_status
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		global $comment_status;

		$actions = array();
		if ( in_array( $comment_status, array( 'all', 'approved' ) ) ) {
			$actions['unapprove'] = __( 'Unapprove', 'wp-comment-types' );
		}
		if ( in_array( $comment_status, array( 'all', 'moderated' ) ) ) {
			$actions['approve'] = __( 'Approve', 'wp-comment-types' );
		}
		if ( in_array( $comment_status, array( 'all', 'moderated', 'approved', 'trash' ) ) ) {
			$actions['spam'] = _x( 'Mark as Spam', 'comment', 'wp-comment-types' );
		}

		if ( 'trash' === $comment_status ) {
			$actions['untrash'] = __( 'Restore', 'wp-comment-types' );
		} elseif ( 'spam' === $comment_status ) {
			$actions['unspam'] = _x( 'Not Spam', 'comment', 'wp-comment-types' );
		}

		if ( in_array( $comment_status, array( 'trash', 'spam' ) ) || ! EMPTY_TRASH_DAYS ) {
			$actions['delete'] = __( 'Delete Permanently', 'wp-comment-types' );
		} else {
			$actions['trash'] = __( 'Move to Trash', 'wp-comment-types' );
		}

		return $actions;
	}

	/**
	 * @global string $comment_status
	 * @global string $comment_type
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		global $comment_status, $comment_type;
		static $has_items;

		if ( ! isset( $has_items ) ) {
			$has_items = $this->has_items();
		}
		?>
		<div class="alignleft actions">
			<?php
			if ( 'top' === $which ) {
				if ( 'comment' === $comment_type ) {
					?>
					<label class="screen-reader-text" for="filter-by-comment-type"><?php esc_html_e( 'Filter by comment type', 'wp-comment-types' ); ?></label>
					<select id="filter-by-comment-type" name="comment_type">
						<option value=""><?php _e( 'All comment types', 'wp-comment-types' ); ?></option>
						<?php
						/**
						 * Filters the comment types dropdown menu.
						 *
						 * @since 2.7.0
						 *
						 * @param string[] $comment_types An array of comment types. Accepts 'Comments', 'Pings'.
						 */
						$comment_types = admin_comment_types_dropdown();

						foreach ( $comment_types as $type => $label ) {
							echo "\t" . '<option value="' . esc_attr( $type ) . '"' . selected( $comment_type, $type, false ) . ">$label</option>\n";
						}
						?>
					</select>

					<?php
					/**
					 * Fires just before the Filter submit button for comment types.
					 *
					 * @since 3.5.0
					 */
					do_action( 'restrict_manage_comments' );
					submit_button( __( 'Filter', 'wp-comment-types' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
				}
			}

			if ( ( 'spam' === $comment_status || 'trash' === $comment_status ) && current_user_can( 'moderate_comments' ) && $has_items ) {
				wp_nonce_field( 'bulk-destroy', '_destroy_nonce' );
				$title = ( 'spam' === $comment_status ) ? esc_attr__( 'Empty Spam', 'wp-comment-types' ) : esc_attr__( 'Empty Trash', 'wp-comment-types' );
				submit_button( $title, 'apply', 'delete_all', false );
			}

			/**
			 * Fires after the Filter submit button for comment types.
			 *
			 * @since 2.5.0
			 *
			 * @param string $comment_status The comment status name. Default 'All'.
			 */
			do_action( 'manage_comments_nav', $comment_status );
			?>
		</div>
		<?php
	}

	/**
	 * @return string|false
	 */
	public function current_action() {
		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {
			return 'delete_all';
		}

		return parent::current_action();
	}

	/**
	 * @global int $post_id
	 *
	 * @return array
	 */
	public function get_columns() {
		global $post_id;

		$columns = array();

		if ( $this->checkbox ) {
			$columns['cb'] = '<input type="checkbox" />';
		}

		$columns['author']  = __( 'Author', 'wp-comment-types' );
		$columns['comment'] = ucfirst( $this->_args['singular'] );

		if ( ! $post_id ) {
			/* translators: Column name or table row header. */
			$columns['response'] = __( 'In Response To', 'wp-comment-types' );
		}

		$columns['date'] = _x( 'Submitted On', 'column name', 'wp-comment-types' );

		return $columns;
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'author'   => 'comment_author',
			'response' => 'comment_post_ID',
			'date'     => 'comment_date',
		);
	}

	/**
	 * Get the name of the default primary column.
	 *
	 * @since 4.3.0
	 *
	 * @return string Name of the default primary column, in this case, 'comment'.
	 */
	protected function get_default_primary_column_name() {
		return 'comment';
	}

	/**
	 * Displays the comments table.
	 *
	 * Overrides the parent display() method to render extra comments.
	 *
	 * @since 3.1.0
	 */
	public function display() {
		wp_nonce_field( 'fetch-list-' . get_class( $this ), '_ajax_fetch_list_nonce' );

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );

		?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
	<thead>
	<tr>
		<?php $this->print_column_headers(); ?>
	</tr>
	</thead>

	<tbody id="the-comment-list" data-wp-lists="list:comment">
		<?php $this->display_rows_or_placeholder(); ?>
	</tbody>

	<tbody id="the-extra-comment-list" data-wp-lists="list:comment" style="display: none;">
		<?php
			/*
			 * Back up the items to restore after printing the extra items markup.
			 * The extra items may be empty, which will prevent the table nav from displaying later.
			 */
			$items       = $this->items;
			$this->items = $this->extra_items;
			$this->display_rows_or_placeholder();
			$this->items = $items;
		?>
	</tbody>

	<tfoot>
	<tr>
		<?php $this->print_column_headers( false ); ?>
	</tr>
	</tfoot>

</table>
		<?php

		$this->display_tablenav( 'bottom' );
	}

	/**
	 * @global WP_Post    $post    Global post object.
	 * @global WP_Comment $comment Global comment object.
	 *
	 * @param WP_Comment $item
	 */
	public function single_row( $item ) {
		global $post, $comment;

		$comment = $item;

		$the_comment_class = wp_get_comment_status( $comment );
		if ( ! $the_comment_class ) {
			$the_comment_class = '';
		}
		$the_comment_class = join( ' ', get_comment_class( $the_comment_class, $comment, $comment->comment_post_ID ) );

		if ( $comment->comment_post_ID > 0 ) {
			$post = get_post( $comment->comment_post_ID );
		}
		$this->user_can = current_user_can( 'edit_comment', $comment->comment_ID );

		echo "<tr id='comment-$comment->comment_ID' class='$the_comment_class'>";
		$this->single_row_columns( $comment );
		echo "</tr>\n";

		unset( $GLOBALS['post'], $GLOBALS['comment'] );
	}

	/**
	 * Generate and display row actions links.
	 *
	 * @since 4.3.0
	 *
	 * @global string $comment_status Status for the current listed comments.
	 *
	 * @param WP_Comment $comment     The comment object.
	 * @param string     $column_name Current column name.
	 * @param string     $primary     Primary column name.
	 * @return string|void Comment row actions output.
	 */
	protected function handle_row_actions( $comment, $column_name, $primary ) {
		global $comment_status;

		if ( $primary !== $column_name ) {
			return '';
		}

		if ( ! $this->user_can ) {
			return;
		}

		$the_comment_status = wp_get_comment_status( $comment );

		$out = '';

		$del_nonce     = esc_html( '_wpnonce=' . wp_create_nonce( "delete-comment_$comment->comment_ID" ) );
		$approve_nonce = esc_html( '_wpnonce=' . wp_create_nonce( "approve-comment_$comment->comment_ID" ) );

		$url = "comment.php?c=$comment->comment_ID";

		$approve_url   = esc_url( $url . "&action=approvecomment&$approve_nonce" );
		$unapprove_url = esc_url( $url . "&action=unapprovecomment&$approve_nonce" );
		$spam_url      = esc_url( $url . "&action=spamcomment&$del_nonce" );
		$unspam_url    = esc_url( $url . "&action=unspamcomment&$del_nonce" );
		$trash_url     = esc_url( $url . "&action=trashcomment&$del_nonce" );
		$untrash_url   = esc_url( $url . "&action=untrashcomment&$del_nonce" );
		$delete_url    = esc_url( $url . "&action=deletecomment&$del_nonce" );

		// Preorder it: Approve | Reply | Quick Edit | Edit | Spam | Trash.
		$actions = array(
			'approve'   => '',
			'unapprove' => '',
			'reply'     => '',
			'quickedit' => '',
			'edit'      => '',
			'spam'      => '',
			'unspam'    => '',
			'trash'     => '',
			'untrash'   => '',
			'delete'    => '',
		);

		// Not looking at all comments.
		if ( $comment_status && 'all' != $comment_status ) {
			if ( 'approved' === $the_comment_status ) {
				$actions['unapprove'] = sprintf(
					'<a href="%s" data-wp-lists="%s" class="vim-u vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
					$unapprove_url,
					"delete:the-comment-list:comment-{$comment->comment_ID}:e7e7d3:action=dim-comment&amp;new=unapproved",
					esc_attr__( 'Unapprove this comment', 'wp-comment-types' ),
					__( 'Unapprove', 'wp-comment-types' )
				);
			} elseif ( 'unapproved' === $the_comment_status ) {
				$actions['approve'] = sprintf(
					'<a href="%s" data-wp-lists="%s" class="vim-a vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
					$approve_url,
					"delete:the-comment-list:comment-{$comment->comment_ID}:e7e7d3:action=dim-comment&amp;new=approved",
					esc_attr__( 'Approve this comment', 'wp-comment-types' ),
					__( 'Approve', 'wp-comment-types' )
				);
			}
		} else {
			$actions['approve'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-a aria-button-if-js" aria-label="%s">%s</a>',
				$approve_url,
				"dim:the-comment-list:comment-{$comment->comment_ID}:unapproved:e7e7d3:e7e7d3:new=approved",
				esc_attr__( 'Approve this comment', 'wp-comment-types' ),
				__( 'Approve', 'wp-comment-types' )
			);

			$actions['unapprove'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-u aria-button-if-js" aria-label="%s">%s</a>',
				$unapprove_url,
				"dim:the-comment-list:comment-{$comment->comment_ID}:unapproved:e7e7d3:e7e7d3:new=unapproved",
				esc_attr__( 'Unapprove this comment', 'wp-comment-types' ),
				__( 'Unapprove', 'wp-comment-types' )
			);
		}

		if ( 'spam' !== $the_comment_status ) {
			$actions['spam'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-s vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				$spam_url,
				"delete:the-comment-list:comment-{$comment->comment_ID}::spam=1",
				esc_attr__( 'Mark this comment as spam', 'wp-comment-types' ),
				/* translators: "Mark as spam" link. */
				_x( 'Spam', 'verb', 'wp-comment-types' )
			);
		} elseif ( 'spam' === $the_comment_status ) {
			$actions['unspam'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-z vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				$unspam_url,
				"delete:the-comment-list:comment-{$comment->comment_ID}:66cc66:unspam=1",
				esc_attr__( 'Restore this comment from the spam', 'wp-comment-types' ),
				_x( 'Not Spam', 'comment', 'wp-comment-types' )
			);
		}

		if ( 'trash' === $the_comment_status ) {
			$actions['untrash'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-z vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				$untrash_url,
				"delete:the-comment-list:comment-{$comment->comment_ID}:66cc66:untrash=1",
				esc_attr__( 'Restore this comment from the Trash', 'wp-comment-types' ),
				__( 'Restore', 'wp-comment-types' )
			);
		}

		if ( 'spam' === $the_comment_status || 'trash' === $the_comment_status || ! EMPTY_TRASH_DAYS ) {
			$actions['delete'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="delete vim-d vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				$delete_url,
				"delete:the-comment-list:comment-{$comment->comment_ID}::delete=1",
				esc_attr__( 'Delete this comment permanently', 'wp-comment-types' ),
				__( 'Delete Permanently', 'wp-comment-types' )
			);
		} else {
			$actions['trash'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="delete vim-d vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				$trash_url,
				"delete:the-comment-list:comment-{$comment->comment_ID}::trash=1",
				esc_attr__( 'Move this comment to the Trash', 'wp-comment-types' ),
				_x( 'Trash', 'verb', 'wp-comment-types' )
			);
		}

		if ( 'spam' !== $the_comment_status && 'trash' !== $the_comment_status ) {
			$actions['edit'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				"comment.php?action=editcomment&amp;c={$comment->comment_ID}",
				esc_attr__( 'Edit this comment', 'wp-comment-types' ),
				__( 'Edit', 'wp-comment-types' )
			);

			$format = '<button type="button" data-comment-id="%d" data-post-id="%d" data-action="%s" class="%s button-link" aria-expanded="false" aria-label="%s">%s</button>';

			$actions['quickedit'] = sprintf(
				$format,
				$comment->comment_ID,
				$comment->comment_post_ID,
				'edit',
				'vim-q comment-inline',
				esc_attr__( 'Quick edit this comment inline', 'wp-comment-types' ),
				__( 'Quick&nbsp;Edit', 'wp-comment-types' )
			);

			$actions['reply'] = sprintf(
				$format,
				$comment->comment_ID,
				$comment->comment_post_ID,
				'replyto',
				'vim-r comment-inline',
				esc_attr__( 'Reply to this comment', 'wp-comment-types' ),
				__( 'Reply', 'wp-comment-types' )
			);
		}

		/** This filter is documented in wp-admin/includes/dashboard.php */
		$actions = apply_filters( 'comment_row_actions', array_filter( $actions ), $comment );

		$i    = 0;
		$out .= '<div class="row-actions">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( ( ( 'approve' === $action || 'unapprove' === $action ) && 2 === $i ) || 1 === $i ) ? $sep = '' : $sep = ' | ';

			// Reply and quickedit need a hide-if-no-js span when not added with ajax
			if ( ( 'reply' === $action || 'quickedit' === $action ) && ! wp_doing_ajax() ) {
				$action .= ' hide-if-no-js';
			} elseif ( ( $action === 'untrash' && $the_comment_status === 'trash' ) || ( $action === 'unspam' && $the_comment_status === 'spam' ) ) {
				if ( '1' == get_comment_meta( $comment->comment_ID, '_wp_trash_meta_status', true ) ) {
					$action .= ' approve';
				} else {
					$action .= ' unapprove';
				}
			}

			$out .= "<span class='$action'>$sep$link</span>";
		}
		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'wp-comment-types' ) . '</span></button>';

		return $out;
	}

	/**
	 * @param WP_Comment $comment The comment object.
	 */
	public function column_cb( $comment ) {
		if ( $this->user_can ) {
			?>
		<label class="screen-reader-text" for="cb-select-<?php echo $comment->comment_ID; ?>"><?php _e( 'Select comment' ); ?></label>
		<input id="cb-select-<?php echo $comment->comment_ID; ?>" type="checkbox" name="delete_comments[]" value="<?php echo $comment->comment_ID; ?>" />
			<?php
		}
	}

	/**
	 * @param WP_Comment $comment The comment object.
	 */
	public function column_comment( $comment ) {
		echo '<div class="comment-author">';
			$this->column_author( $comment );
		echo '</div>';

		if ( $comment->comment_parent ) {
			$parent = get_comment( $comment->comment_parent );
			if ( $parent ) {
				$parent_link = esc_url( get_comment_link( $parent ) );
				$name        = get_comment_author( $parent );
				printf(
					/* translators: %s: Comment link. */
					__( 'In reply to %s.', 'wp-comment-types' ),
					'<a href="' . $parent_link . '">' . $name . '</a>'
				);
			}
		}

		comment_text( $comment );

		if ( $this->user_can ) {
			/** This filter is documented in wp-admin/includes/comment.php */
			$comment_content = apply_filters( 'comment_edit_pre', $comment->comment_content );
			?>
		<div id="inline-<?php echo $comment->comment_ID; ?>" class="hidden">
			<textarea class="comment" rows="1" cols="1"><?php echo esc_textarea( $comment_content ); ?></textarea>
			<div class="author-email"><?php echo esc_attr( $comment->comment_author_email ); ?></div>
			<div class="author"><?php echo esc_attr( $comment->comment_author ); ?></div>
			<div class="author-url"><?php echo esc_attr( $comment->comment_author_url ); ?></div>
			<div class="comment_status"><?php echo $comment->comment_approved; ?></div>
		</div>
			<?php
		}
	}

	/**
	 * @global string $comment_status
	 *
	 * @param WP_Comment $comment The comment object.
	 */
	public function column_author( $comment ) {
		global $comment_status;

		$author_url = get_comment_author_url( $comment );

		$author_url_display = untrailingslashit( preg_replace( '|^http(s)?://(www\.)?|i', '', $author_url ) );
		if ( strlen( $author_url_display ) > 50 ) {
			$author_url_display = wp_html_excerpt( $author_url_display, 49, '&hellip;' );
		}

		echo '<strong>';
		comment_author( $comment );
		echo '</strong><br />';
		if ( ! empty( $author_url_display ) ) {
			printf( '<a href="%s">%s</a><br />', esc_url( $author_url ), esc_html( $author_url_display ) );
		}

		if ( $this->user_can ) {
			if ( ! empty( $comment->comment_author_email ) ) {
				/** This filter is documented in wp-includes/comment-template.php */
				$email = apply_filters( 'comment_email', $comment->comment_author_email, $comment );

				if ( ! empty( $email ) && '@' !== $email ) {
					printf( '<a href="%1$s">%2$s</a><br />', esc_url( 'mailto:' . $email ), esc_html( $email ) );
				}
			}

			$author_ip = get_comment_author_IP( $comment );
			if ( $author_ip ) {
				$author_ip_url = add_query_arg(
					array(
						's'    => $author_ip,
						'mode' => 'detail',
					),
					admin_url( 'edit-comments.php' )
				);
				if ( 'spam' === $comment_status ) {
					$author_ip_url = add_query_arg( 'comment_status', 'spam', $author_ip_url );
				}
				printf( '<a href="%1$s">%2$s</a>', esc_url( $author_ip_url ), esc_html( $author_ip ) );
			}
		}
	}

	/**
	 * @param WP_Comment $comment The comment object.
	 */
	public function column_date( $comment ) {
		$submitted = sprintf(
			/* translators: 1: Comment date, 2: Comment time. */
			__( '%1$s at %2$s', 'wp-comment-types' ),
			/* translators: Comment date format. See https://secure.php.net/date */
			get_comment_date( __( 'Y/m/d', 'wp-comment-types' ), $comment ),
			/* translators: Comment time format. See https://secure.php.net/date */
			get_comment_date( __( 'g:i a', 'wp-comment-types' ), $comment )
		);

		echo '<div class="submitted-on">';
		if ( 'approved' === wp_get_comment_status( $comment ) && ! empty( $comment->comment_post_ID ) ) {
			printf(
				'<a href="%s">%s</a>',
				esc_url( get_comment_link( $comment ) ),
				$submitted
			);
		} else {
			echo $submitted;
		}
		echo '</div>';
	}

	/**
	 * @param WP_Comment $comment The comment object.
	 */
	public function column_response( $comment ) {
		$post = get_post();

		if ( ! $post ) {
			return;
		}

		if ( isset( $this->pending_count[ $post->ID ] ) ) {
			$pending_comments = $this->pending_count[ $post->ID ];
		} else {
			$_pending_count_temp              = get_pending_comments_num( array( $post->ID ), $this->type );
			$pending_comments                 = $_pending_count_temp[ $post->ID ];
			$this->pending_count[ $post->ID ] = $pending_comments;
		}

		if ( current_user_can( 'edit_post', $post->ID ) ) {
			$post_link  = "<a href='" . get_edit_post_link( $post->ID ) . "' class='comments-edit-item-link'>";
			$post_link .= esc_html( get_the_title( $post->ID ) ) . '</a>';
		} else {
			$post_link = esc_html( get_the_title( $post->ID ) );
		}

		echo '<div class="response-links">';
		if ( 'attachment' === $post->post_type ) {
			$thumb = wp_get_attachment_image( $post->ID, array( 80, 60 ), true );
			if ( $thumb ) {
				echo $thumb;
			}
		}
		echo $post_link;
		$post_type_object = get_post_type_object( $post->post_type );
		echo "<a href='" . get_permalink( $post->ID ) . "' class='comments-view-item-link'>" . $post_type_object->labels->view_item . '</a>';
		echo '<span class="post-com-count-wrapper post-com-count-', $post->ID, '">';

		add_filter( 'get_comments_number', array( $this, 'get_comments_number_by_type' ), 10, 2 );
		$this->comments_bubble( $post->ID, $pending_comments );
		remove_filter( 'get_comments_number', array( $this, 'get_comments_number_by_type' ), 10, 2 );

		echo '</span> ';
		echo '</div>';
	}

	/**
	 * Adapt the comment count bubble to the displayed type.
	 *
	 * @since 5.4.0
	 *
	 * @param string|int $count   A string representing the number of comments a post has, otherwise 0.
	 * @param int        $post_id The post ID.
	 */
	public function get_comments_number_by_type( $count, $post_id ) {
		$count = wp_count_comments( $post_id, $this->type );

		return $count->approved;
	}

	/**
	 * @param WP_Comment $comment     The comment object.
	 * @param string     $column_name The custom column's name.
	 */
	public function column_default( $comment, $column_name ) {
		/**
		 * Fires when the default column output is displayed for a single row.
		 *
		 * @since 2.8.0
		 *
		 * @param string $column_name         The custom column's name.
		 * @param int    $comment->comment_ID The custom column's unique ID number.
		 */
		do_action( 'manage_comments_custom_column', $column_name, $comment->comment_ID );
	}
}
