<?php
/**
 * ReadyLaunch - Groups Send Invites template.
 *
 * This template provides the interface for sending group invitations
 * with member selection, custom messages, and invite management.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="bb-rl-group-invites-dashboard bb-rl-group-dashboard-panel">
	<div class="bb-rl-group-invites-header">
		<h2 class="bb-rl-entry-title"><?php esc_html_e( 'Group invites', 'buddyboss' ); ?></h2>
	</div>
	<div id="group-invites-container" class="bb-rl-group-invites-container">
		<div class="bb-groups-invites-left">
			<div class="bb-groups-invites-left-inner">

				<?php
				if ( ! bp_is_group_creation_step( 'group-invites' ) ) {
					bp_get_template_part( 'groups/single/parts/invite-subnav' );
				}
				?>

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
					<?php if ( bp_is_active( 'friends' ) ) { ?>
						<div class="flex items-center bb-rl-members-is-friends">
							<div class="bp-group-message-wrap">
								<input id="bp-group-send-invite-switch-checkbox" class="bp-group-send-invite-switch-checkbox bb-input-switch bs-styled-checkbox" type="checkbox" />
								<label for="bp-group-send-invite-switch-checkbox" class="bp-group-invite-label"><span class="select-members-text"><?php esc_html_e( 'My Connections', 'buddyboss' ); ?></span></label>
							</div>
							<div id="bp-invites-dropdown-options-loader" class="bp-invites-dropdown-options-loader-hide">
								<i class="bb-icons-rl-spinner animate-spin"></i>
							</div>
						</div>
					<?php } ?>
				</div>
				<div class="group-invites-members-listing">
					<div class="bp-invites-feedback">
						<div class="bp-feedback">
							<span class="bp-icon" aria-hidden="true"></span>
							<p></p>
						</div>
					</div>
					<div class="last"></div>
					<span class="total-members-text"></span>
					<ul id="members-list" class="item-list bp-list all-members"></ul>
					<div class="bb-invites-footer">
						<a class="bb-close-invites-members button" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
					</div>
				</div>
			</div>
		</div>
		<div class="bb-groups-invites-right">
			<form id="send_group_invite_form" class="standard-form" data-select2-id="send_group_invite_form">
				<div class="bb-groups-invites-right-top">
					<div class="bb-title-wrap">
						<h2 class="bb-title"><?php esc_html_e( 'Selected Members', 'buddyboss' ); ?></h2>
						<div class="bb-more-invites-wrap"><a class="bb-add-invites" href="#"><span class="bb-icon-rl bb-icon-plus"></span><?php esc_html_e( 'Select Members', 'buddyboss' ); ?></a></div>
					</div>
					<div class="bp-invites-feedback">
						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php esc_html_e( 'Select members to invite by clicking the + button next to each member.', 'buddyboss' ); ?></p>
						</div>
					</div>
					<select name="group_invites_send_to[]" class="send-to-input select2-hidden-accessible" id="group-invites-send-to-input" placeholder="<?php esc_attr_e( 'Type the names of one or more people', 'buddyboss' ); ?>" autocomplete="off" multiple="" style="width: 100%" data-select2-id="group-invites-send-to-input" tabindex="-1" aria-hidden="true"></select>
				</div>
				<div class="bb-groups-invites-right-bottom">
					<div id="bp-group-invite-content">
						<h2 class="bb-title"><?php esc_html_e( 'Message (optional)', 'buddyboss' ); ?></h2>
						<textarea class="bp-faux-placeholder-label" id="send-invites-control" name="group_invite_content" rows="120" cols="150" placeholder="<?php esc_attr_e( 'Write an invitation message...', 'buddyboss' ); ?>"></textarea>
						<input type="hidden" id="group_invite_content_hidden" name="group_invite_content_hidden" value="">
						<div id="whats-new-toolbar">
							<div id="group-invites-new-submit" class="submit">
								<div id="bp-invites-submit-loader" class="bp-invites-submit-loader-hide">
									<i class="bb-icons-rl-spinner animate-spin"></i>
								</div>
								<input type="submit" name="bp_invites_reset" value="<?php esc_attr_e( 'Cancel', 'buddyboss' ); ?>" id="bp_invites_reset" class="small">
								<input type="submit" name="send_group_invite_button" value="<?php esc_attr_e( 'Send Invite', 'buddyboss' ); ?>" id="send_group_invite_button" class="small" disabled>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
	<div class="bb-rl-group-invites-footer">

	</div>
</div>

