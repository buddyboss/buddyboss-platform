<?php
/**
 * ReadyLaunch - Group's edit settings template.
 *
 * This template handles group privacy settings, permissions,
 * and group type selection in the admin interface.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_get_new_group_status = bp_get_new_group_status();
$bp_is_media_active      = bp_is_active( 'media' );

if ( bp_is_group_create() ) : ?>
	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Select Group Settings', 'buddyboss' ); ?>
	</h3>
<?php else : ?>
	<h2 class="bp-screen-title" style="display: block;">
		<?php esc_html_e( 'Privacy', 'buddyboss' ); ?>
	</h2>
<?php endif; ?>

<div class="group-settings-selections bb-rl-group-settings bb-rl-styled-select bb-rl-styled-select--default">

	<fieldset class="radio group-status-type">
		<legend><?php esc_html_e( 'Group visibility', 'buddyboss' ); ?></legend>

		<select id="bp-groups-status" name="group-status" autocomplete="off">
			<option value="public" <?php echo ( 'public' === $bp_get_new_group_status || ! $bp_get_new_group_status ) ? ' selected="selected"' : ''; ?>><?php esc_html_e( 'Public', 'buddyboss' ); ?></option>
			<option value="private" <?php echo( 'private' === $bp_get_new_group_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Private', 'buddyboss' ); ?></option>
			<option value="hidden" <?php echo( 'hidden' === $bp_get_new_group_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Hidden', 'buddyboss' ); ?></option>
		</select>

	</fieldset>

	<fieldset class="radio group-invitations">
		<legend><?php esc_html_e( 'Group Invitations', 'buddyboss' ); ?></legend>

		<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to invite others?', 'buddyboss' ); ?></p>

		<?php
		$invite_status = bp_group_get_invite_status( bp_get_current_group_id() );
		?>

		<select id="group-invite-status" name="group-invite-status" autocomplete="off">
			<option value="members" <?php echo ( 'members' === $invite_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'All group members', 'buddyboss' ); ?></option>
			<option value="mods" <?php echo( 'mods' === $invite_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></option>
			<option value="admins" <?php echo( 'admins' === $invite_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></option>
		</select>
	</fieldset>

	<?php if ( bp_is_active( 'activity' ) ) : ?>

		<fieldset class="radio group-post-form">
			<legend><?php esc_html_e( 'Activity Feeds', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to post into the activity feed?', 'buddyboss' ); ?></p>

			<?php
			$activity_feed_status = bp_group_get_activity_feed_status( bp_get_current_group_id() );
			?>

			<select id="group-activity-feed-status-members" name="group-activity-feed-status" autocomplete="off">
				<option value="members" <?php echo ( 'members' === $activity_feed_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'All group members', 'buddyboss' ); ?></option>
				<option value="mods" <?php echo( 'mods' === $activity_feed_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></option>
				<option value="admins" <?php echo( 'admins' === $activity_feed_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></option>
			</select>
		</fieldset>
		<?php
	endif;

	if ( $bp_is_media_active && bp_is_group_media_support_enabled() ) :
		?>
		<fieldset class="radio group-media">
			<legend><?php esc_html_e( 'Group Photos', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to upload photos?', 'buddyboss' ); ?></p>

			<?php
			$media_status = bp_group_get_media_status( bp_get_current_group_id() );
			?>

			<select id="group-media-status-members" name="group-media-status" autocomplete="off">
				<option value="members" <?php echo ( 'members' === $media_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'All group members', 'buddyboss' ); ?></option>
				<option value="mods" <?php echo( 'mods' === $media_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></option>
				<option value="admins" <?php echo( 'admins' === $media_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></option>
			</select>
		</fieldset>
		<?php
	endif;

	if ( $bp_is_media_active && bp_is_group_albums_support_enabled() ) :
		?>
		<fieldset class="radio group-albums">
			<legend><?php esc_html_e( 'Group Albums', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to create albums?', 'buddyboss' ); ?></p>

			<?php
			$album_status = bp_group_get_album_status( bp_get_current_group_id() );
			?>

			<select id="group-albums-status-members" name="group-album-status" autocomplete="off">
				<option value="members" <?php echo ( 'members' === $album_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'All group members', 'buddyboss' ); ?></option>
				<option value="mods" <?php echo( 'mods' === $album_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></option>
				<option value="admins" <?php echo( 'admins' === $album_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></option>
			</select>
		</fieldset>
		<?php
	endif;

	if ( $bp_is_media_active && bp_is_group_document_support_enabled() ) :
		?>
		<fieldset class="radio group-document">
			<legend><?php esc_html_e( 'Group Documents', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to upload documents?', 'buddyboss' ); ?></p>

			<?php
			$document_status = bp_group_get_document_status( bp_get_current_group_id() );
			?>

			<select id="group-document-status-members" name="group-document-status" autocomplete="off">
				<option value="members" <?php echo ( 'members' === $document_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'All group members', 'buddyboss' ); ?></option>
				<option value="mods" <?php echo( 'mods' === $document_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></option>
				<option value="admins" <?php echo( 'admins' === $document_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></option>
			</select>
		</fieldset>
		<?php
	endif;

	if ( bp_is_active( 'video' ) && bp_is_group_video_support_enabled() ) :
		?>
		<fieldset class="radio group-video">
			<legend><?php esc_html_e( 'Group Videos', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to upload videos?', 'buddyboss' ); ?></p>

			<?php
			$video_status = bp_group_get_video_status( bp_get_current_group_id() );
			?>

			<select id="group-video-status-members" name="group-video-status" autocomplete="off">
				<option value="members" <?php echo ( 'members' === $video_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'All group members', 'buddyboss' ); ?></option>
				<option value="mods" <?php echo( 'mods' === $video_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></option>
				<option value="admins" <?php echo( 'admins' === $video_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></option>
			</select>
		</fieldset>
		<?php
	endif;

	if ( bp_is_active( 'messages' ) && true === bp_disable_group_messages() ) :
		?>
		<fieldset class="radio group-messages">
			<legend><?php esc_html_e( 'Group Messages', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to send group messages?', 'buddyboss' ); ?></p>

			<?php
			$message_status = bp_group_get_message_status( bp_get_current_group_id() );
			?>

			<select name="group-message-status" id="group-messages-status-members" autocomplete="off">
				<option value="members" <?php echo ( 'members' === $message_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'All group members', 'buddyboss' ); ?></option>
				<option value="mods" <?php echo( 'mods' === $message_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></option>
				<option value="admins" <?php echo( 'admins' === $message_status ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></option>
			</select>
		</fieldset>
		<?php
	endif;

	$group_types = bp_groups_get_group_types( array( 'show_in_create_screen' => true ), 'objects' );

	// Hide Group Types if none is selected in Users > Profile Type > E.g. (Students) > Allowed Group Types meta box.
	if ( false === bp_restrict_group_creation() && true === bp_member_type_enable_disable() ) {
		$get_all_registered_member_types = bp_get_active_member_types();
		if ( ! empty( $get_all_registered_member_types ) ) {

			$current_user_member_type = bp_get_member_type( bp_loggedin_user_id() );
			if ( '' !== $current_user_member_type ) {
				$member_type_post_id = bp_member_type_post_by_type( $current_user_member_type );
				$include_group_type  = get_post_meta( $member_type_post_id, '_bp_member_type_enabled_group_type_create', true );
				if ( ! empty( $include_group_type ) && 'none' === $include_group_type[0] ) {
					$group_types = '';
				}
			}
		}
	}

	// Group type selection.
	if ( $group_types ) :
		?>
		<fieldset class="group-create-types">
			<legend><?php esc_html_e( 'Group Type', 'buddyboss' ); ?></legend>

			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'What type of group is this? (optional)', 'buddyboss' ); ?></p>
			<select id="bp-groups-type" name="group-types[]" autocomplete="off">
				<option value="" <?php selected( '', '' ); ?>><?php esc_html_e( 'Select Group Type', 'buddyboss' ); ?></option>
				<?php
				foreach ( $group_types as $group_type ) :

					$group_option = sprintf(
						'<option for="%1$s" value="%2$s" %3$s>%4$s</option>',
						sprintf(
							'group-type-%s',
							$group_type->name
						),
						esc_attr( $group_type->name ),
						selected( ( true === bp_groups_has_group_type( bp_get_current_group_id(), $group_type->name ) ) ? $group_type->name : '', $group_type->name, false ),
						esc_html( $group_type->labels['singular_name'] )
					);

					if ( false === bp_restrict_group_creation() && true === bp_member_type_enable_disable() ) {

						$get_all_registered_member_types = bp_get_active_member_types();

						if ( ! empty( $get_all_registered_member_types ) ) {

							$current_user_member_type = bp_get_member_type( bp_loggedin_user_id() );

							if ( '' !== $current_user_member_type ) {

								$member_type_post_id = bp_member_type_post_by_type( $current_user_member_type );
								$include_group_type  = get_post_meta( $member_type_post_id, '_bp_member_type_enabled_group_type_create', true );

								if ( ! empty( $include_group_type ) ) {
									if ( in_array( $group_type->name, $include_group_type, true ) ) {
										echo wp_kses_post( $group_option );
									}
								} else {
									echo wp_kses_post( $group_option );
								}
							} else {
								echo wp_kses_post( $group_option );
							}
						} else {
							echo wp_kses_post( $group_option );
						}
					} else {
						echo wp_kses_post( $group_option );
					}
				endforeach;
				?>
			</select>
		</fieldset>
		<?php
	endif;

	if ( bp_enable_group_hierarchies() ) :
		$current_parent_group_id = bp_get_parent_group_id();
		$possible_parent_groups  = bp_get_possible_parent_groups();
		?>
		<fieldset class="select group-parent">
			<legend><?php esc_html_e( 'Group Parent', 'buddyboss' ); ?></legend>
			<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which group should be the parent of this group? (optional)', 'buddyboss' ); ?></p>
			<select id="bp-groups-parent" name="bp-groups-parent" autocomplete="off">
				<option value="0" <?php selected( 0, $current_parent_group_id ); ?>><?php esc_html_e( 'Select Parent', 'buddyboss' ); ?></option>
				<?php
				if ( $possible_parent_groups ) {
					foreach ( $possible_parent_groups as $possible_parent_group ) {
						?>
						<option value="<?php echo esc_attr( $possible_parent_group->id ); ?>" <?php selected( $current_parent_group_id, $possible_parent_group->id ); ?>><?php echo esc_html( $possible_parent_group->name ); ?></option>
						<?php
					}
				}
				?>
			</select>
		</fieldset>
	<?php endif; ?>

</div><!-- // .group-settings-selections -->
