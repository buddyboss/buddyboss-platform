<?php
/**
 * Readylaunch - Messages main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with user's private messages.
 *
 * @since   BuddyBoss 2.9.00
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
		<div class="subnav-filters filters user-subnav bb-rl-messages-filters" id="subsubnav"></div><!--This is required for filters-->
		<!-- Message Tabs -->
		<div class="bb-rl-messages-tabs">
			<ul class="bb-rl-messages-tabs-list">
				<li class="bb-rl-messages-tab-item <?php echo ! bp_is_current_action( 'archived' ) ? 'active' : ''; ?>">
					<a href="#" class="bb-rl-messages-tab-link" data-tab="all"><?php esc_html_e( 'All', 'buddyboss' ); ?></a>
				</li>
				<li class="bb-rl-messages-tab-item">
					<a href="#" class="bb-rl-messages-tab-link" data-tab="unread"><?php esc_html_e( 'Unread', 'buddyboss' ); ?></a>
				</li>
				<li class="bb-rl-messages-tab-item <?php echo bp_is_current_action( 'archived' ) ? 'active' : ''; ?>">
					<a href="#" class="bb-rl-messages-tab-link" data-tab="archived"><?php esc_html_e( 'Archive', 'buddyboss' ); ?></a>
				</li>
			</ul>
		</div>
		<!-- End Message Tabs -->
		<div class="bb-rl-messages-search-feedback"></div>
		<div class="bb-rl-messages-threads-list bb-rl-messages-threads-list-user-<?php echo esc_attr( bp_loggedin_user_id() ); ?>" id="bb-rl-messages-threads-list"></div>
	</div>
	<div class="bb-rl-messages-content"></div>
	<div id="bb-rl-messages-right-panel" class="<?php echo bp_is_current_action( 'compose' ) ? 'bp-hide' : ''; ?>"></div>
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
if (
	( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) ||
	( bp_is_active( 'video' ) && bp_is_messages_video_support_enabled() )
) {
	bp_get_template_part( 'common/js-templates/messages/theatre' );
}
