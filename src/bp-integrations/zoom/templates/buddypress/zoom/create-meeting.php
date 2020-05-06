<?php
/**
 * BuddyBoss - Create Meeting
 *
 * @since BuddyBoss 1.2.10
 */
?>
<div id="bp-meeting-create" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<form id="bp-new-zoom-meeting-form" name="bp-new-zoom-meeting-form" class="standard-form" method="post"
					  action="" autocomplete="off">
					<div id="boss-media-create-meeting-popup" class="modal-container">

						<header class="bb-model-header">
							<h4><?php _e( 'Schedule Meeting', 'buddyboss' ); ?></h4>
							<a class="bb-model-close-button" id="bp-meeting-create-meeting-close" href="#"><span
										class="dashicons dashicons-no-alt"></span></a>
						</header>

						<div class="bb-field-wrapper">
							<div class="bb-field-wrap">
								<label for="bp-zoom-meeting-title"><?php _e( 'Title', 'buddyboss' ); ?></label>
								<input type="text" id="bp-zoom-meeting-title"
									value="<?php _e( 'My Meeting', 'buddyboss' ); ?>" tabindex="1"
									name="bp-zoom-meeting-title"/>
							</div>

							<div class="bb-field-wrap">
								<label for="bp-zoom-meeting-start-date"><?php _e( 'When', 'buddyboss' ); ?></label>
								<input type="text" id="bp-zoom-meeting-start-date" value="" tabindex="3" name="bp-zoom-meeting-start-date" />
							</div>

							<div class="bb-field-wrap">
								<label for="bp-zoom-meeting-duration"><?php _e( 'Duration', 'buddyboss' ); ?></label>
								<input type="number" id="bp-zoom-meeting-duration" value="" tabindex="4" name="bp-zoom-meeting-duration" min="0" />
							</div>

							<div class="bb-field-wrap">
								<label for="bp-zoom-meeting-timezone"><?php _e( 'Timezone', 'buddyboss' ); ?></label>
								<select id="bp-zoom-meeting-timezone" name="bp-zoom-meeting-timezone" tabindex="5">
									<?php $timezones = bp_zoom_get_timezone_options(); ?>
									<?php foreach ( $timezones as $k => $timezone ) { ?>
										<option value="<?php echo $k; ?>"><?php echo $timezone; ?></option>
									<?php } ?>
								</select>
							</div>

							<div class="bb-field-wrap">
								<label for="bp-zoom-meeting-password"><?php _e( 'Meeting Password', 'buddyboss' ); ?></label>
								<input type="text" id="bp-zoom-meeting-password" value="" tabindex="7" name="bp-zoom-meeting-password" />
							</div>

							<div class="bb-field-wrap full-row bp-zoom-meeting-alt-host">
								<label for="bp-zoom-meeting-alt-host-ids"><?php _e( 'Hosts', 'buddyboss' ); ?></label>
								<select id="bp-zoom-meeting-alt-host-ids" name="bp-zoom-meeting-alt-host-ids" tabindex="2" multiple>
									<option><?php _e( 'Select hosts', 'buddyboss' ); ?></option>
									<?php
									$users = groups_get_group_members( array(
											'group_role' => array(
													'member',
													'mod',
													'admin'
											)
									) );
									if ( ! empty( $users['members'] ) ) :
										foreach ( $users['members'] as $user ):
											$bp_zoom_user_status = get_user_meta( $user->ID, 'bp_zoom_user_status', true );
											$bp_zoom_user_id = get_user_meta( $user->ID, 'bp_zoom_user_id', true );
											if ( 'active' === $bp_zoom_user_status && ! empty( $bp_zoom_user_id ) && $bp_zoom_user_id !== bp_zoom_api_host() ) : ?>
												<option
														value="<?php echo $bp_zoom_user_id; ?>"><?php echo bp_core_get_user_displayname( $user->ID ) . ' ( ' . $user->user_email . ' )'; ?></option>
											<?php endif;
										endforeach;
									endif;
									?>
								</select>
							</div>

							<div class="bb-field-wrap checkbox-row">
								<label aria-hidden="true"><?php _e( 'Registration', 'buddyboss' ); ?></label>
								<input type="checkbox" name="bp-zoom-meeting-registration" id="bp-zoom-meeting-registration" value="1" class="bs-styled-checkbox" tabindex="6"/>
								<label for="bp-zoom-meeting-registration"><span><?php _e( 'Required', 'buddyboss' ); ?></span></label>
							</div>

							<div class="bb-field-wrap checkbox-row">
								<label aria-hidden="true"><?php _e( 'Host Video', 'buddyboss' ); ?></label>
								<input type="checkbox" id="bp-zoom-meeting-host-video" value="" tabindex="8" name="bp-zoom-meeting-host-video" class="bs-styled-checkbox" />
								<label for="bp-zoom-meeting-host-video"><span><?php _e( 'Start video when host join meeting.', 'buddyboss' ); ?></span></label>
							</div>

							<div class="bb-field-wrap checkbox-row">
								<label aria-hidden="true"><?php _e( 'Participants Video', 'buddyboss' ); ?></label>
								<input type="checkbox" id="bp-zoom-meeting-participants-video" value="" tabindex="9" name="bp-zoom-meeting-participants-video" class="bs-styled-checkbox" />
								<label for="bp-zoom-meeting-participants-video"><span><?php _e( 'Start video when participants join meeting.', 'buddyboss' ); ?></span></label>
							</div>

							<div class="bb-field-wrap checkbox-row">
								<label aria-hidden="true"><?php _e( 'Join Before Host', 'buddyboss' ); ?></label>
								<input type="checkbox" id="bp-zoom-meeting-join-before-host" value="" tabindex="10" name="bp-zoom-meeting-join-before-host" class="bs-styled-checkbox" />
								<label for="bp-zoom-meeting-join-before-host"><span><?php _e( 'Enable join before host.', 'buddyboss' ); ?></span></label>
							</div>
							<div class="bb-field-wrap checkbox-row">
								<label aria-hidden="true"><?php _e( 'Mute Participants', 'buddyboss' ); ?></label>
								<input type="checkbox" id="bp-zoom-meeting-mute-participants" value="" tabindex="11" name="bp-zoom-meeting-mute-participants" class="bs-styled-checkbox" />
								<label for="bp-zoom-meeting-mute-participants"><span><?php _e( 'Mute participants upon entry.', 'buddyboss' ); ?></span></label>
							</div>
							<div class="bb-field-wrap checkbox-row">
								<label aria-hidden="true"><?php _e( 'Waiting Room', 'buddyboss' ); ?></label>
								<input type="checkbox" id="bp-zoom-meeting-waiting-room" value="" tabindex="12" name="bp-zoom-meeting-waiting-room" class="bs-styled-checkbox" />
								<label for="bp-zoom-meeting-waiting-room"><span><?php _e( 'Enable waiting room.', 'buddyboss' ); ?></span></label>
							</div>
							<div class="bb-field-wrap checkbox-row	">
								<label aria-hidden="true"><?php _e( 'Authenticated Users', 'buddyboss' ); ?></label>
								<input type="checkbox" id="bp-zoom-meeting-authentication" value="" tabindex="13" name="bp-zoom-meeting-authentication" class="bs-styled-checkbox" />
								<label for="bp-zoom-meeting-authentication"><span><?php _e( 'Only authenticated users can join.', 'buddyboss' ); ?></span></label>
							</div>
							<div class="bb-field-wrap full-row">
								<label for="bp-zoom-meeting-recording"><?php _e( 'Auto Recording', 'buddyboss' ); ?></label>
								<select id="bp-zoom-meeting-recording" name="bp-zoom-meeting-recording" tabindex="14">
									<option value="none" selected="selected"><?php _e( 'No Recordings', 'buddyboss' ); ?></option>
									<option value="local"><?php _e( 'Local', 'buddyboss' ); ?></option>
									<option value="cloud"><?php _e( 'Cloud', 'buddyboss' ); ?></option>
								</select><br />
								<small><?php _e( 'Set what type of auto recording feature you want to add. Default is none.', 'buddyboss' ); ?></small>
							</div>
						</div>

						<footer class="bb-model-footer">

							<?php wp_nonce_field( 'bp_zoom_meeting' ); ?>
							<?php if ( bp_is_group() ) { ?>
							<input type="hidden" id="bp-zoom-meeting-group-id" name="bp-zoom-meeting-group-id" value="<?php echo bp_get_group_id(); ?>"/>
							<?php } ?>

							<a class="button submit" id="bp-zoom-meeting-form-submit"
							   href="#"><?php _e( 'Create Meeting', 'buddyboss' ); ?></a>
						</footer>

					</div>
				</form>
			</div>
		</div>
	</transition>
</div>
