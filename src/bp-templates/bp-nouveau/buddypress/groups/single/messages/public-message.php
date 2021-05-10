<?php
/**
 * BP Nouveau Group's Public Message template.
 *
 * @since BuddyBoss 1.5.7
 */

$args = array(
	'exclude'             => array( bp_loggedin_user_id() ),
	'exclude_admins_mods' => false,
);

$group_members = groups_get_group_members( $args );

$group_id = 0;
if ( bp_is_active( 'groups' ) && bp_is_group_single() ) {
	$group_id = bp_get_current_group_id();
}
$extensions = bp_is_active( 'media' ) ? bp_document_get_allowed_extension() : false;

?>

<div class="bb-groups-messages-right">
		<form id="send_group_message_form" class="standard-form" data-select2-id="send_group_message_form">
			<div class="bb-groups-messages-right-top">
				<div class="bb-title-wrap">
					<h2 class="bb-title"><?php esc_html_e( 'New Group Message', 'buddyboss' ); ?></h2>
					<a class="group-messages-compose" href="javascript:void(0);"><?php esc_html_e( 'New Group Message', 'buddyboss' ); ?></a>
				</div>
				<div class="bp-select-members-wrap">
					<div class="bp-messages-feedback bp-messages-feedback-hide">
						<div class="bp-feedback">
							<span class="bp-icon" aria-hidden="true"></span>
							<p></p>
						</div>
					</div>
					<?php if ( $group_members['count'] != 0 ) { ?>
						<span class="group-messages-helper-text"><?php esc_html_e( 'Send to', 'buddyboss' ); ?></span>
						<select name="group_messages_send_to[]" class="send-to-input select2-hidden-accessible" id="group-messages-send-to-input" placeholder="<?php esc_html_e( 'Type the names of one or more people', 'buddyboss' ); ?>" autocomplete="off" multiple="" style="width: 100%" data-select2-id="group-messages-send-to-input" tabindex="-1" aria-hidden="true">
							<option value="all" selected="selected"><?php esc_html_e( 'All Group Members', 'buddyboss' ); ?></option>
						</select>
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
								<div class="dropzone closed" id="bp-group-messages-post-media-uploader"></div>
								<input name="bp_group_messages_media" id="bp_group_messages_media" type="hidden" value=""/>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) ) : ?>
								<div class="dropzone closed" id="bp-group-messages-post-document-uploader"></div>
								<input name="bp_group_messages_document" id="bp_group_messages_document" type="hidden" value=""/>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) ) : ?>
								<div class="bp-group-messages-attached-gif-container closed">
									<div class="gif-image-container">
										<img src="" alt="">
									</div>
									<div class="gif-image-remove gif-image-overlay">
										<span class="bb-icon-close"></span>
									</div>
								</div>
								<input name="bp_group_messages_gif" id="bp_group_messages_gif" type="hidden" value=""/>
							<?php endif; ?>
						</div>
						<div id="whats-new-toolbar" class="
						<?php
						if ( ! bp_is_active( 'media' ) ) {
							echo 'media-off'; }
						?>
						">
							<?php if ( bp_is_active( 'media' ) ) : ?>
								<div class="post-elements-buttons-item show-toolbar"  data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>">
									<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
										<span class="bb-icon bb-icon-text-format"></span>
									</a>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), 0, 0 ) ) : ?>
								<div class="post-elements-buttons-item post-media media-support group-message-media-support">
									<a href="#" id="bp-group-messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a photo', 'buddyboss' ); ?>">
										<span class="bb-icon bb-icon-camera-small"></span>
									</a>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), 0, 0 ) ) : ?>
								<div class="post-elements-buttons-item post-media document-support group-message-document-support">
									<a href="#" id="bp-group-messages-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a document', 'buddyboss' ); ?>">
										<span class="bb-icon bb-icon-attach"></span>
									</a>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), 0, 0 ) ) : ?>
								<div class="post-elements-buttons-item post-gif">
									<div class="gif-media-search">
										<a href="#" id="bp-group-messages-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Post a GIF', 'buddyboss' ); ?>">
											<span class="bb-icon bb-icon-gif"></span>
										</a>
										<div class="gif-media-search-dropdown">
											<div class="bp-group-messages-attached-gif-container">
												<div class="gif-search-content">
													<div class="gif-search-query">
														<input type="search" placeholder="<?php esc_html_e( 'Search GIFs', 'buddyboss' ); ?>" class="search-query-input" />
														<span class="search-icon"></span>
													</div>
													<div class="gif-search-results" id="gif-search-results">
														<ul class="gif-search-results-list" >
														</ul>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_emoji( $group_id, bp_loggedin_user_id(), 0, 0 ) ) : ?>
								<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Insert an emoji', 'buddyboss' ); ?>"></div>
							<?php endif; ?>
							<div id="group-messages-new-submit" class="submit">
								<?php

								$disabled = '';
								$args     = array(
									'page'                => 1,
									'per_page'            => 12,
									'group_id'            => bp_get_current_group_id(),
									'exclude'             => array( bp_loggedin_user_id() ),
									'exclude_admins_mods' => false,
								);

								$group_members = groups_get_group_members( $args );

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
