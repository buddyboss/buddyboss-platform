<?php
/**
 * ReadyLaunch - Groups Pending Invites template.
 *
 * This template displays pending group invitations with search functionality
 * and member management interface for reviewing sent invites.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="bb-rl-group-invites-dashboard bb-rl-group-dashboard-panel bb-rl-group-invites-dashboard--pending-invites">
	<div class="bb-rl-group-invites-header">
		<h2 class="bb-rl-entry-title"><?php esc_html_e( 'Group invites', 'buddyboss' ); ?></h2>
	</div>
	<?php
	bp_get_template_part( 'groups/single/parts/invite-subnav' );
	?>
	<div id="group-invites-container" class="bb-rl-group-invites-container bb-rl-group-invites-container--pending-invites">
		<div class="group-invites-column">
			<div class="subnav-filters group-subnav-filters bp-invites-filters">
				<div>
					<div class="group-invites-search subnav-search clearfix" role="search">
						<div class="bp-search">
							<form action="" method="get" id="group_invites_search_form" class="bp-invites-search-form search-form-has-reset" data-bp-search="group-invites">
								<label for="group_invites_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( esc_html__( 'Search members', 'buddyboss' ), false ); ?></label>
								<input type="search" id="group_invites_search" placeholder="<?php esc_attr_e( 'Search members', 'buddyboss' ); ?>"/>
								<button type="submit" id="group_invites_search_submit" class="nouveau-search-submit search-form_submit">
									<span class="bb-icons-rl-magnifying-glass" aria-hidden="true"></span>
									<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search members', 'buddyboss' ); ?></span>
								</button>
								<button type="reset" class="search-form_reset">
									<span class="bb-icons-rl-x" aria-hidden="true"></span>
									<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
								</button>
							</form>
						</div>
					</div>
					<div class="last"></div>
				</div>
			</div>

			<div class="bp-invites-feedback" style="display: none;">
				<div class="bp-invites-feedback">
					<div class="bp-feedback loading">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php esc_html_e( 'Loading Members. Please Wait.', 'buddyboss' ); ?></p>
					</div>
				</div>
			</div>

			<div id="bp-pending-invites-loader" class="bp-pending-invites-loader-hide bb-rl-bp-pending-invites-loader">
				<i class="bb-icons-rl-spinner animate-spin"></i>
			</div>

			<div class="members bp-invites-content">
				<ul id="members-list" class="item-list bp-list"></ul>
			</div>

		</div>
	</div>
</div>

