<?php
/**
 * Handle group media
 *
 * @class       BBM_Group_Media
 * @category    Class
 * @author      BuddyBoss
 */

/**
 * The class_exists() check is recommended, to prevent problems during upgrade
 * or when the Groups component is disabled
 */
if ( class_exists( 'BP_Group_Extension' ) ) :

	class BBM_Group_Media extends BP_Group_Extension {
		/**
		 * Here you can see more customization of the config options
		 */

		public $component_slug;

		function __construct() {

			$this->component_slug = buddyboss_media_component_slug();

			//Photo counts
			$group_id    = bp_get_current_group_id();
			$photos_cnt  = bbm_groups_media_photo_count( $group_id );

			//Group creation tab- Should not show 0 in title
			if ( ! bp_is_current_action('create') ) {
				$name = sprintf( __( 'Photos <span>%d</span>', 'buddyboss-media' ), $photos_cnt );
			} else {
				$name = sprintf( __( 'Photos', 'buddyboss-media' )  );
			}

			$args = array(
				'slug' => $this->component_slug,
				'name' => $name,
				'nav_item_position' => 105,
			);

			$this->setup_nav_items();

			add_action( 'template_redirect', array( $this, 'groups_screen_group_media' ) );

			parent::init( $args );
		}

		function display( $group_id = NULL ) {
			$theme_compat_id = bp_get_theme_compat_id();

			if ( 'legacy' === $theme_compat_id ) {
				// Legacy group media template
				$group_media_tmpl = 'group-media';
			} elseif ( 'nouveau' === $theme_compat_id ) {
				// Nouveau group media template
				$group_media_tmpl = 'bp-nouveau/group-media';
			}

			buddyboss_media_buffer_template_part( $group_media_tmpl );
		}

		function settings_screen( $group_id = NULL ) {
			?>
			<h4><?php _e( 'Photo Albums', 'buddyboss-media' ); ?></h4>

			<p><?php _e( 'Which members of this group are allowed to create album?', 'buddyboss-media' ); ?></p>

			<div class="radio">
				<label for="group-album-status-members"><input type="radio" name="group-album-status" id="group-album-status-members" value="members"<?php bbm_group_show_media_status_setting( 'members' ); ?> /> <?php _e( 'All group members', 'buddyboss-media' ); ?></label>

				<label for="group-album-status-mods"><input type="radio" name="group-album-status" id="group-album-status-mods" value="mods"<?php bbm_group_show_media_status_setting( 'mods' ); ?> /> <?php _e( 'Group admins and mods only', 'buddyboss-media' ); ?></label>

				<label for="group-album-status-admins"><input type="radio" name="group-album-status" id="group-album-status-admins" value="admins"<?php bbm_group_show_media_status_setting( 'admins' ); ?> /> <?php _e( 'Group admins only', 'buddyboss-media' ); ?></label>

			</div>
			<br />
			<?php
		}

		function settings_screen_save( $group_id = NULL ) {
			$setting = isset( $_POST['group-album-status'] ) ? $_POST['group-album-status'] : '';
			groups_update_groupmeta( $group_id, 'media_status', $setting );
		}

		/* Add this method the end of your extension class */
		function setup_nav_items() {

			//Do not add subnav if group component is not active
			if ( ! bp_is_active('groups') || empty(  buddypress()->groups->current_group ) ) {
				return;
			}

			$slug       = buddypress()->groups->current_group->slug.'_photos';
			$group_link = bp_get_group_permalink( buddypress()->groups->current_group );

			$photo_link = trailingslashit( $group_link . $this->component_slug  );

			bp_core_new_subnav_item( array(
				'name'				 => __( 'Uploads', 'buddyboss-media' ),
				'slug'				 => 'uploads',
				'parent_slug'		 => $slug,
				'parent_url'		 => $photo_link,
				'screen_function'	 => 'buddyboss_media_screen_photo_grid',
				'position'			 => 10
			) );

			$group_albums_support = buddyboss_media()->is_group_albums_enabled();

			if ( true === $group_albums_support ) {
				bp_core_new_subnav_item( array(
					'name'				 => __( 'Albums', 'buddyboss-media' ),
					'slug'				 => 'albums',
					'parent_slug'		 => $slug,
					'parent_url'		 => $photo_link,
					'screen_function'	 => 'buddyboss_media_screen_albums',
					'position'			 => 11
				) );
			}

		}

		/**
		 * Handle the display of a group's media pages.
		 */
		function groups_screen_group_media() {

			if ( ! bp_is_groups_component() || ! bp_is_current_action( $this->component_slug  ) )
				return false;

			if ( bp_action_variables() )
				return false;

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . $this->component_slug . '/uploads/' );
		}
	}

	//Check for whether group media support has been enabled
	$group_media_support = buddyboss_media()->is_group_media_enabled();
	if ( bp_is_active('groups') && bp_is_group_single()  && true == $group_media_support ) {
		bp_register_group_extension( 'BBM_Group_Media' );
	}


endif;