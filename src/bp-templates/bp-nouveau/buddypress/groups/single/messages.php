<div id="group-messages-container">

	<div class="bb-groups-messages-left">

		<select name="group-members">
			<option value="all"><?php _e( 'All Group Members', 'buddyboss' ); ?></option>
			<option value="single"><?php _e( 'Select Members', 'buddyboss' ); ?></option>
		</select>

		<div class="group-messages-search subnav-search clearfix" role="search">
			<div class="bp-search">
				<form action="" method="get" id="group_messages_search_form" class="bp-messages-search-form" data-bp-search="group-messages">
					<label for="group_messages_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
					<input type="search" id="group_messages_search" placeholder="<?php esc_attr_e( 'Search', 'buddyboss' ); ?>"/>
					<button type="submit" id="group_messages_search_submit" class="nouveau-search-submit">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
					</button>
				</form>
			</div>
		</div>

	</div>
	<div class="bb-groups-messages-right">
		<h2 class="bb-title"><?php _e( 'New Group Messages', 'buddyboss' ); ?></h2>
		<div class="bp-group-messages-feedback"></div>
		<form id="send_group_message_form" class="standard-form" data-select2-id="send_group_message_form">
			<select name="group_messages_send_to[]" class="send-to-input select2-hidden-accessible" id="group-messages-send-to-input" placeholder="<?php _e( 'Type the names of one or more people','buddyboss' ); ?>" autocomplete="off" multiple="" style="width: 100%" data-select2-id="group-messages-send-to-input" tabindex="-1" aria-hidden="true">
			</select>
			<div id="bp-group-message-content"></div>
		</form>
	</div>

</div>
