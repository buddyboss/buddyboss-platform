<?php
/**
 * BuddyBoss Moderation Comment Class
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Comment.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Comment extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'comment';

	/**
	 * BP_Moderation_Comment constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {
		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );


		/**
		 * Moderation code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'wp_list_comments_args', array( $this, 'set_comments_callback' ), 10 );

		// button class.
		add_filter( 'bp_moderation_get_report_button_args', array( $this, 'update_button_args' ), 10, 3 );
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Comment', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Function to set the comment html callback
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args comment arguments.
	 *
	 * @return mixed
	 */
	public function set_comments_callback( $args ) {

		if ( bp_is_active( 'moderation' ) ) {
			$args['callback'] = array( $this, 'render_blocked_comment' );
		}

		return $args;
	}

	/**
	 * Function to override comment listing
	 *
	 * @param object $comment comment data.
	 * @param array  $args    comment options.
	 * @param int    $depth   comment depth.
	 */
	function render_blocked_comment( $comment, $args, $depth ) {

		if ( 'div' == $args['style'] ) {
			$tag       = 'div';
			$add_below = 'comment';
		} else {
			$tag       = 'li';
			$add_below = 'div-comment';
		}

		$is_user_blocked = $is_user_suspended = false;
		if ( bp_is_active( 'moderation' ) ) {
			$is_user_blocked   = bp_moderation_is_user_suspended( (int) $comment->user_id, true );
			$is_user_suspended = bp_moderation_is_user_suspended( (int) $comment->user_id );
		}
		?>

		<<?php echo $tag; ?><?php comment_class( $args['has_children'] ? 'parent' : '',
				$comment ); ?> id="comment-<?php comment_ID(); ?>">

		<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">

			<?php
			if ( ! $is_user_blocked ) {
				if ( 0 != $args['avatar_size'] ) {
					$user_link = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $comment->user_id ) : get_comment_author_url( $comment );
					?>
					<div class="comment-author vcard">
						<a href="<?php echo $user_link; ?>">
							<?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
						</a>
					</div>
				<?php } ?>

				<div class="comment-content-wrap">
					<div class="comment-meta comment-metadata">
						<?php printf( __( '%s', 'buddyboss-theme' ),
								sprintf( '<cite class="fn comment-author">%s</cite>',
										get_comment_author_link( $comment ) ) ); ?>
						<a class="comment-date" href="<?php echo esc_url( get_comment_link( $comment,
								$args ) ); ?>"><?php printf( __( '%1$s', 'buddyboss-theme' ),
									get_comment_date( '', $comment ),
									get_comment_time() ); ?></a>
					</div>

					<?php if ( '0' == $comment->comment_approved ) { ?>
						<p>
							<em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.',
										'buddyboss-theme' ); ?></em>
						</p>
					<?php } ?>

					<div class="comment-text">
						<?php
						comment_text( $comment,
								array_merge( $args,
										array(
												'add_below' => $add_below,
												'depth'     => $depth,
												'max_depth' => $args['max_depth'],
										) ) );
						?>
					</div>

					<footer class="comment-footer">
						<?php
						comment_reply_link( array_merge( $args,
								array(
										'add_below' => $add_below,
										'depth'     => $depth,
										'max_depth' => $args['max_depth'],
										'before'    => '',
										'after'     => '',
								) ) );
						?>

						<?php edit_comment_link( __( 'Edit', 'buddyboss-theme' ), '', '' ); ?>

						<?php
						echo bp_moderation_get_report_button( array(
								'id'                => 'comment_report',
								'component'         => 'moderation',
								'must_be_logged_in' => true,
								'button_attr'       => array(
										'data-bp-content-id'   => get_comment_ID(),
										'data-bp-content-type' => self::$moderation_type,
								),
						),
								true );
						?>
					</footer>
				</div>
				<?php
			} else {
				?>
				<div class="comment-author vcard">
					<?php echo get_avatar( 0 ); ?>
				</div>
				<div class="comment-content-wrap">
					<div class="comment-text">
						<?php if ( $is_user_suspended ) {
							esc_html_e( 'Content from suspended user.', 'buddyboss' );
						} elseif ( $is_user_blocked ) {
							esc_html_e( 'Content from blocked user.', 'buddyboss' );
						} else {
							esc_html_e( 'Blocked Content.', 'buddyboss' );
						} ?>
					</div>
				</div>
				<?php
			}
			?>
		</article>
		<?php
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $comment_id Comment id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $comment_id ) {
		$comment = get_comment( $comment_id );

		return ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
	}

	/**
	 * Get Content.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int  $comment_id comment id.
	 * @param bool $view_link  add view link
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $comment_id, $view_link = false ) {
		$comment = get_comment( $comment_id );

		$comment_content = ( ! empty( $comment->comment_content ) ) ? $comment->comment_content : '';

		if ( true === $view_link ) {
			$link = '<a href="' . esc_url( self::get_permalink( (int) $comment_id ) ) . '">' . esc_html__( 'View',
							'buddyboss' ) . '</a>';;

			$comment_content = ( ! empty( $comment_content ) ) ? $comment_content . ' ' . $link : $link;
		}

		return $comment_content;
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $comment_id comment id.
	 *
	 * @return string
	 */
	public static function get_permalink( $comment_id ) {
		$url = get_comment_link( $comment_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Function to modify the button class
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array  $button      Button args.
	 * @param string $item_type   Content type.
	 * @param string $is_reported Item reported.
	 *
	 * @return array
	 */
	public function update_button_args( $button, $item_type, $is_reported ) {

		if ( self::$moderation_type === $item_type ) {
			if ( $is_reported ) {
				$button['button_attr']['class'] = 'reported-content';
			} else {
				$button['button_attr']['class'] = 'report-content';
			}
		}

		return $button;
	}
}
