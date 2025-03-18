<?php
/**
 * Readylaunch - Messages main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with user's private messages.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

?>

<input type="hidden" id="thread-id" value="" />
<div class="bb-rl-messages-container bb-rl-view-message bp-rl-messages-container">
	<div class="bb-rl-messages-nav-panel loading">
		<div class="bb-rl-message-header-loading bp-hide">
			<div class="bb-rl-message-header-loading_top">
				<div class="bb-rl-message-header-loading_title bb-bg-animation bb-loading-bg"></div>
				<div class="bb-rl-message-header-loading_option bb-bg-animation bb-loading-bg"></div>
			</div>
			<div class="bb-rl-message-header-loading_description bb-bg-animation bb-loading-bg bb-loading-input"></div>
		</div>
		<div id="bb-rl-messages-thread-list-nav"></div>
		<div class="subnav-filters filters user-subnav bb-rl-messages-filters push-right" id="subsubnav"></div><!--This is required for filters-->
		<div class="bb-rl-messages-search-feedback"></div>
		<div class="bb-rl-messages-threads-list bb-rl-messages-threads-list-user-<?php echo esc_attr( bp_loggedin_user_id() ); ?>" id="bb-rl-messages-threads-list"></div>
	</div>
	<div class="bb-rl-messages-content"></div>
	<div class="bb-rl-messages-right-panel">

		<!-- Single Thread -->
		<div class="bb-rl-message-profile">
			<div class="bb-rl-message-profile-avatar">
				<a href="#">
					<img class="avatar" src="https://avatar.iran.liara.run/public/boy" alt="" />
				</a>
			</div>
			<div class="bb-rl-message-profile-type">
				Admin
			</div>
			<div class="bb-rl-message-profile-name">
				<?php echo esc_html( bp_get_displayed_user_fullname() ); ?>
			</div>
			<div class="bb-rl-message-profile-meta">
				<span class="bb-rl-message-profile-meta-item">Joined 23 Nov 2024</span>
				<span class="bb-rl-message-profile-meta-item">34 followers</span>
			</div>
			<div class="bb-rl-message-profile-meta">
				<span class="bb-rl-message-profile-meta-item">Active now</span>
			</div>
		</div>

		<!-- Multiple Members Thread -->
		<div class="bb-rl-message-multiple-threads">
			<div class="bb-rl-message-multiple-threads-avatar">
				<a href="#">
					<img class="avatar" src="https://avatar.iran.liara.run/public/boy?name=Adam">
				</a>
				<a href="#">
					<img class="avatar" src="https://avatar.iran.liara.run/public/girl?name=Eve">
				</a>
			</div>
			<div class="bb-rl-message-multiple-threads-content">
				Mason, Grace, Daniel, + 4
			</div>
		</div>

		<!-- Group Thread -->
		<div class="bb-rl-message-group-thread">
			
		<div class="bb-rl-message-group-thread-header">
			<div class="bb-rl-message-group-thread-cover">
				<img src="https://picsum.photos/300/100">
			</div>
			<div class="bb-rl-message-group-thread-content">
				<div class="bb-rl-message-group-thread-avatar">
					<a href="#">
						<img class="avatar" src="https://placebear.com/80/80">
					</a>
				</div>
				<div class="bb-rl-message-group-thread-name">
					Sports Freaks
				</div>
				<div class="bb-rl-message-profile-meta">
					<span class="bb-rl-message-profile-meta-item">Public</span>
					<span class="bb-rl-message-profile-meta-item">Group</span>
					<span class="bb-rl-message-profile-meta-item">Active 2 hours ago</span>
				</div>
			</div>
		</div>

	</div>

</div>

<?php

if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) {
	bp_get_template_part( 'media/theatre' );
}
if ( bp_is_active( 'video' ) && bp_is_messages_video_support_enabled() ) {
	bp_get_template_part( 'video/theatre' );
}
if ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() ) {
	bp_get_template_part( 'document/theatre' );
}
