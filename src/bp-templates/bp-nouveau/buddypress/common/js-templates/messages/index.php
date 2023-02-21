<?php
/**
 * BP Nouveau Messages main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with user's private messages.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<input type="hidden" id="thread-id" value="" />
<div class="bp-messages-container bp-view-message">
	<div class="bp-messages-nav-panel loading">
		<div class="message-header-loading bp-hide">
			<div class="message-header-loading_top">
				<div class="message-header-loading_title bb-bg-animation bb-loading-bg"></div>
				<div class="message-header-loading_option bb-bg-animation bb-loading-bg"></div>
			</div>
			<div class="message-header-loading_description bb-bg-animation bb-loading-bg bb-loading-input"></div>
		</div>
		<div id="bb-messages-thread-list-nav"></div>
		<div class="subnav-filters filters user-subnav bp-messages-filters push-right" id="subsubnav"></div><!--This is required for filters-->
		<div class="bp-messages-search-feedback"></div>
		<div class="bp-messages-threads-list bp-messages-threads-list-user-<?php echo esc_attr( bp_loggedin_user_id() ); ?>" id="bp-messages-threads-list"></div>
	</div>
	<div class="bp-messages-content"></div>

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

	/**
	 * Split each js template to its own file. Easier for child theme to
	 * overwrite individual parts.
	 *
	 * @version Buddyboss 1.0.0
	 */
	$template_parts = apply_filters(
		'bp_messages_js_template_parts',
		array(
			'parts/bp-messages-feedback',
			'parts/bp-messages-loading',
			'parts/bp-messages-hook',
			'parts/bp-messages-form',
			'parts/bp-messages-editor',
			'parts/bp-messages-paginate',
			'parts/bp-messages-filters',
			'parts/bp-messages-thread',
			'parts/bp-messages-single-header',
			'parts/bp-messages-single-load-more',
			'parts/bp-messages-single-list',
			'parts/bp-messages-single',
			'parts/bp-messages-editor-toolbar',
			'parts/bp-messages-formatting-toolbar',
			'parts/bp-messages-media',
			'parts/bp-messages-document',
			'parts/bp-messages-video',
			'parts/bp-messages-attached-gif',
			'parts/bp-messages-gif-media-search-dropdown',
			'parts/bp-messages-gif-result-item',
			'parts/bp-messages-no-threads',
			'parts/bp-messages-search-no-threads',
			'parts/bp-messages-filter-loader',
			'parts/bp-messages-empty-single-list',
			'parts/bp-messages-no-archived-threads',
			'parts/bp-messages-archived-nav',
			'parts/bp-messages-unarchived-nav',
		)
	);

	foreach ( $template_parts as $template_part ) {
		bp_get_template_part( 'common/js-templates/messages/' . $template_part );
	}
