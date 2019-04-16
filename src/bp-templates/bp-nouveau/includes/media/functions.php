<?php
/**
 * Media functions
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Media component
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific media scripts.
 */
function bp_nouveau_media_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-media' => array(
			'file'         => 'js/buddypress-media%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'footer'       => true,
		),
		'bp-nouveau-media-theatre' => array(
			'file'         => 'js/buddypress-media-theatre%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'version'      => bp_get_version(),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the media scripts
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_media_enqueue_scripts() {
	wp_enqueue_script( 'bp-nouveau-media' );
	wp_enqueue_script( 'bp-nouveau-media-theatre' );
}

/**
 * Localize the strings needed for the messages UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array $params Associative array containing the JS Strings needed by scripts
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_media_localize_scripts( $params = array() ) {

	$params['media'] = array(
		'max_upload_size' => bp_media_file_upload_max_size(),
		'group_media'     => bp_is_group_media_support_enabled(),
		'group_album'     => bp_is_group_album_support_enabled(),
		'messages_media'  => bp_is_messages_media_support_enabled(),
	);

	if ( bp_is_single_album() ) {
		$params['media']['album_id'] = (int) bp_action_variable( 0 );
	}

	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$params['media']['group_id'] = bp_get_current_group_id();
    }

	return $params;
}

/**
 * Add media theatre template for activity pages
 */
function bp_nouveau_media_add_theatre_template() {
	bp_get_template_part( 'media/theatre' );
}

/**
 * Get activity entry media to render on front end
 */
function bp_nouveau_media_activity_entry() {
	global $media_template;
	$media_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids ) ) ) { ?>
		<div class="bb-activity-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count; echo $media_template->media_count > 5 ? 'bb-media-length-more' : ''; ?>"><?php
			while ( bp_media() ) {
				bp_the_media();
				bp_get_template_part( 'media/activity-entry' );
			} ?>
		</div><?php
	}
}

/**
 * Get activity comment entry media to render on front end
 */
function bp_nouveau_media_activity_comment_entry( $comment_id ) {
	global $media_template;
	$media_ids = bp_activity_get_meta( $comment_id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids ) ) ) { ?>
		<div class="bb-activity-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count; echo $media_template->media_count > 5 ? 'bb-media-length-more' : ''; ?>"><?php
			while ( bp_media() ) {
				bp_the_media();
				bp_get_template_part( 'media/activity-entry' );
			} ?>
		</div><?php
	}
}

/**
 * Update media for activity
 *
 * @param $content
 * @param $user_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_nouveau_media_update_media_meta( $content, $user_id, $activity_id ) {

	if ( ! isset( $_POST['media'] ) || empty( $_POST['media'] ) ) {
		return false;
	}

	$media_list = $_POST['media'];

	if ( ! empty( $media_list ) ) {
		$media_ids = array();
		foreach ( $media_list as $media_index => $media ) {

			// remove actions to avoid infinity loop
			remove_action( 'bp_activity_posted_update', 'bp_nouveau_media_update_media_meta', 10, 3 );
			remove_action( 'bp_groups_posted_update', 'bp_nouveau_media_groups_update_media_meta', 10, 4 );

			// make an activity for the media
			$a_id = bp_activity_post_update( array( 'hide_sitewide' => true ) );

			if ( $a_id ) {
				// update activity meta
				bp_activity_update_meta( $a_id, 'bp_media_activity', '1' );
			}

			add_action( 'bp_activity_posted_update', 'bp_nouveau_media_update_media_meta', 10, 3 );
			add_action( 'bp_groups_posted_update', 'bp_nouveau_media_groups_update_media_meta', 10, 4 );

			$media_id = bp_media_add(
				array(
					'title'         => ! empty( $media['name'] ) ? $media['name'] : '&nbsp;',
					'album_id'      => ! empty( $media['album_id'] ) ? $media['album_id'] : 0,
					'activity_id'   => $a_id,
					'privacy'       => ! empty( $media['privacy'] ) ? $media['privacy'] : 'public',
					'attachment_id' => ! empty( $media['id'] ) ? $media['id'] : 0,
					'menu_order'    => isset( $media['menu_order'] ) ? absint( $media['menu_order'] ) : $media_index,
				)
			);

			if ( $media_id ) {
				$media_ids[] = $media_id;

				//save media meta for activity
				if ( ! empty( $activity_id ) && ! empty( $media['id'] ) ) {
					update_post_meta( $media['id'], 'bp_media_parent_activity_id', $activity_id );
					update_post_meta( $media['id'], 'bp_media_activity_id', $a_id );
				}
			}
		}

		$media_ids = implode( ',', $media_ids );

		//save media meta for activity
		if ( ! empty( $activity_id ) ) {
			bp_activity_update_meta( $activity_id, 'bp_media_ids', $media_ids );
		}
	}
}

/**
 * Update media for group activity
 *
 * @param $content
 * @param $user_id
 * @param $group_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_nouveau_media_groups_update_media_meta( $content, $user_id, $group_id, $activity_id ) {
	bp_nouveau_media_update_media_meta( $content, $user_id, $activity_id );
}

/**
 * Update media for activity comment
 *
 * @param $comment_id
 * @param $r
 * @param $activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_nouveau_media_comments_update_media_meta( $comment_id, $r, $activity ) {
	bp_nouveau_media_update_media_meta( false, false, $comment_id );
}

/**
 * Delete media when related activity is deleted.
 *
 * @since BuddyBoss 1.0.0
 * @param $activities
 */
function bp_nouveau_media_delete_activity_media( $activities ) {
    if ( ! empty( $activities ) ) {
	    remove_action( 'bp_activity_after_delete', 'bp_nouveau_media_delete_activity_media' );
        foreach ( $activities as $activity ) {
	        $activity_id = $activity->id;
	        $media_activity = bp_activity_get_meta( $activity_id, 'bp_media_activity', true );
	        if ( ! empty( $media_activity ) && '1' == $media_activity ) {
		        $result = bp_media_get( array( 'activity_id' => $activity_id, 'fields' => 'ids' ) );
		        if ( ! empty( $result['medias'] ) ) {
                    foreach( $result['medias'] as $media_id ) {
	                    bp_media_delete( $media_id ); // delete media
                    }
                }
            }
        }
	    add_action( 'bp_activity_after_delete', 'bp_nouveau_media_delete_activity_media' );
    }
}

/**
 * Get the nav items for the Media directory
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array An associative array of nav items.
 */
function bp_nouveau_get_media_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'media',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array(),
		'link'      => bp_get_media_directory_permalink(),
		'text'      => __( 'All Media', 'buddyboss' ),
		'count'     => bp_get_total_media_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() ) {
		$nav_items['personal'] = array(
			'component' => 'media',
			'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope
			'li_class'  => array(),
			'link'      => bp_loggedin_user_domain() . bp_get_media_slug() . '/my-media/',
			'text'      => __( 'My Media', 'buddyboss' ),
			'count'     => bp_media_get_total_media_count(),
			'position'  => 15,
		);
	}

	/**
	 * Use this filter to introduce your custom nav items for the media directory.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $nav_items The list of the media directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_media_directory_nav_items', $nav_items );
}

/**
 * Update media privacy according to album's privacy
 *
 * @since BuddyBoss 1.0.0
 * @param $album
 */
function bp_nouveau_media_update_media_privacy( &$album ) {

    if ( ! empty( $album->id ) ) {

	    $privacy   = $album->privacy;
	    $media_ids = BP_Media::get_album_media_ids( $album->id );

	    if ( ! empty( $media_ids ) ) {
	        foreach( $media_ids as $media ) {
		        $media_obj          = new BP_Media( $media );
		        $media_obj->privacy = $privacy;
		        $media_obj->save();
            }
        }
    }
}

function bp_nouveau_media_attach_media_to_message( &$message ) {

    if ( bp_is_messages_media_support_enabled() && ! empty( $message->id ) && ! empty( $_POST['media'] ) ) {
	    $media_list = $_POST['media'];
        $media_ids = array();

        foreach ( $media_list as $media_index => $media ) {

            $media_id = bp_media_add(
                array(
                    'title'         => ! empty( $media['name'] ) ? $media['name'] : '&nbsp;',
                    'privacy'       => 'message',
                    'attachment_id' => ! empty( $media['id'] ) ? $media['id'] : 0,
                )
            );

            if ( $media_id ) {
                $media_ids[] = $media_id;
            }
        }

        $media_ids = implode( ',', $media_ids );

        //save media meta for message
        bp_messages_update_meta( $message->id, 'bp_media_ids', $media_ids );
    }
}