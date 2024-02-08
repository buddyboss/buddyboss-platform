<?php
/**
 * Activity Post form JS Templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/form.php.
 *
 * @since   1.0.0
 * @version 1.8.6
 */

/**
 * Split each js template to its own file. Easier for child theme to
 * overwrite individual parts.
 *
 * @version Buddyboss 1.0.0
 */
$template_parts = apply_filters(
	'bp_messages_js_template_parts',
	array(
		'parts/bp-activity-attached-gif',
		'parts/bp-activity-link-preview',
		'parts/bp-activity-media',
		'parts/bp-activity-document',
		'parts/bp-activity-video',
		'parts/bp-activity-post-case-avatar',
		'parts/bp-activity-post-case-heading',
		'parts/bp-activity-post-case-privacy',
		'parts/bp-activity-post-form-buttons',
		'parts/bp-activity-post-form-feedback',
		'parts/bp-activity-post-form-options',
		'parts/bp-activity-header',
		'parts/bp-activity-target-item',
		'parts/bp-gif-media-search-dropdown',
		'parts/bp-gif-result-item',
		'parts/bp-whats-new-toolbar',
		'parts/bp-editor-toolbar',
		'parts/bp-activity-post-form-privacy',
		'parts/bp-activity-edit-postin',
		'parts/bp-activity-post-privacy-stage-footer',
	)
);

foreach ( $template_parts as $template_part ) {
	bp_get_template_part( 'common/js-templates/activity/' . $template_part );
}
