<?php
/**
 * ReadyLaunch - Groups Create template.
 *
 * This template handles the group creation process with step-by-step
 * navigation and form management for creating new groups.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_groups_create_hook( 'before', 'page' ); ?>
<div class="bb-rl-content-wrapper">
	<div class="bb-rl-create-group">
		<h2 class="bp-subhead"><?php esc_html_e( 'Create a group', 'buddyboss' ); ?></h2>

		<?php bp_nouveau_groups_create_hook( 'before', 'content_template' ); ?>

		<?php
		$current_group_step = bp_get_groups_current_create_step();
		if ( 'group-invites' !== $current_group_step ) {
			?>
			<form action="<?php bp_group_creation_form_action(); ?>" method="post" id="create-group-form" class="standard-form" enctype="multipart/form-data">
		<?php } else { ?>
			<div id="create-group-form" class="standard-form">
			<?php
		}

		bp_nouveau_groups_create_hook( 'before' );
		bp_nouveau_template_notices();
		?>
			<div class="item-body" id="group-create-body">

				<nav class="<?php bp_nouveau_groups_create_steps_classes(); ?>" id="group-create-tabs" role="navigation" aria-label="<?php esc_attr_e( 'Group creation menu', 'buddyboss' ); ?>">
					<ol class="group-create-buttons button-tabs">
						<?php bp_group_creation_tabs(); ?>
					</ol>
				</nav>

				<div class="bb-rl-create-screen-content">
					<?php bp_nouveau_group_creation_screen(); ?>
				</div>

			</div><!-- .item-body -->

		<?php
		bp_nouveau_groups_create_hook( 'after' );

		if ( 'group-invites' !== $current_group_step ) {
			?>
			</form><!-- #create-group-form -->
		<?php } else { ?>
			</div><!-- #create-group-form -->
			<?php
		}
		?>

	</div>
</div>

<?php
bp_nouveau_groups_create_hook( 'after', 'content_template' );
bp_nouveau_groups_create_hook( 'after', 'page' );
