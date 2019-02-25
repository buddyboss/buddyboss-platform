<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook profile Photo grid template into BuddyPress plugins template
 *
 * @since BuddyBoss Media 1.1.0
 *
 * @uses add_action() To add the content hook
 * @uses bp_core_load_template() To load the plugins template
 */
function buddyboss_media_screen_albums() {
  add_action( 'bp_template_content', 'buddyboss_media_template_albums' );
  bp_core_load_template( apply_filters( 'buddyboss_media_screen_albums', 'members/single/plugins' ) );
}

function buddyboss_media_template_albums() {

	$theme_compat_id = bp_get_theme_compat_id();

	// list templates base on current theme compat
	if ( 'legacy' === $theme_compat_id ) {
		// legacy templates list
		$template_album = 'members/single/buddyboss-media-album';
		$template_album_create = 'members/single/buddyboss-media-album-create';
		$template_album_edit = 'members/single/buddyboss-media-album-edit';
		$template_albums = 'members/single/buddyboss-media-albums';
	} elseif ( 'nouveau' === $theme_compat_id ) {
		// nouveau templates list
		$template_album = 'bp-nouveau/members/single/buddyboss-media-album';
		$template_album_create = 'bp-nouveau/members/single/buddyboss-media-album-create';
		$template_album_edit = 'bp-nouveau/members/single/buddyboss-media-album-edit';
		$template_albums = 'bp-nouveau/members/single/buddyboss-media-albums';
	}

	if( isset( $_GET['album'] ) && !empty( $_GET['album'] ) ){
		$album = $_GET['album'];

		if( 'new'==$album ){
			buddyboss_media_load_template( $template_album_create );
		} else {
			add_filter( 'buddyboss_media_albums_loop_args', 'buddyboss_media_query_single_album' );
			buddyboss_media_load_template( $template_album_edit );
			remove_filter( 'buddyboss_media_albums_loop_args', 'buddyboss_media_query_single_album' );
		}
	} else {
		if( bp_action_variable() ){
			//load single album template
			add_filter( 'buddyboss_media_albums_loop_args', 'buddyboss_media_query_single_album' );
			buddyboss_media_load_template( $template_album );
			remove_filter( 'buddyboss_media_albums_loop_args', 'buddyboss_media_query_single_album' );
		} else {
			buddyboss_media_load_template( $template_albums );
		}
	}
}

/**
 * Processes new album/edit album/delete album post request and displays success/error messages.
 * Redirects to new album if new album was created successfuly!
 *
 * @since BuddyBoss Media 1.1.0
 */
function buddyboss_media_albums_process_update(){
	if( ( bp_current_component() == buddyboss_media_component_slug() )
	    || ( bbm_is_group_media_screen( 'albums' ) ) ) {
		if( isset( $_GET['album'] ) && !empty( $_GET['album'] ) ){
			$album = $_GET['album'];
			if( isset( $_POST['btn_submit'] ) ){
				if( !wp_verify_nonce( $_POST['_wpnonce'], 'buddyboss_media_edit_album' ) )
					die('Error!');

				//validation

				if( empty( $_POST['album_title'] ) ){
					bp_core_add_message( __( 'Album title cannot be empty.', 'buddyboss-media' ), 'error');
				} else {

					$data = array(
						'title'       => wp_strip_all_tags( $_POST['album_title'] ),
						'description' => wp_strip_all_tags( $_POST['album_description'] ),
						'privacy'     => filter_input( INPUT_POST, 'album_privacy' ),
					);

					//Group albums - set album group id
					if ( bbm_is_group_media_screen( 'albums' ) ) {
						$data['group_id'] = bp_get_current_group_id();
					}

					if( 'new'==$album ){
						$new_album_id = buddyboss_media_update_album( $data );
						if( $new_album_id ){
							global $bp;

							//Redirect to new album
							if ( bbm_is_group_media_screen( 'albums' ) ) {
								$group_link = bp_get_group_permalink( buddypress()->groups->current_group );
								$new_album_url = trailingslashit( $group_link . buddyboss_media_component_slug() . '/albums/' . $new_album_id  );
							} else {
								$new_album_url = $bp->displayed_user->domain . buddyboss_media_component_slug() . '/albums/' . $new_album_id . '/';
							}

							bp_core_add_message( __( 'Album created successfully.', 'buddyboss-media' ), 'success');
							//redirect to new album
							wp_redirect( $new_album_url );
							exit();
						}
					} else {
						$data['id'] = $album;
						buddyboss_media_update_album( $data );
						bp_core_add_message( __( 'Album was successfully updated.', 'buddyboss-media' ), 'success' );
					}
				}
			}
		}

		//delete album
		if( isset( $_GET['delete'] ) && !empty( $_GET['delete'] ) && isset( $_GET['nonce'] ) && !empty( $_GET['nonce'] ) ){
			$album_to_delete = (int)$_GET['delete'];
			if( wp_verify_nonce( $_GET['nonce'], 'bboss_media_delete_album' ) ){
				/**
				 * all is good.
				 * delete album from albums table.
				 * delete activity meta entry for all photos in this table.
				 */
				$deleted = buddyboss_media_delete_album( $album_to_delete );
				if( $deleted ){
					bp_core_add_message( __( 'Album deleted successfuly!', 'buddyboss-media' ), 'success' );
				} else {
					//shouldn't come here
					bp_core_add_message( __( 'Access Denied!', 'buddyboss-media' ), 'error' );
				}
			} else {
				bp_core_add_message( __( 'Invalid request!', 'buddyboss-media' ), 'error');
			}
		}
	}
}
add_action( 'template_redirect', 'buddyboss_media_albums_process_update' );