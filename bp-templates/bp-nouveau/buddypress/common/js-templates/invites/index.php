<?php
/**
 * BP Nouveau Invites main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with invites.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
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

