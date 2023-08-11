<?php
/**
 * BuddyBoss Messages Template Tags.
 *
 * @package BuddyBoss\Messages\Template
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Retrieve private message threads for display in inbox/sentbox/notices.
 *
 * Similar to WordPress's have_posts() function, this function is responsible
 * for querying the database and retrieving private messages for display inside
 * the theme via individual template parts for a member's inbox/sentbox/notices.
 *
 * @since BuddyPress 1.0.0
 *
 * @global BP_Messages_Box_Template $messages_template
 *
 * @param array|string $args {
 *     Array of arguments. All are optional.
 *     @type int    $user_id      ID of the user whose threads are being loaded.
 *                                Default: ID of the logged-in user.
 *     @type string $box          Current "box" view. If not provided here, the current
 *                                view will be inferred from the URL.
 *     @type int    $per_page     Number of results to return per page. Default: 10.
 *     @type int    $max          Max results to return. Default: false.
 *     @type string $type         Type of messages to return. Values: 'all', 'read', 'unread'
 *                                Default: 'all'
 *     @type string $search_terms Terms to which to limit results. Default:
 *                                the value of $_REQUEST['s'].
 *     @type string $page_arg     URL argument used for the pagination param.
 *                                Default: 'mpage'.
 *     @type array  $meta_query   Meta query arguments. Only applicable if $box is
 *                                not 'notices'. See WP_Meta_Query more details.
 * }
 * @return bool True if there are threads to display, otherwise false.
 */
function bp_has_message_threads( $args = array() ) {
	global $messages_template;

	// The default box the user is looking at.
	$current_action = bp_current_action();
	switch ( $current_action ) {
		case 'notices':
		case 'inbox':
			$default_box = $current_action;
			break;
		default:
			$default_box = 'inbox';
			break;
	}

	// User ID
	// @todo displayed user for moderators that get this far?
	$user_id = bp_displayed_user_id() ?: bp_loggedin_user_id();

	// Search Terms.
	$search_terms = isset( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '';

	// Parse the arguments.
	$r = bp_parse_args(
		$args,
		array(
			'user_id'      => $user_id,
			'box'          => $default_box,
			'per_page'     => 10,
			'max'          => false,
			'type'         => 'all',
			'search_terms' => $search_terms,
			'include'      => false,
			'page_arg'     => 'mpage', // See https://buddypress.trac.wordpress.org/ticket/3679.
			'meta_query'   => array(),
		),
		'has_message_threads'
	);
	// Load the messages loop global up with messages.
	$messages_template = new BP_Messages_Box_Template( $r );

	/**
	 * Filters if there are any message threads to display in inbox/sentbox/notices.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param bool                     $value             Whether or not the message has threads.
	 * @param BP_Messages_Box_Template $messages_template Current message box template object.
	 * @param array                    $r                 Array of parsed arguments passed into function.
	 */
	return apply_filters( 'bp_has_message_threads', $messages_template->has_threads(), $messages_template, $r );
}

/**
 * Check whether there are more threads to iterate over.
 *
 * @return bool
 */
function bp_message_threads() {
	global $messages_template;
	return $messages_template->message_threads();
}

/**
 * Set up the current thread inside the loop.
 *
 * @return object
 */
function bp_message_thread() {
	global $messages_template;
	return $messages_template->the_message_thread();
}

/**
 * Output the ID of the current thread in the loop.
 */
function bp_message_thread_id() {
	echo bp_get_message_thread_id();
}
	/**
	 * Get the ID of the current thread in the loop.
	 *
	 * @return int
	 */
function bp_get_message_thread_id() {
	global $messages_template;

	/**
	 * Filters the ID of the current thread in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id ID of the current thread in the loop.
	 */
	return apply_filters( 'bp_get_message_thread_id', $messages_template->thread->thread_id );
}

/**
 * Output the subject of the current thread in the loop.
 */
function bp_message_thread_subject() {
	echo bp_get_message_thread_subject();
}
	/**
	 * Get the subject of the current thread in the loop.
	 *
	 * @return string
	 */
function bp_get_message_thread_subject() {
	global $messages_template;

	/**
	 * Filters the subject of the current thread in the loop.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value Subject of the current thread in the loop.
	 */
	return apply_filters( 'bp_get_message_thread_subject', $messages_template->thread->last_message_subject );
}

/**
 * Output an excerpt from the current message in the loop.
 */
function bp_message_thread_excerpt() {
	echo bp_get_message_thread_excerpt();
}
	/**
	 * Generate an excerpt from the current message in the loop.
	 *
	 * @return string
	 */
function bp_get_message_thread_excerpt() {
	global $messages_template;

	/**
	 * Filters the excerpt of the current thread in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Excerpt of the current thread in the loop.
	 */
	return apply_filters( 'bp_get_message_thread_excerpt', bp_create_excerpt( wp_strip_all_tags( preg_replace( '#(<br\s*?\/?>|</(\w+)><(\w+)>)#', ' ', $messages_template->thread->last_message_content ) ), 75 ) );
}

/**
 * Output the thread's last message content.
 *
 * When viewing your Inbox, the last message is the most recent message in
 * the thread of which you are *not* the author.
 *
 * When viewing your Sentbox, last message is the most recent message in
 * the thread of which you *are* the member.
 *
 * @since BuddyPress 2.0.0
 */
function bp_message_thread_content() {
	echo bp_get_message_thread_content();
}
	/**
	 * Return the thread's last message content.
	 *
	 * When viewing your Inbox, the last message is the most recent message in
	 * the thread of which you are *not* the author.
	 *
	 * When viewing your Sentbox, last message is the most recent message in
	 * the thread of which you *are* the member.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @return string The raw content of the last message in the thread.
	 */
function bp_get_message_thread_content() {
	global $messages_template;

	/**
	 * Filters the content of the last message in the thread.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param string $last_message_content Content of the last message in the thread.
	 */
	return apply_filters( 'bp_get_message_thread_content', $messages_template->thread->last_message_content );
}

/**
 * Output a link to the page of the current thread's last author.
 */
function bp_message_thread_from() {
	echo bp_get_message_thread_from();
}
	/**
	 * Get a link to the page of the current thread's last author.
	 *
	 * @return string
	 */
function bp_get_message_thread_from() {
	global $messages_template;

	/**
	 * Filters the link to the page of the current thread's last author.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Link to the page of the current thread's last author.
	 */
	return apply_filters( 'bp_get_message_thread_from', bp_core_get_userlink( $messages_template->thread->last_sender_id ) );
}

/**
 * Output links to the pages of the current thread's recipients.
 */
function bp_message_thread_to() {
	echo bp_get_message_thread_to();
}
	/**
	 * Generate HTML links to the pages of the current thread's recipients.
	 *
	 * @return string
	 */
function bp_get_message_thread_to() {
	global $messages_template;

	/**
	 * Filters the HTML links to the pages of the current thread's recipients.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value HTML links to the pages of the current thread's recipients.
	 */
	return apply_filters( 'bp_message_thread_to', BP_Messages_Thread::get_recipient_links( $messages_template->thread->recipients ) );
}

/**
 * Output the permalink for a particular thread.
 *
 * @since BuddyPress 2.9.0 Introduced `$user_id` parameter.
 *
 * @param int $thread_id Optional. ID of the thread. Default: current thread
 *                       being iterated on in the loop.
 * @param int $user_id   Optional. ID of the user relative to whom the link
 *                       should be generated. Default: ID of logged-in user.
 */
function bp_message_thread_view_link( $thread_id = 0, $user_id = null ) {
	echo bp_get_message_thread_view_link( $thread_id, $user_id );
}
	/**
	 * Get the permalink of a particular thread.
	 *
	 * @since BuddyPress 2.9.0 Introduced `$user_id` parameter.
	 *
	 * @param int $thread_id Optional. ID of the thread. Default: current
	 *                       thread being iterated on in the loop.
	 * @param int $user_id   Optional. ID of the user relative to whom the link
	 *                       should be generated. Default: ID of logged-in user.
	 * @return string
	 */
function bp_get_message_thread_view_link( $thread_id = 0, $user_id = null ) {
	global $messages_template;

	if ( empty( $messages_template ) && (int) $thread_id > 0 ) {
		$thread_id = (int) $thread_id;
	} elseif ( ! empty( $messages_template->thread->thread_id ) ) {
		$thread_id = $messages_template->thread->thread_id;
	}

	if ( null === $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	$domain = bp_core_get_user_domain( $user_id );

	/**
	 * Filters the permalink of a particular thread.
	 *
	 * @since BuddyPress 1.0.0
	 * @since BuddyPress 2.6.0 Added the `$thread_id` parameter.
	 * @since BuddyPress 2.9.0 Added the `$user_id` parameter.
	 *
	 * @param string $value     Permalink of a particular thread.
	 * @param int    $thread_id ID of the thread.
	 * @param int    $user_id   ID of the user.
	 */
	return apply_filters( 'bp_get_message_thread_view_link', trailingslashit( $domain . bp_get_messages_slug() . '/view/' . $thread_id ), $thread_id, $user_id );
}

/**
 * Output the URL for deleting the current thread.
 *
 * @since BuddyPress 2.9.0 Introduced `$user_id` parameter.
 *
 * @param int $user_id Optional. ID of the user relative to whom the link
 *                     should be generated. Default: ID of logged-in user.
 */
function bp_message_thread_delete_link( $user_id = null ) {
	echo esc_url( bp_get_message_thread_delete_link( $user_id ) );
}
	/**
	 * Generate the URL for deleting the current thread.
	 *
	 * @since BuddyPress 2.9.0 Introduced `$user_id` parameter.
	 *
	 * @param int $user_id Optional. ID of the user relative to whom the link
	 *                     should be generated. Default: ID of logged-in user.
	 * @return string
	 */
function bp_get_message_thread_delete_link( $user_id = null ) {
	global $messages_template;

	if ( null === $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	$domain = bp_core_get_user_domain( $user_id );

	/**
	 * Filters the URL for deleting the current thread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value   URL for deleting the current thread.
	 * @param int    $user_id ID of the user relative to whom the link should be generated.
	 */
	return apply_filters( 'bp_get_message_thread_delete_link', wp_nonce_url( trailingslashit( $domain . bp_get_messages_slug() . '/' . bp_current_action() . '/delete/' . $messages_template->thread->thread_id ), 'messages_delete_thread' ), $user_id );
}

/**
 * Output the URL used for marking a single message thread as unread.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since BuddyPress 2.2.0
 * @since BuddyPress 2.9.0 Introduced `$user_id` parameter.
 *
 * @param int $user_id Optional. ID of the user relative to whom the link
 *                     should be generated. Default: ID of logged-in user.
 */
function bp_the_message_thread_mark_unread_url( $user_id = null ) {
	echo esc_url( bp_get_the_message_thread_mark_unread_url( $user_id ) );
}
	/**
	 * Return the URL used for marking a single message thread as unread.
	 *
	 * @since BuddyPress 2.2.0
	 * @since BuddyPress 2.9.0 Introduced `$user_id` parameter.
	 *
	 * @param int $user_id Optional. ID of the user relative to whom the link
	 *                     should be generated. Default: ID of logged-in user.
	 * @return string
	 */
function bp_get_the_message_thread_mark_unread_url( $user_id = null ) {

	// Get the message ID.
	$id = bp_get_message_thread_id();

	// Get the args to add to the URL.
	$args = array(
		'action'     => 'unread',
		'message_id' => $id,
	);

	if ( null === $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	$domain = bp_core_get_user_domain( $user_id );

	// Base unread URL.
	$url = trailingslashit( $domain . bp_get_messages_slug() . '/' . bp_current_action() . '/unread' );

	// Add the args to the URL.
	$url = add_query_arg( $args, $url );

	// Add the nonce.
	$url = wp_nonce_url( $url, 'bp_message_thread_mark_unread_' . $id );

	/**
	 * Filters the URL used for marking a single message thread as unread.
	 *
	 * @since BuddyPress 2.2.0
	 * @since BuddyPress 2.9.0 Added `$user_id` parameter.
	 *
	 * @param string $url     URL used for marking a single message thread as unread.
	 * @param int    $user_id ID of the user relative to whom the link should be generated.
	 */
	return apply_filters( 'bp_get_the_message_thread_mark_unread_url', $url, $user_id );
}

/**
 * Output the URL used for marking a single message thread as read.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since BuddyPress 2.2.0
 * @since BuddyPress 2.9.0 Introduced `$user_id` parameter.
 *
 * @param int $user_id Optional. ID of the user relative to whom the link
 *                     should be generated. Default: ID of logged-in user.
 */
function bp_the_message_thread_mark_read_url( $user_id = null ) {
	echo esc_url( bp_get_the_message_thread_mark_read_url( $user_id ) );
}
	/**
	 * Return the URL used for marking a single message thread as read.
	 *
	 * @since BuddyPress 2.2.0
	 * @since BuddyPress 2.9.0 Introduced `$user_id` parameter.
	 *
	 * @param int $user_id Optional. ID of the user relative to whom the link
	 *                     should be generated. Default: ID of logged-in user.
	 * @return string
	 */
function bp_get_the_message_thread_mark_read_url( $user_id = null ) {

	// Get the message ID.
	$id = bp_get_message_thread_id();

	// Get the args to add to the URL.
	$args = array(
		'action'     => 'read',
		'message_id' => $id,
	);

	if ( null === $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	$domain = bp_core_get_user_domain( $user_id );

	// Base read URL.
	$url = trailingslashit( $domain . bp_get_messages_slug() . '/' . bp_current_action() . '/read' );

	// Add the args to the URL.
	$url = add_query_arg( $args, $url );

	// Add the nonce.
	$url = wp_nonce_url( $url, 'bp_message_thread_mark_read_' . $id );

	/**
	 * Filters the URL used for marking a single message thread as read.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param string $url     URL used for marking a single message thread as read.
	 * @param int    $user_id ID of the user relative to whom the link should be generated.
	 */
	return apply_filters( 'bp_get_the_message_thread_mark_read_url', $url );
}

/**
 * Output the CSS class for the current thread.
 */
function bp_message_css_class() {
	echo esc_attr( bp_get_message_css_class() );
}
	/**
	 * Generate the CSS class for the current thread.
	 *
	 * @return string
	 */
function bp_get_message_css_class() {
	global $messages_template;

	$class = false;

	if ( $messages_template->current_thread % 2 == 1 ) {
		$class .= 'alt';
	}

	/**
	 * Filters the CSS class for the current thread.
	 *
	 * @since BuddyPress 1.2.10
	 *
	 * @param string $class Class string to be added to the list of classes.
	 */
	return apply_filters( 'bp_get_message_css_class', trim( $class ) );
}

/**
 * Check whether the current thread has unread items.
 *
 * @return bool True if there are unread items, otherwise false.
 */
function bp_message_thread_has_unread() {
	global $messages_template;

	$retval = ! empty( $messages_template->thread->unread_count )
		? true
		: false;

	/**
	 * Filters whether or not a message thread has unread items.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param bool $retval Whether or not a message thread has unread items.
	 */
	return apply_filters( 'bp_message_thread_has_unread', $retval );
}

/**
 * Output the current thread's unread count.
 */
function bp_message_thread_unread_count() {
	echo esc_html( bp_get_message_thread_unread_count() );
}
	/**
	 * Get the current thread's unread count.
	 *
	 * @return int
	 */
function bp_get_message_thread_unread_count() {
	global $messages_template;

	$count = ! empty( $messages_template->thread->unread_count )
		? (int) $messages_template->thread->unread_count
		: false;

	/**
	 * Filters the current thread's unread count.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $count Current thread unread count.
	 */
	return apply_filters( 'bp_get_message_thread_unread_count', $count );
}

/**
 * Output a thread's total message count.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int|bool $thread_id Optional. ID of the thread. Defaults to current thread ID.
 */
function bp_message_thread_total_count( $thread_id = false ) {
	echo bp_get_message_thread_total_count( $thread_id );
}
	/**
	 * Get the current thread's total message count.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param int|bool $thread_id Optional. ID of the thread.
	 *                            Defaults to current thread ID.
	 * @return int
	 */
function bp_get_message_thread_total_count( $thread_id = false ) {
	if ( false === $thread_id ) {
		$thread_id = bp_get_message_thread_id();
	}

	$thread_template = new BP_Messages_Thread_Template(
		$thread_id,
		'ASC',
		array(
			'update_meta_cache' => false,
		)
	);

	$count = 0;
	if ( ! empty( $thread_template->message_count ) ) {
		$count = intval( $thread_template->message_count );
	}

	/**
	 * Filters the current thread's total message count.
	 *
	 * @since BuddyPress 2.2.0
	 * @since BuddyPress 2.6.0 Added the `$thread_id` parameter.
	 *
	 * @param int $count     Current thread total message count.
	 * @param int $thread_id ID of the queried thread.
	 */
	return apply_filters( 'bp_get_message_thread_total_count', $count, $thread_id );
}

/**
 * Output markup for the current thread's total and unread count.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int|bool $thread_id Optional. ID of the thread. Default: current thread ID.
 */
function bp_message_thread_total_and_unread_count( $thread_id = false ) {
	echo bp_get_message_thread_total_and_unread_count( $thread_id );
}
	/**
	 * Get markup for the current thread's total and unread count.
	 *
	 * @param int|bool $thread_id Optional. ID of the thread. Default: current thread ID.
	 * @return string Markup displaying the total and unread count for the thread.
	 */
function bp_get_message_thread_total_and_unread_count( $thread_id = false ) {
	if ( false === $thread_id ) {
		$thread_id = bp_get_message_thread_id();
	}

	$total  = bp_get_message_thread_total_count( $thread_id );
	$unread = bp_get_message_thread_unread_count( $thread_id );

	return sprintf(
		/* translators: 1: total number, 2: accessibility text: number of unread messages */
		'<span class="thread-count">(%1$s)</span> <span class="bp-screen-reader-text">%2$s</span>',
		bp_core_number_format( $total ),
		sprintf( _n( '%d unread', '%d unread', $unread, 'buddyboss' ), bp_core_number_format( $unread ) )
	);
}

/**
 * Output the unformatted date of the last post in the current thread.
 */
function bp_message_thread_last_post_date_raw() {
	echo bp_get_message_thread_last_post_date_raw();
}
	/**
	 * Get the unformatted date of the last post in the current thread.
	 *
	 * @return string
	 */
function bp_get_message_thread_last_post_date_raw() {
	global $messages_template;

	/**
	 * Filters the unformatted date of the last post in the current thread.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $last_message_date Unformatted date of the last post in the current thread.
	 */
	return apply_filters( 'bp_get_message_thread_last_message_date', $messages_template->thread->last_message_date );
}

/**
 * Output the nicely formatted date of the last post in the current thread.
 */
function bp_message_thread_last_post_date() {
	echo bp_get_message_thread_last_post_date();
}
	/**
	 * Get the nicely formatted date of the last post in the current thread.
	 *
	 * @return string
	 */
function bp_get_message_thread_last_post_date() {

	/**
	 * Filters the nicely formatted date of the last post in the current thread.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $value Formatted date of the last post in the current thread.
	 */
	return apply_filters( 'bp_get_message_thread_last_post_date', bp_format_time( strtotime( bp_get_message_thread_last_post_date_raw() ) ) );
}

/**
 * Output the avatar for the last sender in the current message thread.
 *
 * @see bp_get_message_thread_avatar() for a description of arguments.
 *
 * @param array|string $args See {@link bp_get_message_thread_avatar()}.
 */
function bp_message_thread_avatar( $args = '' ) {
	echo bp_get_message_thread_avatar( $args );
}
	/**
	 * Return the avatar for the last sender in the current message thread.
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array|string $args {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string      $type   Default: 'thumb'.
	 *     @type int|bool    $width  Default: false.
	 *     @type int|bool    $height Default: false.
	 *     @type string      $class  Default: 'avatar'.
	 *     @type string|bool $id     Default: false.
	 *     @type string      $alt    Default: 'Profile picture of [display name]'.
	 * }
	 * @return string User avatar string.
	 */
function bp_get_message_thread_avatar( $args = '' ) {
	global $messages_template;

	$fullname = bp_core_get_user_displayname( $messages_template->thread->last_sender_id );
	$alt      = sprintf( __( 'Profile photo of %s', 'buddyboss' ), $fullname );

	$r = bp_parse_args(
		$args,
		array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
			'class'  => 'avatar',
			'id'     => false,
			'alt'    => $alt,
		)
	);

	/**
	 * Filters the avatar for the last sender in the current message thread.
	 *
	 * @since BuddyPress 1.0.0
	 * @since BuddyPress 2.6.0 Added the `$r` parameter.
	 *
	 * @param string $value User avatar string.
	 * @param array  $r     Array of parsed arguments.
	 */
	return apply_filters(
		'bp_get_message_thread_avatar',
		bp_core_fetch_avatar(
			array(
				'item_id' => $messages_template->thread->last_sender_id,
				'type'    => $r['type'],
				'alt'     => $r['alt'],
				'css_id'  => $r['id'],
				'class'   => $r['class'],
				'width'   => $r['width'],
				'height'  => $r['height'],
			)
		),
		$r
	);
}

/**
 * Output the unread messages count for the current inbox.
 *
 * @since BuddyPress 2.6.x Added $user_id argument.
 *
 * @param int $user_id The user ID.
 *
 * @return int $unread_count Total inbox unread count for user.
 */
function bp_total_unread_messages_count( $user_id = 0 ) {
	echo bp_get_total_unread_messages_count( $user_id );
}
	/**
	 * Get the unread messages count for the current inbox.
	 *
	 * @since BuddyPress 2.6.x Added $user_id argument.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return int $unread_count Total inbox unread count for user.
	 */
function bp_get_total_unread_messages_count( $user_id = 0 ) {

	/**
	 * Filters the unread messages count for the current inbox.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $value Unread messages count for the current inbox.
	 */
	return apply_filters( 'bp_get_total_unread_messages_count', BP_Messages_Thread::get_inbox_count( $user_id ) );
}

/**
 * Output the pagination HTML for the current thread loop.
 */
function bp_messages_pagination() {
	echo bp_get_messages_pagination();
}
	/**
	 * Get the pagination HTML for the current thread loop.
	 *
	 * @return string
	 */
function bp_get_messages_pagination() {
	global $messages_template;

	/**
	 * Filters the pagination HTML for the current thread loop.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $pag_links Pagination HTML for the current thread loop.
	 */
	return apply_filters( 'bp_get_messages_pagination', $messages_template->pag_links );
}

/**
 * Generate the "Viewing message x to y (of z messages)" string for a loop.
 */
function bp_messages_pagination_count() {
	global $messages_template;

	$start_num = intval( ( $messages_template->pag_page - 1 ) * $messages_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $messages_template->pag_num - 1 ) > $messages_template->total_thread_count ) ? $messages_template->total_thread_count : $start_num + ( $messages_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $messages_template->total_thread_count );

	$message = sprintf( _n( 'Viewing 1 message', 'Viewing %1$s - %2$s of %3$s messages', $messages_template->total_thread_count, 'buddyboss' ), $from_num, $to_num, $total );

	echo esc_html( $message );
}

/**
 * Output the Private Message search form.
 *
 * @todo  Move markup to template part in: /members/single/messages/search.php
 * @since BuddyPress 1.6.0
 */
function bp_message_search_form() {

	// Get the default search text.
	$default_search_value = bp_get_search_default_text( 'messages' );

	// Setup a few values based on what's being searched for.
	$search_submitted   = ! empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value;
	$search_placeholder = ( $search_submitted === $default_search_value ) ? ' placeholder="' . esc_attr( $search_submitted ) . '"' : '';
	$search_value       = ( $search_submitted !== $default_search_value ) ? ' value="' . esc_attr( $search_submitted ) . '"' : '';

	// Start the output buffer, so form can be filtered.
	ob_start(); ?>

	<form action="" method="get" id="search-message-form">
		<label for="messages_search" class="bp-screen-reader-text">
		<?php
			/* translators: accessibility text */
			esc_html_e( 'Search Messages', 'buddyboss' );
		?>
		</label>
		<input type="text" name="s" id="messages_search"<?php echo $search_placeholder . $search_value; ?> />
		<input type="submit" class="button" id="messages_search_submit" name="messages_search_submit" value="<?php esc_html_e( 'Search', 'buddyboss' ); ?>" />
	</form>

	<?php

	// Get the search form from the above output buffer.
	$search_form_html = ob_get_clean();

	/**
	 * Filters the private message component search form.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param string $search_form_html HTML markup for the message search form.
	 */
	echo apply_filters( 'bp_message_search_form', $search_form_html );
}

/**
 * Echo the form action for Messages HTML forms.
 */
function bp_messages_form_action() {
	echo esc_url( bp_get_messages_form_action() );
}
	/**
	 * Return the form action for Messages HTML forms.
	 *
	 * @return string The form action.
	 */
function bp_get_messages_form_action() {

	/**
	 * Filters the form action for Messages HTML forms.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value The form action.
	 */
	return apply_filters( 'bp_get_messages_form_action', trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() . '/' . bp_action_variable( 0 ) ) );
}

/**
 * Output the default username for the recipient box.
 */
function bp_messages_username_value() {
	echo esc_attr( bp_get_messages_username_value() );
}
	/**
	 * Get the default username for the recipient box.
	 *
	 * @return string
	 */
function bp_get_messages_username_value() {
	if ( isset( $_COOKIE['bp_messages_send_to'] ) ) {

		/**
		 * Filters the default username for the recipient box.
		 *
		 * Value passed into filter is dependent on if the 'bp_messages_send_to'
		 * cookie or 'r' $_GET parameter is set.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param string $value Default user name.
		 */
		return apply_filters( 'bp_get_messages_username_value', $_COOKIE['bp_messages_send_to'] );
	} elseif ( isset( $_GET['r'] ) && ! isset( $_COOKIE['bp_messages_send_to'] ) ) {
		/** This filter is documented in bp-messages-template.php */
		return apply_filters( 'bp_get_messages_username_value', $_GET['r'] );
	}
}

/**
 * Output the default value for the Subject field.
 */
function bp_messages_subject_value() {
	echo esc_attr( bp_get_messages_subject_value() );
}
	/**
	 * Get the default value for the Subject field.
	 *
	 * Will get a value out of $_POST['subject'] if available (ie after a
	 * failed submission).
	 *
	 * @return string
	 */
function bp_get_messages_subject_value() {

	// Sanitized in bp-messages-filters.php.
	$subject = ! empty( $_POST['subject'] )
		? $_POST['subject']
		: '';

	/**
	 * Filters the default value for the subject field.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $subject The default value for the subject field.
	 */
	return apply_filters( 'bp_get_messages_subject_value', $subject );
}

/**
 * Output the default value for the Compose content field.
 */
function bp_messages_content_value() {
	echo esc_textarea( bp_get_messages_content_value() );
}
	/**
	 * Get the default value fo the Compose content field.
	 *
	 * Will get a value out of $_POST['content'] if available (ie after a
	 * failed submission).
	 *
	 * @return string
	 */
function bp_get_messages_content_value() {

	// Sanitized in bp-messages-filters.php.
	$content = ! empty( $_POST['content'] )
		? $_POST['content']
		: '';

	/**
	 * Filters the default value for the content field.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $content The default value for the content field.
	 */
	return apply_filters( 'bp_get_messages_content_value', $content );
}

/**
 * Output the markup for the message type dropdown.
 */
function bp_messages_options() {
	?>

	<label for="message-type-select" class="bp-screen-reader-text">
	<?php
		/* translators: accessibility text */
		_e( 'Select:', 'buddyboss' );
	?>
	</label>
	<select name="message-type-select" id="message-type-select">
		<option value=""><?php _e( 'Select', 'buddyboss' ); ?></option>
		<option value="read"><?php _ex( 'Read', 'Past Participle: Messages have been read', 'buddyboss' ); ?></option>
		<option value="unread"><?php _e( 'Unread', 'buddyboss' ); ?></option>
		<option value="all"><?php _e( 'All', 'buddyboss' ); ?></option>
	</select> &nbsp;

	<?php if ( ! bp_is_current_action( 'notices' ) ) : ?>

		<a href="#" id="mark_as_read"><?php _e( 'Mark as Read', 'buddyboss' ); ?></a> &nbsp;
		<a href="#" id="mark_as_unread"><?php _e( 'Mark as Unread', 'buddyboss' ); ?></a> &nbsp;

		<?php wp_nonce_field( 'bp_messages_mark_messages_read', 'mark-messages-read-nonce', false ); ?>
		<?php wp_nonce_field( 'bp_messages_mark_messages_unread', 'mark-messages-unread-nonce', false ); ?>

	<?php endif; ?>

	<a href="#" id="delete_<?php echo bp_current_action(); ?>_messages"><?php _e( 'Delete Selected', 'buddyboss' ); ?></a> &nbsp;
	<?php wp_nonce_field( 'bp_messages_delete_selected', 'delete-selected-nonce', false ); ?>
	<?php
}

/**
 * Output the dropdown for bulk management of messages.
 *
 * @since BuddyPress 2.2.0
 */
function bp_messages_bulk_management_dropdown() {
	?>
	<label class="bp-screen-reader-text" for="messages-select">
	<?php
		_e( 'Select Bulk Action', 'buddyboss' );
	?>
	</label>
	<select name="messages_bulk_action" id="messages-select">
		<option value="" selected="selected"><?php _e( 'Bulk Actions', 'buddyboss' ); ?></option>
		<option value="read"><?php _e( 'Mark read', 'buddyboss' ); ?></option>
		<option value="unread"><?php _e( 'Mark unread', 'buddyboss' ); ?></option>
		<option value="delete"><?php _e( 'Delete', 'buddyboss' ); ?></option>
		<?php
			/**
			 * Action to add additional options to the messages bulk management dropdown.
			 *
			 * @since BuddyPress 2.3.0
			 */
			do_action( 'bp_messages_bulk_management_dropdown' );
		?>
	</select>
	<input type="submit" id="messages-bulk-manage" class="button action" value="<?php esc_attr_e( 'Apply', 'buddyboss' ); ?>">
	<?php
}

/**
 * Return whether or not the notice is currently active.
 *
 * @since BuddyPress 1.6.0
 *
 * @return bool
 */
function bp_messages_is_active_notice() {
	global $messages_template;

	$retval = ! empty( $messages_template->thread->is_active )
		? true
		: false;

	/**
	 * Filters whether or not the notice is currently active.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param bool $retval Whether or not the notice is currently active.
	 */
	return apply_filters( 'bp_messages_is_active_notice', $retval );
}

/**
 * Output a string for the active notice.
 *
 * Since 1.6 this function has been deprecated in favor of text in the theme.
 *
 * @since BuddyPress 1.0.0
 * @deprecated 1.6.0
 * @return bool
 */
function bp_message_is_active_notice() {
	echo bp_get_message_is_active_notice();
}
	/**
	 * Returns a string for the active notice.
	 *
	 * Since 1.6 this function has been deprecated in favor of text in the
	 * theme.
	 *
	 * @since BuddyPress 1.0.0
	 * @deprecated 1.6.0
	 * @return string
	 */
function bp_get_message_is_active_notice() {

	$string = bp_messages_is_active_notice()
		? __( 'Currently Active', 'buddyboss' )
		: '';

	return apply_filters( 'bp_get_message_is_active_notice', $string );
}

/**
 * Output the ID of the current notice in the loop.
 */
function bp_message_notice_id() {
	echo (int) bp_get_message_notice_id();
}
	/**
	 * Get the ID of the current notice in the loop.
	 *
	 * @return int
	 */
function bp_get_message_notice_id() {
	global $messages_template;

	/**
	 * Filters the ID of the current notice in the loop.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $id ID of the current notice in the loop.
	 */
	return apply_filters( 'bp_get_message_notice_id', $messages_template->thread->id );
}

/**
 * Output the post date of the current notice in the loop.
 */
function bp_message_notice_post_date() {
	echo bp_get_message_notice_post_date();
}
	/**
	 * Get the post date of the current notice in the loop.
	 *
	 * @return string
	 */
function bp_get_message_notice_post_date() {
	global $messages_template;

	/**
	 * Filters the post date of the current notice in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Formatted post date of the current notice in the loop.
	 */
	return apply_filters( 'bp_get_message_notice_post_date', bp_format_time( strtotime( $messages_template->thread->date_sent ) ) );
}

/**
 * Output the subject of the current notice in the loop.
 */
function bp_message_notice_subject() {
	echo bp_get_message_notice_subject();
}
	/**
	 * Get the subject of the current notice in the loop.
	 *
	 * @return string
	 */
function bp_get_message_notice_subject() {
	global $messages_template;

	/**
	 * Filters the subject of the current notice in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $subject Subject of the current notice in the loop.
	 */
	return apply_filters( 'bp_get_message_notice_subject', $messages_template->thread->subject );
}

/**
 * Output the text of the current notice in the loop.
 */
function bp_message_notice_text() {
	echo bp_get_message_notice_text();
}
	/**
	 * Get the text of the current notice in the loop.
	 *
	 * @return string
	 */
function bp_get_message_notice_text() {
	global $messages_template;

	/**
	 * Filters the text of the current notice in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $message Text for the current notice in the loop.
	 */
	return apply_filters( 'bp_get_message_notice_text', $messages_template->thread->message );
}

/**
 * Output the URL for deleting the current notice.
 */
function bp_message_notice_delete_link() {
	echo esc_url( bp_get_message_notice_delete_link() );
}
	/**
	 * Get the URL for deleting the current notice.
	 *
	 * @return string Delete URL.
	 */
function bp_get_message_notice_delete_link() {
	global $messages_template;

	/**
	 * Filters the URL for deleting the current notice.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value URL for deleting the current notice.
	 * @param string $value Text indicating action being executed.
	 */
	return apply_filters( 'bp_get_message_notice_delete_link', wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/notices/delete/' . $messages_template->thread->id ), 'messages_delete_notice' ) );
}

/**
 * Output the URL for deactivating the current notice.
 */
function bp_message_activate_deactivate_link() {
	echo esc_url( bp_get_message_activate_deactivate_link() );
}
	/**
	 * Get the URL for deactivating the current notice.
	 *
	 * @return string
	 */
function bp_get_message_activate_deactivate_link() {
	global $messages_template;

	if ( 1 === (int) $messages_template->thread->is_active ) {
		$link = wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/notices/deactivate/' . $messages_template->thread->id ), 'messages_deactivate_notice' );
	} else {
		$link = wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/notices/activate/' . $messages_template->thread->id ), 'messages_activate_notice' );
	}

	/**
	 * Filters the URL for deactivating the current notice.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $link URL for deactivating the current notice.
	 */
	return apply_filters( 'bp_get_message_activate_deactivate_link', $link );
}

/**
 * Output the Deactivate/Activate text for the notice action link.
 */
function bp_message_activate_deactivate_text() {
	echo esc_html( bp_get_message_activate_deactivate_text() );
}
	/**
	 * Generate the text ('Deactivate' or 'Activate') for the notice action link.
	 *
	 * @return string
	 */
function bp_get_message_activate_deactivate_text() {
	global $messages_template;

	if ( 1 === (int) $messages_template->thread->is_active ) {
		$text = __( 'Deactivate', 'buddyboss' );
	} else {
		$text = __( 'Activate', 'buddyboss' );
	}

	/**
	 * Filters the "Deactivate" or "Activate" text for notice action links.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $text Text used for notice action links.
	 */
	return apply_filters( 'bp_message_activate_deactivate_text', $text );
}

/**
 * Output the messages component slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_messages_slug() {
	echo bp_get_messages_slug();
}
	/**
	 * Return the messages component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_messages_slug() {

	/**
	 * Filters the messages component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug Messages component slug.
	 */
	return apply_filters( 'bp_get_messages_slug', buddypress()->messages->slug );
}

/**
 * Generate markup for currently active notices.
 */
function bp_message_get_notices() {
	$notice = BP_Messages_Notice::get_active();

	if ( empty( $notice ) ) {
		return false;
	}

	$closed_notices = bp_get_user_meta( bp_loggedin_user_id(), 'closed_notices', true );

	if ( empty( $closed_notices ) ) {
		$closed_notices = array();
	}

	if ( is_array( $closed_notices ) ) {
		if ( ! in_array( $notice->id, $closed_notices ) && $notice->id ) {
			?>
			<div id="message" class="info notice" rel="n-<?php echo esc_attr( $notice->id ); ?>">
				<p>
					<strong><?php echo stripslashes( wp_filter_kses( $notice->subject ) ); ?></strong><br />
					<?php echo stripslashes( wp_filter_kses( $notice->message ) ); ?>
					<button type="button" id="close-notice" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Dismiss this notice', 'buddyboss' ); ?>"><span class="bp-screen-reader-text"><?php _e( 'Dismiss this notice', 'buddyboss' ); ?></span> <span aria-hidden="true">&Chi;</span></button>
					<?php wp_nonce_field( 'bp_messages_close_notice', 'close-notice-nonce' ); ?>
				</p>
			</div>
			<?php
		}
	}
}

/**
 * Output the URL for the Private Message link in member profile headers.
 */
function bp_send_private_message_link() {
	echo esc_url( bp_get_send_private_message_link() );
}
	/**
	 * Generate the URL for the Private Message link in member profile headers.
	 *
	 * @return bool|string False on failure, otherwise the URL.
	 */
function bp_get_send_private_message_link() {

	if ( bp_is_my_profile() || ! is_user_logged_in() ) {
		return false;
	}

	if ( bp_displayed_user_id() > 0 ) {
		$user_id = bp_displayed_user_id();
	} elseif ( ! empty( $_GET['user'] ) && is_string( $_GET['user'] ) ) {
		$user_id = absint( $_GET['user'] );
	} else {
		$user_id = $GLOBALS['members_template']->member->ID;
	}

	/**
	 * Filters the URL for the Private Message link in member profile headers.
	 *
	 * @since BuddyPress 1.2.10
	 *
	 * @param string $value URL for the Private Message link in member profile headers.
	 */
	return apply_filters( 'bp_get_send_private_message_link', wp_nonce_url( bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_members_get_user_nicename( $user_id ) ) );
}

/**
 * Output the 'Message' button for member profile headers.
 *
 * Explicitly named function to avoid confusion with public messages.
 *
 * @since BuddyPress 1.2.6
 */
function bp_send_private_message_button() {
	echo bp_get_send_message_button();
}

/**
 * Output the 'Message' button for member profile headers.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyPress 3.0.0 Added `$args` parameter.
 *
 * @param array|string $args See {@link bp_get_send_message_button()}.
 */
function bp_send_message_button( $args = '' ) {
	echo bp_get_send_message_button( $args );
}

/**
 * Generate the 'Message' button for member profile headers.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyPress 3.0.0 Added `$args` parameter.
 *
 * @param array|string $args {
 *     All arguments are optional. See {@link BP_Button} for complete
 *     descriptions.
 *     @type string $id                Default: 'private_message'.
 *     @type string $component         Default: 'messages'.
 *     @type bool   $must_be_logged_in Default: true.
 *     @type bool   $block_self        Default: true.
 *     @type int    $message_receiver_user_id        Default: message receiver user id.
 *     @type string $wrapper_id        Default: 'send-private-message'.
 *     @type string $link_href         Default: the private message link for
 *                                     the current member in the loop.
 *     @type string $link_text         Default: 'Message'.
 *     @type string $link_class        Default: 'send-message'.
 *     @type bool   $is_tooltips       Is enabled tooltips? Default: false.
 *     @type string $data-balloon      The tooltips content. Default: null.
 *     @type string $data-balloon-pos  The position of tooltips. Default: null.
 *     @type string $prefix_link_text  Prefix of the button/link title. Default: null.
 *     @type string $postfix_link_text Postfix of the button/link title. Default: null.
 *     @type array  $button_attr {
 *          Button attributes to make it hover.
 *          @type string|bool $hover_type             The type of hover. There three options 'hover', 'static', 'false'. Default: false.
 *          @type string|null $data-title             The button hover title. Default: null.
 *          @type string|null $data-title-displayed   The button hover out title. Default: null.
 *          @type bool        $add_pre_post_text      Add prefix and postfix to the button title. Default: false.
 *     }
 * }
 * @return string
 */
function bp_get_send_message_button( $args = '' ) {

	if ( bp_displayed_user_id() > 0 ) {
		$receiver_user_id = bp_displayed_user_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( ! empty( $_GET['user'] ) && is_string( $_GET['user'] ) ) {
		$receiver_user_id = absint( $_GET['user'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		$receiver_user_id = ( $GLOBALS['members_template']->member->ID ) ? $GLOBALS['members_template']->member->ID : 0;
	}

	$r = bp_parse_args(
		$args,
		array(
			'id'                       => 'private_message',
			'component'                => 'messages',
			'must_be_logged_in'        => true,
			'block_self'               => true,
			'message_receiver_user_id' => $receiver_user_id,
			'wrapper_id'               => 'send-private-message',
			'link_href'                => bp_get_send_private_message_link(),
			'link_text'                => esc_html__( 'Send Message', 'buddyboss' ),
			'link_class'               => 'send-message',
			'is_tooltips'              => false,
			'data-balloon-pos'         => '',
			'data-balloon'             => esc_html__( 'Send Message', 'buddyboss' ),
			'prefix_link_text'         => '',
			'postfix_link_text'        => '',
			'button_attr'              => array(
				'hover_type'           => false,
				'data-title'           => '',
				'data-title-displayed' => '',
			),
		)
	);

	// Note: 'bp_get_send_message_button' is a legacy filter. Use
	// 'bp_get_send_message_button_args' instead. See #4536.
	return apply_filters(
		'bp_get_send_message_button',
		/**
		 * Filters the "Private Message" button for member profile headers.
		 *
		 * @since BuddyPress 1.8.0
		 *
		 * @param array $value See {@link BP_Button}.
		 */
		bp_get_button( apply_filters( 'bp_get_send_message_button_args', $r ) )
	);
}

/**
 * Output the URL of the Messages AJAX loader gif.
 */
function bp_message_loading_image_src() {
	echo esc_url( bp_get_message_loading_image_src() );
}
	/**
	 * Get the URL of the Messages AJAX loader gif.
	 *
	 * @return string
	 */
function bp_get_message_loading_image_src() {

	/**
	 * Filters the URL of the Messages AJAX loader gif.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value URL of the Messages AJAX loader gif.
	 */
	return apply_filters( 'bp_get_message_loading_image_src', buddypress()->messages->image_base . '/ajax-loader.gif' );
}

/**
 * Output the markup for the message recipient tabs.
 */
function bp_message_get_recipient_tabs() {
	$recipients = explode( ' ', bp_get_message_get_recipient_usernames() );

	foreach ( $recipients as $recipient ) {

		$user_id = bp_is_username_compatibility_mode()
			? bp_core_get_userid( $recipient )
			: bp_core_get_userid_from_nicename( $recipient );

		if ( ! empty( $user_id ) ) :
			?>

			<li id="un-<?php echo esc_attr( $recipient ); ?>" class="friend-tab">
				<span>
				<?php
					echo bp_core_fetch_avatar(
						array(
							'item_id' => $user_id,
							'type'    => 'thumb',
							'width'   => 15,
							'height'  => 15,
						)
					);
					echo bp_core_get_userlink( $user_id );
				?>
				</span>
			</li>

			<?php
		endif;
	}
}

/**
 * Output recipient usernames for prefilling the 'To' field on the Compose screen.
 */
function bp_message_get_recipient_usernames() {
	echo esc_attr( bp_get_message_get_recipient_usernames() );
}
	/**
	 * Get the recipient usernames for prefilling the 'To' field on the Compose screen.
	 *
	 * @return string
	 */
function bp_get_message_get_recipient_usernames() {

	// Sanitized in bp-messages-filters.php.
	$recipients = isset( $_GET['r'] )
		? $_GET['r']
		: '';

	/**
	 * Filters the recipients usernames for prefilling the 'To' field on the Compose screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $recipients Recipients usernames for 'To' field prefilling.
	 */
	return apply_filters( 'bp_get_message_get_recipient_usernames', $recipients );
}

/**
 * Initialize the messages template loop for a specific thread.
 *
 * @param array|string $args {
 *     Array of arguments. All are optional.
 *     @type int    $thread_id         ID of the thread whose messages you are displaying.
 *                                     Default: if viewing a thread, the thread ID will be parsed from
 *                                     the URL (bp_action_variable( 0 )).
 *     @type string $order             'ASC' or 'DESC'. Default: 'ASC'.
 *     @type bool   $update_meta_cache Whether to pre-fetch metadata for
 *                                     queried message items. Default: true.
 * }
 * @return bool True if there are messages to display, otherwise false.
 */
function bp_thread_has_messages( $args = '' ) {
	global $thread_template;

	$thread_template = bp_get_thread( $args );

	return $thread_template->has_messages();
}

/**
 * Initialize the messages template loop for a specific thread and sets thread.
 *
 * @param array|string $args {
 *     Array of arguments. All are optional.
 *     @type int    $thread_id         ID of the thread whose messages you are displaying.
 *                                     Default: if viewing a thread, the thread ID will be parsed from
 *                                     the URL (bp_action_variable( 0 )).
 *     @type string $order             'ASC' or 'DESC'. Default: 'ASC'.
 *     @type bool   $update_meta_cache Whether to pre-fetch metadata for
 *                                     queried message items. Default: true.
 * }
 * @return object thread template.
 */
function bp_get_thread( $args = '' ) {
	global $thread_template;

	$r = bp_parse_args(
		$args,
		array(
			'thread_id'         => false,
			'order'             => 'DESC',
			'update_meta_cache' => true,
		),
		'thread_has_messages'
	);

	if ( empty( $r['thread_id'] ) && bp_is_messages_component() && bp_is_current_action( 'view' ) ) {
		$r['thread_id'] = (int) bp_action_variable( 0 );
	}

	// Set up extra args.
	$extra_args = $r;
	unset( $extra_args['thread_id'], $extra_args['order'] );

	$thread_template = new BP_Messages_Thread_Template( $r['thread_id'], $r['order'], $extra_args );

	return $thread_template;
}

/**
 * Output the 'ASC' or 'DESC' messages order string for this loop.
 */
function bp_thread_messages_order() {
	echo esc_attr( bp_get_thread_messages_order() );
}
	/**
	 * Get the 'ASC' or 'DESC' messages order string for this loop.
	 *
	 * @return string
	 */
function bp_get_thread_messages_order() {
	global $thread_template;
	return $thread_template->thread->messages_order;
}

/**
 * Check whether there are more messages to iterate over.
 *
 * @return bool
 */
function bp_thread_messages() {
	global $thread_template;

	return $thread_template->messages();
}

/**
 * Set up the current thread inside the loop.
 *
 * @return object
 */
function bp_thread_the_message() {
	global $thread_template;

	return $thread_template->the_message();
}

/**
 * Output the ID of the thread that the current loop belongs to.
 */
function bp_the_thread_id() {
	echo (int) bp_get_the_thread_id();
}
	/**
	 * Get the ID of the thread that the current loop belongs to.
	 *
	 * @return int
	 */
function bp_get_the_thread_id() {
	global $thread_template;

	/**
	 * Filters the ID of the thread that the current loop belongs to.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param int $thread_id ID of the thread.
	 */
	return apply_filters( 'bp_get_the_thread_id', $thread_template->thread->thread_id );
}

/**
 * Output the subject of the thread currently being iterated over.
 */
function bp_the_thread_subject() {
	echo bp_get_the_thread_subject();
}
	/**
	 * Get the subject of the thread currently being iterated over.
	 *
	 * @return string
	 */
function bp_get_the_thread_subject() {
	global $thread_template;

	/**
	 * Filters the subject of the thread currently being iterated over.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string $last_message_subject Subject of the thread currently being iterated over.
	 */
	return apply_filters( 'bp_get_the_thread_subject', $thread_template->thread->last_message_subject );
}

/**
 * Get a list of thread recipients or a "x recipients" string.
 *
 * In BuddyPress 2.2.0, this parts of this functionality were moved into the
 * members/single/messages/single.php template. This function is no longer used
 * by BuddyPress.
 *
 * @return string
 */
function bp_get_the_thread_recipients() {
	if ( 5 <= bp_get_thread_recipients_count() ) {
		$recipients = sprintf( __( '%s recipients', 'buddyboss' ), bp_core_number_format( bp_get_thread_recipients_count() ) );
	} else {
		$recipients = bp_get_thread_recipients_list();
	}

	return apply_filters( 'bp_get_the_thread_recipients', $recipients );
}

/**
 * Get the number of recipients in the current thread.
 *
 * @since BuddyPress 2.2.0
 *
 * @return int
 */
function bp_get_thread_recipients_count() {
	global $thread_template;
	/**
	 * Filters the total number of recipients in a thread.
	 *
	 * @since BuddyPress 2.8.0
	 *
	 * @param int $count Total recipients number.
	 */
	return (int) apply_filters( 'bp_get_thread_recipients_count', count( $thread_template->thread->recipients ) );
}

/**
 * Get the max number of recipients to list in the 'Conversation between...' gloss.
 *
 * @since BuddyPress 2.3.0
 *
 * @return int
 */
function bp_get_max_thread_recipients_to_list() {
	/**
	 * Filters the max number of recipients to list in the 'Conversation between...' gloss.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param int $count Recipient count. Default: 5.
	 */
	return (int) apply_filters( 'bp_get_max_thread_recipients_to_list', 5 );
}

/**
 * Output HTML links to recipients in the current thread.
 *
 * @since BuddyPress 2.2.0
 */
function bp_the_thread_recipients_list() {
	echo bp_get_thread_recipients_list();
}
	/**
	 * Generate HTML links to the profiles of recipients in the current thread.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @return string
	 */
function bp_get_thread_recipients_list() {
	global $thread_template;

	$recipient_links = array();

	foreach ( (array) $thread_template->thread->recipients as $recipient ) {
		if ( (int) $recipient->user_id !== bp_loggedin_user_id() ) {
			$recipient_link = bp_core_get_userlink( $recipient->user_id );

			if ( empty( $recipient_link ) ) {
				$recipient_link = __( 'Deleted User', 'buddyboss' );
			}

			$recipient_links[] = $recipient_link;
		} else {
			$recipient_links[] = __( 'you', 'buddyboss' );
		}
	}

	// Concatenate to natural language string.
	$recipient_links = wp_sprintf_l( '%l', $recipient_links );

	/**
	 * Filters the HTML links to the profiles of recipients in the current thread.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param string $value Comma-separated list of recipient HTML links for current thread.
	 */
	return apply_filters( 'bp_get_the_thread_recipients_list', $recipient_links );
}

/**
 * Echo the ID of the current message in the thread.
 *
 * @since BuddyPress 1.9.0
 */
function bp_the_thread_message_id() {
	echo (int) bp_get_the_thread_message_id();
}
	/**
	 * Get the ID of the current message in the thread.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @return int
	 */
function bp_get_the_thread_message_id() {
	global $thread_template;

	$thread_message_id = isset( $thread_template->message->id )
		? (int) $thread_template->message->id
		: null;

	/**
	 * Filters the ID of the current message in the thread.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param int $thread_message_id ID of the current message in the thread.
	 */
	return apply_filters( 'bp_get_the_thread_message_id', $thread_message_id );
}

/**
 * Output the CSS classes for messages within a single thread.
 *
 * @since BuddyPress 2.1.0
 */
function bp_the_thread_message_css_class() {
	echo esc_attr( bp_get_the_thread_message_css_class() );
}
	/**
	 * Generate the CSS classes for messages within a single thread.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @return string
	 */
function bp_get_the_thread_message_css_class() {
	global $thread_template;

	$classes = array();

	// Zebra-striping.
	$classes[] = bp_get_the_thread_message_alt_class();

	// ID of the sender.
	$classes[] = 'sent-by-' . intval( $thread_template->message->sender_id );

	// Whether the sender is the same as the logged-in user.
	if ( bp_loggedin_user_id() == $thread_template->message->sender_id ) {
		$classes[] = 'sent-by-me';
	}

	/**
	 * Filters the CSS classes for messages within a single thread.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param array $classes Array of classes to add to the HTML class attribute.
	 */
	$classes = apply_filters( 'bp_get_the_thread_message_css_class', $classes );

	return implode( ' ', $classes );
}

/**
 * Output the CSS class used for message zebra striping.
 */
function bp_the_thread_message_alt_class() {
	echo esc_attr( bp_get_the_thread_message_alt_class() );
}
	/**
	 * Get the CSS class used for message zebra striping.
	 *
	 * @return string
	 */
function bp_get_the_thread_message_alt_class() {
	global $thread_template;

	if ( $thread_template->current_message % 2 == 1 ) {
		$class = 'even alt';
	} else {
		$class = 'odd';
	}

	/**
	 * Filters the CSS class used for message zebra striping.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $class Class determined to be next for zebra striping effect.
	 */
	return apply_filters( 'bp_get_the_thread_message_alt_class', $class );
}

/**
 * Output the ID for message sender within a single thread.
 *
 * @since BuddyPress 2.1.0
 */
function bp_the_thread_message_sender_id() {
	echo (int) bp_get_the_thread_message_sender_id();
}
	/**
	 * Return the ID for message sender within a single thread.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @return int
	 */
function bp_get_the_thread_message_sender_id() {
	global $thread_template;

	$user_id = ! empty( $thread_template->message->sender_id )
		? $thread_template->message->sender_id
		: 0;

	/**
	 * Filters the ID for message sender within a single thread.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param int $user_id ID of the message sender.
	 */
	return (int) apply_filters( 'bp_get_the_thread_message_sender_id', (int) $user_id );
}

/**
 * Output the avatar for the current message sender.
 *
 * @param array|string $args See {@link bp_get_the_thread_message_sender_avatar_thumb()}
 *                           for a description.
 */
function bp_the_thread_message_sender_avatar( $args = '' ) {
	echo bp_get_the_thread_message_sender_avatar_thumb( $args );
}
	/**
	 * Get the avatar for the current message sender.
	 *
	 * @param array|string $args {
	 *     Array of arguments. See {@link bp_core_fetch_avatar()} for more
	 *     complete details. All arguments are optional.
	 *     @type string $type   Avatar type. Default: 'thumb'.
	 *     @type int    $width  Avatar width. Default: default for your $type.
	 *     @type int    $height Avatar height. Default: default for your $type.
	 * }
	 * @return string <img> tag containing the avatar.
	 */
function bp_get_the_thread_message_sender_avatar_thumb( $args = '' ) {
	global $thread_template;

	$r = bp_parse_args(
		$args,
		array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
		)
	);

	/**
	 * Filters the avatar for the current message sender.
	 *
	 * @since BuddyPress 1.1.0
	 * @since BuddyPress 2.6.0 Added the `$r` parameter.
	 *
	 * @param string $value <img> tag containing the avatar value.
	 * @param array  $r     Array of parsed arguments.
	 */
	return apply_filters(
		'bp_get_the_thread_message_sender_avatar_thumb',
		bp_core_fetch_avatar(
			array(
				'item_id' => $thread_template->message->sender_id,
				'type'    => $r['type'],
				'width'   => $r['width'],
				'height'  => $r['height'],
				'alt'     => bp_core_get_user_displayname( $thread_template->message->sender_id ),
			)
		),
		$r
	);
}

/**
 * Output a link to the sender of the current message.
 *
 * @since BuddyPress 1.1.0
 */
function bp_the_thread_message_sender_link() {
	echo esc_url( bp_get_the_thread_message_sender_link() );
}
	/**
	 * Get a link to the sender of the current message.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string
	 */
function bp_get_the_thread_message_sender_link() {
	global $thread_template;

	/**
	 * Filters the link to the sender of the current message.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value Link to the sender of the current message.
	 */
	return apply_filters( 'bp_get_the_thread_message_sender_link', bp_core_get_userlink( $thread_template->message->sender_id, false, true ) );
}

/**
 * Output the display name of the sender of the current message.
 *
 * @since BuddyPress 1.1.0
 */
function bp_the_thread_message_sender_name() {
	echo esc_html( bp_get_the_thread_message_sender_name() );
}
	/**
	 * Get the display name of the sender of the current message.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string
	 */
function bp_get_the_thread_message_sender_name() {
	global $thread_template;

	$display_name = bp_core_get_user_displayname( $thread_template->message->sender_id );

	if ( empty( $display_name ) ) {
		$display_name = __( 'Deleted User', 'buddyboss' );
	}

	/**
	 * Filters the display name of the sender of the current message.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $display_name Display name of the sender of the current message.
	 */
	return apply_filters( 'bp_get_the_thread_message_sender_name', $display_name );
}

/**
 * Output the URL for deleting the current thread.
 *
 * @since BuddyPress 1.5.0
 */
function bp_the_thread_delete_link() {
	echo esc_url( bp_get_the_thread_delete_link() );
}
	/**
	 * Get the URL for deleting the current thread.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string URL
	 */
function bp_get_the_thread_delete_link() {

	/**
	 * Filters the URL for deleting the current thread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value URL for deleting the current thread.
	 * @param string $value Text indicating action being executed.
	 */
	return apply_filters( 'bp_get_message_thread_delete_link', wp_nonce_url( bp_displayed_user_domain() . bp_get_messages_slug() . '/inbox/delete/' . bp_get_the_thread_id(), 'messages_delete_thread' ) );
}

/**
 * Output the 'Sent x hours ago' string for the current message.
 *
 * @since BuddyPress 1.1.0
 */
function bp_the_thread_message_time_since() {
	echo bp_get_the_thread_message_time_since();
}
	/**
	 * Generate the 'Sent x hours ago' string for the current message.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string
	 */
function bp_get_the_thread_message_time_since() {

	/**
	 * Filters the 'Sent x hours ago' string for the current message.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value Default text of 'Sent x hours ago'.
	 */
	return apply_filters( 'bp_get_the_thread_message_time_since', sprintf( __( '%s', 'buddyboss' ), bp_core_time_since( bp_get_the_thread_message_date_sent() ) ) );
}

/**
 * Output the timestamp for the current message.
 *
 * @since BuddyPress 2.1.0
 */
function bp_the_thread_message_date_sent() {
	echo bp_get_the_thread_message_date_sent();
}
	/**
	 * Generate the 'Sent x hours ago' string for the current message.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @return int
	 */
function bp_get_the_thread_message_date_sent() {
	global $thread_template;

	/**
	 * Filters the date sent value for the current message as a timestamp.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $value Timestamp of the date sent value for the current message.
	 */
	return apply_filters( 'bp_get_the_thread_message_date_sent', strtotime( $thread_template->message->date_sent ) );
}

/**
 * Output the content of the current message in the loop.
 *
 * @since BuddyPress 1.1.0
 */
function bp_the_thread_message_content() {
	echo bp_get_the_thread_message_content();
}
	/**
	 * Get the content of the current message in the loop.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string
	 */
function bp_get_the_thread_message_content() {
	global $thread_template;

	$content = $thread_template->message->message;

	// If user was deleted, mark content as deleted.
	if ( false === bp_core_get_core_userdata( bp_get_the_thread_message_sender_id() ) ) {
		$content = esc_html__( 'This message was deleted', 'buddyboss' );
	}

	/**
	 * Filters the content of the current message in the loop.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $message The content of the current message in the loop.
	 */
	return apply_filters( 'bp_get_the_thread_message_content', $content );
}

/**
 * Output the excerpt of the current message in the loop.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_the_thread_message_excerpt() {
	echo bb_get_the_thread_message_excerpt();
}
/**
 * Get the excerpt of the current message in the loop.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return string
 */
function bb_get_the_thread_message_excerpt() {
	global $thread_template;

	$content = $thread_template->message->message;

	// If user was deleted, mark content as deleted.
	if ( false === bp_core_get_core_userdata( bp_get_the_thread_message_sender_id() ) ) {
		$content = esc_html__( 'This message was deleted', 'buddyboss' );
	}

	/**
	 * Filters the excerpt of the current message in the loop.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $message The excerpt of the current message in the loop.
	 */
	return apply_filters( 'bb_get_the_thread_message_excerpt', preg_replace( '#(<br\s*?\/?>|</(\w+)><(\w+)>)#', ' ', $content ) );
}

/** Embeds *******************************************************************/

/**
 * Enable oEmbed support for Messages.
 *
 * @since BuddyPress 1.5.0
 *
 * @see BP_Embed
 */
function bp_messages_embed() {
	add_filter( 'embed_post_id', 'bp_get_the_thread_message_id' );
	add_filter( 'bp_embed_get_cache', 'bp_embed_message_cache', 10, 3 );
	add_action( 'bp_embed_update_cache', 'bp_embed_message_save_cache', 10, 3 );
}
add_action( 'thread_loop_start', 'bp_messages_embed' );

/**
 * Fetch a private message item's cached embeds.
 *
 * Used during {@link BP_Embed::parse_oembed()} via {@link bp_messages_embed()}.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $cache    An empty string passed by BP_Embed::parse_oembed() for
 *                         functions like this one to filter.
 * @param int    $id       The ID of the message item.
 * @param string $cachekey The cache key generated in BP_Embed::parse_oembed().
 * @return mixed The cached embeds for this message item.
 */
function bp_embed_message_cache( $cache, $id, $cachekey ) {
	return bp_messages_get_meta( $id, $cachekey );
}

/**
 * Set a private message item's embed cache.
 *
 * Used during {@link BP_Embed::parse_oembed()} via {@link bp_messages_embed()}.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $cache    An empty string passed by BP_Embed::parse_oembed() for
 *                         functions like this one to filter.
 * @param string $cachekey The cache key generated in BP_Embed::parse_oembed().
 * @param int    $id       The ID of the message item.
 */
function bp_embed_message_save_cache( $cache, $cachekey, $id ) {
	bp_messages_update_meta( $id, $cachekey, $cache );
}

/**
 * Get the total number of recipients in the current thread.
 *
 * @since BuddyBoss 1.7.6
 *
 * @return int Return total recipients count.
 */
function bb_get_thread_total_recipients_count() {
	global $thread_template;
	/**
	 * Filters the total number of recipients in a thread.
	 *
	 * @since 1.7.6
	 *
	 * @param int $count Total recipients number.
	 */
	return (int) apply_filters( 'bb_get_thread_total_recipients_count', $thread_template->thread->total_recipients_count );
}

/**
 * Generate the human readable string for the current thread last message.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int|string $last_message_date The earlier time from which you're calculating
 *                                      the time elapsed. Enter either as an integer Unix timestamp,
 *                                      or as a date string of the format 'Y-m-d h:i:s'.
 *
 * @return string
 */
function bb_get_thread_sent_date( $last_message_date = false ) {
	global $messages_template;

	if ( empty( $last_message_date ) && isset( $messages_template->thread->last_message_date ) ) {
		$last_message_date = $messages_template->thread->last_message_date;
	}

	/**
	 * Filters the value to use if the time since is unknown.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$unknown_text = apply_filters( 'bb_get_thread_sent_date_unknown_text', __( 'Sometime', 'buddyboss' ) );

	if ( empty( $last_message_date ) ) {
		return $unknown_text;
	}

	/**
	 * Filters the value to use if the time since is right now.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$right_now_text = apply_filters( 'bb_get_thread_sent_date_right_now_text', __( 'Now', 'buddyboss' ) );

	// Array of time period chunks.
	$chunks = array(
		YEAR_IN_SECONDS,
		WEEK_IN_SECONDS,
		DAY_IN_SECONDS,
		1,
	);

	if ( is_numeric( $last_message_date ) ) {
		$last_message_date_formatted = bp_core_get_format_date( $last_message_date, 'Y-m-d H:i:s' );
	} else {
		$last_message_date_formatted = $last_message_date;
	}

	// Add 5mins to check the with the sent date.
	$five_minutes = date_i18n( 'Y-m-d H:i:s', strtotime( '+5 min', strtotime( $last_message_date_formatted ) ) );

	// Convert UTC to WordPress timezone.
	$last_message_date = get_date_from_gmt( $last_message_date_formatted );

	// Backup formatted date to use later.
	$old_last_date     = $last_message_date;
	$last_message_date = strtotime( $last_message_date );

	if ( strtotime( 'now' ) <= strtotime( $five_minutes ) ) {
		$output = $right_now_text;
	} else {

		/**
		 * $newer_date will equal false if we want to know the time elapsed between
		 * a date and the today end time. $newer_date will have a value if we want to
		 * work out time elapsed between two known dates.
		 */
		$newer_date = date_i18n( 'Y-m-d' ) . ' 23:59:59';
		$newer_date = strtotime( $newer_date );

		// Difference in seconds.
		$since = $newer_date - $last_message_date;
		if ( 0 > $since ) {
			$output = $unknown_text;

			/**
			 * We only want to output only one chunk of time here, eg:
			 * Dec 20, 2021
			 * May 26
			 * Wednesday
			 * Yesterday
			 * 1:00PM
			 * Now
			 * so there's only one bit of calculation below:
			 */
		} else {

			/**
			 * Initializing the count variable to avoid undefined notice
			 */
			$count   = 0;
			$seconds = 0;

			for ( $i = 0, $j = count( $chunks ); $i < $j; ++$i ) {
				$seconds = $chunks[ $i ];

				// Finding the biggest chunk (if the chunk fits, break).
				$count = floor( $since / $seconds );
				if ( 0 != $count ) {
					break;
				}
			}

			// If $i iterates all the way to $j, then the event happened 0 seconds ago.
			if ( ! isset( $chunks[ $i ] ) ) {
				$output = $right_now_text;

			} else {
				// Set output var.
				switch ( $seconds ) {
					case YEAR_IN_SECONDS:
						$date_year    = bp_core_get_format_date( $old_last_date, 'Y' );
						$current_year = bp_core_get_format_date( bp_core_current_time( false ), 'Y' );

						if ( $current_year === $date_year ) {
							$output = bp_core_get_format_date( $old_last_date, 'M j' );
						} else {
							$output = bp_core_get_format_date( $old_last_date, 'M j, Y' );
						}
						break;
					case WEEK_IN_SECONDS:
						$start_week = bb_get_week_timestamp( '-7 days' );
						$end_week   = bb_get_week_timestamp( 'now', 'end' );

						if ( $start_week <= $last_message_date && $end_week >= $last_message_date ) {
							$output = bp_core_get_format_date( $old_last_date, 'l' );
						} else {
							$output = bp_core_get_format_date( $old_last_date, 'M j' );
						}
						break;
					case DAY_IN_SECONDS:
						$start_week = bb_get_week_timestamp( '-7 days' );
						$end_week   = bb_get_week_timestamp( 'now', 'end' );
						if ( 1 == $count ) {
							$output = __( 'Yesterday', 'buddyboss' );
						} elseif ( $start_week <= $last_message_date && $end_week >= $last_message_date ) {
							$output = bp_core_get_format_date( $old_last_date, 'l' );
						} else {
							$output = bp_core_get_format_date( $old_last_date, 'M j' );
						}
						break;
					case 1:
						$output = bp_core_get_format_date( $old_last_date, 'g:i A' );
						break;
					default:
						$output = $right_now_text;
				}

				// No output, so happened right now.
				if ( ! (int) $count ) {
					$output = $right_now_text;
				}
			}
		}
	}

	/**
	 * Filters the date sent value for the current message as a timestamp.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string     $output             Timestamp of the date sent value for the current message.
	 * @param int|string $old_last_date     The earlier time from which you're calculating
	 *                                      the time elapsed. Enter either as an integer Unix timestamp,
	 *                                      or as a date string of the format 'Y-m-d h:i:s'.
	 */
	return apply_filters( 'bb_get_thread_sent_date', $output, $old_last_date );
}

/**
 * Generate the human readable string for the current thread start date.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int|string $thread_start_date The earlier time from which you're calculating
 *                                      the time elapsed. Enter either as an integer Unix timestamp,
 *                                      or as a date string of the format 'Y-m-d h:i:s'.
 * @param bool       $show_week_days    Display week day if true.
 *
 * @return string
 */
function bb_get_thread_start_date( $thread_start_date = false, $show_week_days = true ) {
	global $thread_template;

	if ( empty( $thread_start_date ) && isset( $thread_template->thread->first_message_date ) ) {
		$thread_start_date = $thread_template->thread->first_message_date;
	}

	/**
	 * Filters the value to use if the time since is unknown.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$unknown_text = apply_filters( 'bb_get_thread_start_date_unknown_text', __( 'Sometime', 'buddyboss' ) );

	if ( empty( $thread_start_date ) ) {
		return $unknown_text;
	}

	/**
	 * Filters the value to use if the time since is right now.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$today_text = apply_filters( 'bb_get_thread_start_date_today_text', __( 'today', 'buddyboss' ) );

	// Array of time period chunks.
	$chunks = array(
		YEAR_IN_SECONDS,
		WEEK_IN_SECONDS,
		DAY_IN_SECONDS,
		1,
	);

	if ( is_numeric( $thread_start_date ) ) {
		$last_message_date_formatted = bp_core_get_format_date( $thread_start_date, 'Y-m-d H:i:s' );
	} else {
		$last_message_date_formatted = $thread_start_date;
	}

	// Convert UTC to WordPress timezone.
	$thread_start_date = get_date_from_gmt( $last_message_date_formatted );

	// Backup formatted date to use later.
	$old_start_date    = $thread_start_date;
	$thread_start_date = strtotime( $thread_start_date );

	/**
	 * $newer_date will equal false if we want to know the time elapsed between
	 * a date and the today end time. $newer_date will have a value if we want to
	 * work out time elapsed between two known dates.
	 */
	$newer_date = date_i18n( 'Y-m-d' ) . ' 23:59:59';
	$newer_date = strtotime( $newer_date );

	$end_week = bb_get_week_timestamp( '-7 days' );

	// Difference in seconds.
	$since = $newer_date - $thread_start_date;
	if ( 0 > $since ) {
		$output = $unknown_text;

		/**
		 * We only want to output only one chunk of time here, eg:
		 * Dec 20, 2021
		 * May 26
		 * Wednesday
		 * Yesterday
		 * Today
		 * so there's only one bit of calculation below:
		 */
	} else {

		/**
		 * Initializing the count variable to avoid undefined notice
		 */
		$count   = 0;
		$seconds = 0;

		for ( $i = 0, $j = count( $chunks ); $i < $j; ++$i ) {
			$seconds = $chunks[ $i ];

			// Finding the biggest chunk (if the chunk fits, break).
			$count = floor( $since / $seconds );
			if ( 0 != $count ) {
				break;
			}
		}

		// If $i iterates all the way to $j, then the event happened 0 seconds ago.
		if ( ! isset( $chunks[ $i ] ) ) {
			$output = $today_text;

		} else {

			if ( $show_week_days ) {
				$format = 'l, F j';
			} else {
				$format = 'F j';
			}

			// Set output var.
			switch ( $seconds ) {
				case YEAR_IN_SECONDS:
					$date_year    = bp_core_get_format_date( $old_start_date, 'Y' );
					$current_year = bp_core_get_format_date( bp_core_current_time( false ), 'Y' );

					if ( $current_year === $date_year ) {
						$output = bp_core_get_format_date( $old_start_date, $format );
					} else {
						$output = bp_core_get_format_date( $old_start_date, 'F j, Y' );
					}
					break;
				case WEEK_IN_SECONDS:
					if ( $end_week <= $thread_start_date ) {
						$output = bp_core_get_format_date( $old_start_date, 'l' );
					} else {
						$output = bp_core_get_format_date( $old_start_date, $format );
					}
					break;
				case DAY_IN_SECONDS:
					if ( 1 == $count ) {
						$output = __( 'yesterday', 'buddyboss' );
					} elseif ( $end_week <= $thread_start_date ) {
						$output = bp_core_get_format_date( $old_start_date, 'l' );
					} else {
						$output = bp_core_get_format_date( $old_start_date, $format );
					}
					break;
				default:
					$output = $today_text;
			}

			// No output, so happened right now.
			if ( ! (int) $count ) {
				$output = $today_text;
			}
		}
	}

	/**
	 * Filters the date sent value for the current message as a timestamp.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string     $value             Timestamp of the date sent value for the current message.
	 * @param int|string $old_start_date    The earlier time from which you're calculating
	 *                                      the time elapsed. Enter either as an integer Unix timestamp,
	 *                                      or as a date string of the format 'Y-m-d h:i:s'.
	 * @param bool   $show_week_days        Optional. Show the weekdays or not.
	 */
	return apply_filters( 'bb_get_thread_start_date', $output, $old_start_date, $show_week_days );
}

/**
 * Generate the 'g:i A' string for the current message.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return string
 */
function bb_get_the_thread_message_sent_time() {
	global $thread_template;

	$sent_date_formatted      = $thread_template->message->date_sent;
	$site_sent_date           = get_date_from_gmt( $sent_date_formatted );
	$thread_message_sent_time = date_i18n( 'g:i A', strtotime( $site_sent_date ) );

	/**
	 * Filters the 'Sent x hours ago' string for the current message.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $value Default text of 'Sent x hours ago'.
	 */
	return apply_filters( 'bb_get_the_thread_message_sent_time', $thread_message_sent_time );
}

/**
 * Output the archived messages component slug.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_messages_archived_slug() {
	echo bb_get_messages_archived_slug();
}

/**
 * Return the archived messages component slug.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return string
 */
function bb_get_messages_archived_slug() {

	/**
	 * Filters the archived messages component slug.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $slug Archived messages component slug.
	 */
	return apply_filters( 'bb_get_messages_archived_slug', 'archived' );
}

/**
 * Output the archived messages component URL.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_messages_archived_url() {
	echo esc_url( bb_get_messages_archived_url() );
}

/**
 * Return the archived messages component URL.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return string
 */
function bb_get_messages_archived_url() {

	/**
	 * Filters the archived messages component URL.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $slug Archived messages component URL.
	 */
	return apply_filters( 'bb_get_messages_archived_url', trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/' . bb_get_messages_archived_slug() ) );
}

/**
 * Output the permalink for a particular archived thread.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $thread_id Optional. ID of the archived thread. Default: current archived thread
 *                       being iterated on in the loop.
 * @param int $user_id   Optional. ID of the user relative to whom the link
 *                       should be generated. Default: ID of logged-in user.
 */
function bb_message_archived_thread_view_link( $thread_id = 0, $user_id = null ) {
	echo bb_get_message_archived_thread_view_link( $thread_id, $user_id );
}
/**
 * Get the permalink of a particular archived thread.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $thread_id Optional. ID of the archived thread. Default: current
 *                       archived thread being iterated on in the loop.
 * @param int $user_id   Optional. ID of the user relative to whom the link
 *                       should be generated. Default: ID of logged-in user.
 * @return string
 */
function bb_get_message_archived_thread_view_link( $thread_id = 0, $user_id = null ) {
	global $messages_template;

	if ( empty( $messages_template ) && (int) $thread_id > 0 ) {
		$thread_id = (int) $thread_id;
	} elseif ( ! empty( $messages_template->thread->thread_id ) ) {
		$thread_id = $messages_template->thread->thread_id;
	}

	if ( null === $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	$domain = bp_core_get_user_domain( $user_id );

	/**
	 * Filters the permalink of a particular archived thread.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $value     Permalink of a particular archived thread.
	 * @param int    $thread_id ID of the archived thread.
	 * @param int    $user_id   ID of the user.
	 */
	return apply_filters( 'bp_get_message_thread_view_link', trailingslashit( $domain . bp_get_messages_slug() . '/archived/view/' . $thread_id ), $thread_id, $user_id );
}

/**
 * Apply filter to show hide the message button on member list page.
 *
 * @since BuddyBoss 2.3.90
 *
 * @param bool $enabled_message_action Whether to show the message button or not.
 * @param int  $member_id              Member ID.
 * @param int  $current_user_id        Current user ID.
 *
 * @return bool
 */
function bb_member_loop_show_message_button( $enabled_message_action, $member_id, $current_user_id ) {
	return (bool) (
		$enabled_message_action &&
		bb_messages_user_can_send_message(
			array(
				'sender_id'     => $current_user_id,
				'recipients_id' => $member_id,
			)
		)
	);
}

add_filter( 'bb_member_loop_show_message_button', 'bb_member_loop_show_message_button', 10, 3 );
