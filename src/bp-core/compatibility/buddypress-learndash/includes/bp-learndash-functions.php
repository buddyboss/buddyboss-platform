<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * courses name can be changed from here
 * @return string
 */
function bp_learndash_profile_courses_name()
{
    return LearnDash_Custom_Label::get_label( 'courses' );
}

/**
 * courses slug can be changed from here
 * @return string
 */
function bp_learndash_profile_courses_slug()
{
    return apply_filters( 'bp_learndash_profile_courses_slug', 'courses' );
}

/**
 * My courses name can be changed from here
 * @return string
 */
function bp_learndash_profile_my_courses_name()
{
    return sprintf( __( 'My %s', 'buddypress-learndash' ), LearnDash_Custom_Label::get_label( 'courses' ) );
}

/**
 * My courses slug can be changed from here
 * @return string
 */
function bp_learndash_profile_my_courses_slug()
{
    return apply_filters( 'bp_learndash_profile_my_courses_slug', 'my-courses' );
}

function bp_learndash_profile_create_courses_name() {
    return sprintf( __( 'Create a %s', 'buddypress-learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
}

function bp_learndash_profile_create_courses_slug() {
    return apply_filters( 'bp_learndash_profile_create_courses_slug', 'create-courses' );
}

function bp_learndash_get_nav_link($slug, $parent_slug=''){
    $displayed_user_id = bp_displayed_user_id();
    $user_domain = ( ! empty( $displayed_user_id ) ) ? bp_displayed_user_domain() : bp_loggedin_user_domain();
    if(!empty($parent_slug)){
        $nav_link = trailingslashit( $user_domain . $parent_slug .'/'. $slug );
    }else{
        $nav_link = trailingslashit( $user_domain . $slug );
    }
    return $nav_link;
}

function bp_learndash_adminbar_nav_link($slug, $parent_slug=''){
    $user_domain = bp_loggedin_user_domain();
    if(!empty($parent_slug)){
        $nav_link = trailingslashit( $user_domain . $parent_slug .'/'. $slug );
    }else{
        $nav_link = trailingslashit( $user_domain . $slug );
    }
    return $nav_link;
}

function bp_learndash_get_all_users(){
    global $wpdb;
    $user_ids = array();
    $user_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
    return $user_ids;
}

function bp_learndash_sql_member_type_id($type_name){
    global $wpdb;

    if ( empty( $type_name ) ) return;

    $type_id = $wpdb->get_col("SELECT t.term_id FROM {$wpdb->prefix}terms t INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id WHERE t.slug = '".$type_name."' AND  tt.taxonomy = 'bp_member_type' ");
    return !isset($type_id[0]) ? '' : $type_id[0];
}

function bp_learndash_sql_members_by_type($type_id){
    global $wpdb;

    if ( empty( $type_id ) ) return array();

    $student_ids = $wpdb->get_col("SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->prefix}term_relationships r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = ".$type_id);

    return (array) $student_ids;
}

function bp_learndash_sql_members_count_by_type($type_id){
    global $wpdb;

    if ( empty( $type_id ) ) return;

    $student_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} u INNER JOIN {$wpdb->prefix}term_relationships r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = ".$type_id);
    return $student_count;
}

function bp_learndash_members_count_by_type($type_name){

    if ( empty( $type_name ) ) return;

    $type_id = bp_learndash_sql_member_type_id($type_name);
    $student_ids = bp_learndash_sql_members_by_type($type_id);
    $members_count = is_array( $student_ids ) ? count($student_ids) : 0;
    return $members_count;
}

function bp_learndash_get_user_profile($user_id=''){
    if(isset($user_id))
        $user_id = $user_id;
    else
    {
        $current_user = wp_get_current_user();

        if(empty($current_user->ID))
            return;

        $user_id = $current_user->ID;
    }
    $user_courses = ld_get_mycourses($user_id);
    if(empty($current_user))
        $current_user = get_user_by("id", $user_id);
    $usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );
    $quiz_attempts_meta = empty($usermeta) ?  false : $usermeta;
    $quiz_attempts  = array();
    if(!empty($quiz_attempts_meta))
        foreach($quiz_attempts_meta as $quiz_attempt) {
            $c = learndash_certificate_details($quiz_attempt['quiz'], $user_id);
            $quiz_attempt['post'] = get_post( $quiz_attempt['quiz'] );
            $quiz_attempt["percentage"]  = !empty($quiz_attempt["percentage"])? $quiz_attempt["percentage"]:(!empty($quiz_attempt["count"])? $quiz_attempt["score"]*100/$quiz_attempt["count"]:0  );

            if($user_id == get_current_user_id() && !empty($c["certificateLink"]) && ((isset($quiz_attempt['percentage']) && $quiz_attempt['percentage'] >= $c["certificate_threshold"] * 100)))
                $quiz_attempt['certificate'] = $c;
            $quiz_attempts[learndash_get_course_id($quiz_attempt['quiz'])][] = $quiz_attempt;
        }
    return $user_courses;
}

/**
 * Get Course members
 * @param type $course_id
 * @return array
 */
function bp_learndash_get_course_members( $course_id ) {
	$meta = get_post_meta( $course_id, '_sfwd-courses', true );

	if ( !empty( $meta['sfwd-courses_course_access_list'] ) )
		$course_access_list = explode( ',', $meta['sfwd-courses_course_access_list'] );
	else
		$course_access_list = array();

	return $course_access_list;
}

/**
 * Add members to groups
 * @param type $course_id
 * @param type $group_id
 */
function bp_learndash_add_members_group( $course_id, $group_id ) {

	$course_students = bp_learndash_get_course_members( $course_id );

	if ( empty( $course_students ) ) {
		return;
	}
	if ( is_array( $course_students ) ) {
		foreach ( $course_students as $course_students_id ) {
			groups_join_group( $group_id, $course_students_id );
		}
	} else {
		groups_join_group( $group_id, $course_students );
	}
}

/**
 * Removes members from group
 * @param type $course_id
 * @param type $group_id
 */
function bp_learndash_remove_members_group( $course_id, $group_id ) {

	$course_students = bp_learndash_get_course_members( $course_id );

	if ( empty( $course_students ) ) {
		return;
	}
	if ( is_array( $course_students ) ) {
		foreach ( $course_students as $course_students_id ) {
			groups_remove_member( $course_students_id, $group_id );
		}
	} else {
		groups_remove_member( $course_students, $group_id );
	}
}

/**
 * Add course teacher as group admin
 * @param type $course_id
 * @param type $group_id
 */
 function bp_learndash_course_teacher_group_admin( $course_id, $group_id ) {

	$teacher = get_post_field( 'post_author', $course_id );
	groups_join_group( $group_id, $teacher );
	$member = new BP_Groups_Member( $teacher, $group_id );
	$member->promote( 'admin' );

 }

/**
 * Inserts a new forum and attachs it to the group
 * @param type $group_id
 */
function bp_learndash_attach_forum( $group_id ) {
	global $wpdb;

	if ( class_exists('bbPress') && groups_get_group( $group_id )->enable_forum ) {

		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( $group->enable_forum == '1' ) {

			// Use the existing forum IDs
			$forum_ids = array_values( bbp_get_group_forum_ids( $group_id ) );

			//Insert new forum if the group has not any forum linked with it
			if ( empty( $forum_ids ) ) {
				$forum_id = bbp_insert_forum( array( 'post_title' => $group->name ) );
			} else {

				// No support for multiple forums yet
				$forum_id = (int) ( is_array( $forum_ids )
					? $forum_ids[0]
					: $forum_ids );

				$wpdb->query( "UPDATE {$wpdb->posts} SET post_title = '{$group->name}' WHERE id = {$forum_id}" );
			}

			bbp_add_forum_id_to_group( $group_id, $forum_id );
			bbp_add_group_id_to_forum( $forum_id, $group_id );
			bp_learndash_enable_disable_group_forum( '1', $group_id );
		}

	}
}

/**
 * Group forum enable/disable
 * @param type $enable
 * @param type $group_id
 */
function bp_learndash_enable_disable_group_forum( $enable, $group_id ) {
	$group = groups_get_group( array( 'group_id' => $group_id ) );
	$group->enable_forum = $enable;
	$group->save();
}

/**
* alter group status
* @param type $group_id
*/
function bp_learndash_alter_group_status( $group_id ) {
   $group = groups_get_group( array( 'group_id' => $group_id ) );

   if ( 'public' == $group->status ) {
	   $group->status = 'private';

   } elseif ( 'hidden' == $group->status ) {
	   $group->status = 'hidden';
   }
   $group->save();

}

/**
 * Update group avatar with course avatar
 * @global type $bp
 * @param type $course_id
 * @param type $group_id
 */
function bp_learndash_update_group_avatar( $course_id, $group_id ) {

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	$group_avatar = bp_get_group_has_avatar( $group_id );
	if ( ! empty( $group_avatar ) ) {
		return;
	}

	$attached_media_id = get_post_thumbnail_id( $course_id, $group_id );

	if ( empty($attached_media_id) ) {
		return;
	}

	$attachment_src = wp_get_attachment_image_src( $attached_media_id, 'full' );

	$wp_upload_dir = wp_upload_dir();
	$tfile = uniqid() . '.jpg';
	file_put_contents( $wp_upload_dir[ "basedir" ] . "/" . $tfile, file_get_contents( $attachment_src[0] ) );

	$temp_file = download_url( $wp_upload_dir[ "baseurl" ] . "/" . $tfile, 5 );

	if ( ! is_wp_error( $temp_file ) ) {

		// array based on $_FILE as seen in PHP file uploads
		$file = array(
			'name' => basename( $tfile ), // ex: wp-header-logo.png
			'type' => 'image/png',
			'tmp_name' => $temp_file,
			'error' => 0,
			'size' => filesize( $temp_file ),
		);

		$_FILES[ "file" ] = $file;
	}

	global $bp;
	if ( ! isset( $bp->groups->current_group ) || ! isset( $bp->groups->current_group->id ) ) {
		//required for groups_avatar_upload_dir function
		$bp->groups->current_group = new stdClass();
		$bp->groups->current_group->id = $group_id;
	}

	if ( ! isset( $bp->avatar_admin ) )
		$bp->avatar_admin = new stdClass ();

	if ( isset($_POST['action']) ) {
        $original_action = $_POST[ 'action' ];
    }

	$_POST[ 'action' ] = 'bp_avatar_upload';
	// Pass the file to the avatar upload handler
	if ( bp_core_avatar_handle_upload( $_FILES, 'groups_avatar_upload_dir' ) ) {
		//avatar upload was successful
		//do cropping
		list($width, $height, $type, $attr) = getimagesize( $bp->avatar_admin->image->url );
		$args = array(
			'object' => 'group',
			'avatar_dir' => 'group-avatars',
			'item_id' => $bp->groups->current_group->id,
			'original_file' => bp_get_avatar_to_crop_src(),
			'crop_x' => 0,
			'crop_y' => 0,
			'crop_h' => $height,
			'crop_w' => $width
		);

		bp_core_avatar_handle_crop( $args );
	}

    if ( isset($original_action) ) {
        $_POST[ 'action' ] = $original_action;
    }
}

/**
 * Record an activity item
 */
function bp_learndash_record_activity( $args = '' ) {
    global $bp;

    if ( !function_exists( 'bp_activity_add' ) ) return false;

    $defaults = array(
        'id' => false,
        'user_id' => $bp->loggedin_user->id,
        'action' => '',
        'content' => '',
        'primary_link' => '',
        'component' => $bp->profile->id,
        'type' => false,
        'item_id' => false,
        'secondary_item_id' => false,
        'recorded_time' => gmdate( "Y-m-d H:i:s" ),
        'hide_sitewide' => false
    );

    $r = wp_parse_args( $args, $defaults );
    extract( $r );

    $activity_id = groups_record_activity( array(
        'id' => $id,
        'user_id' => $user_id,
        'action' => $action,
        'content' => $content,
        'primary_link' => $primary_link,
        'component' => $component,
        'type' => $type,
        'item_id' => $item_id,
        'secondary_item_id' => $secondary_item_id,
        'recorded_time' => $recorded_time,
        'hide_sitewide' => $hide_sitewide
    ) );

	bp_activity_add_meta( $activity_id, 'bp_learndash_group_activity_markup', 'true' );

	return $activity_id;
}

	/**
	* Learndash activity filter
	* @param type $has_activities
	* @param type $activities
	* @return type array
	*/
	function bp_learndash_activity_filter( $has_activities, $activities ) {

	   if ( bp_current_component() != 'activity' ) {
		   return $has_activities;
	   }
	   $remove_from_stream = false;

	   foreach ( $activities->activities as $key => $activity ) {

		   if ( function_exists('groups_is_user_member') && $activity->component == 'groups' && ! groups_is_user_member( bp_current_user_id(), $activity->item_id ) ) {
			   $act_visibility = bp_activity_get_meta( $activity->id, 'bp_learndash_group_activity_markup',true );
			   if ( !empty( $act_visibility ) ) {
				   $remove_from_stream = true;
			   }
		   }

		   if ( $remove_from_stream && isset( $activities->activity_count ) ) {
			   $activities->activity_count = $activities->activity_count - 1;
			   unset( $activities->activities[ $key ] );
			   $remove_from_stream = false;
		   }
	   }

	   $activities_new = array_values( $activities->activities );
	   $activities->activities = $activities_new;

	   return $has_activities;
	}

	// add_action( 'bp_has_activities', 'bp_learndash_activity_filter', 110, 2 );

	/**
	 * Learndash menu items
	 * @global type $pagenow
	 */
	function learndash_add_custom_menu_items() {
		global $pagenow;

		if( 'nav-menus.php' == $pagenow ) {
			add_meta_box( 'add-learndash-links', 'Learndash', 'wp_nav_menu_item_learndash_links_meta_box', 'nav-menus', 'side', 'low' );
		}
	}
	add_action( 'admin_init', 'learndash_add_custom_menu_items' );

	function wp_nav_menu_item_learndash_links_meta_box( $object ) {
		global $nav_menu_selected_id;

		$menu_items = array(
			'#learndashmycourses' => sprintf( __( 'My %s', 'buddypress-learndash' ), LearnDash_Custom_Label::get_label( 'courses' ) ),
		);

		$menu_items_obj = array();
		foreach ( $menu_items as $value => $title ) {
			$menu_items_obj[$title] = new stdClass;
			$menu_items_obj[$title]->object_id			= esc_attr( $value );
			$menu_items_obj[$title]->title				= esc_attr( $title );
			$menu_items_obj[$title]->url				= esc_attr( $value );
			$menu_items_obj[$title]->description 		= 'description';
			$menu_items_obj[$title]->db_id 				= 0;
			$menu_items_obj[$title]->object 			= 'learndash';
			$menu_items_obj[$title]->menu_item_parent 	= 0;
			$menu_items_obj[$title]->type 				= 'custom';
			$menu_items_obj[$title]->target 			= '';
			$menu_items_obj[$title]->attr_title 		= '';
			$menu_items_obj[$title]->classes 			= array();
			$menu_items_obj[$title]->xfn 				= '';
		}

		$walker = new Walker_Nav_Menu_Checklist( array() );
		?>

		<div id="learndash-links" class="learndashdiv taxonomydiv">
			<div id="tabs-panel-learndash-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">

				<ul id="learndash-linkschecklist" class="list:learndash-links categorychecklist form-no-clear">
					<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $menu_items_obj ), 0, (object)array( 'walker' => $walker ) ); ?>
				</ul>

			</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'buddypress-learndash' ); ?>" name="add-learndash-links-menu-item" id="submit-learndash-links" />
					<span class="spinner"></span>
				</span>
			</p>
		</div><!-- .learndashdiv -->
		<?php
	}

	/**
	 * learndash_setup_nav_menu_item function.
	 *
	 * Generate the urls for Learndash custom menu items.
	 *
	 * @access public
	 * @param object $item
	 * @return object $item
	 */
	function learndash_setup_nav_menu_item( $item ) {
		global $pagenow, $wp_rewrite;

		if( 'nav-menus.php' != $pagenow && !defined('DOING_AJAX') && isset( $item->url ) && 'custom' == $item->type ) {

			$my_courses_url = bp_get_loggedin_user_link().bp_learndash_profile_courses_slug();

			switch ( $item->url ) {

				case '#learndashmycourses':
					$item->url = $my_courses_url;
					break;

				default:
					break;
			}

			$_root_relative_current = untrailingslashit( $_SERVER['REQUEST_URI'] );
			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_root_relative_current );
			$item_url = untrailingslashit( $item->url );
			$_indexless_current = untrailingslashit( preg_replace( '/' . preg_quote( $wp_rewrite->index, '/' ) . '$/', '', $current_url ) );
			// Highlight current menu item
			if ( $item_url && in_array( $item_url, array( $current_url, $_indexless_current, $_root_relative_current ) ) ) {
				$item->classes[] = 'current-menu-item current_page_item';
			}

		} // endif nav

		return $item;

	} // End learndash_setup_nav_menu_item()

	add_filter( 'wp_setup_nav_menu_item', 'learndash_setup_nav_menu_item' );

/**
 * Hide lessons and topics from users if they dont have access to its parent course.
 * Prevent those from appearing in bp-global-search results.
 *
 * @param string $sql
 * @param mixed $args
 * @return string
 */
function bp_learndash_bgs_filter_entries( $sql, $args='' ){
    //dont change if the query is for any other post type
    if( !isset( $args['post_type'] ) || !in_array( $args['post_type'], array( 'sfwd-lessons', 'sfwd-topic' ) ) ){
        return $sql;
    }

    $filtered_post_ids = array( 1 );//dummy, to return no results
    /**
     * Get all course ids the user has access to.
     * This includes courses open to guest users.
     */
    $user_courses = ld_get_mycourses( get_current_user_id() );
    if( !empty( $user_courses ) && is_array( $user_courses ) ){
        $args = array(
            'post_type'         => $args['post_type'],
            'posts_per_page'    => -1,
			'fields'            => 'ids',
            'meta_query'        => array(
                array(
                    'key'       => 'course_id',
                    'value'     => $user_courses,
                    'compare'   => 'IN',
                ),
            ),
        );
        $pi_q = new WP_Query( $args );

        if( $pi_q->have_posts() ){
            $filtered_post_ids = array();
            while( $pi_q->have_posts() ){
                $pi_q->the_post();
                $filtered_post_ids[] = get_the_ID();
            }
        }
        wp_reset_postdata();
    }

    $post_ids_csv = implode( ',', $filtered_post_ids );
    $sql .= " AND id IN ( {$post_ids_csv} ) ";

    return $sql;
}
add_filter( 'BBoss_Global_Search_CPT_sql', 'bp_learndash_bgs_filter_entries', 9, 2 );

function bp_learndash_group_activity_is_on( $key, $group_id=false, $default_true=true ){
    if ( ! function_exists( 'buddypress' ) || ! bp_is_active( 'groups' ) ) {
        return false;
    }
    if( !$group_id ){
        $group_id = bp_get_group_id();
    }

    $retval = $default_true;
        $bp_sensei_course_activity = groups_get_groupmeta( $group_id, 'group_extension_course_setting_activities' );
    if( is_array( $bp_sensei_course_activity ) ){
        $retval = isset( $bp_sensei_course_activity[$key] );
    }

    return $retval;
}

/**
 * Get attached group id from course id
 * @param $course_id
 * @return null|string
 */
function bp_learndash_course_group_id( $course_id ) {
	global $wpdb;

	$sql = "SELECT group_id FROM {$wpdb->base_prefix}bp_groups_groupmeta WHERE  meta_key = 'bp_course_attached' AND meta_value = {$course_id}";

	return $wpdb->get_var( $sql );
}


/**
 * Output current status of course
 *
 * @since 2.1.0
 *
 * @param  int 		$id
 * @param  int 		$user_id
 * @return string 	output of current course status
 */
function bp_learndash_course_status( $id, $user_id = null ) {
    if ( empty( $user_id ) ) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
    }

    $course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

    $has_completed_topic = false;

    if ( ! empty( $course_progress[ $id ] ) && ! empty( $course_progress[ $id ]['topics'] ) && is_array( $course_progress[ $id ]['topics'] ) ) {
        foreach ( $course_progress[ $id ]['topics'] as $lesson_topics ) {
            if ( ! empty( $lesson_topics ) && is_array( $lesson_topics ) ) {
                foreach ( $lesson_topics as $topic ) {
                    if ( ! empty( $topic ) ) {
                        $has_completed_topic = true;
                        break;
                    }
                }
            }

            if ( $has_completed_topic ) {
                break;
            }
        }
    }

    $quiz_notstarted = true;
    $quizzes = learndash_get_global_quiz_list( $id );

    if ( ! empty( $quizzes ) ) {
        foreach ( $quizzes as $quiz ) {
            if ( ! learndash_is_quiz_notcomplete( $user_id, array( $quiz->ID => 1 ) ) ) {
                $quiz_notstarted = false;
            }
        }
    }

    if ( ( empty( $course_progress[ $id ] ) || empty( $course_progress[ $id ]['lessons'] ) && ! $has_completed_topic ) && $quiz_notstarted ) {
       return 'not_started';
    } else if ( empty( $course_progress[ $id ] ) || @$course_progress[ $id ]['completed'] < @$course_progress[ $id ]['total'] ) {
       return 'in_progress';
    } else {
       return 'completed';
    }
}

/**
 * Add or remove member from the buddypress group on a course access update
 * @param $user_id
 * @param $course_id
 * @param $remove
 */
function bp_learndash_user_course_access_update( $user_id, $course_id, $remove ) {
    $group_attached = (int) get_post_meta( $course_id, 'bp_course_group', true );

    if ( empty( $group_attached ) ) {
        return;
    }

    if ( false == $remove && !groups_is_user_member( $user_id, $group_attached ) ) {

        // Add a student to the group
        groups_join_group( $group_attached, $user_id );

        // Record course started activity
        if( bp_learndash_group_activity_is_on( 'user_course_start', $group_attached ) ) {
            global $bp;
            $user_link = bp_core_get_userlink($user_id);
            $course_title = get_the_title($course_id);
            $course_link = get_permalink($course_id);
            $course_link_html = '<a href="' . esc_url($course_link) . '">' . $course_title . '</a>';
            $args = array(
                'type' => 'started_course',
                'user_id' => $user_id,
                'action' => apply_filters('bp_learndash_user_course_start_activity',
                    sprintf(__('%1$s started taking the course %2$s', 'buddypress-learndash'),
                        $user_link, $course_link_html), $user_id, $course_id),
                'item_id' => $group_attached,
                'secondary_item_id' =>  $course_id,
                'component' => $bp->groups->id
            );
            $activity_recorded = bp_learndash_record_activity($args);
            if ($activity_recorded) {
                bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $course_id);
            }
        }
    } elseif ( true == $remove ) {
        // Remove student from the group
        groups_remove_member( $user_id, $group_attached );
    }
}