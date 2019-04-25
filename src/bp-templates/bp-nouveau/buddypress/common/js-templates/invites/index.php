<?php
/**
 * BP Nouveau Invites main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with invites.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Invite Members', 'buddyboss' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Invite Members', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<div id="group-invites-container">

	<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group invitations menu', 'buddyboss' ); ?>"></nav>

	<div class="group-invites-column">
		<div class="subnav-filters group-subnav-filters bp-invites-filters"></div>
		<div class="bp-invites-feedback"></div>
		<div class="members bp-invites-content"></div>
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
	'parts/bp-group-invites-feedback',
	'parts/bp-invites-filters',
	'parts/bp-invites-form',
	'parts/bp-invites-nav',
	'parts/bp-invites-paginate',
	'parts/bp-invites-selection',
	'parts/bp-invites-users'
] );

foreach ( $template_parts as $template_part ) {
	bp_get_template_part( 'common/js-templates/invites/' . $template_part );
}
