<?php
/**
 * BuddyBoss - Groups Zoom Single Meeting
 *
 * @since BuddyBoss 1.2.10
 */

if ( bp_has_zoom_meetings( array( 'include' => bp_zoom_get_current_meeting_id() ) ) ) :
	while ( bp_zoom_meeting() ) : bp_the_zoom_meeting(); ?>
		<div class="meeting-item-container">
			<h2><?php bp_zoom_meeting_title(); ?></h2>
			<div id="bp-zoom-single-meeting" class="meeting-item meeting-item-table single-meeting-item-table"
				 data-id="<?php bp_zoom_meeting_id(); ?>"
				 data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>">
				<div class="single-meeting-item">
					<label class="meeting-item-head"><?php _e( 'Topic', 'buddyboss' ); ?></label>
					<div class="meeting-item-col">
						<?php bp_zoom_meeting_title(); ?>
					</div>
				</div>
				<div class="single-meeting-item">
					<label class="meeting-item-head"><?php _e( 'Time', 'buddyboss' ); ?></label>
					<div class="meeting-item-col">
						<?php echo bp_core_get_format_date( bp_get_zoom_meeting_start_date(), bp_core_date_format( true, true ) ); ?>
						<?php bp_zoom_meeting_timezone(); ?>
					</div>
				</div>
				<div class="single-meeting-item">
					<label class="meeting-item-head"><?php _e( 'Meeting ID', 'buddyboss' ); ?></label>
					<div class="meeting-item-col">
						<?php bp_zoom_meeting_zoom_meeting_id(); ?>
					</div>
				</div>
				<div class="single-meeting-item">
					<label class="meeting-item-head"><?php _e( 'Meeting Password', 'buddyboss' ); ?></label>
					<div class="meeting-item-col">
						<?php if ( ! empty( bp_get_zoom_meeting_password() ) ) : ?>
							<div class="z-form-row-action" style="display: inline-block;">
								<span class="hide-password"
									  style="display:inline-block;"><strong>********</strong></span>
								<span class="show-password"
									  style="display:none;margin-right: 16px;font-size:13px;"><strong><?php echo bp_get_zoom_meeting_password(); ?></strong></span>
								<a href="javascript:;" class="toggle-password show-pass"
								   style="display: inline;"><?php _e( 'Show', 'buddyboss' ); ?></a>
								<a href="javascript:;" class="toggle-password hide-pass"
								   style="display: none;"><?php _e( 'Hide', 'buddyboss' ); ?></a>
							</div>
						<?php else: ?>
							<label id="label_option_password" class="checkbox" style="margin-top: -7px;"><i
										class="status-icon"></i><?php _e( 'Require meeting password', 'buddyboss' ); ?>
							</label>
						<?php endif; ?>
					</div>
				</div>
				<div class="single-meeting-item">
					<label class="meeting-item-head"><?php _e( 'Invite Attendees', 'buddyboss' ); ?></label>
					<div class="meeting-item-col">
						<?php _e( 'Join URL:', 'buddyboss' ); ?> <a target="_blank"
																	href="<?php bp_zoom_meeting_zoom_join_url(); ?>"><?php bp_zoom_meeting_zoom_join_url(); ?></a>
						<span>
					<a id="copy-invitation" class="edit" href="#" role="button"
					   data-join-url="<?php bp_zoom_meeting_zoom_join_url(); ?>">
						<?php _e( 'Copy the invitation', 'buddyboss' ); ?>
					</a>
				</span>
					</div>
				</div>
				<div class="single-meeting-item">
					<div>
						<label class="meeting-item-head"><?php _e( 'Video', 'buddyboss' ); ?></label>
						<div class="meeting-item-col">
							<?php _e( 'Host', 'buddyboss' ); ?>
							<?php echo bp_get_zoom_meeting_host_video() ? __( ' On', 'buddyboss' ) : __( 'Off', 'buddyboss' ); ?>
						</div>
					</div>
					<div>
						<label class="meeting-item-head"></label>
						<div class="meeting-item-col">
							<?php _e( 'Participant', 'buddyboss' ); ?>
							<?php echo bp_get_zoom_meeting_participants_video() ? __( 'On', 'buddyboss' ) : __( 'Off', 'buddyboss' ); ?>
						</div>
					</div>
					<div>
						<div class="meeting-item-head"><?php _e( 'Meeting Options', 'buddyboss' ); ?></div>
						<div class="meeting-item-col">
					<span>
						<i class="dashicons <?php echo bp_get_zoom_meeting_join_before_host() ? 'dashicons-yes' : 'dashicons-no-alt'; ?>"></i>
						<?php _e( 'Enable join before host', 'buddyboss' ); ?>
					</span>
						</div>
					</div>
					<div>
						<div class="meeting-item-head"></div>
						<div class="meeting-item-col">
					<span>
						<i class="dashicons <?php echo bp_get_zoom_meeting_mute_participants() ? 'dashicons-yes' : 'dashicons-no-alt'; ?>"></i>
						<?php _e( 'Mute participants upon entry', 'buddyboss' ); ?>
					</span>
						</div>
					</div>
					<div>
						<div class="meeting-item-head"></div>
						<div class="meeting-item-col">
					<span>
						<i class="dashicons <?php echo bp_get_zoom_meeting_waiting_room() ? 'dashicons-yes' : 'dashicons-no-alt'; ?>"></i><?php _e( 'Enable waiting room', 'buddyboss' ); ?>
					</span>
						</div>
					</div>
					<div>
						<div class="meeting-item-head"></div>
						<div class="meeting-item-col">
					<span>
						<i class="dashicons <?php echo bp_get_zoom_meeting_enforce_login() ? 'dashicons-yes' : 'dashicons-no-alt'; ?>"></i><?php _e( 'Only authenticated users can join', 'buddyboss' ); ?>
					</span>
						</div>
					</div>
					<div class="form-group">
						<div class="meeting-item-head"></div>
						<div class="meeting-item-col">
					<span for="option_autorec">
						<i class="dashicons <?php echo 'cloud' === bp_get_zoom_meeting_auto_recording() ? 'dashicons-yes' : 'dashicons-no-alt'; ?>"></i>
						<span><?php _e( 'Record the meeting automatically in the cloud', 'buddyboss' ); ?></span>
					</span>
						</div>
					</div>
				</div>
				<div class="single-meeting-item last-col">
					<div class="meeting-item-col meeting-action last-col full">
						<a role="button" data-nonce="<?php echo wp_create_nonce( 'bp_zoom_meeting_delete' ); ?>"
						   class="btn delete bp-zoom-meeting-delete"
						   href="javascript:;"><?php _e( 'Delete this Meeting', 'buddyboss' ); ?></a>
						<div class="pull-right">
							<a role="button" class="button small outline"
							   href="<?php echo trailingslashit( bp_get_group_permalink( groups_get_group( bp_get_zoom_meeting_group_id() ) ) . 'zoom/meetings/edit/' . bp_get_zoom_meeting_id() ); ?>"><?php _e( 'Edit this Meeting', 'buddyboss' ); ?></a>
							<a type="button" class="button small outline"
							   href="<?php echo bp_get_zoom_meeting_zoom_start_url(); ?>"><?php _e( 'Start this Meeting', 'buddyboss' ); ?></a>
						</div>
					</div>
				</div>
			</div>

		</div>
	<?php endwhile;
endif;

