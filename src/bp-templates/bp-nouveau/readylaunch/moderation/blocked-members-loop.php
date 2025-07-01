<?php
/**
 * ReadyLaunch - Blocked Members Loop template.
 *
 * This template is used to loop through the blocked members.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if ( empty( $_POST['page'] ) || 1 === (int) bb_filter_input_string( INPUT_POST, 'page' ) ) {
	?>
	<div class="bp-feedback bp-messages error moderation_notice is_hidden bb-rl-notice bb-rl-notice--alt bb-rl-notice--error"><span class="bp-icon bb-icons" aria-hidden="true"></span><span><?php esc_html_e( 'Sorry, you were not able to report this member.', 'buddyboss' ); ?></span></div>
	<table id="moderation-list" class="bp-tables-user">
		<thead>
			<th class="title">
				<?php
				esc_html_e( 'Blocked Members', 'buddyboss' );
				?>
			</th>
			<th class="title"></th>
			<th class="title"></th>
		</thead>
	<tbody>
	<?php
}

while ( bp_moderation() ) :
	bp_the_moderation();
	bp_get_template_part( 'moderation/moderation-blocked-members-entry' );
endwhile;

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if ( empty( $_POST['page'] ) || 1 === (int) bb_filter_input_string( INPUT_POST, 'page' ) ) :
	?>
	</tbody>
	</table>
	<?php
endif;

if ( bp_moderation_has_more_items() ) {
	?>
	<div class="pager">
		<div class="md-more-container load-more text-center">
			<a class="button outline" href="<?php bp_moderation_load_more_link(); ?>">
				<?php
				esc_html_e( 'Load More', 'buddyboss' );
				?>
			</a>
		</div>
	</div>
	<?php
}
