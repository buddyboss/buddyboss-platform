<?php
/**
 * BuddyPress Tokens for email.
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
/**
 * Set up the bp-core-email-tokens component.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_setup_core_email_tokens() {
	new BP_Email_Tokens();
}
add_action( 'bp_init', 'bp_setup_core_email_tokens', 0 );

/**
 * All generic email notifications for the WP
 */
function bp_email_set_content_type(){
	return "text/html";
}

function bp_email_core_wp_get_template( $content = '', $user = false ) {

	if ( ! $user ) {
		return $content;
	}

	ob_start();

	// Remove 'bp_replace_the_content' filter to prevent infinite loops.
	remove_filter( 'the_content', 'bp_replace_the_content' );

	set_query_var( 'email_content', $content );
	set_query_var( 'email_user', $user );
	bp_get_template_part( 'assets/emails/wp/email-template' );

	// Remove 'bp_replace_the_content' filter to prevent infinite loops.
	add_filter( 'the_content', 'bp_replace_the_content' );

	// Get the output buffer contents.
	$output = ob_get_clean();

	return $output;
}

if ( ! function_exists('wp_notify_postauthor') ) :
	/**
	 * Notify an author (and/or others) of a comment/trackback/pingback on a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int|WP_Comment  $comment_id Comment ID or WP_Comment object.
	 * @param string          $deprecated Not used
	 * @return bool True on completion. False if no email addresses were specified.
	 */
	function wp_notify_postauthor( $comment_id, $deprecated = null ) {
		if ( null !== $deprecated ) {
			_deprecated_argument( __FUNCTION__, '3.8.0' );
		}

		$comment = get_comment( $comment_id );
		if ( empty( $comment ) || empty( $comment->comment_post_ID ) )
			return false;

		$post    = get_post( $comment->comment_post_ID );
		$author  = get_userdata( $post->post_author );

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
		 * @since 3.7.0
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
		 * @since 3.8.0
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

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$comment_content = wp_specialchars_decode( $comment->comment_content );

		switch ( $comment->comment_type ) {
			case 'trackback':
				/* translators: 1: Post title */
				$notify_message  = sprintf( __( 'New trackback on your post "%s"' ), $post->post_title ) . "\r\n";
				/* translators: 1: Trackback/pingback website name, 2: website IP address, 3: website hostname */
				$notify_message .= sprintf( __('Website: %1$s (IP address: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
				$notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment_content ) . "\r\n\r\n";
				$notify_message .= __( 'You can see all trackbacks on this post here:' ) . "\r\n";
				/* translators: 1: blog name, 2: post title */
				$subject = sprintf( __('[%1$s] Trackback: "%2$s"'), $blogname, $post->post_title );
				break;
			case 'pingback':
				/* translators: 1: Post title */
				$notify_message  = sprintf( __( 'New pingback on your post "%s"' ), $post->post_title ) . "\r\n";
				/* translators: 1: Trackback/pingback website name, 2: website IP address, 3: website hostname */
				$notify_message .= sprintf( __('Website: %1$s (IP address: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
				$notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment_content ) . "\r\n\r\n";
				$notify_message .= __( 'You can see all pingbacks on this post here:' ) . "\r\n";
				/* translators: 1: blog name, 2: post title */
				$subject = sprintf( __('[%1$s] Pingback: "%2$s"'), $blogname, $post->post_title );
				break;
			default: // Comments
				$notify_message  = sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
				/* translators: 1: comment author, 2: comment author's IP address, 3: comment author's hostname */
				$notify_message .= sprintf( __( 'Author: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __( 'Email: %s' ), $comment->comment_author_email ) . "\r\n";
				$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
				$notify_message .= sprintf( __('Comment: %s' ), "\r\n" . $comment_content ) . "\r\n\r\n";
				$notify_message .= __( 'You can see all comments on this post here:' ) . "\r\n";
				/* translators: 1: blog name, 2: post title */
				$subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
				break;
		}
		$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
		$notify_message .= sprintf( __('Permalink: %s'), get_comment_link( $comment ) ) . "\r\n";

		if ( user_can( $post->post_author, 'edit_comment', $comment->comment_ID ) ) {
			if ( EMPTY_TRASH_DAYS ) {
				$notify_message .= sprintf( __( 'Trash it: %s' ), admin_url( "comment.php?action=trash&c={$comment->comment_ID}#wpbody-content" ) ) . "\r\n";
			} else {
				$notify_message .= sprintf( __( 'Delete it: %s' ), admin_url( "comment.php?action=delete&c={$comment->comment_ID}#wpbody-content" ) ) . "\r\n";
			}
			$notify_message .= sprintf( __( 'Spam it: %s' ), admin_url( "comment.php?action=spam&c={$comment->comment_ID}#wpbody-content" ) ) . "\r\n";
		}

		$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));

		if ( '' == $comment->comment_author ) {
			$from = "From: \"$blogname\" <$wp_email>";
			if ( '' != $comment->comment_author_email )
				$reply_to = "Reply-To: $comment->comment_author_email";
		} else {
			$from = "From: \"$comment->comment_author\" <$wp_email>";
			if ( '' != $comment->comment_author_email )
				$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
		}

		$message_headers = "$from\n"
		                   . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

		if ( isset($reply_to) )
			$message_headers .= $reply_to . "\n";

		/**
		 * Filters the comment notification email text.
		 *
		 * @since 1.5.2
		 *
		 * @param string $notify_message The comment notification email text.
		 * @param int    $comment_id     Comment ID.
		 */
		$notify_message = apply_filters( 'comment_notification_text', $notify_message, $comment->comment_ID );

		/**
		 * Filters the comment notification email subject.
		 *
		 * @since 1.5.2
		 *
		 * @param string $subject    The comment notification email subject.
		 * @param int    $comment_id Comment ID.
		 */
		$subject = apply_filters( 'comment_notification_subject', $subject, $comment->comment_ID );

		/**
		 * Filters the comment notification email headers.
		 *
		 * @since 1.5.2
		 *
		 * @param string $message_headers Headers for the comment notification email.
		 * @param int    $comment_id      Comment ID.
		 */
		$message_headers = apply_filters( 'comment_notification_headers', $message_headers, $comment->comment_ID );

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		foreach ( $emails as $email ) {
			$notify_message = bp_email_core_wp_get_template( $notify_message, get_user_by( 'email', $email ) );
			@wp_mail( $email, wp_specialchars_decode( $subject ), $notify_message, $message_headers );
		}

		remove_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		if ( $switched_locale ) {
			restore_previous_locale();
		}

		return true;
	}
endif;

if ( !function_exists('wp_notify_moderator') ) :
	/**
	 * Notifies the moderator of the site about a new comment that is awaiting approval.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * Uses the {@see 'notify_moderator'} filter to determine whether the site moderator
	 * should be notified, overriding the site setting.
	 *
	 * @param int $comment_id Comment ID.
	 * @return true Always returns true.
	 */
	function wp_notify_moderator($comment_id) {
		global $wpdb;

		$maybe_notify = get_option( 'moderation_notify' );

		/**
		 * Filters whether to send the site moderator email notifications, overriding the site setting.
		 *
		 * @since 4.4.0
		 *
		 * @param bool $maybe_notify Whether to notify blog moderator.
		 * @param int  $comment_ID   The id of the comment for the notification.
		 */
		$maybe_notify = apply_filters( 'notify_moderator', $maybe_notify, $comment_id );

		if ( ! $maybe_notify ) {
			return true;
		}

		$comment = get_comment($comment_id);
		$post = get_post($comment->comment_post_ID);
		$user = get_userdata( $post->post_author );
		// Send to the administration and to the post author if the author can modify the comment.
		$emails = array( get_option( 'admin_email' ) );
		if ( $user && user_can( $user->ID, 'edit_comment', $comment_id ) && ! empty( $user->user_email ) ) {
			if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) )
				$emails[] = $user->user_email;
		}

		$switched_locale = switch_to_locale( get_locale() );

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
		$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$comment_content = wp_specialchars_decode( $comment->comment_content );

		switch ( $comment->comment_type ) {
			case 'trackback':
				/* translators: 1: Post title */
				$notify_message  = sprintf( __('A new trackback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				/* translators: 1: Trackback/pingback website name, 2: website IP address, 3: website hostname */
				$notify_message .= sprintf( __( 'Website: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				/* translators: 1: Trackback/pingback/comment author URL */
				$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
				$notify_message .= __('Trackback excerpt: ') . "\r\n" . $comment_content . "\r\n\r\n";
				break;
			case 'pingback':
				/* translators: 1: Post title */
				$notify_message  = sprintf( __('A new pingback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				/* translators: 1: Trackback/pingback website name, 2: website IP address, 3: website hostname */
				$notify_message .= sprintf( __( 'Website: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				/* translators: 1: Trackback/pingback/comment author URL */
				$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
				$notify_message .= __('Pingback excerpt: ') . "\r\n" . $comment_content . "\r\n\r\n";
				break;
			default: // Comments
				/* translators: 1: Post title */
				$notify_message  = sprintf( __('A new comment on the post "%s" is waiting for your approval'), $post->post_title ) . "<br/>";
				$notify_message .= get_permalink($comment->comment_post_ID) . "<br/>";
				/* translators: 1: Comment author name, 2: comment author's IP address, 3: comment author's hostname */
				$notify_message .= sprintf( __( 'Author: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "<br/>";
				/* translators: 1: Comment author URL */
				$notify_message .= sprintf( __( 'Email: %s' ), $comment->comment_author_email ) . "<br/>";
				/* translators: 1: Trackback/pingback/comment author URL */
				$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "<br/>";
				/* translators: 1: Comment text */
				$notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment_content ) . "<br/>";
				break;
		}

		/* translators: Comment moderation. 1: Comment action URL */
		$notify_message .= sprintf( __( 'Approve it: %s' ), admin_url( "comment.php?action=approve&c={$comment_id}#wpbody-content" ) ) . "<br/>";

		if ( EMPTY_TRASH_DAYS ) {
			/* translators: Comment moderation. 1: Comment action URL */
			$notify_message .= sprintf( __( 'Trash it: %s' ), admin_url( "comment.php?action=trash&c={$comment_id}#wpbody-content" ) ) . "<br/>";
		} else {
			/* translators: Comment moderation. 1: Comment action URL */
			$notify_message .= sprintf( __( 'Delete it: %s' ), admin_url( "comment.php?action=delete&c={$comment_id}#wpbody-content" ) ) . "<br/>";
		}

		/* translators: Comment moderation. 1: Comment action URL */
		$notify_message .= sprintf( __( 'Spam it: %s' ), admin_url( "comment.php?action=spam&c={$comment_id}#wpbody-content" ) ) . "<br/>";

		/* translators: Comment moderation. 1: Number of comments awaiting approval */
		$notify_message .= sprintf( _n('Currently %s comment is waiting for approval. Please visit the moderation panel:',
				'Currently %s comments are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n($comments_waiting) ) . "<br/>";
		$notify_message .= admin_url( "edit-comments.php?comment_status=moderated#wpbody-content" ) . "<br/>";

		/* translators: Comment moderation notification email subject. 1: Site name, 2: Post title */
		$subject = sprintf( __('[%1$s] Please moderate: "%2$s"'), $blogname, $post->post_title );
		$message_headers = '';

		/**
		 * Filters the list of recipients for comment moderation emails.
		 *
		 * @since 3.7.0
		 *
		 * @param array $emails     List of email addresses to notify for comment moderation.
		 * @param int   $comment_id Comment ID.
		 */
		$emails = apply_filters( 'comment_moderation_recipients', $emails, $comment_id );

		/**
		 * Filters the comment moderation email text.
		 *
		 * @since 1.5.2
		 *
		 * @param string $notify_message Text of the comment moderation email.
		 * @param int    $comment_id     Comment ID.
		 */
		$notify_message = apply_filters( 'comment_moderation_text', $notify_message, $comment_id );

		/**
		 * Filters the comment moderation email subject.
		 *
		 * @since 1.5.2
		 *
		 * @param string $subject    Subject of the comment moderation email.
		 * @param int    $comment_id Comment ID.
		 */
		$subject = apply_filters( 'comment_moderation_subject', $subject, $comment_id );

		/**
		 * Filters the comment moderation email headers.
		 *
		 * @since 2.8.0
		 *
		 * @param string $message_headers Headers for the comment moderation email.
		 * @param int    $comment_id      Comment ID.
		 */
		$message_headers = apply_filters( 'comment_moderation_headers', $message_headers, $comment_id );

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		foreach ( $emails as $email ) {
			$notify_message = bp_email_core_wp_get_template( $notify_message, get_user_by( 'email', $email ) );
			@wp_mail( $email, wp_specialchars_decode( $subject ), $notify_message, $message_headers );
		}

		remove_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );

		if ( $switched_locale ) {
			restore_previous_locale();
		}

		return true;
	}
endif;


if ( !function_exists('wp_password_change_notification') ) :
	/**
	 * Notify the blog admin of a user changing password, normally via email.
	 *
	 * @since 2.7.0
	 *
	 * @param WP_User $user User object.
	 */
	function wp_password_change_notification( $user ) {
		// send a copy of password change notification to the admin
		// but check to see if it's the admin whose password we're changing, and skip this
		if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
			/* translators: %s: user name */
			$message = sprintf( __( 'Password changed for user: %s' ), $user->user_login ) . "\r\n";
			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

			$wp_password_change_notification_email = array(
				'to'      => get_option( 'admin_email' ),
				/* translators: Password change notification email subject. %s: Site title */
				'subject' => __( '[%s] Password Changed' ),
				'message' => $message,
				'headers' => '',
			);

			/**
			 * Filters the contents of the password change notification email sent to the site admin.
			 *
			 * @since 4.9.0
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

if ( !function_exists('wp_new_user_notification') ) {
	/**
	 * Email login credentials to a newly-registered user.
	 *
	 * A new user registration notification is also sent to admin email.
	 *
	 * @since 2.0.0
	 * @since 4.3.0 The `$plaintext_pass` parameter was changed to `$notify`.
	 * @since 4.3.1 The `$plaintext_pass` parameter was deprecated. `$notify` added as a third parameter.
	 * @since 4.6.0 The `$notify` parameter accepts 'user' for sending notification only to the user created.
	 *
	 * @global wpdb         $wpdb      WordPress database object for queries.
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

		global $wpdb, $wp_hasher;
		$user = get_userdata( $user_id );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		if ( 'user' !== $notify ) {
			$switched_locale = switch_to_locale( get_locale() );

			/* translators: %s: site title */
			$message  = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
			/* translators: %s: user login */
			$message .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
			/* translators: %s: user email address */
			$message .= sprintf( __( 'Email: %s' ), $user->user_email ) . "\r\n";

			$wp_new_user_notification_email_admin = array(
				'to'      => get_option( 'admin_email' ),
				/* translators: Password change notification email subject. %s: Site title */
				'subject' => __( '[%s] New User Registration' ),
				'message' => $message,
				'headers' => '',
			);

			/**
			 * Filters the contents of the new user notification email sent to the site admin.
			 *
			 * @since 4.9.0
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
		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

		$switched_locale = switch_to_locale( get_user_locale( $user ) );

		/* translators: %s: user login */
		$message = sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		$message .= __('To set your password, visit the following address:') . "\r\n\r\n";
		$message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";

		$message .= wp_login_url() . "\r\n";

		$wp_new_user_notification_email = array(
			'to'      => $user->user_email,
			/* translators: Password change notification email subject. %s: Site title */
			'subject' => __( '[%s] Your username and password info' ),
			'message' => $message,
			'headers' => '',
		);

		/**
		 * Filters the contents of the new user notification email sent to the new user.
		 *
		 * @since 4.9.0
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
	 * @since BuddyBoss 3.1.1
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

		$message = __( '<p>Someone has requested a password reset for the following account:</p>', 'buddyboss' );
		/* translators: %s: site name */
		$message .= sprintf( __( '<p>Site Name: <b>%s</b></p>', 'buddyboss' ), $site_name );
		/* translators: %s: user login */
		$message .= sprintf( __( '<p>Username: <b>%s</b></p>', 'buddyboss' ), $user_login );
		$message .= __( '<p>If this was a mistake, just ignore this email and nothing will happen.</p>', 'buddyboss'  );
		$message .= sprintf( __( '<p>To reset your password <a href="%s">Click here</a></p>', 'buddyboss' ), network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) );

		$message = bp_email_core_wp_get_template( $message, $user_data );

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		return $message;
	}

	add_filter( 'retrieve_password_message', 'bp_email_retrieve_password_message', 10, 4 );
}


if ( ! function_exists( 'bp_email_wpmu_signup_blog_notification_email' ) ) {
	/**
	 * Filters the message content of the new blog notification email.
	 *
	 * Content should be formatted for transmission via wp_mail().
	 *
	 * @since BuddyBoss 3.1.1
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

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

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
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string $content    Content of the notification email.
	 * @param string $user_login User login name.
	 * @param string $user_email User email address.
	 * @param string $key        Activation key created in wpmu_signup_user().
	 * @param array  $meta       Signup meta data. Default empty array.
	 */
	function bp_email_wpmu_signup_user_notification_email( $content, $user_login, $user_email, $key, $meta ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		$content = bp_email_core_wp_get_template( $content, get_user_by( 'email', $user_email ) );

		return $content;
	}

	add_filter( 'wpmu_signup_user_notification_email', 'bp_email_wpmu_signup_user_notification_email', 10, 5 );
}

if ( ! function_exists( 'bp_email_newblog_notify_siteadmin' ) ) {
	/**
	 * Filters the message body of the new site activation email sent
	 * to the network administrator.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string $msg Email body.
	 */
	function bp_email_newblog_notify_siteadmin( $msg ) {

		$email = get_site_option( 'admin_email' );
		if ( is_email($email) == false )
			return $msg;

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

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
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string  $msg  Email body.
	 * @param WP_User $user WP_User instance of the new user.
	 */
	function bp_email_newuser_notify_siteadmin( $msg, $user ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

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
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string $welcome_email Message body of the email.
	 * @param int    $blog_id       Blog ID.
	 * @param int    $user_id       User ID.
	 * @param string $password      User password.
	 * @param string $title         Site title.
	 * @param array  $meta          Signup meta data. By default, contains the requested privacy setting and lang_id.
	 */
	function bp_email_update_welcome_email( $welcome_email, $blog_id, $user_id, $password, $title, $meta ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

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
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string $welcome_email The message body of the account activation success email.
	 * @param int    $user_id       User ID.
	 * @param string $password      User password.
	 * @param array  $meta          Signup meta data. Default empty array.
	 */
	function bp_email_update_welcome_user_email( $welcome_email, $user_id, $password, $meta ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

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
	 * @since BuddyBoss 3.1.1
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

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, $current_user );

		return $email_text;
	}

	add_filter( 'new_network_admin_email_content', 'bp_email_new_network_admin_email_content', 10, 2 );
}

if ( ! function_exists( 'bp_email_network_admin_email_change_email' ) ) {
	/**
	 * Filters the contents of the email notification sent when the network admin email address is changed.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param array $email_change_email {
	 *            Used to build wp_mail().
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

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		$email_change_email = bp_email_core_wp_get_template( $email_change_email, get_user_by( 'email', $new_email ) );

		return $email_change_email;
	}

	add_filter( 'network_admin_email_change_email', 'bp_email_network_admin_email_change_email', 10, 4 );
}

if ( ! function_exists( 'bp_email_site_admin_email_change_email' ) ) {
	/**
	 * Filters the contents of the email notification sent when the site admin email address is changed.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param array $email_change_email {
	 *            Used to build wp_mail().
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

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		$email_change_email = bp_email_core_wp_get_template( $email_change_email, get_user_by( 'email', $new_email ) );

		return $email_change_email;
	}

	add_filter( 'site_admin_email_change_email', 'bp_email_site_admin_email_change_email', 10, 3 );
}

if ( ! function_exists( 'bp_email_wp_password_change_email' ) ) {
	/**
	 * Filters the contents of the email sent when the user's password is changed.
	 *
	 * @since BuddyBoss 3.1.1
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
	 */
	function bp_email_wp_password_change_email( $pass_change_email, $user, $userdata ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		$pass_change_email = bp_email_core_wp_get_template( $pass_change_email, $user );

		return $pass_change_email;
	}

	add_filter( 'password_change_email', 'bp_email_wp_password_change_email', 10, 3 );
}

if ( ! function_exists( 'bp_email_wp_email_change_email' ) ) {
	/**
	 * Filters the contents of the email sent when the user's email is changed.
	 *
	 * @since BuddyBoss 3.1.1
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
	 */
	function bp_email_wp_email_change_email( $email_change_email, $user, $userdata ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		$email_change_email = bp_email_core_wp_get_template( $email_change_email, $user );

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
	 * @since BuddyBoss 3.1.1
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

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

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
	 * @since BuddyBoss 3.1.1
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

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, get_user_by( 'email', $email_data['admin_email'] ) );

		return $email_text;
	}
	
	add_filter( 'user_confirmed_action_email_content', 'bp_email_wp_user_confirmed_action_email_content', 10, 2 );
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
	 * @since BuddyBoss 3.1.1
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

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); //add this to support html in email

		$email_text = bp_email_core_wp_get_template( $email_text, get_user_by( 'email', $admin_email ) );

		return $email_text;
	}

	add_filter( 'new_admin_email_content', 'bp_email_wp_new_admin_email_content', 10, 2 );
}