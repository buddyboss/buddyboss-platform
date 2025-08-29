<?php
/**
 * Readylaunch - Messages right panel template.
 *
 * This template provides the right panel content for messages
 * including participant information, media, and files tabs.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<script type="text/html" id="tmpl-bp-messages-right-panel">
	<div class="bb-rl-right-panel_message_header">
		<h3 class="bb-rl-right-panel_title">
			<?php esc_html_e( 'About', 'buddyboss' ); ?>
		</h3>
		<a href="#" class="message_action__close_info" aria-label="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>">
			<i class="bb-icons-rl-x"></i>
		</a>
	</div>
	<#
	if (data.is_group_thread && data.group_name && data.group_name.length > 1) { #>
		<!-- Group Thread -->
		<div class="bb-rl-message-group-thread">
			<# if (data.group_cover_image) { #>
			<div class="bb-rl-message-group-thread-cover">
				<img decoding="async" src="{{data.group_cover_image}}">
			</div>
			<# } #>
			<div class="bb-rl-message-group-thread-content">
				<div class="bb-rl-message-group-thread-avatar">
					<a href="{{data.group_link}}">
						<img decoding="async" class="avatar" src="{{data.group_avatar}}">
					</a>
				</div>
				<div class="bb-rl-message-group-thread-name">
					{{data.group_name}}
				</div>
				<div class="bb-rl-message-profile-meta">
					<# if (data.group_status) { #>
						{{{data.group_status}}}
					<# } #>
					<# if (data.group_last_active) { #>
						<span class="bb-rl-message-profile-meta-item">{{data.group_last_active}}</span>
					<# } #>
				</div>
			</div>
		</div>
	<# } else if (data.recipients && data.recipients.count > 2) { #>
		<!-- Multiple Members Thread -->
		<div class="bb-rl-message-multiple-threads">
				<div class="bb-rl-message-multiple-threads-avatar">
					<# var count = 0; #>
					<# _.each( data.recipients.members, function( member ) { #>
						<# if (count < 2) { #>
							<a href="{{{ member.user_link }}}">
								<img class="avatar" src="{{{ member.avatar }}}">
							</a>
						<# } #>
						<# count++; #>
					<# }); #>
				</div>
				<div class="bb-rl-message-multiple-threads-content">
					<# 
					var members = data.recipients.members;
					var names = [];
					var count = 0;
					var totalMembers = _.size(members);
					
					_.each(members, function(member) {
						if (count < 2) {
							names.push(member.user_name);
						}
						count++;
					});

					if (totalMembers <= 3) {
						var allNames = [];
						_.each(members, function(member) {
							allNames.push(member.user_name);
						});
						print(allNames.join(', '));
					} else {
						print(names.join(', ') + ' + ' + (totalMembers - 2));
					} 
					#>
				</div>
			</div>
	<# } else {
		#>
		<!-- Single Thread -->
		<div class="bb-rl-message-profile">
		<# var recipient = _.find(data.recipients.members, function(item) { return !item.is_you; }); #>
		<# if (!recipient) { #>
			<# recipient = _.find(data.recipients.members, function(item) { return item.is_you; }); #>
		<# } #>
			<# 
			if (recipient) {
				#>
				<div class="bb-rl-message-profile-avatar">
					<a href="{{recipient.user_link}}">
						<img decoding="async" class="avatar" src="{{recipient.avatar}}" alt="{{recipient.user_name}}">
						<# if ( typeof( recipient.user_presence ) != "undefined" && recipient.user_presence !== null && recipient.user_presence.length > 1 ) { #>
							{{{recipient.user_presence}}}
						<# } #>
					</a>
				</div>
				<# if ( recipient.member_type && recipient.member_type.label ) { #>
					<div class="bb-rl-message-profile-type" style="color:{{recipient.member_type.color.text}}; background-color:{{recipient.member_type.color.background}};">
						{{ recipient.member_type.label }}
					</div>
				<# } #>
				<div class="bb-rl-message-profile-name">
					<# if (recipient.is_you) { #>
					You
					<# } else { #>
					{{recipient.user_name}}
					<# } #>
				</div>
				<div class="bb-rl-message-profile-meta">
					<# if (recipient.joined_date) { #>
					<span class="bb-rl-message-profile-meta-item">{{recipient.joined_date}}</span>
					<# } #>
					<# if (recipient.followers_count) { #>
					<span class="bb-rl-message-profile-meta-item">{{recipient.followers_count}} followers</span>
					<# } #>
				</div>
				<# if (recipient.last_active) { #>
				<div class="bb-rl-message-profile-meta">
					<span class="bb-rl-message-profile-meta-item">{{recipient.last_active}}</span>
				</div>
				<# } #>
			<# } #>
		</div>
	<# } #>

	<div class="bb-rl-message-right-panel-inner">
		<!-- Tab navigation -->
		<div class="bb-rl-message-right-tabs">
			<button class="bb-rl-tab-item active" data-tab="participants">
				<?php esc_html_e( 'Participants', 'buddyboss' ); ?>
			</button>
			<#
			var mediaComponentActive = <?php echo bp_is_active( 'media' ) ? 'true' : 'false'; ?>;
			var videoComponentActive = <?php echo bp_is_active( 'video' ) ? 'true' : 'false'; ?>;
			var messagesMediaEnabled = <?php echo function_exists( 'bp_is_messages_media_support_enabled' ) ? bp_is_messages_media_support_enabled() : 'false'; ?>;
			var groupMediaEnabled    = <?php echo function_exists( 'bp_is_group_media_support_enabled' ) ? bp_is_group_media_support_enabled() : 'false'; ?>;
			var messagesVideoEnabled = <?php echo function_exists( 'bp_is_messages_video_support_enabled' ) ? bp_is_messages_video_support_enabled() : 'false'; ?>;
			var groupVideoEnabled    = <?php echo function_exists( 'bp_is_group_video_support_enabled' ) ? bp_is_group_video_support_enabled() : 'false'; ?>;

			var mediaActive         = mediaComponentActive && ( messagesMediaEnabled || groupMediaEnabled );
			var videoActive         = videoComponentActive && ( messagesVideoEnabled || groupVideoEnabled );
			var groupMediaActive    = mediaComponentActive && groupMediaEnabled;
			var messagesMediaActive = mediaComponentActive && messagesMediaEnabled;
			var groupVideoActive    = videoComponentActive && groupVideoEnabled;
			var messagesVideoActive = videoComponentActive && messagesVideoEnabled;
			#>
			<# if (
				(mediaActive || videoActive) &&
				(
					(
						( groupMediaActive || groupVideoActive ) &&
						data.group_id &&
						'group' === data.message_from
					) ||
					(
						( messagesMediaActive || messagesVideoActive ) &&
						'group' !== data.message_from
					)
				)
			) { #>
				<button class="bb-rl-tab-item" data-tab="media">
				<?php esc_html_e( 'Media', 'buddyboss' ); ?>
				</button>
			<# }
			var filesActive = <?php echo ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) ? 'true' : 'false'; ?>;
			var groupDocumentActive = <?php echo ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() ) ? 'true' : 'false'; ?>;
			var messagesDocumentActive = <?php echo ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() ) ? 'true' : 'false'; ?>;
			if ( 
				filesActive &&
				(
					groupDocumentActive &&
					data.group_id &&
					'group' === data.message_from 
				) || 
				(
					messagesDocumentActive &&
					'group' !== data.message_from
				)
			) { #>
				<button class="bb-rl-tab-item" data-tab="files">
					<?php esc_html_e( 'Files', 'buddyboss' ); ?>
				</button>
			<# } #>
		</div>
		
		<!-- Tab content -->
		<div class="bb-rl-message-right-content">
			<!-- Participants tab -->
			<div class="bb-rl-tab-content active" id="participants-tab">
				<div class="bb-rl-message-right-loading">
					<div class="bb-rl-loader"></div>
				</div>
			</div>
			
			<!-- Media tab -->
			<div class="bb-rl-tab-content" id="media-tab">
				<div class="bb-rl-message-right-loading">
					<div class="bb-rl-loader"></div>
				</div>
			</div>
			
			<!-- Files tab -->
			<div class="bb-rl-tab-content" id="files-tab">
				<div class="bb-rl-message-right-loading">
					<div class="bb-rl-loader"></div>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/html" id="tmpl-bp-messages-right-panel-participants">
	<# _.each( data, function( participant ) { #>
		<div class="bb-rl-participant-item">
			<div class="bb-rl-participant-avatar">
				<# if ( participant.profile_url ) { #>
					<a href="{{participant.profile_url}}">
						<img src="{{participant.avatar}}" alt="{{participant.name}}">
						<# if ( typeof( participant.user_presence ) != "undefined" && participant.user_presence !== null && participant.user_presence.length > 1 ) { #>
							{{{participant.user_presence}}}
						<# } #>
					</a>
				<# } else { #>
					<img src="{{participant.avatar}}" alt="{{participant.name}}">
				<# } #>
			</div>
			<div class="bb-rl-participant-info">
				<h4 class="bb-rl-participant-name">
					<# if ( participant.profile_url ) { #>
						<a href="{{participant.profile_url}}">{{participant.name}}</a>
					<# } else { #>
						{{participant.name}}
					<# } #>
				</h4>
			
				<# if (participant.role) { #>
					<span class="bb-rl-participant-role">{{participant.role}}</span>
				<# } #>
					
				<div class="bb-rl-participant-meta">
					<# if (participant.joined_date) { #>
						<span>Joined {{participant.joined_date}}</span>
					<# } #>
					<# if (participant.last_active) { #>
						<span>{{participant.last_active}}</span>
					<# } #>
				</div>
			</div>
		</div>
	<# }); #>
</script>

<script type="text/html" id="tmpl-bp-messages-right-panel-media">
	<# if ( data.length > 0 ) {
		_.each( data, function( item ) { #>
			<div class="bb-rl-media-item" data-type="{{item.type}}">
				<a class="bb-rl-open-media-video-theatre bb-{{item.type}}-cover-wrap bb-item-cover-wrap bb-open-{{item.type}}"
					data-id="{{item.id}}"
					data-attachment-id="{{item.attachment_id}}"
					data-attachment-full="{{item.full}}"
					data-privacy="{{item.privacy}}"
					data-type="{{item.type}}"
					href="#">
						<img src="{{item.thumbnail}}" alt="{{item.title}}">
				</a>
			</div>
		<# }); #>
	<# } else { #>
		<div class="bb-rl-no-content"><?php esc_html_e( 'No media found in this conversation.', 'buddyboss' ); ?></div>
	<# } #>
</script>

<script type="text/html" id="tmpl-bp-messages-right-panel-files">
	<# 
	if ( data.length > 0 ) {
		_.each( data, function( file ) { #>
			<div class="bb-rl-file-item">
				<# if ( file.svg_icon ) { #>
					<i class="bb-rl-file-icon {{file.svg_icon}}"></i>
				<# } else { #>
					<i class="bb-rl-file-icon bb-icons-rl-file-{{file.extension}}"></i>
				<# } #>
				<div class="bb-rl-file-info">
					<h4 class="bb-rl-file-name">
						<a class="bb-rl-document-detail-wrap bb-rl-open-document-theatre"
							href="{{file.url}}" 
							title="{{file.full_title}}"
							data-id="{{file.id}}"
							data-activity-id=""
							data-icon-class="{{file.svg_icon}}"
							data-attachment-id="{{file.attachment_id}}"
							data-attachment-full=""
							data-privacy="{{file.privacy}}"
							data-extension="{{file.extension}}"
							data-author="{{file.author}}"
							data-preview="{{file.preview}}"
							data-full-preview="{{file.full_preview}}"
							data-text-preview="{{file.text_preview}}"
							data-mp3-preview="{{file.mp3_preview}}"
							data-document-title="{{file.document_title}}"
							data-video-preview="{{file.video}}"
							data-mirror-text="{{file.mirror_text}}">
								{{file.title}}
						</a>
					</h4>
				</div>
			</div>
		<# }); #>
	<# } else { #>
		<div class="bb-rl-no-content"><?php esc_html_e( 'No files found in this conversation.', 'buddyboss' ); ?></div>
	<# } #>
</script>