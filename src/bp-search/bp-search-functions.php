<?php
/**
 * BuddyBoss Search Functions.
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// add shortcode support
add_shortcode( 'BBOSS_AJAX_SEARCH_FORM', 'bp_search_ajax_search_form_shortcode' );
/**
 * Returns search buffer ajax template part
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_ajax_search_form_shortcode( $atts ) {
	return bp_search_buffer_template_part( 'ajax-search-form', '', false );
}

/**
 * Returns a trimmed activity content string.
 * Must be used while inside activity loop
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_activity_intro( $character_limit = 50 ) {
	$content = '';
	if ( bp_activity_has_content() ) {
		$content = bp_get_activity_content_body();

		if ( $content ) {
			$content = wp_strip_all_tags( $content, true );

			$shortened_content = substr( $content, 0, $character_limit );
			if ( strlen( $content ) > $character_limit ) {
				$shortened_content .= '&hellip;';
			}

			$content = $shortened_content;
		}
	}

	return apply_filters( 'bp_search_activity_intro', $content );
}

/**
 * Returns a trimmed reply content string.
 * Works for replies as well as topics.
 * Must be used while inside the loop
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_reply_intro( $character_limit = 50 ) {
	$content = '';

	switch ( get_post_type( get_the_ID() ) ) {
		case 'topic':
			$reply_content = bbp_get_topic_content( get_the_ID() );
			break;
		case 'reply':
			$reply_content = bbp_get_reply_content( get_the_ID() );
			break;
		default:
			$reply_content = get_the_content();
			break;
	}

	if ( $reply_content ) {
		$content     = wp_strip_all_tags( $reply_content, true );
		$search_term = BP_Search::instance()->get_search_term();

		$search_term_position = stripos( $content, $search_term );
		if ( $search_term_position !== false ) {
			$shortened_content = bp_search_result_match( $content, $search_term );

			// highlight search keyword

			$shortened_content = str_ireplace( $search_term, '<strong>' . $search_term . '</strong>', $shortened_content );
		} else {
			$shortened_content = substr( $content, 0, $character_limit );

			if ( strlen( $content ) > $character_limit ) {
				$shortened_content .= '&hellip;';
			}
		}

		$content = $shortened_content;
	}

	return apply_filters( 'bp_search_reply_intro', $content );
}

/**
 * Returns   highlighted search keyword and trimmed content string with
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $content
 * @param int     $character_limit
 *
 * @return mixed|void
 */
function bp_search_result_intro( $content, $character_limit = 50 ) {

	$content     = wp_strip_all_tags( $content, true );
	$search_term = BP_Search::instance()->search->get_search_term();

	$search_term_position = stripos( $content, $search_term );

	if ( $search_term_position !== false ) {
		$shortened_content = '&hellip;' . substr( $content, $search_term_position, $character_limit );
		// highlight search keyword

		$shortened_content = str_ireplace( $search_term, '<strong>' . $search_term . '</strong>', $shortened_content );
	} else {
		$shortened_content = substr( $content, 0, $character_limit );
	}

	if ( strlen( $content ) > $character_limit ) {
		$shortened_content .= '&hellip;';
	}

	$content = $shortened_content;

	return apply_filters( 'bp_search_result_intro', $content );
}

/**
 * Find a certain word in a string, and then wrap around it
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $in
 * @param $wordToFind
 * @param int        $numWordsToWrap
 *
 * @return string
 */
function bp_search_result_match( $in, $wordToFind, $numWordsToWrap = 10 ) {

	$words       = preg_split( '/\s+/', $in );
	$wordsToFind = preg_split( '/\s+/', $wordToFind );

	foreach ( $wordsToFind as $key => $value ) {
		$found_words = preg_grep( '/' . $value . '.*/i', $words );
		$found_pos   = array_keys( $found_words );

		if ( count( $found_pos ) ) {
			$pos = $found_pos[0];
			break;
		}
	}

	if ( isset( $pos ) ) {

		$start  = ( $pos - $numWordsToWrap > 0 ) ? $pos - $numWordsToWrap : 0;
		$length = ( ( $pos + ( $numWordsToWrap + 1 ) < count( $words ) ) ? $pos + ( $numWordsToWrap + 1 ) : count( $words ) ) - $start;
		$slice  = array_slice( $words, $start, $length );

		$pre_start = ( $start > 0 ) ? '&hellip;' : '';

		$post_end = ( $pos + ( $numWordsToWrap + 1 ) < count( $words ) ) ? '&hellip;' : '';

		$out = $pre_start . implode( ' ', $slice ) . $post_end;

		return $out;
	}

	return $in;

}

if ( ! function_exists( 'bp_search_pagination' ) ) :

	/**
	 * Prints pagination links for given parameters with pagination value as a querystring parameter.
	 * Instead of printing http://yourdomain.com/../../page/2/, it prints http://yourdomain.com/../../?list=2
	 *
	 * If your theme uses twitter bootstrap styles, define a constant :
	 * define('BOOTSTRAP_ACTIVE', true)
	 * and this function will generate the bootstrap-pagination-compliant html.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int    $total_items total number of items(grand total)
	 * @param int    $items_per_page number of items displayed per page
	 * @param int    $curr_paged current page number, 1 based index
	 * @param string $slug part of url to be appended after the home_url and before the '/?ke1=value1&...' part. withour any starting or trailing '/'
	 * @param string $hashlink Optional, the '#' link to be appended ot url, optional
	 * @param int    $links_on_each_side Optional, how many links to be displayed on each side of current active page. Default 2.
	 * @param string $param_key the name of the queystring paramter for page value. Default 'list'
	 *
	 * @return void
	 */
	function bp_search_pagination( $total_items, $items_per_page, $curr_paged, $slug, $links_on_each_side = 2, $hashlink = '', $param_key = 'list' ) {
		$use_bootstrap = false;
		if ( defined( 'BOOTSTRAP_ACTIVE' ) ) {
			$use_bootstrap = true;
		}

		$s = $links_on_each_side; // no of tabs to show for previos/next paged links
		if ( $curr_paged == 0 ) {
			$curr_paged = 1;
		}
		/*
		 $elements : an array of arrays; each child array will have following structure
		  $child[0] = text of the link
		  $child[1] = page no of target page
		  $child[2] = link type :: link|current|nolink
		 */
		$elements    = array();
		$no_of_pages = ceil( $total_items / $items_per_page );
		// prev lik
		if ( $curr_paged > 1 ) {
			$elements[] = array( '&larr;', $curr_paged - 1, 'link' );
		}
		// generating $s(2) links before the current one
		if ( $curr_paged > 1 ) {
			$rev_array = array(); // paged in reverse order
			$i         = $curr_paged - 1;
			$counter   = 0;
			while ( $counter < $s && $i > 0 ) {
				$rev_array[] = $i;
				$i --;
				$counter ++;
			}
			$arr = array_reverse( $rev_array );
			if ( $counter == $s ) {
				$elements[] = array( ' &hellip; ', '', 'nolink' );
			}
			foreach ( $arr as $el ) {
				$elements[] = array( $el, $el, 'link' );
			}
			unset( $rev_array );
			unset( $arr );
			unset( $i );
			unset( $counter );
		}

		// generating $s+1(3) links after the current one (includes current)
		if ( $curr_paged <= $no_of_pages ) {
			$i       = $curr_paged;
			$counter = 0;
			while ( $counter < $s + 1 && $i <= $no_of_pages ) {
				if ( $i == $curr_paged ) {
					$elements[] = array( $i, $i, 'current' );
				} else {
					$elements[] = array( $i, $i, 'link' );
				}
				$counter ++;
				$i ++;
			}
			if ( $counter == $s + 1 ) {
				$elements[] = array( ' &hellip; ', '', 'nolink' );
			}
			unset( $i );
			unset( $counter );
		}
		// next link
		if ( $curr_paged < $no_of_pages ) {
			$elements[] = array( '&rarr;', $curr_paged + 1, 'link' );
		}
		/* enough php, lets echo some html */
		if ( isset( $elements ) && count( $elements ) > 1 ) {
			?>
			<div class="pagination navigation">
				<?php if ( $use_bootstrap ) : ?>
				<div class='pagination-links'>
					<?php else : ?>
					<div class="pagination-links">
						<?php endif; ?>
						<?php
						foreach ( $elements as $e ) {
							$link_html = '';
							$class     = '';
							switch ( $e[2] ) {
								case 'link':
									unset( $_GET[ $param_key ] );
									$base_link = get_bloginfo( 'url' ) . "/$slug?";
									foreach ( $_GET as $k => $v ) {
										$base_link .= "$k=$v&";
									}
									$base_link .= "$param_key=$e[1]";
									if ( isset( $hashlink ) && $hashlink != '' ) {
										$base_link .= "#$hashlink";
									}
									$link_html = "<a href='" . esc_url( $base_link ) . "' title='" . esc_attr( $e[0] ) . "' class='page-numbers' data-pagenumber='" . esc_attr( $e[1] ) . "'>" . esc_html( $e[0] ) . "</a>";
									break;
								case 'current':
									$class = 'active';
									if ( $use_bootstrap ) {
										$link_html = "<span>" . esc_html( $e[0] ) . " <span class='sr-only'>(current)</span></span>";
									} else {
										$link_html = "<span class='page-numbers current'>" . esc_html( $e[0] ) . "</span>";
									}
									break;
								default:
									if ( $use_bootstrap ) {
										$link_html = "<span>" . esc_html( $e[0] ) . "</span>";
									} else {
										$link_html = "<span class='page-numbers'>" . esc_html( $e[0] ) . "</span>";
									}
									break;
							}

							// $link_html = "<li class='" . esc_attr($class) . "'>" . $link_html . "</li>";
							echo $link_html;
						}
						?>
						<?php if ( $use_bootstrap ) : ?>
					</div>
					<?php else : ?>
				</div>
			<?php endif; ?>
			</div>
			<?php
		}
	}

endif;

/**
 * Outputs BuddyBoss pagination number viewing and total
 *
 * @since BuddyBoss 1.0.0
 *
 * @return mixed|void
 */
function bp_search_pagination_page_counts( $total_items, $items_per_page, $curr_paged ) {

	if ( $curr_paged == 0 ) {
		$curr_paged = 1;
	}

	$to_num   = $curr_paged * $items_per_page;
	$from_num = $to_num - ( $items_per_page - 1 );

	?>
	<div class="pag-count bottom">
		<div class="pag-data">
			<?php printf( __( 'Viewing %1$d - %2$d of %3$d results', 'buddyboss' ), $from_num, min( $total_items, $to_num ), $total_items ); ?>
		</div>
	</div>
	<?php
}


/**
 * Buddyboss global search items options
 *
 * @since BuddyBoss 1.0.0
 *
 * @return mixed|void
 */
function bp_search_items() {

	$items = array(
		'posts'          => __( 'Blog Posts', 'buddyboss' ),
		'pages'          => __( 'Pages', 'buddyboss' ),
		'posts_comments' => __( 'Post Comments', 'buddyboss' ),
		'members'        => __( 'Members', 'buddyboss' ),
	);

	// forums?

	$items['forum'] = __( 'Forums', 'buddyboss' );
	$items['topic'] = __( 'Forum Discussions', 'buddyboss' );
	$items['reply'] = __( 'Forum Replies', 'buddyboss' );

	// other buddypress components
	$bp_components = array(
		'groups'   => __( 'Groups', 'buddyboss' ),
		'activity' => __( 'Activity', 'buddyboss' ),
		'messages' => __( 'Messages', 'buddyboss' ),
		/*
		 should we search notifications as well?
		'notifications'	=> __( 'Notifications', 'buddyboss' ), */
	);

	// only the active ones please!
	foreach ( $bp_components as $component => $label ) {
		if ( function_exists( 'bp_is_active' ) && bp_is_active( $component ) ) {
			$items[ $component ] = $label;

			if ( 'activity' === $component ) {
				$items['activity_comment'] = __( 'Activity Comments', 'buddyboss' );
			}
		}
	}

	return apply_filters( 'bp_search_items', $items );
}

/**
 * Generate search string on profile update
 *
 * @since BuddyBoss 1.0.0
 *
 * @param type $user_id
 * @param type $posted_field_ids
 * @param type $errors
 * @param type $old_values
 * @param type $new_values
 */
function bb_gs_create_searchstring( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {

	if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'xprofile' ) ) {
		return;
	}

	$items_to_search = BP_Search::instance()->searchable_items;

	$search_string = '';

	foreach ( $new_values as $key => $value ) {

		if ( in_array( 'xprofile_field_' . $key, $items_to_search ) ) {
			$search_string = $search_string . ' ' . $value['value'];
		}
	}
	update_user_meta( $user_id, 'bbgs_search_string', $search_string );

}

add_action( 'xprofile_updated_profile', 'bb_gs_create_searchstring', 1, 5 );

/**
 * Generate search string on group update.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param type $groupid
 */
function bb_gs_create_group_searchstring( $groupid ) {

	if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'groups' ) ) {
		return;
	}

	if ( empty( $groupid ) ) {
		global $bp;
		$groupid = $bp->groups->new_group_id;
	}

	$address = isset( $_POST['bpla-group-address'] ) ? $_POST['bpla-group-address'] : '';
	$street  = isset( $_POST['bpla-group-street'] ) ? $_POST['bpla-group-street'] : '';
	$city    = isset( $_POST['bpla-group-city'] ) ? $_POST['bpla-group-city'] : '';
	$state   = isset( $_POST['bpla-group-state'] ) ? $_POST['bpla-group-state'] : '';
	$zip     = isset( $_POST['bpla-group-zip'] ) ? $_POST['bpla-group-zip'] : '';
	$country = isset( $_POST['bpla-group-country'] ) ? $_POST['bpla-group-country'] : '';

	if ( $address ) {
		groups_update_groupmeta( $groupid, 'bbgs_group_search_string', $address );
	}
	if ( $street ) {
		groups_update_groupmeta( $groupid, 'bbgs_group_search_string', $address . ' ' . $street );
	}
	if ( $city ) {
		groups_update_groupmeta( $groupid, 'bbgs_group_search_string', $address . ' ' . $street . ' ' . $city );
	}
	if ( $state ) {
		groups_update_groupmeta( $groupid, 'bbgs_group_search_string', $address . ' ' . $street . ' ' . $city . ' ' . $state );
	}
	if ( $zip ) {
		groups_update_groupmeta( $groupid, 'bbgs_group_search_string', $address . ' ' . $street . ' ' . $city . ' ' . $state . ' ' . $zip );
	}
	if ( $country ) {
		groups_update_groupmeta( $groupid, 'bbgs_group_search_string', $address . ' ' . $street . ' ' . $city . ' ' . $state . ' ' . $zip . ' ' . $country );
	}

}

add_action( 'groups_create_group_step_save_group-details', 'bb_gs_create_group_searchstring' );
add_action( 'groups_details_updated', 'bb_gs_create_group_searchstring' );

add_filter( 'body_class', 'bp_search_body_class', 10, 1 );

/**
 * Add 'buddypress' and 'directory' in Body tag classes list
 *
 * @param $wp_classes
 *
 * @return mixed|void
 */
function bp_search_body_class( $wp_classes ) {

	if ( bp_search_is_search() ) { // if search page.
		$wp_classes[] = 'buddypress';
		$wp_classes[] = 'directory';
	}

	$wp_classes[] = 'bp-search';

	return apply_filters( 'bp_search_body_class', $wp_classes );
}

/**
 * Returns array of search field labels.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_get_search_user_fields() {
	return array(
		'user_meta'    => __( 'User Meta', 'buddyboss' ),
		'display_name' => __( 'Display Name', 'buddyboss' ),
		'user_email'   => __( 'User Email', 'buddyboss' ),
		'user_login'   => __( 'Username', 'buddyboss' ),
	);
}

/**
 * Returns the default post thumbnail based on post type
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_get_post_thumbnail_default( $post_type, $icon_type = 'svg' ) {

	$default = array(
		'product'             => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/product.svg' : 'bb-icon-shopping-cart',
		'sfwd-courses'        => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/course.svg' : 'bb-icon-course',
		'sfwd-lessons'        => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/course-content.svg' : 'bb-icon-book',
		'sfwd-topic'          => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/course-content.svg' : 'bb-icon-file-bookmark',
		'sfwd-quiz'           => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/quiz.svg' : 'bb-icon-f bb-icon-quiz',
		'post'                => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/blog-post.svg' : 'bb-icon-article',
		'forum'               => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/forum.svg' : 'bb-icon-comments-square',
		'topic'               => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/forum.svg' : 'bb-icon-comment-square-dots',
		'reply'               => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/forum.svg' : 'bb-icon-reply',
		'bp-member-type'      => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/membership.svg' : 'bb-icon-user',
		'memberpressproduct'  => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/membership.svg' : 'bb-icon-user',
		'wp-parser-function'  => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/code.svg' : 'bb-icon-code',
		'wp-parser-class'     => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/code.svg' : 'bb-icon-code',
		'wp-parser-hook'      => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/code.svg' : 'bb-icon-code',
		'wp-parser-method'    => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/code.svg' : 'bb-icon-code',
		'command'             => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/code.svg' : 'bb-icon-code',
		'course'              => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/course.svg' : 'bb-icon-course',
		'llms_membership'     => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/membership.svg' : 'bb-icon-user',
		'lesson'              => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/course-content.svg' : 'bb-icon-book',
		'llms_assignment'     => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/course-content.svg' : 'bb-icon-file-bookmark',
		'llms_certificate'    => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/course-content.svg' : 'bb-icon-certificate',
		'llms_my_certificate' => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/course-content.svg' : 'bb-icon-certificate',
		'llms_quiz'           => ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/quiz.svg' : 'bb-icon-quiz',
	);

	if ( isset( $default[ $post_type ] ) ) {
		return $default[ $post_type ];
	}

	return ( 'svg' === $icon_type ) ? buddypress()->plugin_url . 'bp-core/images/search/default.svg' : 'bb-icon-f bb-icon-file-doc';

}

/**
 * Returns total number of LearnDash lessons
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_get_total_lessons_count( $course_id ) {
	$lesson_ids = learndash_course_get_children_of_step( $course_id, $course_id, 'sfwd-lessons' );

	return count( $lesson_ids );
}

/**
 * Returns total number of LearnDash topics
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_get_total_topics_count( $lesson_id ) {
	$course_id = learndash_get_course_id( $lesson_id );
	$topic_ids = learndash_course_get_children_of_step( $course_id, $lesson_id, 'sfwd-topic' );

	return count( $topic_ids );
}

/**
 * Returns total number of LearnDash quizzes
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_get_total_quizzes_count( $lesson_id ) {
	$course_id = learndash_get_course_id( $lesson_id );
	$quiz_ids  = learndash_course_get_children_of_step( $course_id, $lesson_id, 'sfwd-quiz' );

	return count( $quiz_ids );
}

/**
 * Determines whether the query is for a network search.
 *
 * @since BuddyBoss 1.0.0
 * @return bool
 */
function bp_search_is_search() {
	return ! is_admin() && is_search() && isset( $_REQUEST['bp_search'] );
}

if ( in_array( 'geo-my-wp/geo-my-wp.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function bps_get_request( $type, $form = 0 ) {
		$current        = bps_current_page();
		$hidden_filters = bps_get_hidden_filters();

		$cookie  = apply_filters( 'bps_cookie_name', 'bps_request' );
		$request = isset( $_REQUEST['bps_form'] ) ? $_REQUEST : array();
		if ( empty( $request ) && isset( $_COOKIE[ $cookie ] ) ) {
			parse_str( stripslashes( $_COOKIE[ $cookie ] ), $request );
		}

		switch ( $type ) {
			case 'form':
				if ( isset( $request['bps_form'] ) && $request['bps_form'] != $form ) {
					$request = array();
				}
				break;

			case 'filters':
				if ( isset( $request['bps_directory'] ) && $request['bps_directory'] != $current ) {
					$request = array();
				}
				foreach ( $hidden_filters as $key => $value ) {
					unset( $request[ $key ] );
				}
				break;

			case 'search':
				if ( isset( $request['bps_directory'] ) && $request['bps_directory'] != $current ) {
					$request = array();
				}
				foreach ( $hidden_filters as $key => $value ) {
					$request[ $key ] = $value;
				}
				break;
		}

		return apply_filters( 'bps_request', $request, $type, $form );
	}

	function bps_current_page() {
		$current = defined( 'DOING_AJAX' ) ? parse_url(
			$_SERVER['HTTP_REFERER'],
			PHP_URL_PATH
		) : parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

		return apply_filters( 'bps_current_page', $current );        // published interface, 20190324
	}

	function bps_get_hidden_filters() {
		$data = bps_get_directory_data();
		unset( $data['page'], $data['template'], $data['ajax_template'], $data['show'], $data['order_by'] );

		return apply_filters( 'bps_hidden_filters', $data );
	}

	function bps_get_directory_data() {
		global $bps_directory_data;

		$data   = array();
		$cookie = apply_filters( 'bps_cookie_name', 'bps_directory' );

		if ( ! defined( 'DOING_AJAX' ) ) {
			$data = isset( $bps_directory_data ) ? $bps_directory_data : array();
		} elseif ( isset( $_COOKIE[ $cookie ] ) ) {
			$current = bps_current_page();
			parse_str( stripslashes( $_COOKIE[ $cookie ] ), $data );
			if ( $data['page'] != $current ) {
				$data = array();
			}
		}

		return apply_filters( 'bps_directory_data', $data );
	}
}

/**
 * Function to prevent to show the restricted content by third part plugins.
 *
 * @param int    $post_id post id to check that it is restricted or not.
 * @param int    $user_id user id to check that it is restricted or not.
 * @param string $type component type.
 *
 * @return array
 */
function bp_search_is_post_restricted( $post_id = 0, $user_id = 0, $type = 'post' ) {

	$restricted_post_data = array();

	if ( empty( $post_id ) ) {
		return $restricted_post_data;
	}

	if ( class_exists( 'PMPro_Members_List_Table' ) ) {

		$user_has_post_access = pmpro_has_membership_access( $post_id, $user_id );

		// check for the default post.
		if ( $user_has_post_access && 'post' === $type ) {
			$restricted_post_data['post_class']     = 'has-access';
			$restricted_post_data['post_thumbnail'] = get_the_post_thumbnail_url() ? get_the_post_thumbnail_url() : bp_search_get_post_thumbnail_default( get_post_type(), 'icon' );
			$restricted_post_data['post_content']   = make_clickable( get_the_excerpt() );
			$restricted_post_data['has_thumb']      = (bool) get_the_post_thumbnail_url();
		} elseif ( 'post' === $type ) {
			$restricted_post_data['post_class']     = 'has-no-access';
			$restricted_post_data['post_thumbnail'] = bp_search_get_post_thumbnail_default( get_post_type(), 'icon' );
			$restricted_post_data['post_content']   = pmpro_membership_content_filter( apply_filters( 'bp_post_restricted_message', 'This post has restricted content' ), false );
			$restricted_post_data['has_thumb']      = false;
		}

		// Check for the forums.
		if ( $user_has_post_access && 'forum' === $type ) {
			$restricted_post_data['post_class']     = 'has-access';
			$restricted_post_data['post_thumbnail'] = bbp_get_forum_thumbnail_src( $post_id ) ? bbp_get_forum_thumbnail_src( $post_id ) : bp_search_get_post_thumbnail_default( get_post_type(), 'icon' );
			$restricted_post_data['post_content']   = wp_trim_words( bbp_get_forum_content( $post_id ), 30, '...' );
			$restricted_post_data['has_thumb']      = (bool) bbp_get_forum_thumbnail_src( $post_id );
		} elseif ( 'forum' === $type ) {
			$restricted_post_data['post_class']     = 'has-no-access';
			$restricted_post_data['post_thumbnail'] = bp_search_get_post_thumbnail_default( get_post_type(), 'icon' );
			$restricted_post_data['post_content']   = pmpro_membership_content_filter( apply_filters( 'bp_post_restricted_message', 'This post has restricted content' ), false );
			$restricted_post_data['has_thumb']      = false;
		}
	} else {
		$restricted_post_data['post_class']     = 'has-access';
		$restricted_post_data['post_thumbnail'] = get_the_post_thumbnail_url() ? get_the_post_thumbnail_url() : bp_search_get_post_thumbnail_default( get_post_type(), 'icon' );
		$restricted_post_data['post_content']   = make_clickable( get_the_excerpt() );
		$restricted_post_data['has_thumb']      = (bool) get_the_post_thumbnail_url();
	}

	return $restricted_post_data;
}

/**
 * Generates keywords based on passed search terms.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param string $search_term Search string.
 * @param string $post_type   Post type.
 *
 * @return array Array of search string.
 */
function bb_search_get_search_keywords_by_term( $search_term = '', $post_type = '' ) {
	static $cache_search_terms = array();

	$search_term_array = array();

	if ( empty( $search_term ) ) {
		return $search_term_array;
	}

	$cache_key = 'bb_search_terms_' . $post_type . '_' . sanitize_title( $search_term );
	if ( isset( $cache_search_terms[ $cache_key ] ) ) {
		return $cache_search_terms[ $cache_key ];
	}

	// There are no line breaks in <input /> fields.
	$search_term = str_replace( array( "\r", "\n" ), '', stripslashes( $search_term ) );

	if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $search_term, $matches ) ) {
		$search_term_array = bb_search_parse_search_terms( $matches[0] );

		// If the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence.
		if ( empty( $search_term_array ) || count( $search_term_array ) > 9 ) {
			$search_term_array = array( $search_term );
		}
	} else {
		$search_term_array = array( $search_term );
	}

	// Set cache for search keywords.
	$cache_search_terms[ $cache_key ] = $search_term_array;

	return $search_term_array;
}

/**
 * Filter the generated keywords based on passed search terms.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param array $terms Search keywords.
 *
 * @return array Array contains validate search string.
 */
function bb_search_parse_search_terms( $terms = array() ) {
	$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
	$checked    = array();

	if ( empty( $terms ) ) {
		return $checked;
	}

	$stopwords = bb_search_get_search_stopwords();

	foreach ( $terms as $term ) {
		// Keep before/after spaces when term is for exact match.
		if ( preg_match( '/^".+"$/', $term ) ) {
			$term = trim( $term, "\"'" );
		} else {
			$term = trim( $term, "\"' " );
		}

		// Avoid single A-Z and single dashes.
		if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
			continue;
		}

		if ( in_array( call_user_func( $strtolower, $term ), $stopwords, true ) ) {
			continue;
		}

		$checked[] = $term;
	}

	return $checked;
}

/**
 * Retrieve stopwords used when parsing search terms.
 *
 * @since BuddyBoss 2.0.0
 *
 * @return array Stopwords.
 */
function bb_search_get_search_stopwords() {
	static $stoped_keywords = array();

	if ( ! empty( $stoped_keywords ) ) {
		return $stoped_keywords;
	}

	/*
	 * translators: This is a comma-separated list of very common words that should be excluded from a search,
	 * like a, an, and the. These are usually called "stopwords". You should not simply translate these individual
	 * words into your language. Instead, look for and provide commonly accepted stopwords in your language.
	 */
	$words = explode(
		',',
		_x(
			'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
			'Comma-separated list of search stopwords in your language',
			'buddyboss'
		)
	);

	foreach ( $words as $word ) {
		$word = trim( $word, "\r\n\t " );
		if ( $word ) {
			$stoped_keywords[] = $word;
		}
	}

	return $stoped_keywords;
}
