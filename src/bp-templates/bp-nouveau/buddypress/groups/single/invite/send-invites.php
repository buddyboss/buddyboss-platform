<?php
/**
 * BuddyBoss - Groups Send Invites
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/invite/send-invites.php.
 *
 * @since   BuddyBoss 1.2.3
 * @version 1.2.3
 */

?>
<?php
if ( ! bp_is_group_creation_step( 'group-invites' ) ) {
	bp_get_template_part( 'groups/single/parts/invite-subnav' );
}
?>
<div id="group-invites-container">
	<div class="bb-groups-invites-left">
		<div class="bb-groups-invites-left-inner">
			<div class="bb-panel-head">
				<div class="bb-panel-subhead">
					<h4 class="total-members-text"><?php _e( 'Members', 'buddyboss' ); ?></h4>
					<?php if ( bp_is_active( 'friends' ) ) { ?>
					<div id="bp-invites-dropdown-options-loader" class="bp-invites-dropdown-options-loader-hide">
						<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
					</div>
					<div class="bp-group-message-wrap">
						<input id="bp-group-send-invite-switch-checkbox" class="bp-group-send-invite-switch-checkbox bb-input-switch bs-styled-checkbox" type="checkbox" />
						<label for="bp-group-send-invite-switch-checkbox" class="bp-group-invite-label"><span class="select-members-text"><?php _e( 'My Connections', 'buddyboss' ); ?></span></label>
					</div>
					<?php } ?>
				</div>
			</div>

			<div class="group-invites-search subnav-search clearfix" role="search">
				<div class="bp-search">
					<form action="" method="get" id="group_invites_search_form" class="bp-invites-search-form search-form-has-reset" data-bp-search="group-invites">
						<label for="group_invites_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
						<input type="search" id="group_invites_search" placeholder="<?php esc_attr_e( 'Search Members', 'buddyboss' ); ?>"/>
						<button type="submit" id="group_invites_search_submit" class="nouveau-search-submit search-form_submit">
							<span class="bb-icon-l bb-icon-search" aria-hidden="true"></span>
							<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Members', 'buddyboss' ); ?></span>
						</button>
						<button type="reset" class="search-form_reset">
							<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
							<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
						</button>
					</form>
				</div>
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
					<a class="bb-close-invites-members button" href="#"><?php _e( 'Done', 'buddyboss' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<div class="bb-groups-invites-right">
		<form id="send_group_invite_form" class="standard-form" data-select2-id="send_group_invite_form">
			<div class="bb-groups-invites-right-top">
				<div class="bb-title-wrap">
					<h2 class="bb-title"><?php _e( 'Send Invites', 'buddyboss' ); ?></h2>
					<div class="bb-more-invites-wrap"><a class="bb-add-invites" href="#"><span class="bb-icon-rl bb-icon-plus"></span><?php _e( 'Select Members', 'buddyboss' ); ?></a></div>
				</div>
				<div class="bp-invites-feedback">
					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php esc_html_e( 'Select members to invite by clicking the + button next to each member.', 'buddyboss' ); ?></p>
					</div>
				</div>
				<select name="group_invites_send_to[]" class="send-to-input select2-hidden-accessible" id="group-invites-send-to-input" placeholder="<?php _e( 'Type the names of one or more people','buddyboss' ); ?>" autocomplete="off" multiple="" style="width: 100%" data-select2-id="group-invites-send-to-input" tabindex="-1" aria-hidden="true"></select>
			</div>
			<div class="bb-groups-invites-right-bottom">
				<div id="bp-group-invite-content">
					<textarea class="bp-faux-placeholder-label" id="send-invites-control" name="group_invite_content" rows="120" cols="150" placeholder="<?php _e( 'Customize the message of your invite.','buddyboss' ); ?>"></textarea>
					<input type="hidden" id="group_invite_content_hidden" name="group_invite_content_hidden" value="">
					<div id="whats-new-toolbar">
						<div id="group-invites-new-submit" class="submit">
							<div id="bp-invites-submit-loader" class="bp-invites-submit-loader-hide">
								<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
							</div>
							<input type="submit" name="send_group_invite_button" value="<?php esc_attr_e( 'Send', 'buddyboss' ); ?>" id="send_group_invite_button" class="small">
							<input type="submit" name="bp_invites_reset" value="<?php esc_attr_e( 'Cancel', 'buddyboss' ); ?>" id="bp_invites_reset" class="small">
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

