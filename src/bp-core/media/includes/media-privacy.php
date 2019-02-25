<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


function bbm_get_media_visibility() {
    $is_single_group = bp_is_group();
    if ( $is_single_group ) {
		/*
		 * if group is hidden or private, we shouldn't show activity privacy options
		 * as the privacy is determined on group level anyway.
		 */
		global $groups_template;
		$group = & $groups_template->group;
		if ( !empty( $group ) && ( 'hidden' == $group->status || 'private' == $group->status ) ) {
			//this is a hidden/private group. dont show the privacy UI
			return apply_filters( 'bbm_get_media_visibility_filter', '' );
		}
	}
    $html = '<select name="bbm-media-privacy" id="bbm-media-privacy">';

    $options = bbm_get_visibility_lists( $is_single_group );

    foreach( $options as $key=>$val ){
        $html .= "<option value='" . esc_attr( $key ) . "'>$val</option>";
    }
    $html .= '</select>';

    return apply_filters( 'bbm_get_media_visibility_filter', $html );
}

function bbm_get_groups_media_visibility() {
    if( bp_is_group() ){
        /*
         * if group is hidden or private, we shouldn't show media privacy options
         * as the privacy is determined on group level anyway.
         */
        global $groups_template;
		$group =& $groups_template->group;
        if( !empty( $group ) && ( 'hidden'==$group->status || 'private'==$group->status ) ) {
            //this is a hidden/private group. dont show the privacy UI
            return apply_filters( 'bbm_get_groups_media_visibility', '' );
        }
    }
    $html = '<select name="bbmedia-media-privacy" id="bbmedia-media-privacy">';

    $options = bbm_get_visibility_lists(true);

    foreach( $options as $key=>$val ){
        $html .= "<option value='" . esc_attr( $key ) . "'>$val</option>";
    }
    $html .= '</select>';

    return apply_filters( 'bbm_get_groups_media_visibility', $html );
}

/**
 * get options for visibility
 * @param bool $friend
 * @param bool $group
 * @return array
 */
function bbm_get_visibility_lists($is_group_media = false){

	// Return if buddyboss_wall is not active
	if ( ! function_exists('buddyboss_wall') ) {
		return array();
	}

    $bp_displayed_user_id = bp_displayed_user_id();
    $bp_loggedin_user_id = bp_loggedin_user_id();
	$disble_everyone_privacy = buddyboss_wall()->option( 'disable_everyone_option' );
	$options = array();

	if ( !$disble_everyone_privacy ) {
		$options['public'] = __('Everyone', 'buddyboss-media');
	}

	$options['loggedin'] = __('Logged In Users', 'buddyboss-media');

    if( $bp_displayed_user_id == $bp_loggedin_user_id || !bp_is_user_activity() ) {
        $options['onlyme'] = __('Only Me', 'buddyboss-media');
    }
    if( bp_is_active( 'friends' ) ) {
        $options['friends'] = __('My Friends', 'buddyboss-media');
    }
    if( $is_group_media && bp_is_active( 'groups' ) ) {
        $options['grouponly'] = __('Group Members', 'buddyboss-media');
    }

    return $options;
}

function bbm_is_media_visible( $media_id, $bp_loggedin_user_id, $is_super_admin ) {
    global $wpdb;

    $query = "SELECT activity_id, media_author, privacy FROM {$wpdb->prefix}buddyboss_media WHERE media_id = {$media_id}";

    $media_data = $wpdb->get_row( $query );

    $visibility = $media_data->privacy;
    $visible = true;

    if( $bp_loggedin_user_id != $media_data->media_author ) {

        switch ( $visibility ) {
            //Logged in users
            case 'loggedin' :
                if( !$bp_loggedin_user_id )
                    $visible = false;
                break;

            //My friends
            case 'friends' :
                if ( bp_is_active( 'friends' ) ) {
                    $is_friend = friends_check_friendship( $bp_loggedin_user_id, $media_data->media_author );
                    if( !$is_friend )
                        $visible = false;
                }
                break;

            //Only group members
            case 'grouponly' :
                $group_is_user_member = groups_is_user_member( $bp_loggedin_user_id, $media_data->activity_id );
                if( !$group_is_user_member )
                    $visible = false;
                break;

            //Only Me
            case 'onlyme' :
                if( $bp_loggedin_user_id != $media_data->media_author )
                    $visible = false;
                break;

            default:
                //public
                break;
        }
    }


    $visible = apply_filters( 'bbm_media_visibility_filter', $visible, $visibility, $media_id );

    return $visible;

}


