<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the default items to search though, if nothing has been selected in settings.
 * 
 * @since 1.0.0
 * @param mixed $value
 * @return mixed
 */
function bb_global_search_default_items_to_search( $value ){
	if( empty( $value ) ){
		/**
		 * Setting > what to search?
		 * If admin has not selected anything yet( right after activating the plugin maybe),
		 * lets make sure search results do return someting at least.
		 * So, by default, we'll search though blog posts and members.
		 */
		$value = array( 'posts', 'pages', 'members' );
	}
	
	/*
	 * If member search is turned on, but none of wp_user table fields or xprofile fields are selected,
	 * we'll force username and nicename fields
	 */
	if( in_array( 'members', $value ) ){
		// Is any wp_user table colum or xprofile field selected?
		$field_selected = false;
		foreach( $value as $item_to_search ){
			if( strpos( $item_to_search, 'member_field_' )===0 || strpos( $item_to_search, 'xprofile_field_' )===0 ){
				$field_selected = true;
				break;
			}
		}
		
		//if not, lets add username and nicename to default items to search
		if( !$field_selected ){
			$value[] = 'member_field_user_login';
			$value[] = 'member_field_user_nicename';
		}
	}
	
	return $value;
}
add_filter( 'buddyboss_global_search_option_items-to-search', 'bb_global_search_default_items_to_search' );

/**
 * Remove 'messages' and 'notifications' from search, if user is not logged In
 * 
 * @since 1.0.0
 * @param mixed $value
 * @return mixed
 */
function bboss_global_search_remove_search_types_for_guests( $search_types ){
	if( !is_admin() && !empty( $search_types ) && !is_user_logged_in() ){
		$items_to_remove = array( 'messages', 'notifications' );
		$filtered_search_types = array();
		foreach( $search_types as $search_type ){
			if( !in_array( $search_type, $items_to_remove ) ){
				$filtered_search_types[] = $search_type;
			}
		}
		
		$search_types = $filtered_search_types;
	}
	return $search_types;
}
add_filter( 'buddyboss_global_search_option_items-to-search', 'bboss_global_search_remove_search_types_for_guests', 9 );


add_filter( 'template_include', 'buddyboss_global_search_override_wp_native_results', 999 ); //don't leave any chance!.

/**
 * Force native wp search section to load page template so we can hook stuff into it.
 *
 * @since 1.0.0
 * @param mixed $value
 * @return mixed
 **/

function buddyboss_global_search_override_wp_native_results($template) {
	
	if ( is_search()  ) { //if search page.
		
		
		$live_template = locate_template( array( 'buddyboss-global-search.php' ,'page.php','single.php','index.php' ) );
		
		if ( '' != $live_template ) {
			return $live_template;
		}

	}
	
	return $template;
}


/**
 * Load dummy post for wp native search result. magic starts here.
 * @since 1.0.0 
 * @param mixed $value
 * @return mixed
 **/

add_filter( 'template_include', 'buddyboss_global_search_result_page_dummy_post_load', 999 ); //don't leave any chance!.

function buddyboss_global_search_result_page_dummy_post_load($template) {
	global $wp_query;
	
	if(!is_search()) { //cancel if not search page.
		return $template; 
	}
	
	$dummy = array(
               'ID'                    => 0,
               'post_status'           => 'public',
               'post_author'           => 0,
               'post_parent'           => 0,
               'post_type'             => 'page',
               'post_date'             => 0,
               'post_date_gmt'         => 0,
               'post_modified'         => 0,
               'post_modified_gmt'     => 0,
               'post_content'          => '',
               'post_title'            => __('Search Results',"buddypress-global-search"),
               'post_excerpt'          => '',
               'post_content_filtered' => '',
               'post_mime_type'        => '',
               'post_password'         => '',
               'post_name'             => '',
               'guid'                  => '',
               'menu_order'            => 0,
               'pinged'                => '',
               'to_ping'               => '',
               'ping_status'           => '',
               'comment_status'        => 'closed',
               'comment_count'         => 0,
               'filter'                => 'raw',
               'is_404'                => false,
               'is_page'               => false,
               'is_single'             => false,
               'is_archive'            => false,
               'is_tax'                => false,
               'is_search'             => true,
           );
	// Set the $post global
	$post = new WP_Post( (object) $dummy );
   
	// Copy the new post global into the main $wp_query
	$wp_query->post       = $post;
	$wp_query->posts      = array( $post );
	$wp_query->post_count      = 1;
	$wp_query->max_num_pages      = 0;
		
	return $template;
}

/**
 * Force native wp search page not to look any data into db to save query and performance
 * @since 1.0.0
 * @param mixed $value
 * @return mixed
 **/

add_filter('pre_get_posts','buddyboss_global_search_clear_native_search_query');

function buddyboss_global_search_clear_native_search_query($query) {
	
	if ($query->is_search && !is_admin() ) {
    
	    remove_filter('pre_get_posts','buddyboss_global_search_clear_native_search_query'); //only do first time
    
	}
    
	return $query;
}
