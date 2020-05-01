<?php
/**
 * BuddyBoss - Groups Create Zoom Meetings
 *
 * @since BuddyBoss 1.2.10
 */

if ( bp_has_zoom_meetings( array( 'include' => bp_zoom_get_edit_meeting_id() ) ) ) :
	while ( bp_zoom_meeting() ) : bp_the_zoom_meeting();
?>
<div id="bp-new-zoom-meeting" class="bp-new-zoom-meeting-form">
	<form id="bp-new-zoom-meeting-form" name="bp-new-zoom-meeting-form" class="standard-form" method="post" action="">
		<fieldset class="bp-zoom-meeting-form">
			<legend></legend>
			<div class="bp-zoom-meeting-form-wrap">
				<p>
					<label for="bp-zoom-meeting-title"><?php _e( 'Title', 'buddyboss' ); ?></label>
					<input type="text" id="bp-zoom-meeting-title" value="<?php bp_zoom_meeting_title(); ?>" tabindex="1" name="bp-zoom-meeting-title" />
				</p>
				<p>
					<label for="bp-zoom-meeting-alt-host-ids"><?php _e( 'Hosts', 'buddyboss' ); ?></label>
					<select id="bp-zoom-meeting-alt-host-ids" name="bp-zoom-meeting-alt-host-ids" tabindex="2" multiple>
						<?php $users = groups_get_group_members( array( 'group_role' => array( 'member', 'mod', 'admin' ) ) ); ?>
						<?php $alt_host_ids = explode( ',', bp_get_zoom_meeting_alternative_host_ids() ); ?>
						<?php
						if ( ! empty( $users['members'] ) ) :
							foreach ( $users['members'] as $user ):
								$bp_zoom_user_status = get_user_meta( $user->ID, 'bp_zoom_user_status', true );
								$bp_zoom_user_id = get_user_meta( $user->ID, 'bp_zoom_user_id', true );
								if ( 'active' === $bp_zoom_user_status && ! empty( $bp_zoom_user_id ) && $bp_zoom_user_id !== bp_zoom_api_host() ) : ?>
									<option
											value="<?php echo $bp_zoom_user_id; ?>" <?php echo in_array( $bp_zoom_user_id, $alt_host_ids ) ? 'selected="selected"' : ''; ?>><?php echo bp_core_get_user_displayname( $user->ID ) . ' ( ' . $user->user_email . ' )'; ?></option>
								<?php endif;
							endforeach;
						endif;
						?>
					</select>
				</p>
				<p>
					<label for="bp-zoom-meeting-start-date"><?php _e( 'When', 'buddyboss' ); ?></label>
					<input type="text" id="bp-zoom-meeting-start-date" value="<?php bp_zoom_meeting_start_date(); ?>" tabindex="3" name="bp-zoom-meeting-start-date" />
				</p>
				<p>
					<label for="bp-zoom-meeting-duration"><?php _e( 'Duration', 'buddyboss' ); ?></label>
					<input type="number" id="bp-zoom-meeting-duration" value="<?php bp_zoom_meeting_duration(); ?>" tabindex="4" name="bp-zoom-meeting-duration" min="0" />
				</p>
				<p>
					<label for="bp-zoom-meeting-timezone"><?php _e( 'Timezone', 'buddyboss' ); ?></label>
					<select id="bp-zoom-meeting-timezone" name="bp-zoom-meeting-timezone" tabindex="5">
						<?php $timezones = bp_zoom_get_timezone_options(); ?>
						<?php foreach ( $timezones as $k => $timezone ) { ?>
							<option value="<?php echo $k; ?>" <?php echo bp_get_zoom_meeting_timezone() === $k ? 'selected' : ''; ?>><?php echo $timezone; ?></option>
						<?php } ?>
					</select>
				</p>
				<p>
					<label aria-hidden="true"><?php _e( 'Registration', 'buddyboss' ); ?></label>
					<input type="checkbox" name="bp-zoom-meeting-registration" id="bp-zoom-meeting-registration" value="1" class="bs-styled-checkbox" tabindex="6" <?php checked( 1, bp_get_zoom_meeting_authentication() ); ?> />
					<label for="bp-zoom-meeting-registration"><?php _e( 'Required', 'buddyboss' ); ?></label>

				</p>
				<p>
					<label for="bp-zoom-meeting-password"><?php _e( 'Meeting Password', 'buddyboss' ); ?></label>
					<input type="text" id="bp-zoom-meeting-password" value="<?php bp_zoom_meeting_password(); ?>" tabindex="7" name="bp-zoom-meeting-password" />
				</p>
				<p>

					<label aria-hidden="true"><?php _e( 'Host Video', 'buddyboss' ); ?></label>
					<input type="checkbox" id="bp-zoom-meeting-host-video" value="" tabindex="8" name="bp-zoom-meeting-host-video" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_host_video() ); ?> />
					<label for="bp-zoom-meeting-host-video"><?php _e( 'Start video when host join meeting.', 'buddyboss' ); ?></label>
				</p>
				<p>
					<label aria-hidden="true"><?php _e( 'Participants Video', 'buddyboss' ); ?></label>
					<input type="checkbox" id="bp-zoom-meeting-participants-video" value="" tabindex="9" name="bp-zoom-meeting-participants-video" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_participants_video() ); ?> />
					<label for="bp-zoom-meeting-participants-video"><?php _e( 'Start video when participants join meeting.', 'buddyboss' ); ?></label>
				</p>
				<p>
					<label aria-hidden="true"><?php _e( 'Join Before Host', 'buddyboss' ); ?></label>
					<input type="checkbox" id="bp-zoom-meeting-join-before-host" value="" tabindex="10" name="bp-zoom-meeting-join-before-host" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_join_before_host() ); ?> />
					<label for="bp-zoom-meeting-join-before-host"><?php _e( 'Enable join before host.', 'buddyboss' ); ?></label>
				</p>
				<p>
					<label aria-hidden="true"><?php _e( 'Mute Participants', 'buddyboss' ); ?></label>
					<input type="checkbox" id="bp-zoom-meeting-mute-participants" value="" tabindex="11" name="bp-zoom-meeting-mute-participants" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_mute_participants() ); ?> />
					<label for="bp-zoom-meeting-mute-participants"><?php _e( 'Mute participants upon entry.', 'buddyboss' ); ?></label>
				</p>
				<p>
					<label aria-hidden="true"><?php _e( 'Waiting Room', 'buddyboss' ); ?></label>
					<input type="checkbox" id="bp-zoom-meeting-waiting-room" value="" tabindex="12" name="bp-zoom-meeting-waiting-room" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_waiting_room() ); ?> />
					<label for="bp-zoom-meeting-waiting-room"><?php _e( 'Enable waiting room.', 'buddyboss' ); ?></label>
				</p>
				<p>
					<label aria-hidden="true"><?php _e( 'Authenticated Users', 'buddyboss' ); ?></label>
					<input type="checkbox" id="bp-zoom-meeting-authentication" value="" tabindex="13" name="bp-zoom-meeting-authentication" class="bs-styled-checkbox" <?php checked( 1, bp_get_zoom_meeting_enforce_login() ); ?> />
					<label for="bp-zoom-meeting-authentication"><?php _e( 'Only authenticated users can join.', 'buddyboss' ); ?></label>
				</p>
				<p>
					<label for="bp-zoom-meeting-recording"><?php _e( 'Auto Recording', 'buddyboss' ); ?></label>
					<select id="bp-zoom-meeting-recording" name="bp-zoom-meeting-recording" tabindex="14">
						<option value="none" <?php echo 'none' === bp_get_zoom_meeting_auto_recording() ? 'selected' : ''; ?>><?php _e( 'No Recordings', 'buddyboss' ); ?></option>
						<option value="local" <?php echo 'local' === bp_get_zoom_meeting_auto_recording() ? 'selected' : ''; ?>><?php _e( 'Local', 'buddyboss' ); ?></option>
						<option value="cloud" <?php echo 'cloud' === bp_get_zoom_meeting_auto_recording() ? 'selected' : ''; ?>><?php _e( 'Cloud', 'buddyboss' ); ?></option>
					</select><br />
					<small><?php _e( 'Set what type of auto recording feature you want to add. Default is none.', 'buddyboss' ); ?></small>
				</p>

				<div class="bp-zoom-meeting-form-submit-wrapper">
					<?php wp_nonce_field( 'bp_zoom_meeting' ); ?>
					<input type="hidden" id="bp-zoom-meeting-id" name="bp-zoom-meeting-id" value="<?php bp_zoom_meeting_id(); ?>"/>
					<input type="hidden" id="bp-zoom-meeting-zoom-id" name="bp-zoom-meeting-zoom-id" value="<?php bp_zoom_meeting_zoom_meeting_id(); ?>"/>
					<input type="hidden" id="bp-zoom-meeting-group-id" name="bp-zoom-meeting-group-id" value="<?php bp_zoom_meeting_group_id(); ?>"/>
					<button type="submit" tabindex="15" id="bp-zoom-meeting-form-submit" name="bp-zoom-meeting-form-submit" class="button submit"><?php _e( 'Update Meeting', 'buddyboss' ); ?></button>
				</div>

			</div>
		</fieldset>
	</form>
</div>
<?php endwhile;
endif;
