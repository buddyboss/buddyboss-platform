<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//add shortcode support
add_shortcode( 'BBOSS_AJAX_SEARCH_FORM', 'buddyboss_global_search_ajax_search_form_shortcode' );
function buddyboss_global_search_ajax_search_form_shortcode( $atts ) {
	return buddyboss_global_search_buffer_template_part( 'ajax-search-form', '', false );
}

/**
 * Returns a trimmed activity content string.
 * Must be used while inside activity loop
 */
function buddyboss_global_search_activity_intro( $character_limit = 50 ) {
	$content = '';
	if ( bp_activity_has_content() ) {
		$content = bp_get_activity_content_body();

		if ( $content ) {
			$content = wp_strip_all_tags( $content, true );

			$shortened_content = substr( $content, 0, $character_limit );
			if ( strlen( $content ) > $character_limit ) {
				$shortened_content .= '...';
			}

			$content = $shortened_content;
		}
	}

	return apply_filters( 'buddyboss_global_search_activity_intro', $content );
}

/**
 * Returns a trimmed reply content string.
 * Works for replies as well as topics.
 * Must be used while inside the loop
 */
function buddyboss_global_search_reply_intro( $character_limit = 50 ) {
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
			$shortened_content = buddyboss_global_search_result_match( $content, $search_term );

			//highlight search keyword

			$shortened_content = str_ireplace( $search_term, "<strong>" . $search_term . "</strong>", $shortened_content );
		} else {
			$shortened_content = substr( $content, 0, $character_limit );

			if ( strlen( $content ) > $character_limit ) {
				$shortened_content .= '...';
			}
		}

		$content = $shortened_content;
	}

	return apply_filters( 'buddyboss_global_search_reply_intro', $content );
}

/**
 * Returns   highlighted search keyword and trimmed content string with
 *
 * @param $content
 * @param int $character_limit
 *
 * @return mixed|void
 */
function buddyboss_global_search_result_intro( $content, $character_limit = 50 ) {

	$content     = wp_strip_all_tags( $content, true );
	$search_term = BP_Search::instance()->search->get_search_term();

	$search_term_position = stripos( $content, $search_term );

	if ( $search_term_position !== false ) {
		$shortened_content = '...' . substr( $content, $search_term_position, $character_limit );
		//highlight search keyword

		$shortened_content = str_ireplace( $search_term, "<strong>" . $search_term . "</strong>", $shortened_content );
	} else {
		$shortened_content = substr( $content, 0, $character_limit );
	}

	if ( strlen( $content ) > $character_limit ) {
		$shortened_content .= '...';
	}

	$content = $shortened_content;

	return apply_filters( 'buddyboss_global_search_result_intro', $content );
}

/**
 * Find a certain word in a string, and then wrap around it
 *
 * @param $in
 * @param $wordToFind
 * @param int $numWordsToWrap
 *
 * @return string
 */
function buddyboss_global_search_result_match( $in, $wordToFind, $numWordsToWrap = 10 ) {

	$words       = preg_split( '/\s+/', $in );
	$wordsToFind = preg_split( '/\s+/', $wordToFind );

	foreach ( $wordsToFind as $key => $value ) {
		$found_words = preg_grep( "/" . $value . ".*/i", $words );
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

		$pre_start = ( $start > 0 ) ? "..." : "";

		$post_end = ( $pos + ( $numWordsToWrap + 1 ) < count( $words ) ) ? "..." : "";

		$out = $pre_start . implode( ' ', $slice ) . $post_end;

		return $out;
	}

	return $in;

}

if ( ! function_exists( 'emi_generate_paging_param' ) ):

	/**
	 * Prints pagination links for given parameters with pagination value as a querystring parameter.
	 * Instead of printing http://yourdomain.com/../../page/2/, it prints http://yourdomain.com/../../?list=2
	 *
	 * If your theme uses twitter bootstrap styles, define a constant :
	 * define('BOOTSTRAP_ACTIVE', true)
	 * and this function will generate the bootstrap-pagination-compliant html.
	 *
	 * @param int $total_items total number of items(grand total)
	 * @param int $items_per_page number of items displayed per page
	 * @param int $curr_paged current page number, 1 based index
	 * @param string $slug part of url to be appended after the home_url and before the '/?ke1=value1&...' part. withour any starting or trailing '/'
	 * @param string $hashlink Optional, the '#' link to be appended ot url, optional
	 * @param int $links_on_each_side Optional, how many links to be displayed on each side of current active page. Default 2.
	 * @param string $param_key the name of the queystring paramter for page value. Default 'list'
	 *
	 * @return void
	 */
	function emi_generate_paging_param( $total_items, $items_per_page, $curr_paged, $slug, $links_on_each_side = 2, $hashlink = "", $param_key = "list" ) {
		$use_bootstrap = false;
		if ( defined( 'BOOTSTRAP_ACTIVE' ) ) {
			$use_bootstrap = true;
		}

		$s = $links_on_each_side; //no of tabs to show for previos/next paged links
		if ( $curr_paged == 0 ) {
			$curr_paged = 1;
		}
		/* $elements : an array of arrays; each child array will have following structure
		  $child[0] = text of the link
		  $child[1] = page no of target page
		  $child[2] = link type :: link|current|nolink
		 */
		$elements    = array();
		$no_of_pages = ceil( $total_items / $items_per_page );
		//prev lik
		if ( $curr_paged > 1 ) {
			$elements[] = array( '&larr;', $curr_paged - 1, 'link' );
		}
		//generating $s(2) links before the current one
		if ( $curr_paged > 1 ) {
			$rev_array = array(); //paged in reverse order
			$i         = $curr_paged - 1;
			$counter   = 0;
			while ( $counter < $s && $i > 0 ) {
				$rev_array[] = $i;
				$i --;
				$counter ++;
			}
			$arr = array_reverse( $rev_array );
			if ( $counter == $s ) {
				$elements[] = array( ' ... ', '', 'nolink' );
			}
			foreach ( $arr as $el ) {
				$elements[] = array( $el, $el, 'link' );
			}
			unset( $rev_array );
			unset( $arr );
			unset( $i );
			unset( $counter );
		}

		//generating $s+1(3) links after the current one (includes current)
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
				$elements[] = array( ' ... ', '', 'nolink' );
			}
			unset( $i );
			unset( $counter );
		}
		//next link
		if ( $curr_paged < $no_of_pages ) {
			$elements[] = array( '&rarr;', $curr_paged + 1, 'link' );
		}
		/* enough php, lets echo some html */
		if ( isset( $elements ) && count( $elements ) > 1 ) {
			?>
			<div class="pagination navigation">
				<?php if ( $use_bootstrap ): ?>
				<div class='pagination-links'>
					<?php else: ?>
					<div class="pagination-links">
						<?php endif; ?>
						<?php
						foreach ( $elements as $e ) {
							$link_html = "";
							$class     = "";
							switch ( $e[2] ) {
								case 'link':
									unset( $_GET[ $param_key ] );
									$base_link = get_bloginfo( 'url' ) . "/$slug?";
									foreach ( $_GET as $k => $v ) {
										$base_link .= "$k=$v&";
									}
									$base_link .= "$param_key=$e[1]";
									if ( isset( $hashlink ) && $hashlink != "" ) {
										$base_link .= "#$hashlink";
									}
									$link_html = "<a href='$base_link' title='$e[0]' class='page-numbers' data-pagenumber='$e[1]'>$e[0]</a>";
									break;
								case 'current':
									$class = "active";
									if ( $use_bootstrap ) {
										$link_html = "<span>$e[0] <span class='sr-only'>(current)</span></span>";
									} else {
										$link_html = "<span class='page-numbers current'>$e[0]</span>";
									}
									break;
								default:
									if ( $use_bootstrap ) {
										$link_html = "<span>$e[0]</span>";
									} else {
										$link_html = "<span class='page-numbers'>$e[0]</span>";
									}
									break;
							}

							//$link_html = "<li class='" . esc_attr($class) . "'>" . $link_html . "</li>";
							echo $link_html;
						}
						?>
						<?php if ( $use_bootstrap ): ?>
					</div>
					<?php else: ?>
				</div>
			<?php endif; ?>
			</div>
			<?php
		}
	}

endif;


/**
 * Buddyboss global search items options
 * @return mixed|void
 */
function buddyboos_global_search_items() {

	$items = array(
		'posts'          => __( 'Blog Posts', 'buddypress-global-search' ),
		'pages'          => __( 'Pages', 'buddypress-global-search' ),
		'posts_comments' => __( 'Post Comments', 'buddypress-global-search' ),
		'members'        => __( 'Members', 'buddypress-global-search' ),
	);

	//forums?

	$items['forum'] = __( 'Forums', 'buddypress-global-search' );
	$items['topic'] = __( 'Forums Topics', 'buddypress-global-search' );
	$items['reply'] = __( 'Forums Replies', 'buddypress-global-search' );


	//other buddypress components
	$bp_components = array(
		'groups'   => __( 'Groups', 'buddypress-global-search' ),
		'activity' => __( 'Activity', 'buddypress-global-search' ),
		'messages' => __( 'Messages', 'buddypress-global-search' ),
		/* should we search notifications as well?
		'notifications'	=> __( 'Notifications', 'buddypress-global-search' ), */
	);

	//only the active ones please!
	foreach ( $bp_components as $component => $label ) {
		if ( function_exists( 'bp_is_active' ) && bp_is_active( $component ) ) {
			$items[ $component ] = $label;

			if ( 'activity' === $component ) {
				$items['activity_comment'] = __( 'Activity Comment', 'buddypress-global-search' );
			}
		}
	}

	return apply_filters( 'buddyboos_global_search_items', $items );
}

/**
 * Generate search string on profile update
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

add_filter( 'body_class', 'buddyboss_global_search_body_class', 10, 1 );

/**
 * Add 'buddypress' and 'directory' in Body tag classes list
 *
 * @param $wp_classes
 *
 * @return mixed|void
 */
function buddyboss_global_search_body_class( $wp_classes ) {

	if ( is_search() ) { //if search page.
		$wp_classes[] = 'buddypress';
		$wp_classes[] = 'directory';
	}

	$wp_classes[] = 'bp-search';

	return apply_filters( 'buddyboss_global_search_body_class', $wp_classes );
}

function bp_get_search_user_fields() {
	return [
		'user_meta'    => __( 'User Meta', 'buddyboss' ),
		'display_name' => __( 'Display Name', 'buddyboss' ),
		'user_email'   => __( 'User Email', 'buddyboss' ),
		'user_login'   => __( 'Username', 'buddyboss' )
	];
}

function bp_search_get_post_thumbnail_default( $post_type ) {

	$default = [
		'product'     => buddypress()->plugin_url . 'bp-core/images/mystery-product.png',
		'sfwd-course' => buddypress()->plugin_url . 'bp-core/images/mystery-course.png',
		'post'        => buddypress()->plugin_url . 'bp-core/images/mystery-blog.png',
	];

	return
		isset( $default[ $post_type ] ) ?
			$default[ $post_type ] :
			buddypress()->plugin_url . 'bp-core/images/mystery-default.png';
}
