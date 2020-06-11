<?php

$args = array(
	'exclude'             => array( bp_loggedin_user_id() ),
	'exclude_admins_mods' => false,
);

$group_members = groups_get_group_members( $args );

?>

<div id="group-messages-container">

	<?php if ( $group_members['count'] != 0 ) { ?>
		<div class="bb-groups-messages-left">
			<div class="bb-groups-messages-left-inner">
				<div class="bb-panel-head">
					<div class="bb-panel-subhead">
						<h4 class="total-members-text"><?php esc_html_e( 'Members', 'buddyboss' ); ?></h4>
						<div class="bp-group-message-wrap">
							<input id="bp-group-message-switch-checkbox" class="bp-group-message-switch-checkbox bb-input-switch bs-styled-checkbox" type="checkbox" checked>
							<label for="bp-group-message-switch-checkbox" class="bp-group-message-label"><span class="select-members-text"><?php esc_html_e( 'Select All', 'buddyboss' ); ?></span></label>
						</div>
					</div>
					<div id="bp-message-dropdown-options" class="bp-message-dropdown-options-hide">
						<div>
							<i class="bb-icon-loader animate-spin"></i>
						</div>
					</div>
				</div>

				<div class="group-messages-search subnav-search clearfix" role="search">
					<div class="bp-search">
						<form action="" method="get" id="group_messages_search_form" class="bp-messages-search-form" data-bp-search="group-messages">
							<label for="group_messages_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
							<input type="search" id="group_messages_search" placeholder="<?php esc_attr_e( 'Search Members', 'buddyboss' ); ?>"/>
							<button type="submit" id="group_messages_search_submit" class="nouveau-search-submit">
								<span class="bb-icon-search" aria-hidden="true"></span>
								<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Members', 'buddyboss' ); ?></span>
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
	<?php } ?>

	<div class="bb-groups-messages-right">
		<form id="send_group_message_form" class="standard-form" data-select2-id="send_group_message_form">
			<div class="bb-groups-messages-right-top">
				<div class="bb-title-wrap">
					<h2 class="bb-title"><?php esc_html_e( 'New Group Message', 'buddyboss' ); ?></h2>
					<a class="group-messages-compose" href="javascript:void(0);"><?php esc_html_e( 'New Group Message', 'buddyboss' ); ?></a>
					<div class="add-more-members"><a class="bb-add-members" href="#"><span class="bb-icon-plus-circle"></span><?php esc_html_e( 'Select Members', 'buddyboss' ); ?></a></div>
				</div>
				<div class="bp-select-members-wrap">
					<div class="bp-messages-feedback bp-messages-feedback-hide">
						<div class="bp-feedback">
							<span class="bp-icon" aria-hidden="true"></span>
							<p></p>
						</div>
					</div>
					<?php if ( $group_members['count'] != 0 ) { ?>
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
							<?php if ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() ) : ?>
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
							<?php if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) : ?>
								<div class="post-elements-buttons-item post-media media-support">
									<a href="#" id="bp-group-messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a photo', 'buddyboss' ); ?>">
										<span class="bb-icon bb-icon-camera-small"></span>
									</a>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() ) : ?>
								<div class="post-elements-buttons-item post-media document-support">
									<a href="#" id="bp-group-messages-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a document', 'buddyboss' ); ?>">
										<span class="bb-icon bb-icon-attach"></span>
									</a>
								</div>
							<?php endif; ?>
							<?php if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) : ?>
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
							<?php if ( bp_is_active( 'media' ) && bp_is_messages_emoji_support_enabled() ) : ?>
								<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Insert an emoji', 'buddyboss' ); ?>"></div>
							<?php endif; ?>
							<div id="group-messages-new-submit" class="submit">
								<select name="group-messages-type" class="group-messages-type">
									<option value="open"><?php esc_html_e( 'Group Thread', 'buddyboss' ); ?></option>
									<option value="private"><?php esc_html_e( 'Private Reply (BCC)', 'buddyboss' ); ?></option>
								</select>
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
								} ?>
								<input <?php echo esc_attr( $disabled ); ?> type="submit" name="send_group_message_button" value="<?php _e( 'Send Message', 'buddyboss' ); ?>" id="send_group_message_button" class="small">
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</form>
	</div>
</div>
