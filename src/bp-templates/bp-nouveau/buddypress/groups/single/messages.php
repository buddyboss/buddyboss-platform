<div id="group-messages-container">
	<div class="bb-groups-messages-left">
		<div class="bb-panel-head">
			<select class="group-messages-select-members-dropdown" name="group-members">
				<option value="all"><?php _e( 'All Group Members', 'buddyboss' ); ?></option>
				<option value="single"><?php _e( 'Select Members', 'buddyboss' ); ?></option>
			</select>
		</div>

		<div class="group-messages-search subnav-search clearfix" role="search">
			<div class="bp-search">
				<form action="" method="get" id="group_messages_search_form" class="bp-messages-search-form" data-bp-search="group-messages">
					<label for="group_messages_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
					<input type="search" id="group_messages_search" placeholder="<?php esc_attr_e( 'Search Members', 'buddyboss' ); ?>"/>
					<button type="submit" id="group_messages_search_submit" class="nouveau-search-submit">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Members', 'buddyboss' ); ?></span>
					</button>
				</form>
			</div>
		</div>

		<div class="group-messages-members-listing">
			<div class="last"></div>

			<div class="bp-messages-feedback bp-messages-feedback-hide">
				<div class="bp-feedback">
					<span class="bp-icon" aria-hidden="true"></span>
					<p></p>
				</div>
			</div>
			<h4 class="total-members-text"></h4>
			<ul id="members-list" class="item-list bp-list all-members"></ul>
		</div>
	</div>

	<div class="bb-groups-messages-right">
		<form id="send_group_message_form" class="standard-form" data-select2-id="send_group_message_form">
			<div class="bb-groups-messages-right-top">
				<h2 class="bb-title"><?php _e( 'New Group Message', 'buddyboss' ); ?></h2>
				<div class="bp-messages-feedback bp-messages-feedback-hide">
					<div class="bp-feedback">
						<span class="bp-icon" aria-hidden="true"></span>
						<p></p>
					</div>
				</div>
				<select name="group_messages_send_to[]" class="send-to-input select2-hidden-accessible" id="group-messages-send-to-input" placeholder="<?php _e( 'Type the names of one or more people','buddyboss' ); ?>" autocomplete="off" multiple="" style="width: 100%" data-select2-id="group-messages-send-to-input" tabindex="-1" aria-hidden="true">
					<option value="all" selected="selected"><?php _e( 'All Group Members', 'buddyboss' ); ?></option>
				</select>
			</div>

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
							<div class="bp-group-messages-attached-gif-container closed">
								<div class="gif-image-container">
									<img src="" alt="">
								</div>
								<div class="gif-image-remove gif-image-overlay">
									<span class="dashicons dashicons-no"></span>
								</div>
							</div>
							<input name="bp_group_messages_gif" id="bp_group_messages_gif" type="hidden" value=""/>
						<?php endif; ?>
					</div>
					<div id="whats-new-toolbar">
						<?php if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) : ?>
							<div class="post-elements-buttons-item post-media">
								<a href="#" id="bp-group-messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php _e( 'Attach a photo', 'buddyboss' ); ?>">
									<span class="dashicons dashicons-admin-media"></span>
								</a>
							</div>
						<?php endif; ?>
						<?php if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) : ?>
							<div class="post-elements-buttons-item post-gif">
								<div class="gif-media-search">
									<a href="#" id="bp-group-messages-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php _e( 'Post a GIF', 'buddyboss' ); ?>">
										<span class="dashicons dashicons-smiley"></span>
									</a>
									<div class="gif-media-search-dropdown">
										<div class="bp-group-messages-attached-gif-container">
											<div class="gif-search-content">
												<div class="gif-search-query">
													<input type="search" placeholder="<?php _e( 'Search GIFs', 'buddyboss' ); ?>" class="search-query-input" />
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
							<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php _e( 'Insert an emoji', 'buddyboss' ); ?>"></div>
						<?php endif; ?>
						<div id="group-messages-new-submit" class="submit">
							<select name="group-messages-type" class="group-messages-type">
								<option value="open"><?php _e( 'Group Thread', 'buddyboss' ); ?></option>
								<option value="private"><?php _e( 'Private Reply (BCC)', 'buddyboss' ); ?></option>
							</select>
							<input type="submit" name="send_group_message_button" value="Send Message" id="send_group_message_button" class="small">
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
