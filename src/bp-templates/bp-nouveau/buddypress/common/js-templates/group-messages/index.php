<?php
/**
 * BP Nouveau messages main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with messages.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Group Messages', 'buddyboss' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Group Messages', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<div id="group-messages-container">

	<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group Messages menu', 'buddyboss' ); ?>"></nav>

	<div class="group-messages-column">
		<div class="subnav-filters group-subnav-filters bp-messages-filters"></div>
		<div class="bp-messages-feedback"></div>
		<div class="members bp-messages-content"></div>
	</div>

</div>

<?php
/**
 * Split each js template to its own file. Easier for child theme to
 * overwrite individual parts.
 *
 * @version Buddyboss 1.0.0
 */
$template_parts = apply_filters( 'bp_messages_js_template_parts', [
	'parts/bp-group-messages-feedback',
	'parts/bp-messages-filters',
	'parts/bp-messages-form',
	'parts/bp-messages-nav',
	'parts/bp-messages-paginate',
	'parts/bp-messages-selection',
	'parts/bp-messages-users'
] );

foreach ( $template_parts as $template_part ) {
	bp_get_template_part( 'common/js-templates/group-messages/' . $template_part );
}
