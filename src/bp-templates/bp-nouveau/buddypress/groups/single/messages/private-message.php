<?php
/**
 * BP Nouveau Group's Private Message template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/messages/private-message.php.
 *
 * @since   BuddyBoss 1.5.7
 * @version 1.5.7
 */

$args = array(
	'exclude'             => array( bp_loggedin_user_id() ),
	'exclude_admins_mods' => false,
	'xprofile_query'      => false,
	'populate_extras'     => false,
);

$group_members = groups_get_group_members( $args );
$total_count   = 0;
$all_text      = esc_html__( 'All Group Members', 'buddyboss' );

if ( ! empty( $group_members ) && isset( $group_members['members'] ) && ! empty( $group_members['members'] ) ) {
	foreach ( $group_members['members'] as $member ) {

		if (
			bb_messages_user_can_send_message(
				array(
					'sender_id'     => bp_loggedin_user_id(),
					'recipients_id' => $member->ID,
					'group_id'      => bp_get_current_group_id(),

				)
			)
		) {
			$total_count ++;
		}
	}
}

if ( $total_count > 0 ) {
	$all_text = sprintf( _n( '%s Member', '%s Members', $total_count, 'buddyboss' ), $total_count );
}

if ( 0 === $total_count ) {
	?>
	<div class="bb-groups-messages-private-full">
		<div class="bp-messages-feedback bp-messages-feedback-hide">
			<div class="bp-feedback info">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php esc_html_e( 'You don\'t have access to send a private message to any member of this group.', 'buddyboss' ); ?></p>
			</div>
		</div>
	</div>
	<?php
} else {

	$group_id = 0;
	if ( bp_is_active( 'groups' ) && bp_is_group_single() ) {
		$group_id = bp_get_current_group_id();
	}

	if ( $group_members['count'] != 0 ) {
		?>
		<div class="bb-groups-messages-left">
			<div class="bb-groups-messages-left-inner">
				<div class="bb-panel-head">
					<div class="bb-panel-subhead">
						<h4 class="total-members-text"><?php esc_html_e( 'Group Members', 'buddyboss' ); ?></h4>
						<div class="bp-group-message-wrap" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'You are not allowed to Create New Thread with all group members.', 'buddyboss' ); ?>">
							<input id="bp-group-message-switch-checkbox" class="bp-group-message-switch-checkbox bb-input-switch bs-styled-checkbox" type="checkbox">
							<label for="bp-group-message-switch-checkbox" class="bp-group-message-label">
								<span class="select-members-text"><?php esc_html_e( 'Select All', 'buddyboss' ); ?></span>
							</label>
						</div>
					</div>
					<div id="bp-message-dropdown-options" class="bp-message-dropdown-options-hide">
						<div>
							<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
						</div>
					</div>
				</div>

				<div class="group-messages-search subnav-search clearfix" role="search">
					<div class="bp-search">
						<form action="" method="get" id="group_messages_search_form" class="bp-messages-search-form search-form-has-reset" data-bp-search="group-messages">
							<label for="group_messages_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
							<input type="search" id="group_messages_search" placeholder="<?php esc_attr_e( 'Search Members', 'buddyboss' ); ?>"/>
							<button type="submit" id="group_messages_search_submit" class="nouveau-search-submit search-form_submit">
								<span class="bb-icon-l bb-icon-search" aria-hidden="true"></span>
								<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Members', 'buddyboss' ); ?></span>
							</button>
							<button type="reset" class="search-form_reset">
								<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
								<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
							</button>
						</form>
					</div>
				</div>

				<div class="group-messages-members-listing">
					<div class="last" style="display: none;"></div>

					<div class="bp-messages-feedback bp-messages-feedback-hide">
						<div class="bp-feedback">
							<span class="bp-icon" aria-hidden="true"></span>
							<p></p>
						</div>
					</div>

					<ul id="members-list" class="item-list bp-list all-members"></ul>
				</div>

				<div class="bb-panel-footer">
					<a class="bb-close-select-members button" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}
	?>

	<div class="bb-groups-messages-right">
		<form id="send_group_message_form" class="standard-form" data-select2-id="send_group_message_form">
			<input type="hidden" class="count-all-members-text" value="<?php echo esc_attr( $all_text ); ?>">
			<div class="bb-groups-messages-right-top">
				<div class="bb-title-wrap">
					<h2 class="bb-title"><?php esc_html_e( 'New Private Message', 'buddyboss' ); ?></h2>
					<a class="group-messages-compose" href="javascript:void(0);"><?php esc_html_e( 'New Private Message', 'buddyboss' ); ?></a>
					<div class="add-more-members"><a class="bb-add-members" href="#"><span class="bb-icon-rl bb-icon-plus-circle"></span><?php esc_html_e( 'Select Members', 'buddyboss' ); ?></a></div>
				</div>
				<div class="bp-select-members-wrap">
					<div class="bp-messages-feedback bp-messages-feedback-hide">
						<div class="bp-feedback">
							<span class="bp-icon" aria-hidden="true"></span>
							<p></p>
						</div>
					</div>
					<?php if ( 0 !== $group_members['count'] ) { ?>
						<span class="group-messages-helper-text"><?php esc_html_e( 'Send to', 'buddyboss' ); ?></span>
						<select name="group_messages_send_to[]" class="send-to-input select2-hidden-accessible" id="group-messages-send-to-input" placeholder="<?php esc_html_e( 'Type the names of one or more people', 'buddyboss' ); ?>" autocomplete="off" multiple="" style="width: 100%" data-select2-id="group-messages-send-to-input" tabindex="-1" aria-hidden="true"></select>
					<?php } ?>
				</div>
			</div>
			<?php if ( 0 !== $group_members['count'] ) { ?>
				<div class="bb-groups-messages-right-bottom">
					<div id="bp-group-message-content">
						<div id="group_message_content" name="group_message_content" tabindex="3"></div>
						<input type="hidden" id="group_message_content_hidden" name="group_message_content_hidden" value="">
						<div id="whats-new-attachments">
							<?php if ( bp_is_active( 'media' ) ) : ?>
								<div class="dropzone closed media-dropzone" id="bp-group-messages-post-media-uploader"></div>
								<input name="bp_group_messages_media" id="bp_group_messages_media" type="hidden" value=""/>
								<div class="forum-post-media-template" style="display:none;">
									<div class="dz-preview">
										<div class="dz-image">
											<img data-dz-thumbnail/>
										</div>
										<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
										<div class="dz-details">
											<div class="dz-filename"><span data-dz-name></span></div>
											<div class="dz-size" data-dz-size></div>
										</div>
										<div class="dz-progress-ring-wrap">
											<i class="bb-icon-f bb-icon-camera"></i>
											<svg class="dz-progress-ring" width="54" height="54">
												<circle class="progress-ring__circle" stroke="white" stroke-width="3" fill="transparent" r="24.5" cx="27" cy="27" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
											</svg>
										</div>
										<div class="dz-error-message"><span data-dz-errormessage></span></div>
										<div class="dz-error-mark">
											<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>
													Error</title>
												<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
													<g stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF" fill-opacity="0.816519475">
														<path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"></path>
													</g>
												</g>
											</svg>
										</div>
									</div>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) ) : ?>
								<div class="dropzone closed video-dropzone" id="bp-group-messages-post-video-uploader"></div>
								<input name="bp_group_messages_video" id="bp_group_messages_video" type="hidden" value=""/>
								<div class="forum-post-video-template" style="display:none;">
									<div class="dz-preview dz-file-preview well" id="dz-preview-template">
										<div class="dz-details">
											<div class="dz-filename"><span data-dz-name></span></div>
										</div>
										<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
										<div class="dz-progress-ring-wrap">
											<i class="bb-icon-f bb-icon-video"></i>
											<svg class="dz-progress-ring" width="54" height="54">
												<circle class="progress-ring__circle" stroke="white" stroke-width="3" fill="transparent" r="24.5" cx="27" cy="27" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
											</svg>
										</div>
										<!-- <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div> -->
										<div class="dz-error-message"><span data-dz-errormessage></span></div>
										<div class="dz-success-mark">
											<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>
													Check</title>
												<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
													<path d="M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z" stroke-opacity="0.198794158" stroke="#747474" fill-opacity="0.816519475" fill="#FFFFFF"></path>
												</g>
											</svg>
										</div>
										<div class="dz-error-mark">
											<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>
													Error</title>
												<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
													<g stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF" fill-opacity="0.816519475">
														<path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"></path>
													</g>
												</g>
											</svg>
										</div>
										<div class="dz-progress-count"><?php esc_html_e( '0% Uploaded', 'buddyboss' ); ?></span></div>
										<div class="dz-video-thumbnail"></span></div>
									</div>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) ) : ?>
								<div class="dropzone closed document-dropzone" id="bp-group-messages-post-document-uploader"></div>
								<input name="bp_group_messages_document" id="bp_group_messages_document" type="hidden" value=""/>
								<div class="forum-post-document-template" style="display:none;">
									<div class="dz-preview dz-file-preview">
										<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
										<div class="dz-details">
											<div class="dz-icon"><span class="bb-icon-l bb-icon-file"></span></div>
											<div class="dz-filename"><span data-dz-name></span></div>
											<div class="dz-size" data-dz-size></div>
										</div>
										<div class="dz-progress-ring-wrap">
											<i class="bb-icon-f bb-icon-file-attach"></i>
											<svg class="dz-progress-ring" width="54" height="54">
												<circle class="progress-ring__circle" stroke="white" stroke-width="3" fill="transparent" r="24.5" cx="27" cy="27" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
											</svg>
										</div>
										<div class="dz-error-message"><span data-dz-errormessage></span></div>
										<div class="dz-error-mark">
											<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>
													Error</title>
												<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
													<g stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF" fill-opacity="0.816519475">
														<path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"></path>
													</g>
												</g>
											</svg>
										</div>
									</div>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) ) : ?>
								<div class="bp-group-messages-attached-gif-container closed">
									<div class="gif-image-container">
										<img src="" alt="">
									</div>
									<div class="gif-image-remove gif-image-overlay">
										<span class="bb-icon-l bb-icon-times"></span>
									</div>
								</div>
								<input name="bp_group_messages_gif" id="bp_group_messages_gif" type="hidden" value=""/>
							<?php endif; ?>
						</div>
						<div id="whats-new-toolbar" class="
						<?php
						if ( ! bp_is_active( 'media' ) ) {
							echo 'media-off';
						}
						?>
						">
							<?php if ( bp_is_active( 'media' ) ) : ?>
								<div class="post-elements-buttons-item show-toolbar" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>">
									<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
										<span class="bb-icon-l bb-icon-font"></span>
									</a>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), 0, 0, 'message' ) ) : ?>
								<div class="post-elements-buttons-item post-media media-support group-message-media-support">
									<a href="#" id="bp-group-messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>">
										<span class="bb-icon-l bb-icon-camera"></span>
									</a>
								</div>
							<?php endif; ?>
							<?php
							$video_extensions = ( function_exists( 'bp_video_get_allowed_extension' ) ) ? bp_video_get_allowed_extension() : '';
							if ( bp_is_active( 'media' ) && ! empty( $video_extensions ) && bb_user_has_access_upload_video( $group_id, bp_loggedin_user_id(), 0, 0, 'message' ) ) :
								?>
								<div class="post-elements-buttons-item post-video video-support">
									<a href="#" id="bp-group-messages-video-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>">
										<i class="bb-icon-l bb-icon-video"></i>
									</a>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), 0, 0, 'message' ) ) : ?>
								<div class="post-elements-buttons-item post-media document-support group-message-document-support">
									<a href="#" id="bp-group-messages-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>">
										<span class="bb-icon-l bb-icon-attach"></span>
									</a>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), 0, 0, 'message' ) ) : ?>
								<div class="post-elements-buttons-item post-gif">
									<div class="gif-media-search">
										<a href="#" id="bp-group-messages-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>">
											<span class="bb-icon-l bb-icon-gif"></span>
										</a>
										<div class="gif-media-search-dropdown">
											<div class="bp-group-messages-attached-gif-container">
												<div class="gif-search-content">
													<div class="gif-search-query">
														<input type="search" placeholder="<?php esc_html_e( 'Search GIPHY...', 'buddyboss' ); ?>" class="search-query-input"/>
														<span class="search-icon"></span>
													</div>
													<div class="gif-search-results" id="gif-search-results">
														<ul class="gif-search-results-list">
														</ul>
														<div class="gif-alert gif-no-results">
															<i class="bb-icon-l bb-icon-image-slash"></i>
															<p><?php esc_html_e( 'No results found', 'buddyboss' ); ?></p>
														</div>

														<div class="gif-alert gif-no-connection">
															<i class="bb-icon-l bb-icon-cloud-slash"></i>
															<p><?php esc_html_e( 'Could not connect to GIPHY', 'buddyboss' ); ?></p>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_emoji( $group_id, bp_loggedin_user_id(), 0, 0, 'message' ) ) : ?>
								<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>"></div>
							<?php endif; ?>
							<div id="group-messages-new-submit" class="submit">
								<select name="group-messages-type" class="group-messages-type">
									<option value="private"><?php esc_html_e( 'Send Individually', 'buddyboss' ); ?></option>
									<option value="open"><?php esc_html_e( 'Create New Thread', 'buddyboss' ); ?></option>
								</select>
								<?php
								$disabled = '';
								if ( empty( $group_members['members'] ) ) {
									$disabled = 'disabled';
								}
								?>
								<button <?php echo esc_attr( $disabled ); ?> type="submit" name="send_group_message_button" id="send_group_message_button" class="small"><?php esc_html_e( 'Send Message', 'buddyboss' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</form>
	</div>

	<?php
}
