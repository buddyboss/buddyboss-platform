<?php
/**
 * BP Nouveau messages thread template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-thread.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bp-messages-thread">
	<#
	var other_recipients = _.reject(data.recipients, function(item) {
		return item.is_you;
	});

	var current_user = _.find(data.recipients, function(item) {
		return item.is_you;
	});

	var include_you = other_recipients.length >= 2;
	var first_four = _.first(other_recipients, 4);

	if ( first_four.length == 0 ) {
		include_you = true;
	}


	var action_other_recipients = _.reject(data.action_recipients.members, function(item) {
		return item.is_you;
	});

	var action_current_user = _.find(data.action_recipients.members, function(item) {
		return item.is_you;
	});

	var action_include_you = action_other_recipients.length >= 2;
	var action_first_three = _.first(action_other_recipients, 3);

	if ( action_first_three.length == 0 ) {
		action_include_you = true;
	}

	var read_unread_action = 'unread';
	if ( data.unread ) {
		read_unread_action = 'read';
	}

	var senderName = data.sender_name;
	if ( ! data.is_group_thread && data.recipients && 1 === parseInt( data.recipientsCount ) ) {
		senderName = '';
	}

	var threadAvatarClass = '';
	if ( 1 === data.avatars.length ) {
		if ( ! data.is_group_thread ) {
			if ( data.avatars[0].is_user_suspended || data.avatars[0].is_user_blocked ) {
				threadAvatarClass += ' bp-suspended-avatar';
			}
			if ( data.avatars[0].is_user_suspended ) {
				threadAvatarClass += ' bp-user-suspended';
			}
			if ( data.avatars[0].is_user_blocked ) {
				threadAvatarClass += ' bp-user-blocked';
			}
			if ( data.avatars[0].is_user_blocked_by ) {
				threadAvatarClass += ' bp-user-blocked-by';
			}
		}
	}
	#>

	<div class="bb_more_options message-thread-options">
		<a href="#" class="bb_more_options_action bp-tooltip">
			<i class="bb-icon-menu-dots-h"></i>
		</a>
		<ul class="bb_more_options_list message_action__list" data-bp-thread-id="{{ data.id }}">
			<li class="{{ read_unread_action }}">
				<a data-bp-action="{{ read_unread_action }}" href="#" data-mark-read-text="<?php esc_html_e( 'Mark as read', 'buddyboss' ); ?>"  data-mark-unread-text="<?php esc_html_e( 'Mark as unread', 'buddyboss' ); ?>">
					<# if ( data.unread ) { #>
						<?php esc_html_e( 'Mark as read', 'buddyboss' ); ?>
					<# } else { #>
						<?php esc_html_e( 'Mark as unread', 'buddyboss' ); ?>
					<# } #>
				</a>
			</li>

			<# if ( data.is_thread_archived ) { #>
			<li class="unhide_thread">
				<a data-bp-action="unhide_thread" href="#"><?php esc_html_e( 'Unarchive', 'buddyboss' ); ?>
				</a>
			</li>
			<# } else { #>
			<li class="hide_thread">
				<a data-bp-action="hide_thread" href="#"><?php esc_html_e( 'Archive', 'buddyboss' ); ?>
				</a>
			</li>
			<# } #>

			<# if ( data.is_group_thread || action_other_recipients.length > 2 ) { #>
			<li class="view_members">
				<a href="#message-members-list" id="view_more_members" class="view_more_members" data-thread-id="{{data.id}}" data-tp="{{data.action_recipients.total_pages}}" data-tc="{{data.action_recipients.count}}" data-pp="{{data.action_recipients.per_page}}" data-cp="1" data-action="bp_view_more"><?php esc_html_e( 'View members', 'buddyboss' ); ?></a>
			</li>
			<# } #>

			<?php if ( bp_is_active( 'moderation' ) && bp_is_moderation_member_blocking_enable() ) { ?>
				<# if ( data.action_recipients.count > 1 ) { #>
					<li class="report_thread">
						<a id="mass-block-member" href="#mass-user-block-list" class="mass-block-member" data-thread-id="{{data.id}}" data-cp="1" data-text="<?php esc_html_e( 'Block a Member', 'buddyboss' ); ?>"><?php esc_html_e( 'Block a member', 'buddyboss' ); ?></a>
					</li>
				<# } else if ( action_other_recipients.length == 1 && action_other_recipients[0].is_user_blocked ) { #>
					<li class="reported_thread">
						<a href="#"><?php esc_html_e( 'Blocked', 'buddyboss' ); ?></a>
					</li>
				<# } else if ( action_other_recipients.length == 1 && true == action_other_recipients[0].can_be_blocked && 1 !== action_other_recipients[0].is_deleted ) { #>
					<li class="report_thread">
						<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>-{{other_recipients[0].id}}" href="#block-member" class="block-member" data-bp-content-id="{{action_other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>"><?php esc_html_e( 'Block member', 'buddyboss' ); ?></a>
					</li>
				<# } #>
			<?php } ?>

			<?php if ( bp_is_active( 'moderation' ) && bb_is_moderation_member_reporting_enable() ) { ?>
				<# if (  data.action_recipients.count > 1 ) { #>
					<li class="report_member_thread">
						<a id="mass-report-member" href="#mass-user-block-list" class="mass-report-member" data-thread-id="{{data.id}}" data-cp="1"><?php esc_html_e( 'Report a member', 'buddyboss' ); ?></a>
					</li>
				<# } else if ( action_other_recipients.length == 1 && action_other_recipients[0].is_user_reported && true !== other_recipients[0].is_user_suspended ) { #>
					<li class="report_member_thread">
						<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>-{{action_other_recipients[0].id}}" href="#reported-content" class="reported-content" reported_type="{{action_other_recipients[0].reported_type}}" item_id="{{action_other_recipients[0].id}}" item_type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>"><?php esc_html_e( 'Report member', 'buddyboss' ); ?></a>
					</li>
				<# } else if ( action_other_recipients.length == 1 && true == action_other_recipients[0].can_be_report && 1 !== action_other_recipients[0].is_deleted && true !== other_recipients[0].is_user_suspended ) { #>
					<li class="report_member_thread">
						<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>-{{action_other_recipients[0].id}}" href="#content-report" class="report-content" data-bp-content-id="{{action_other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>" reported_type="{{action_other_recipients[0].reported_type}}"><?php esc_html_e( 'Report member', 'buddyboss' ); ?></a>
					</li>
				<# } #>
			<?php } ?>


			<li class="delete_messages" data-bp-action="delete">
				<a data-bp-action="delete" href="#"><?php esc_html_e( 'Delete your messages', 'buddyboss' ); ?></a>
			</li>
			<?php
			$can_delete_conversation = false;
			if ( bp_current_user_can( 'bp_moderate' ) ) {
				$can_delete_conversation = true;
			} elseif ( class_exists( 'BP_Core_Members_Switching' ) ) {
				$old_user = BP_Core_Members_Switching::get_old_user();
				if ( ! empty( $old_user ) ) {
					$can_delete_conversation = true;
				}
			}

			if ( $can_delete_conversation ) {
				?>
				<li class="delete_thread">
					<a data-bp-action="delete_thread" href="#"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></a>
				</li>
			<?php } ?>
		</ul>
	</div>

	<a class="bp-message-link bp-message-link-{{data.id}}" href="../{{data.id}}/" data-thread-id="{{data.id}}">
		<div class="thread-avatar {{ threadAvatarClass }}">
			<# if ( data.avatars && data.avatars.length > 1  ) {
				if ( data.avatars.length == 2 ) { #>
					<div class="thread-multiple-avatar">
				<# } #>
					<img class="avatar" src="{{{data.avatars[0].url}}}" alt="{{data.avatars[0].name}}"/>
					<# if ( data.avatars[1] ) { #>
						<img class="avatar" src="{{{data.avatars[1].url}}}" alt="{{data.avatars[1].name}}"/>
					<# }
				if ( data.avatars.length == 2 ) { #>
					</div>
				<# } #>
			<# } else if ( data.group_avatar && data.group_avatar.length > 1 && data.is_group_thread ) { #>
				<img class="avatar" src="{{{data.group_avatar}}}" alt="{{data.group_name}}" />
			<# } else { #>
				<# if ( other_recipients.length > 1 ) { #>
					<span class="recipients-count">{{other_recipients.length}}</span>
					<img class="avatar" src="{{{data.sender_avatar}}}" alt="{{data.sender_name}}" />
				<# } else { #>
					<# var recipient = _.first(other_recipients)? _.first(other_recipients) : current_user; #>
					<# if ( typeof( recipient ) != "undefined" && recipient !== null && recipient.avatar.length > 1 && recipient.user_name.length > 1 ) { #>
						<img class="avatar" src="{{{recipient.avatar}}}" alt="{{recipient.user_name}}" />
							<# if ( typeof( recipient.user_presence ) != "undefined" && recipient.user_presence !== null && recipient.user_presence.length > 1 ) { #>
								{{{recipient.user_presence}}}
							<# } #>
							<# if ( 0 === recipient.is_deleted ) {
								if ( recipient.is_user_blocked ) {
									#>
									  <i class="user-status-icon bb-icon-f bb-icon-cancel"></i>
									<#
								} else if ( recipient.is_user_blocked_by || false === data.can_user_send_message_in_thread ) {
									#>
									  <i class="user-status-icon bb-icon-f bb-icon-lock"></i>
									<#
								}
							} #>
					<# } #>
				<# } #>
			<# } #>
		</div>

		<div class="thread-content {{ ( data.is_user_suspended || data.is_user_blocked ) && ! data.is_group_thread ? 'bp-suspended-content' : '' }}" data-thread-id="{{data.id}}">
			<div class="thread-to">

				<# if ( data.group_name && data.group_name.length && data.is_group_thread ) { #>
					<span class="user-name">{{data.group_name}}</span>
				<# } else { #>
					<# for ( i in first_four ) { #>
						<span class="user-name">{{other_recipients[i].user_name}}<# if ( i != first_four.length - 1  || ( i == first_four.length -1 && data.toOthers ) ) { #><?php esc_html_e( ',', 'buddyboss' ); ?><# } #></span>
					<# } #>

					<# if ( data.toOthers ) { #>
						<span class="num-name">{{data.toOthers}}</span>
					<# } #>

				<# } #>
			</div>

			<div class="thread-subject">
				<div class="typing-indicator bp-hide"></div>
				<# if ( ! data.is_user_suspended && ! data.is_user_blocked ) { #>
					<span class="thread-excerpt">
						<span class="last-message-sender">
							<#
							var displayName = false;
							var checkedContent = data.content.replace(/<\/?[^>]+(>|$)/g, '').replace(/^\s+|\s+$/gm,'');
							if ( data.sender_is_you && '' !== checkedContent ) {
								displayName = true;
								#>
								<?php esc_html_e( 'You', 'buddyboss' ); ?>:
							<# } else if ( data.sender_is_you && '' === checkedContent ) {
								displayName = true;
								#>
								<?php esc_html_e( 'You', 'buddyboss' ); ?>
							<# } else { #>
								<# if ( senderName && '' !== senderName && '' !== checkedContent ) {
									displayName = true;
									#>
									{{ senderName }}:
								<# } else if ( senderName && '' !== senderName && '' === checkedContent ) {
									displayName = true;
									#>
									{{ senderName }}
								<# } #>
							<# } #>
						</span>
						<#
						var dataExcerpt = data.excerpt;
						if ( dataExcerpt && true === displayName && 'undefined' !== typeof data.has_media && data.has_media ) {
							dataExcerpt = dataExcerpt.toLowerCase();
						}
						#>
						{{{dataExcerpt}}}
					</span>
				<# } #>
				<div class="thread-date">
					<time datetime="{{data.date.toISOString()}}">{{data.display_date}}</time>
				</div>
			</div>

		</div>
	</a>

</script>
