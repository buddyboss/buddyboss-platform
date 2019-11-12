<?php
/**
 * BuddyBoss - Groups Send Invites
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

?>
<div id="group-invites-container">
	<?php
	if ( ! bp_is_group_creation_step( 'group-invites' ) ) {
		bp_get_template_part( 'groups/single/parts/invite-subnav' );
	}
	?>
	<div class="bb-groups-invites-left">
		<select class="group-invites-select-members-dropdown" name="group-members">
			<option value="members"><?php _e( 'All Members', 'buddyboss' ); ?></option>
			<option value="friends"><?php _e( 'My Connections', 'buddyboss' ); ?></option>
		</select>
		<div id="bp-invites-dropdown-options-loader" class="bp-invites-dropdown-options-loader-hide">
			<div>
				<i class="dashicons dashicons-update animate-spin"></i>
			</div>
		</div>
		<div class="group-invites-search subnav-search clearfix" role="search">
			<div class="bp-search">
				<form action="" method="get" id="group_invites_search_form" class="bp-invites-search-form" data-bp-search="group-invites">
					<label for="group_invites_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
					<input type="search" id="group_invites_search" placeholder="<?php esc_attr_e( 'Search Members', 'buddyboss' ); ?>"/>
					<button type="submit" id="group_invites_search_submit" class="nouveau-search-submit">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Members', 'buddyboss' ); ?></span>
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
			<div class="last">

			</div>
			<span class="total-members-text"></span>
			<ul id="members-list" class="item-list bp-list all-members">

			</ul>
		</div>
	</div>
	<div class="bb-groups-invites-right">
		<form id="send_group_invite_form" class="standard-form" data-select2-id="send_group_invite_form">
			<div class="bb-groups-invites-right-top">
				<h2 class="bb-title"><?php _e( 'New Group Invites', 'buddyboss' ); ?></h2>
				<div class="bp-invites-feedback">
					<div class="bp-feedback">
						<span class="bp-icon" aria-hidden="true"></span>
						<p></p>
					</div>
				</div>
				<select name="group_invites_send_to[]" class="send-to-input select2-hidden-accessible" id="group-invites-send-to-input" placeholder="<?php _e( 'Type the names of one or more people','buddyboss' ); ?>" autocomplete="off" multiple="" style="width: 100%" data-select2-id="group-invites-send-to-input" tabindex="-1" aria-hidden="true"></select>
			</div>
			<div class="bb-groups-invites-right-bottom">
				<div id="bp-group-invite-content">
					<textarea class="bp-faux-placeholder-label" id="send-invites-control" name="group_invite_content" rows="120" cols="150"></textarea>
					<input type="hidden" id="group_invite_content_hidden" name="group_invite_content_hidden" value="">
					<div id="whats-new-toolbar">
						<div id="group-invites-new-submit" class="submit">
							<input type="submit" name="send_group_invite_button" value="Send" id="send_group_invite_button" class="small">
							<input type="submit" name="bp_invites_reset" value="Cancel" id="bp_invites_reset" class="small">
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

