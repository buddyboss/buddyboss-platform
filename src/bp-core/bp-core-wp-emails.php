<?php
/**
 * BuddyPress WP emails.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_notify_postauthor' ) ) :
	/**
	 * Notify an author (and/or others) of a comment/trackback/pingback on a post.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int|WP_Comment $comment_id Comment ID or WP_Comment object.
	 * @param string         $deprecated Not used
	 * @return bool True on completion. False if no email addresses were specified.
	 */
	function wp_notify_postauthor( $comment_id, $deprecated = null ) {
		if ( null !== $deprecated ) {
			_deprecated_argument( __FUNCTION__, '3.8.0' );
		}

		$comment = get_comment( $comment_id );
		if ( empty( $comment ) || empty( $comment->comment_post_ID ) ) {
			return false;
		}

		$post   = get_post( $comment->comment_post_ID );
		$author = get_userdata( $post->post_author );

		// Who to notify? By default, just the post author, but others can be added.
		$emails = array();
		if ( $author ) {
			$emails[] = $author->user_email;
		}

		/**
		 * Filters the list of email addresses to receive a comment notification.
		 *
		 * By default, only post authors are notified of comments. This filter allows
		 * others to be added.
		 *
		 * @since BuddyPress 3.7.0
		 *
		 * @param array $emails     An array of email addresses to receive a comment notification.
		 * @param int   $comment_id The comment ID.
		 */
		$emails = apply_filters( 'comment_notification_recipients', $emails, $comment->comment_ID );
		$emails = array_filter( $emails );

		// If there are no addresses to send the comment to, bail.
		if ( ! count( $emails ) ) {
			return false;
		}

		// Facilitate unsetting below without knowing the keys.
		$emails = array_flip( $emails );

		/**
		 * Filters whether to notify comment authors of their comments on their own posts.
		 *
		 * By default, comment authors aren't notified of their comments on their own
		 * posts. This filter allows you to override that.
		 *
		 * @since BuddyPress 3.8.0
		 *
		 * @param bool $notify     Whether to notify the post author of their own comment.
		 *                         Default false.
		 * @param int  $comment_id The comment ID.
		 */
		$notify_author = apply_filters( 'comment_notification_notify_author', false, $comment->comment_ID );

		// The comment was left by the author
		if ( $author && ! $notify_author && $comment->user_id == $post->post_author ) {
			unset( $emails[ $author->user_email ] );
		}

		// The author moderated a comment on their own post
		if ( $author && ! $notify_author && $post->post_author == get_current_user_id() ) {
			unset( $emails[ $author->user_email ] );
		}

		// The post author is no longer a member of the blog
		if ( $author && ! $notify_author && ! user_can( $post->post_author, 'read_post', $post->ID ) ) {
			unset( $emails[ $author->user_email ] );
		}

		// If there's no email to send the comment to, bail, otherwise flip array back around for use below
		if ( ! count( $emails ) ) {
			return false;
		} else {
			$emails = array_flip( $emails );
		}

		$switched_locale = switch_to_locale( get_locale() );

		$comment_author_domain = @gethostbyaddr( $comment->comment_author_IP );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname        = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$comment_content = wp_specialchars_decode( $comment->comment_content );

		// Get customizer setting options
		$settings = bp_email_get_appearance_settings();

		switch ( $comment->comment_type ) {
			case 'trackback':
				$title          = __( 'added trackback on your post', 'buddyboss' );
				$footer_message = sprintf( __( '<a href="%s">Click here</a> to see all trackbacks on this post.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ) . '#comments' );
				$moderate_text  = __( 'Moderate this trackback: ', 'buddyboss' );

				/* translators: 1: blog name, 2: post title */
				$subject = sprintf( __( '[%1$s] Trackback: "%2$s"', 'buddyboss' ), $blogname, $post->post_title );
				break;
			case 'pingback':
				$title          = __( 'added pingback on your post', 'buddyboss' );
				$footer_message = sprintf( __( '<a href="%s">Click here</a> to see all pingbacks on this post.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ) . '#comments' );
				$moderate_text  = __( 'Moderate this pingback: ', 'buddyboss' );

				/* translators: 1: blog name, 2: post title */
				$subject = sprintf( __( '[%1$s] Pingback: "%2$s"', 'buddyboss' ), $blogname, $post->post_title );
				break;
			default: // Comments
				$title          = __( 'added comment on your post', 'buddyboss' );
				$footer_message = sprintf( __( '<a href="%s">Click here</a> to see all comments on this post.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ) . '#comments' );
				$moderate_text  = __( 'Moderate this comment: ', 'buddyboss' );

				/* translators: 1: blog name, 2: post title */
				$subject = sprintf( __( '[%1$s] Comment: "%2$s"', 'buddyboss' ), $blogname, $post->post_title );
				break;
		}

		ob_start();
		?>
		<p>
			<?php if ( ! empty( $comment->user_id ) ) { ?>
				<a href="<?php echo esc_attr( bp_core_get_user_domain( $comment->user_id ) ); ?>">
					<?php echo $comment->comment_author; ?>
				</a>
				<?php
			} else {
				echo $comment->comment_author;
			}
			?>
			<?php echo $title; ?>
			<a href="<?php echo get_permalink( $comment->comment_post_ID ); ?>"><?php echo $post->post_title; ?></a>
		</p>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<?php if ( ! empty( $comment->user_id ) ) { ?>
				<tr>
					<td align="center">
						<table cellpadding="0" cellspacing="0" border="0" width="100%">
							<tbody>
							<tr>
								<td valign="middle" width="65px" style="vertical-align: middle;">
									<a style="display: block; width: 47px;" href="<?php echo esc_attr( bp_core_get_user_domain( $comment->user_id ) ); ?>"
									   target="_blank" rel="nofollow">
										<?php
										$avatar_url = bp_core_fetch_avatar(
											array(
												'item_id' => $comment->user_id,
												'width'   => 100,
												'height'  => 100,
												'type'    => 'full',
												'html'    => false,
											)
										);
										?>
										<img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47"
											 style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;"
											 border="0">
									</a>
								</td>
								<td width="88%" style="vertical-align: middle;">
									<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;">
										<?php echo bp_core_get_user_displayname( $comment->user_id ); ?>
									</div>
								</td>
							</tr>
							</tbody>
						</table>
					</td>
				</tr>

				<tr>
					<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
				</tr>
			<?php } ?>

			<tr>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%"
						   style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						<tbody>
						<tr>
							<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
						</tr>
						<tr>
							<td align="center">
								<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
									<tbody>
									<tr>
										<td>
											<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
												<?php echo wpautop( $comment_content ); ?>
											</div>
										</td>
									</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td>
					<a href="<?php echo esc_url( get_comment_link( $comment ) ); ?>" target="_blank" rel="nofollow"
					   style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px;  min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding:8px; "><?php esc_html_e( 'Reply', 'buddyboss' ); ?></a>
				</td>
			</tr>

			<tr>
				<td>
					<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
						<p><?php echo $footer_message; ?></p>
						<?php
						$approve_comment = sprintf( __( '<a href="%s">Approve</a>', 'buddyboss' ), admin_url( "comment.php?action=approve&c={$comment_id}#wpbody-content" ) );
						$trash_comment   = '';
						$delete_comment  = '';
						$spam_comment    = '';

						if ( user_can( $post->post_author, 'edit_comment', $comment->comment_ID ) ) {
							if ( EMPTY_TRASH_DAYS ) {
								/* translators: Comment moderation. 1: Comment action URL */
								$trash_comment = sprintf( __( '<a href="%s">Trash</a>', 'buddyboss' ), admin_url( "comment.php?action=trash&c={$comment_id}#wpbody-content" ) );
							} else {
								/* translators: Comment moderation. 1: Comment action URL */
								$delete_comment = sprintf( __( '<a href="%s">Delete</a>', 'buddyboss' ), admin_url( "comment.php?action=delete&c={$comment_id}#wpbody-content" ) );
							}

							/* translators: Comment moderation. 1: Comment action URL */
							$spam_comment = sprintf( __( '<a href="%s">Spam</a>', 'buddyboss' ), admin_url( "comment.php?action=spam&c={$comment_id}#wpbody-content" ) );
						}
						?>
						<p>
							<?php
							echo $moderate_text . $approve_comment;
							if ( ! empty( $trash_comment ) ) {
								echo ', ' . $trash_comment;
							}

							if ( ! empty( $delete_comment ) ) {
								echo ', ' . $delete_comment;
							}

							if ( ! empty( $spam_comment ) ) {
								echo ', ' . $spam_comment;
							}
							?>
						</p>
					</div>
				</td>
			</tr>
		</table>

		<?php
		$notify_message = ob_get_clean();

		$wp_email = 'wordpress@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );

		if ( '' == $comment->comment_author ) {
			$from = "From: \"$blogname\" <$wp_email>";
			if ( '' != $comment->comment_author_email ) {
				$reply_to = "Reply-To: $comment->comment_author_email";
			}
		} else {
			$from = "From: \"$comment->comment_author\" <$wp_email>";
			if ( '' != $comment->comment_author_email ) {
				$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
			}
		}

		$message_headers = "$from\n"
						   . 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\"\n";

		if ( isset( $reply_to ) ) {
			$message_headers .= $reply_to . "\n";
		}

		/**
		 * Filters the comment notification email text.
		 *
		 * @since BuddyPress 1.5.2
		 *
		 * @param string $notify_message The comment notification email text.
		 * @param int    $comment_id     Comment ID.
		 */
		$notify_message = apply_filters( 'comment_notification_text', $notify_message, $comment->comment_ID );

		/**
		 * Filters the comment notification email subject.
		 *
		 * @since BuddyPress 1.5.2
		 *
		 * @param string $subject    The comment notification email subject.
		 * @param int    $comment_id Comment ID.
		 */
		$subject = apply_filters( 'comment_notification_subject', $subject, $comment->comment_ID );

		/**
		 * Filters the comment notification email headers.
		 *
		 * @since BuddyPress 1.5.2
		 *
		 * @param string $message_headers Headers for the comment notification email.
		 * @param int    $comment_id      Comment ID.
		 */
		$message_headers = apply_filters( 'comment_notification_headers', $message_headers, $comment->comment_ID );

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		foreach ( $emails as $email ) {
			$email_notify_message = bp_email_core_wp_get_template( $notify_message, get_user_by( 'email', $email ) );
			@wp_mail( $email, wp_specialchars_decode( $subject ), $email_notify_message, $message_headers );
		}

		remove_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		if ( $switched_locale ) {
			restore_previous_locale();
		}

		return true;
	}
endif;

if ( ! function_exists( 'wp_notify_moderator' ) ) :
	/**
	 * Notifies the moderator of the site about a new comment that is awaiting approval.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * Uses the {@see 'notify_moderator'} filter to determine whether the site moderator
	 * should be notified, overriding the site setting.
	 *
	 * @param int $comment_id Comment ID.
	 * @return true Always returns true.
	 */
	function wp_notify_moderator( $comment_id ) {
		global $wpdb;

		$maybe_notify = get_option( 'moderation_notify' );

		/**
		 * Filters whether to send the site moderator email notifications, overriding the site setting.
		 *
		 * @since BuddyPress 4.4.0
		 *
		 * @param bool $maybe_notify Whether to notify blog moderator.
		 * @param int  $comment_ID   The id of the comment for the notification.
		 */
		$maybe_notify = apply_filters( 'notify_moderator', $maybe_notify, $comment_id );

		if ( ! $maybe_notify ) {
			return true;
		}

		$comment = get_comment( $comment_id );
		$post    = get_post( $comment->comment_post_ID );
		$user    = get_userdata( $post->post_author );
		// Send to the administration and to the post author if the author can modify the comment.
		$emails = array( get_option( 'admin_email' ) );
		if ( $user && user_can( $user->ID, 'edit_comment', $comment_id ) && ! empty( $user->user_email ) ) {
			if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
				$emails[] = $user->user_email;
			}
		}

		$switched_locale = switch_to_locale( get_locale() );

		$comment_author_domain = @gethostbyaddr( $comment->comment_author_IP );
		$comments_waiting      = $wpdb->get_var( "SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'" );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname        = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$comment_content = wp_specialchars_decode( $comment->comment_content );

		// Get customizer setting options
		$settings = bp_email_get_appearance_settings();

		switch ( $comment->comment_type ) {
			case 'trackback':
				$title          = sprintf( __( 'added new trackback on the post <a href="%1$s">"%2$s"</a> is waiting for your approval.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ), $post->post_title );
				$footer_message = sprintf( __( '<a href="%s">Click here</a> to see all trackbacks on this post.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ) . '#comments' );
				$moderate_text  = __( 'Moderate this trackback: ', 'buddyboss' );

				break;
			case 'pingback':
				$title          = sprintf( __( 'added new pingback on the post <a href="%1$s">"%2$s"</a> is waiting for your approval.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ), $post->post_title );
				$footer_message = sprintf( __( '<a href="%s">Click here</a> to see all pingbacks on this post.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ) . '#comments' );
				$moderate_text  = __( 'Moderate this pingback: ', 'buddyboss' );

				break;
			default: // Comments
				$title          = sprintf( __( 'added new comment on the post <a href="%1$s">"%2$s"</a> is waiting for your approval.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ), $post->post_title );
				$footer_message = sprintf( __( '<a href="%s">Click here</a> to see all comments on this post.', 'buddyboss' ), get_permalink( $comment->comment_post_ID ) . '#comments' );
				$moderate_text  = __( 'Moderate this comment: ', 'buddyboss' );

				break;
		}

		ob_start();
		?>
		<p>
			<?php if ( ! empty( $comment->user_id ) ) { ?>
				<a href="<?php echo esc_attr( bp_core_get_user_domain( $comment->user_id ) ); ?>">
					<?php echo $comment->comment_author; ?>
				</a>
				<?php
			} else {
				echo $comment->comment_author;
			}
			?>
			<?php echo $title; ?>
		</p>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<?php if ( ! empty( $comment->user_id ) ) { ?>
				<tr>
					<td align="center">
						<table cellpadding="0" cellspacing="0" border="0" width="100%">
							<tbody>
							<tr>
								<td valign="middle" width="65px" style="vertical-align: middle;">
									<a style="display: block; width: 47px;" href="<?php echo esc_attr( bp_core_get_user_domain( $comment->user_id ) ); ?>"
									   target="_blank" rel="nofollow">
										<?php
										$avatar_url = bp_core_fetch_avatar(
											array(
												'item_id' => $comment->user_id,
												'width'   => 100,
												'height'  => 100,
												'type'    => 'full',
												'html'    => false,
											)
										);
										?>
										<img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47"
											 style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;"
											 border="0">
									</a>
								</td>
								<td width="88%" style="vertical-align: middle;">
									<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;">
										<?php echo bp_core_get_user_displayname( $comment->user_id ); ?>
									</div>
								</td>
							</tr>
							</tbody>
						</table>
					</td>
				</tr>

				<tr>
					<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
				</tr>
			<?php } ?>

			<tr>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%"
						   style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						<tbody>
						<tr>
							<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
						</tr>
						<tr>
							<td align="center">
								<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
									<tbody>
									<tr>
										<td>
											<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
												<?php echo wpautop( $comment_content ); ?>
											</div>
										</td>
									</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td>
					<a href="<?php echo get_comment_link( $comment ); ?>" target="_blank" rel="nofollow"
					   style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px; min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding: 8px;"><?php esc_html_e( 'Reply', 'buddyboss' ); ?></a>
				</td>
			</tr>

			<tr>
				<td>
					<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
						<p><?php echo $footer_message; ?></p>
						<?php
						$approve_comment = sprintf( __( '<a href="%s">Approve</a>', 'buddyboss' ), admin_url( "comment.php?action=approve&c={$comment_id}#wpbody-content" ) );
						$trash_comment   = '';
						$delete_comment  = '';

						if ( EMPTY_TRASH_DAYS ) {
							/* translators: Comment moderation. 1: Comment action URL */
							$trash_comment = sprintf( __( '<a href="%s">Trash</a>', 'buddyboss' ), admin_url( "comment.php?action=trash&c={$comment_id}#wpbody-content" ) );
						} else {
							/* translators: Comment moderation. 1: Comment action URL */
							$delete_comment = sprintf( __( '<a href="%s">Delete</a>', 'buddyboss' ), admin_url( "comment.php?action=delete&c={$comment_id}#wpbody-content" ) );
						}

						/* translators: Comment moderation. 1: Comment action URL */
						$spam_comment = sprintf( __( '<a href="%s">Spam</a>', 'buddyboss' ), admin_url( "comment.php?action=spam&c={$comment_id}#wpbody-content" ) );
						?>
						<p>
							<?php
							echo $moderate_text . $approve_comment;

							if ( ! empty( $trash_comment ) ) {
								echo ', ' . $trash_comment;
							}

							if ( ! empty( $delete_comment ) ) {
								echo ', ' . $delete_comment;
							}

							echo ', ' . $spam_comment;
							?>
						</p>
					</div>
				</td>
			</tr>
		</table>

		<?php
		$notify_message = ob_get_clean();

		/* translators: Comment moderation notification email subject. 1: Site name, 2: Post title */
		$subject         = sprintf( __( '[%1$s] Please moderate: "%2$s"', 'buddyboss' ), $blogname, $post->post_title );
		$message_headers = '';

		/**
		 * Filters the list of recipients for comment moderation emails.
		 *
		 * @since BuddyPress 3.7.0
		 *
		 * @param array $emails     List of email addresses to notify for comment moderation.
		 * @param int   $comment_id Comment ID.
		 */
		$emails = apply_filters( 'comment_moderation_recipients', $emails, $comment_id );

		/**
		 * Filters the comment moderation email text.
		 *
		 * @since BuddyPress 1.5.2
		 *
		 * @param string $notify_message Text of the comment moderation email.
		 * @param int    $comment_id     Comment ID.
		 */
		$notify_message = apply_filters( 'comment_moderation_text', $notify_message, $comment_id );

		/**
		 * Filters the comment moderation email subject.
		 *
		 * @since BuddyPress 1.5.2
		 *
		 * @param string $subject    Subject of the comment moderation email.
		 * @param int    $comment_id Comment ID.
		 */
		$subject = apply_filters( 'comment_moderation_subject', $subject, $comment_id );

		/**
		 * Filters the comment moderation email headers.
		 *
		 * @since BuddyPress 2.8.0
		 *
		 * @param string $message_headers Headers for the comment moderation email.
		 * @param int    $comment_id      Comment ID.
		 */
		$message_headers = apply_filters( 'comment_moderation_headers', $message_headers, $comment_id );

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		foreach ( $emails as $email ) {
			$email_notify_message = bp_email_core_wp_get_template( $notify_message, get_user_by( 'email', $email ) );
			@wp_mail( $email, wp_specialchars_decode( $subject ), $email_notify_message, $message_headers );
		}

		remove_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		if ( $switched_locale ) {
			restore_previous_locale();
		}

		return true;
	}
endif;

if ( ! function_exists( 'wp_password_change_notification' ) ) :
	/**
	 * Notify the blog admin of a user changing password, normally via email.
	 *
	 * @since BuddyPress 2.7.0
	 *
	 * @param WP_User $user User object.
	 */
	function wp_password_change_notification( $user ) {
		// send a copy of password change notification to the admin
		// but check to see if it's the admin whose password we're changing, and skip this
		if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
			/* translators: %s: user name */
			$message = '<p>' . sprintf( __( 'Password changed for user: %s', 'buddyboss' ), $user->user_login ) . '</p>';
			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

			$wp_password_change_notification_email = array(
				'to'      => get_option( 'admin_email' ),
				/* translators: Password change notification email subject. %s: Site title */
				'subject' => __( '[%s] Password Changed', 'buddyboss' ),
				'message' => $message,
				'headers' => '',
			);

			/**
			 * Filters the contents of the password change notification email sent to the site admin.
			 *
			 * @since BuddyPress 4.9.0
			 *
			 * @param array   $wp_password_change_notification_email {
			 *     Used to build wp_mail().
			 *
			 *     @type string $to      The intended recipient - site admin email address.
			 *     @type string $subject The subject of the email.
			 *     @type string $message The body of the email.
			 *     @type string $headers The headers of the email.
			 * }
			 * @param WP_User $user     User object for user whose password was changed.
			 * @param string  $blogname The site title.
			 */
			$wp_password_change_notification_email = apply_filters( 'wp_password_change_notification_email', $wp_password_change_notification_email, $user, $blogname );

			add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

			$wp_password_change_notification_email['message'] = bp_email_core_wp_get_template( $wp_password_change_notification_email['message'], $user );

			wp_mail(
				$wp_password_change_notification_email['to'],
				wp_specialchars_decode( sprintf( $wp_password_change_notification_email['subject'], $blogname ) ),
				$wp_password_change_notification_email['message'],
				$wp_password_change_notification_email['headers']
			);

			remove_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );
		}
	}
endif;

if ( ! function_exists( 'wp_new_user_notification' ) ) {
	/**
	 * Email login credentials to a newly-registered user.
	 *
	 * A new user registration notification is also sent to admin email.
	 *
	 * @since BuddyPress 2.0.0
	 * @since BuddyPress 4.3.0 The `$plaintext_pass` parameter was changed to `$notify`.
	 * @since BuddyPress 4.3.1 The `$plaintext_pass` parameter was deprecated. `$notify` added as a third parameter.
	 * @since BuddyPress 4.6.0 The `$notify` parameter accepts 'user' for sending notification only to the user created.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @global PasswordHash $wp_hasher Portable PHP password hashing framework instance.
	 *
	 * @param int    $user_id    User ID.
	 * @param null   $deprecated Not used (argument deprecated).
	 * @param string $notify     Optional. Type of notification that should happen. Accepts 'admin' or an empty
	 *                           string (admin only), 'user', or 'both' (admin and user). Default empty.
	 */
	function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
		if ( $deprecated !== null ) {
			_deprecated_argument( __FUNCTION__, '4.3.1' );
		}

		global $wp_hasher;
		$user = get_userdata( $user_id );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		if ( 'user' !== $notify ) {
			$switched_locale = switch_to_locale( get_locale() );

			/* translators: %s: site title */
			$message = '<p>' . sprintf( __( 'New user registration on your site %s:', 'buddyboss' ), $blogname ) . '</p>';
			/* translators: %s: user login */
			$message .= '<p>' . sprintf( __( 'Username: <b>%s</b>', 'buddyboss' ), $user->user_login ) . '</p>';
			/* translators: %s: user email address */
			$message .= '<p>' . sprintf( __( 'Email: <b>%s</b>', 'buddyboss' ), $user->user_email ) . '</p>';

			$wp_new_user_notification_email_admin = array(
				'to'      => get_option( 'admin_email' ),
				/* translators: Password change notification email subject. %s: Site title */
				'subject' => __( '[%s] New User Registration', 'buddyboss' ),
				'message' => $message,
				'headers' => '',
			);

			/**
			 * Filters the contents of the new user notification email sent to the site admin.
			 *
			 * @since BuddyPress 4.9.0
			 *
			 * @param array   $wp_new_user_notification_email {
			 *     Used to build wp_mail().
			 *
			 *     @type string $to      The intended recipient - site admin email address.
			 *     @type string $subject The subject of the email.
			 *     @type string $message The body of the email.
			 *     @type string $headers The headers of the email.
			 * }
			 * @param WP_User $user     User object for new user.
			 * @param string  $blogname The site title.
			 */
			$wp_new_user_notification_email_admin = apply_filters( 'wp_new_user_notification_email_admin', $wp_new_user_notification_email_admin, $user, $blogname );

			add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

			$wp_new_user_notification_email_admin['message'] = bp_email_core_wp_get_template( $wp_new_user_notification_email_admin['message'], $user );

			@wp_mail(
				$wp_new_user_notification_email_admin['to'],
				wp_specialchars_decode( sprintf( $wp_new_user_notification_email_admin['subject'], $blogname ) ),
				$wp_new_user_notification_email_admin['message'],
				$wp_new_user_notification_email_admin['headers']
			);

			remove_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}

		// `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notification.
		if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
			return;
		}

		// Generate something random for a password reset key.
		$key = wp_generate_password( 20, false );

		/** This action is documented in wp-login.php */
		do_action( 'retrieve_password_key', $user->user_login, $key );

		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}
		$hashed = time() . ':' . $wp_hasher->HashPassword( $key );

		$key_saved = wp_update_user(
			array(
				'ID'                  => $user->ID,
				'user_activation_key' => $hashed,
			)
		);

		$switched_locale = switch_to_locale( get_user_locale( $user ) );

		/* translators: %s: user login */
		$message  = '<p>' . sprintf( __( 'Username: %s', 'buddyboss' ), $user->user_login ) . '</p>';
		$message .= '<p>' . sprintf( __( 'To set your password <a href="%s">Click here</a>.', 'buddyboss' ), network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) ) . '</p>';
		$message .= wp_login_url();

		$wp_new_user_notification_email = array(
			'to'      => $user->user_email,
			/* translators: Password change notification email subject. %s: Site title */
			'subject' => __( '[%s] Your username and password info', 'buddyboss' ),
			'message' => $message,
			'headers' => '',
		);

		/**
		 * Filters the contents of the new user notification email sent to the new user.
		 *
		 * @since BuddyPress 4.9.0
		 *
		 * @param array   $wp_new_user_notification_email {
		 *     Used to build wp_mail().
		 *
		 *     @type string $to      The intended recipient - New user email address.
		 *     @type string $subject The subject of the email.
		 *     @type string $message The body of the email.
		 *     @type string $headers The headers of the email.
		 * }
		 * @param WP_User $user     User object for new user.
		 * @param string  $blogname The site title.
		 */
		$wp_new_user_notification_email = apply_filters( 'wp_new_user_notification_email', $wp_new_user_notification_email, $user, $blogname );

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		$wp_new_user_notification_email['message'] = bp_email_core_wp_get_template( $wp_new_user_notification_email['message'], $user );

		wp_mail(
			$wp_new_user_notification_email['to'],
			wp_specialchars_decode( sprintf( $wp_new_user_notification_email['subject'], $blogname ) ),
			$wp_new_user_notification_email['message'],
			$wp_new_user_notification_email['headers']
		);

		remove_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}
}

if ( ! function_exists( 'bp_email_retrieve_password_message' ) ) {
	/**
	 * Filters the message body of the password reset mail.
	 *
	 * If the filtered message is empty, the password reset email will not be sent.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string  $message    Default mail message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 */
	function bp_email_retrieve_password_message( $message, $key, $user_login, $user_data ) {

		if ( is_multisite() ) {
			$site_name = get_network()->site_name;
		} else {
			/*
			 * The blogname option is escaped with esc_html on the way into the database
			 * in sanitize_option we want to reverse this for the plain text arena of emails.
			 */
			$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		}

		$message = '<p>' . __( 'Someone has requested a password reset for the following account:', 'buddyboss' ) . '</p>';
		/* translators: %s: site name */
		$message .= '<p>' . sprintf( __( 'Site Name: <b>%s</b>', 'buddyboss' ), $site_name ) . '</p>';
		/* translators: %s: user login */
		$message .= '<p>' . sprintf( __( 'Username: <b>%s</b>', 'buddyboss' ), $user_login ) . '</p>';
		$message .= '<p>' . __( 'If this was a mistake, just ignore this email and nothing will happen.', 'buddyboss' ) . '</p>';
		$message .= '<p>' . sprintf( __( 'To reset your password <a href="%s">Click here</a>.', 'buddyboss' ), network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) . '</p>';

		$message = bp_email_core_wp_get_template( $message, $user_data );

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		if ( has_filter( 'wp_mail_content_type', 'pmpro_wp_mail_content_type' ) ) {
			remove_filter( 'wp_mail_content_type', 'pmpro_wp_mail_content_type' );
		}

		return $message;
	}

	add_filter( 'retrieve_password_message', 'bp_email_retrieve_password_message', 10, 4 );
}

if ( ! function_exists( 'bp_email_wp_password_change_email' ) ) {
	/**
	 * Filters the contents of the email sent when the user's password is changed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $pass_change_email {
	 *            Used to build wp_mail().
	 *            @type string $to      The intended recipients. Add emails in a comma separated string.
	 *            @type string $subject The subject of the email.
	 *            @type string $message The content of the email.
	 *                The following strings have a special meaning and will get replaced dynamically:
	 *                - ###USERNAME###    The current user's username.
	 *                - ###ADMIN_EMAIL### The admin email in case this was unexpected.
	 *                - ###EMAIL###       The user's email address.
	 *                - ###SITENAME###    The name of the site.
	 *                - ###SITEURL###     The URL to the site.
	 *            @type string $headers Headers. Add headers in a newline (\r\n) separated string.
	 *        }
	 * @param array $user     The original user array.
	 * @param array $userdata The updated user array.
	 *
	 * @return array $pass_change_email Password Change Email data.
	 */
	function bp_email_wp_password_change_email( $pass_change_email, $user, $userdata ) {

		if ( bb_enabled_legacy_email_preference() || is_admin() ) {

			/* translators: Do not translate USERNAME, ADMIN_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
			$pass_change_text  = '<p>' . __( 'Hi ###USERNAME###,', 'buddyboss' ) . '</p>';
			$pass_change_text .= '<p>' . __( 'This notice confirms that your password was changed on ###SITENAME###.', 'buddyboss' ) . '</p>';
			$pass_change_text .= '<p>' . __( 'If you did not change your password, please contact the Site Administrator at <br />###ADMIN_EMAIL###', 'buddyboss' ) . '</p>';
			$pass_change_text .= '<p>' . __( 'This email has been sent to ###EMAIL###', 'buddyboss' ) . '</p>';
			$pass_change_text .= '<p>' . __( 'Regards, <br />All at ###SITENAME### <br />###SITEURL###', 'buddyboss' ) . '</p>';

			$pass_change_email = array(
				'to'      => $user['user_email'],
				/* translators: User password change notification email subject. 1: Site name */
				'subject' => __( '[%s] Notice of Password Change', 'buddyboss' ),
				'message' => $pass_change_text,
				'headers' => '',
			);

			add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email.

			$pass_change_email['message'] = bp_email_core_wp_get_template( $pass_change_email['message'], $user );

		} else {
			/* translators: Do not translate USERNAME, ADMIN_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
			$pass_change_text = '';

			$pass_change_email = array(
				'to'      => '',
				'subject' => '',
				'message' => '',
				'headers' => '',
			);

			add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email.

			$pass_change_email['message'] = bp_email_core_wp_get_template( $pass_change_email['message'], $user );
		}

		return $pass_change_email;
	}

	add_filter( 'password_change_email', 'bp_email_wp_password_change_email', 10, 3 );
}

if ( ! function_exists( 'bp_email_wp_email_change_email' ) ) {
	/**
	 * Filters the contents of the email sent when the user's email is changed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $email_change_email {
	 *            Used to build wp_mail().
	 *            @type string $to      The intended recipients.
	 *            @type string $subject The subject of the email.
	 *            @type string $message The content of the email.
	 *                The following strings have a special meaning and will get replaced dynamically:
	 *                - ###USERNAME###    The current user's username.
	 *                - ###ADMIN_EMAIL### The admin email in case this was unexpected.
	 *                - ###NEW_EMAIL###   The new email address.
	 *                - ###EMAIL###       The old email address.
	 *                - ###SITENAME###    The name of the site.
	 *                - ###SITEURL###     The URL to the site.
	 *            @type string $headers Headers.
	 *        }
	 * @param array $user The original user array.
	 * @param array $userdata The updated user array.
	 *
	 * @return array $email_change_email Email change array
	 */
	function bp_email_wp_email_change_email( $email_change_email, $user, $userdata ) {

		/* translators: Do not translate USERNAME, ADMIN_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_change_text  = '<p>' . __( 'Hi ###USERNAME###,', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'This notice confirms that your email address on ###SITENAME### was changed to ###NEW_EMAIL###.', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'If you did not change your email, please contact the Site Administrator at <br/>###ADMIN_EMAIL###', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'This email has been sent to ###EMAIL###', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'Regards, <br />All at ###SITENAME### <br />###SITEURL###', 'buddyboss' ) . '</p>';

		$email_change_email = array(
			'to'      => $user['user_email'],
			/* translators: User email change notification email subject. 1: Site name */
			'subject' => __( '[%s] Notice of Email Change', 'buddyboss' ),
			'message' => $email_change_text,
			'headers' => '',
		);

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_change_email['message'] = bp_email_core_wp_get_template( $email_change_email['message'], $user );

		return $email_change_email;
	}

	add_filter( 'email_change_email', 'bp_email_wp_email_change_email', 10, 3 );
}

if ( ! function_exists( 'bp_email_wp_new_user_email_content' ) ) {
	/**
	 * Filters the text of the email sent when a change of user email address is attempted.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 * ###USERNAME###  The current user's username.
	 * ###ADMIN_URL### The link to click on to confirm the email change.
	 * ###EMAIL###     The new email.
	 * ###SITENAME###  The name of the site.
	 * ###SITEURL###   The URL to the site.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $email_text     Text in the email.
	 * @param array  $new_user_email {
	 *     Data relating to the new user email address.
	 *
	 *     @type string $hash     The secure hash used in the confirmation link URL.
	 *     @type string $newemail The proposed new email address.
	 * }
	 */
	function bp_email_wp_new_user_email_content( $email_text, $new_user_email ) {

		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return $email_text;
		}

		/* translators: Do not translate USERNAME, ADMIN_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_text  = '<p>' . __( 'Howdy ###USERNAME###,', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'You recently requested to have the email address on your account changed.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'If this is correct, please <a href="###ADMIN_URL###">click here</a> to change it.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'You can safely ignore and delete this email if you do not want to take this action.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'This email has been sent to ###EMAIL###', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'Regards, <br />All at ###SITENAME### <br />###SITEURL###', 'buddyboss' ) . '</p>';

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, $current_user );

		return $email_text;
	}

	add_filter( 'new_user_email_content', 'bp_email_wp_new_user_email_content', 10, 2 );
}

if ( ! function_exists( 'bp_email_wp_user_confirmed_action_email_content' ) ) {
	/**
	 * Filters the body of the user request confirmation email.
	 *
	 * The email is sent to an administrator when an user request is confirmed.
	 * The following strings have a special meaning and will get replaced dynamically:
	 *
	 * ###SITENAME###    The name of the site.
	 * ###USER_EMAIL###  The user email for the request.
	 * ###DESCRIPTION### Description of the action being performed so the user knows what the email is for.
	 * ###MANAGE_URL###  The URL to manage requests.
	 * ###SITEURL###     The URL to the site.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $email_text Text in the email.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $user_email  The email address confirming a request
	 *     @type string          $description Description of the action being performed so the user knows what the email is for.
	 *     @type string          $manage_url  The link to click manage privacy requests of this type.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 *     @type string          $admin_email The administrator email receiving the mail.
	 * }
	 */
	function bp_email_wp_user_confirmed_action_email_content( $email_text, $email_data ) {

		if ( ! empty( $email_data['admin_email'] ) ) {

			$email = $email_data['admin_email'];

			/* translators: Do not translate SITENAME, SITEURL; those are placeholders. */
			$email_text  = '<p>' . __( 'Howdy,', 'buddyboss' ) . '</p>';
			$email_text .= '<p>' . __( 'A user data privacy request has been confirmed on ###SITENAME###:', 'buddyboss' ) . '</p>';
			$email_text .= '<p>' . __( 'User: ###USER_EMAIL###', 'buddyboss' ) . '</p>';
			$email_text .= '<p>' . __( 'Request: ###DESCRIPTION###', 'buddyboss' ) . '</p>';
			$email_text .= '<p>' . __( 'You can view and manage these data privacy requests here:', 'buddyboss' ) . '</p>';
			$email_text .= '<p><a href="###MANAGE_URL###">' . __( '###MANAGE_URL###', 'buddyboss' ) . '</a></p>';
			$email_text .= '<p>' . __( 'Regards, <br />All at <a href="###SITEURL###">###SITENAME###</a> <br /><a href="###SITEURL###">###SITEURL###</a>', 'buddyboss' ) . '</p>';

		} else {
			if ( empty( $email_data['privacy_policy_url'] ) ) {
				/* translators: Do not translate SITENAME, SITEURL; those are placeholders. */
				$email_text  = '<p>' . __( 'Howdy,', 'buddyboss' ) . '</p>';
				$email_text .= '<p>' . __( 'Your request to erase your personal data on ###SITENAME### has been completed.', 'buddyboss' ) . '</p>';
				$email_text .= '<p>' . __( 'If you have any follow-up questions or concerns, please contact the site administrator.', 'buddyboss' ) . '</p>';
				$email_text .= '<p>' . __( 'Regards, <br />All at <a href="###SITEURL###">###SITENAME###</a> <br /><a href="###SITEURL###">###SITEURL###</a>', 'buddyboss' ) . '</p>';
			} else {
				/* translators: Do not translate SITENAME, SITEURL, PRIVACY_POLICY_URL; those are placeholders. */
				$email_text  = '<p>' . __( 'Howdy,', 'buddyboss' ) . '</p>';
				$email_text .= '<p>' . __( 'Your request to erase your personal data on ###SITENAME### has been completed.', 'buddyboss' ) . '</p>';
				$email_text .= '<p>' . __( 'If you have any follow-up questions or concerns, please contact the site administrator.', 'buddyboss' ) . '</p>';
				$email_text .= '<p>' . __( 'For more information, you can also read our privacy policy: ###PRIVACY_POLICY_URL###', 'buddyboss' ) . '</p>';
				$email_text .= '<p>' . __( 'Regards, <br />All at <a href="###SITEURL###">###SITENAME###</a> <br /><a href="###SITEURL###">###SITEURL###</a>', 'buddyboss' ) . '</p>';
			}

			$email = $email_data['user_email'];
		}

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, get_user_by( 'email', $email ) );

		return $email_text;
	}

	add_filter( 'user_confirmed_action_email_content', 'bp_email_wp_user_confirmed_action_email_content', 10, 2 );
}

if ( ! function_exists( 'bp_email_wp_user_request_action_email_content' ) ) {
	/**
	 * Filters the text of the email sent when an account action is attempted.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 *
	 * ###DESCRIPTION### Description of the action being performed so the user knows what the email is for.
	 * ###CONFIRM_URL### The link to click on to confirm the account action.
	 * ###SITENAME###    The name of the site.
	 * ###SITEURL###     The URL to the site.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $email_text Text in the email.
	 * @param array  $email_data {
	 *     Data relating to the account action email.
	 *
	 *     @type WP_User_Request $request     User request object.
	 *     @type string          $email       The email address this is being sent to.
	 *     @type string          $description Description of the action being performed so the user knows what the email is for.
	 *     @type string          $confirm_url The link to click on to confirm the account action.
	 *     @type string          $sitename    The site name sending the mail.
	 *     @type string          $siteurl     The site URL sending the mail.
	 * }
	 */
	function bp_email_wp_user_request_action_email_content( $email_text, $email_data ) {

		/* translators: Do not translate DESCRIPTION, CONFIRM_URL, SITENAME, SITEURL: those are placeholders. */
		$email_text  = '<p>' . __( 'Howdy,', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'A request has been made to perform the following action on your account: <br />###DESCRIPTION###', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'To confirm this, please click on the following link: <br /><a href="###CONFIRM_URL###">###CONFIRM_URL###</a>', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'You can safely ignore and delete this email if you do not want to take this action.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'Regards, <br />All at <a href="###SITEURL###">###SITENAME###</a> <br /><a href="###SITEURL###">###SITEURL###</a>', 'buddyboss' ) . '</p>';

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, get_user_by( 'email', $email_data['email'] ) );

		return $email_text;
	}

	add_filter( 'user_request_action_email_content', 'bp_email_wp_user_request_action_email_content', 10, 2 );
}

if ( ! function_exists( 'bp_email_wp_new_admin_email_content' ) ) {
	/**
	 * Filters the text of the email sent when a change of site admin email address is attempted.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 * ###USERNAME###  The current user's username.
	 * ###ADMIN_URL### The link to click on to confirm the email change.
	 * ###EMAIL###     The proposed new site admin email address.
	 * ###SITENAME###  The name of the site.
	 * ###SITEURL###   The URL to the site.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $email_text      Text in the email.
	 * @param array  $new_admin_email {
	 *     Data relating to the new site admin email address.
	 *
	 *     @type string $hash     The secure hash used in the confirmation link URL.
	 *     @type string $newemail The proposed new site admin email address.
	 * }
	 */
	function bp_email_wp_new_admin_email_content( $email_text, $new_admin_email ) {

		$admin_email = get_option( 'admin_email' );

		if ( ! is_email( $admin_email ) ) {
			return $email_text;
		}

		/* translators: Do not translate USERNAME, ADMIN_URL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_text  = '<p>' . __( 'Howdy ###USERNAME###,', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'You recently requested to have the administration email address on your site changed.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'If this is correct, please <a href="###ADMIN_URL###">click here</a> to change it.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'You can safely ignore and delete this email if you do not want to take this action.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'This email has been sent to ###EMAIL###', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'Regards, <br />All at ###SITENAME### <br />###SITEURL###', 'buddyboss' ) . '</p>';

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, get_user_by( 'email', $admin_email ) );

		return $email_text;
	}

	add_filter( 'new_admin_email_content', 'bp_email_wp_new_admin_email_content', 10, 2 );
}

/**
 * WPMU Emails Strat Here
 */
if ( ! function_exists( 'bp_email_wpmu_signup_blog_notification_email' ) ) {
	/**
	 * Filters the message content of the new blog notification email.
	 *
	 * Content should be formatted for transmission via wp_mail().
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $content    Content of the notification email.
	 * @param string $domain     Site domain.
	 * @param string $path       Site path.
	 * @param string $title      Site title.
	 * @param string $user_login User login name.
	 * @param string $user_email User email address.
	 * @param string $key        Activation key created in wpmu_signup_blog().
	 * @param array  $meta       Signup meta data. By default, contains the requested privacy setting and lang_id.
	 */
	function bp_email_wpmu_signup_blog_notification_email( $content, $domain, $path, $title, $user_login, $user_email, $key, $meta ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$content = bp_email_core_wp_get_template( $content, get_user_by( 'email', $user_email ) );

		return $content;
	}

	add_filter( 'wpmu_signup_blog_notification_email', 'bp_email_wpmu_signup_blog_notification_email', 10, 8 );
}

if ( ! function_exists( 'bp_email_wpmu_signup_user_notification_email' ) ) {
	/**
	 * Filters the content of the notification email for new user sign-up.
	 *
	 * Content should be formatted for transmission via wp_mail().
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $content    Content of the notification email.
	 * @param string $user_login User login name.
	 * @param string $user_email User email address.
	 * @param string $key        Activation key created in wpmu_signup_user().
	 * @param array  $meta       Signup meta data. Default empty array.
	 */
	function bp_email_wpmu_signup_user_notification_email( $content, $user_login, $user_email, $key, $meta ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email.

		add_filter(
			'wp_mail',
			function ( $args ) use ( $content, $key, $user_email ) {
				$args['message'] = sprintf(
					$content,
					site_url( "wp-activate.php?key=$key" )
				);
				$args['message'] = bp_email_core_wp_get_template( $args['message'], get_user_by( 'email', $user_email ) );

				return $args;
			}
		);

		return $content;
	}

	add_filter( 'wpmu_signup_user_notification_email', 'bp_email_wpmu_signup_user_notification_email', 999, 5 );
}

if ( ! function_exists( 'bp_email_newblog_notify_siteadmin' ) ) {
	/**
	 * Filters the message body of the new site activation email sent
	 * to the network administrator.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $msg Email body.
	 */
	function bp_email_newblog_notify_siteadmin( $msg ) {

		$email = get_site_option( 'admin_email' );
		if ( is_email( $email ) == false ) {
			return $msg;
		}

		$options_site_url = esc_url( network_admin_url( 'settings.php' ) );
		$blogname         = get_option( 'blogname' );
		$siteurl          = site_url();
		restore_current_blog();

		/* translators: New site notification email. */
		$msg  = '<p>' . sprintf( __( 'New Site: %s', 'buddyboss' ), $blogname ) . '</p>';
		$msg .= '<p>' . sprintf( __( 'URL: %s', 'buddyboss' ), $siteurl ) . '</p>';
		$msg .= '<p>' . sprintf( __( 'Remote IP address: %s', 'buddyboss' ), wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) . '</p>';
		$msg .= '<p>' . sprintf( __( 'Disable these notifications:%s', 'buddyboss' ), $options_site_url ) . '</p>';

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$msg = bp_email_core_wp_get_template( $msg, get_user_by( 'email', $email ) );

		return $msg;
	}

	add_filter( 'newblog_notify_siteadmin', 'bp_email_newblog_notify_siteadmin', 10 );
}

if ( ! function_exists( 'bp_email_newuser_notify_siteadmin' ) ) {
	/**
	 * Filters the message body of the new user activation email sent
	 * to the network administrator.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string  $msg  Email body.
	 * @param WP_User $user WP_User instance of the new user.
	 */
	function bp_email_newuser_notify_siteadmin( $msg, $user ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$msg = bp_email_core_wp_get_template( $msg, $user );

		return $msg;
	}

	add_filter( 'newuser_notify_siteadmin', 'bp_email_newuser_notify_siteadmin', 10, 2 );
}

if ( ! function_exists( 'bp_email_update_welcome_email' ) ) {
	/**
	 * Filters the content of the welcome email after site activation.
	 *
	 * Content should be formatted for transmission via wp_mail().
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $welcome_email Message body of the email.
	 * @param int    $blog_id       Blog ID.
	 * @param int    $user_id       User ID.
	 * @param string $password      User password.
	 * @param string $title         Site title.
	 * @param array  $meta          Signup meta data. By default, contains the requested privacy setting and lang_id.
	 */
	function bp_email_update_welcome_email( $welcome_email, $blog_id, $user_id, $password, $title, $meta ) {

		if ( $welcome_email == false ) {
			/* translators: Do not translate USERNAME, SITE_NAME, BLOG_URL, PASSWORD: those are placeholders. */
			$welcome_email  = '<p>' . __( 'Howdy USERNAME,', 'buddyboss' ) . '</p>';
			$welcome_email .= '<p>' . __( 'Your new SITE_NAME site has been successfully set up at: <br />BLOG_URL', 'buddyboss' ) . '</p>';
			$welcome_email .= '<p>' . __( 'You can log in to the administrator account with the following information:', 'buddyboss' ) . '</p>';
			$welcome_email .= '<p>' . __( 'Username: USERNAME', 'buddyboss' ) . '</p>';
			$welcome_email .= '<p>' . __( 'Password: PASSWORD', 'buddyboss' ) . '</p>';
			$welcome_email .= '<p>' . __( 'Log in here: BLOG_URLwp-login.php', 'buddyboss' ) . '</p>';
			$welcome_email .= '<p>' . __( 'We hope you enjoy your new site. Thanks! <br />--The Team @ SITE_NAME', 'buddyboss' ) . '</p>';
		}

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$welcome_email = bp_email_core_wp_get_template( $welcome_email, get_userdata( $user_id ) );

		return $welcome_email;
	}

	add_filter( 'update_welcome_email', 'bp_email_update_welcome_email', 10, 6 );
}

if ( ! function_exists( 'bp_email_update_welcome_user_email' ) ) {
	/**
	 * Filters the content of the welcome email after user activation.
	 *
	 * Content should be formatted for transmission via wp_mail().
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $welcome_email The message body of the account activation success email.
	 * @param int    $user_id       User ID.
	 * @param string $password      User password.
	 * @param array  $meta          Signup meta data. Default empty array.
	 */
	function bp_email_update_welcome_user_email( $welcome_email, $user_id, $password, $meta ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$welcome_email = bp_email_core_wp_get_template( $welcome_email, get_userdata( $user_id ) );

		return $welcome_email;
	}

	add_filter( 'update_welcome_user_email', 'bp_email_update_welcome_user_email', 10, 4 );
}

if ( ! function_exists( 'bp_email_new_network_admin_email_content' ) ) {
	/**
	 * Filters the text of the email sent when a change of network admin email address is attempted.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 * ###USERNAME###  The current user's username.
	 * ###ADMIN_URL### The link to click on to confirm the email change.
	 * ###EMAIL###     The proposed new network admin email address.
	 * ###SITENAME###  The name of the network.
	 * ###SITEURL###   The URL to the network.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $email_text      Text in the email.
	 * @param array  $new_admin_email {
	 *     Data relating to the new network admin email address.
	 *
	 *     @type string $hash     The secure hash used in the confirmation link URL.
	 *     @type string $newemail The proposed new network admin email address.
	 * }
	 */
	function bp_email_new_network_admin_email_content( $email_text, $new_admin_email ) {

		$current_user = wp_get_current_user();
		if ( empty( $current_user->ID ) ) {
			return $email_text;
		}

		/* translators: Do not translate USERNAME, ADMIN_URL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_text  = '<p>' . __( 'Howdy ###USERNAME###,', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'You recently requested to have the network admin email address on your network changed.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'If this is correct, please click on the following link to change it: <br />###ADMIN_URL###', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'You can safely ignore and delete this email if you do not want to take this action.', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'This email has been sent to ###EMAIL###', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'Regards, <br /> All at ###SITENAME### <br /> ###SITEURL###', 'buddyboss' ) . '</p>';

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, $current_user );

		return $email_text;
	}

	add_filter( 'new_network_admin_email_content', 'bp_email_new_network_admin_email_content', 10, 2 );
}

if ( ! function_exists( 'bp_email_network_admin_email_change_email' ) ) {
	/**
	 * Filters the contents of the email notification sent when the network admin email address is changed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array  $email_change_email {
	 *             Used to build wp_mail().
	 *
	 *            @type string $to      The intended recipient.
	 *            @type string $subject The subject of the email.
	 *            @type string $message The content of the email.
	 *                The following strings have a special meaning and will get replaced dynamically:
	 *                - ###OLD_EMAIL### The old network admin email address.
	 *                - ###NEW_EMAIL### The new network admin email address.
	 *                - ###SITENAME###  The name of the network.
	 *                - ###SITEURL###   The URL to the site.
	 *            @type string $headers Headers.
	 *        }
	 * @param string $old_email  The old network admin email address.
	 * @param string $new_email  The new network admin email address.
	 * @param int    $network_id ID of the network.
	 */
	function bp_email_network_admin_email_change_email( $email_change_email, $old_email, $new_email, $network_id ) {

		/* translators: Do not translate OLD_EMAIL, NEW_EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_change_text  = '<p>' . __( 'Hi,', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'This notice confirms that the network admin email address was changed on ###SITENAME###.', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'The new network admin email address is ###NEW_EMAIL###.', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'This email has been sent to ###OLD_EMAIL###', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'Regards, <br /> All at ###SITENAME### <br /> ###SITEURL###', 'buddyboss' ) . '</p>';

		$email_change_email = array(
			'to'      => $old_email,
			/* translators: Network admin email change notification email subject. %s: Network title */
			'subject' => __( '[%s] Notice of Network Admin Email Change', 'buddyboss' ),
			'message' => $email_change_text,
			'headers' => '',
		);

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_change_email = bp_email_core_wp_get_template( $email_change_email, get_user_by( 'email', $new_email ) );

		return $email_change_email;
	}

	add_filter( 'network_admin_email_change_email', 'bp_email_network_admin_email_change_email', 10, 4 );
}

if ( ! function_exists( 'bp_email_site_admin_email_change_email' ) ) {
	/**
	 * Filters the contents of the email notification sent when the site admin email address is changed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array  $email_change_email {
	 *             Used to build wp_mail().
	 *
	 *            @type string $to      The intended recipient.
	 *            @type string $subject The subject of the email.
	 *            @type string $message The content of the email.
	 *                The following strings have a special meaning and will get replaced dynamically:
	 *                - ###OLD_EMAIL### The old site admin email address.
	 *                - ###NEW_EMAIL### The new site admin email address.
	 *                - ###SITENAME###  The name of the site.
	 *                - ###SITEURL###   The URL to the site.
	 *            @type string $headers Headers.
	 *        }
	 * @param string $old_email The old site admin email address.
	 * @param string $new_email The new site admin email address.
	 */
	function bp_email_site_admin_email_change_email( $email_change_email, $old_email, $new_email ) {

		/* translators: Do not translate OLD_EMAIL, NEW_EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_change_text  = '<p>' . __( 'Hi,', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'This notice confirms that the admin email address was changed on ###SITENAME###.', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'The new admin email address is ###NEW_EMAIL###.', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'This email has been sent to ###OLD_EMAIL###', 'buddyboss' ) . '</p>';
		$email_change_text .= '<p>' . __( 'Regards, <br /> All at ###SITENAME### <br /> ###SITEURL###', 'buddyboss' ) . '</p>';

		$email_change_email = array(
			'to'      => $old_email,
			/* translators: Site admin email change notification email subject. %s: Site title */
			'subject' => __( '[%s] Notice of Admin Email Change', 'buddyboss' ),
			'message' => $email_change_text,
			'headers' => '',
		);

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_change_email = bp_email_core_wp_get_template( $email_change_email, get_user_by( 'email', $new_email ) );

		return $email_change_email;
	}

	add_filter( 'site_admin_email_change_email', 'bp_email_site_admin_email_change_email', 10, 3 );
}

if ( ! function_exists( 'bp_email_wp_privacy_personal_data_email_content' ) ) {

	/**
	 * Filters the text of the email sent with a personal data export file.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 * ###EXPIRATION###         The date when the URL will be automatically deleted.
	 * ###LINK###               URL of the personal data export file for the user.
	 * ###SITENAME###           The name of the site.
	 * ###SITEURL###            The URL to the site.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $email_text     Text in the email.
	 * @param int    $request_id     The request ID for this personal data export.
	 */
	function bp_email_wp_privacy_personal_data_email_content( $email_text, $request_id ) {
		// Get the request data.
		if ( function_exists( 'wp_get_user_request' ) ) {
			$request = wp_get_user_request( $request_id );
		} else {
			$request = wp_get_user_request_data( $request_id );
		}

		$email_text  = '<p>' . __( 'Howdy,', 'buddyboss' ) . '</p>';
		$email_text .= '<p>' . __( 'Your request for an export of personal data has been completed. You may download your personal data by clicking on the link below. For privacy and security, we will automatically delete the file on ###EXPIRATION###, so please download it before then.', 'buddyboss' ) . '</p>';
		$email_text .= '<p><a href="###LINK###">' . __( '###LINK###', 'buddyboss' ) . '</a></p>';
		$email_text .= '<p>' . __( 'Regards, <br /> All at <a href="###SITEURL###">###SITENAME###</a> <br /> <a href="###SITEURL###">###SITEURL###</a>', 'buddyboss' ) . '</p>';

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, get_user_by( 'email', $request->email ) );

		return $email_text;
	}

	add_filter( 'wp_privacy_personal_data_email_content', 'bp_email_wp_privacy_personal_data_email_content', 10, 2 );
}
