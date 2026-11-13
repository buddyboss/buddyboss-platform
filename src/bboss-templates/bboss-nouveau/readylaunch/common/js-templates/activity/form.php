<?php
/**
 * Activity Post form JS Templates for ReadyLaunch.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Split each js template to its own file. Easier for child theme to
 * overwrite individual parts.
 *
 * @version BuddyBoss 2.9.00
 */
$template_parts = apply_filters(
	'bp_messages_js_template_parts',
	array(
		'parts/bboss-activity-attached-gif',
		'parts/bboss-activity-link-preview',
		'parts/bboss-activity-media',
		'parts/bp-activity-document',
		'parts/bp-activity-video',
		'parts/bboss-activity-post-case-avatar',
		'parts/bboss-activity-post-case-heading',
		'parts/bboss-activity-post-case-privacy',
		'parts/bboss-activity-post-form-buttons',
		'parts/bboss-activity-post-form-feedback',
		'parts/bboss-activity-post-form-options',
		'parts/bboss-activity-header',
		'parts/bboss-activity-target-item',
		'parts/bboss-gif-media-search-dropdown',
		'parts/bboss-gif-result-item',
		'parts/bboss-whats-new-toolbar',
		'parts/bboss-editor-toolbar',
		'parts/bboss-activity-post-form-privacy',
		'parts/bboss-activity-edit-postin',
		'parts/bboss-activity-edit-postin-header',
		'parts/bboss-activity-post-privacy-stage-footer',
		'parts/bboss-activity-post-form-topic-selector',
		'parts/bboss-activity-post-form-title',
	)
);

foreach ( $template_parts as $template_part ) {
	bboss_get_template_part( 'common/js-templates/activity/' . $template_part );
}
