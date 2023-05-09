<?php
/**
 * BP Nouveau messages single header template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-single-header.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bp-messages-single-header">
	<#
	var other_recipients = _.reject(data.recipients.members, function(item) {    return item.is_you;    });
	var current_user = _.find(data.recipients.members, function(item) {    return item.is_you == true;    });

	var include_you = other_recipients.length >= 2;

	if ( other_recipients.length == 0 ) {
		include_you = true;
	}

	var first_four = _.first(other_recipients, 4);

	if ( first_four.length == 0 ) {
		include_you = true;
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

	<header class="single-message-thread-header">
		<div class="thread-avatar {{ threadAvatarClass }}">
			<# if ( data.avatars && data.avatars.length > 1  ) {
			if ( data.avatars.length == 2 ) { #>
			<div class="thread-multiple-avatar">
				<# } #>
				<a <# if ( data.is_group_thread || ( ! data.is_group_thread && ! ( data.avatars[0].is_user_suspended || data.avatars[0].is_user_blocked ) ) ) { #> href="{{{data.avatars[0].link}}}"<# } #>><img class="avatar" src="{{{data.avatars[0].url}}}" alt="{{data.avatars[0].name}}"/></a>
				<# if ( data.avatars[1] ) { #>
				<a <# if ( data.is_group_thread || ( ! data.is_group_thread && ! ( data.avatars[1].is_user_suspended || data.avatars[1].is_user_blocked ) ) ) { #> href="{{{data.avatars[1].link}}}"<# } #>><img class="avatar" src="{{{data.avatars[1].url}}}" alt="{{data.avatars[1].name}}"/></a>
				<# }
				if ( data.avatars.length == 2 ) { #>
			</div>
			<# } #>
			<# } else if ( data.group_avatar && data.group_avatar.length > 1 && data.is_group_thread ) { #>
			<a href="{{data.group_link}}"><img class="avatar" src="{{{data.group_avatar}}}" alt="{{data.group_name}}" /></a>
			<# } else { #>
			<# if ( other_recipients.length > 1 ) { #>
			<span class="recipients-count">{{other_recipients.length}}</span>
			<a href=""><img class="avatar" src="{{{data.sender_avatar}}}" alt="{{data.sender_name}}" /></a>
			<# } else { #>
			<# var recipient = _.first(other_recipients) ? _.first(other_recipients) : current_user; #>
			<# if ( typeof( recipient ) != "undefined" && recipient !== null && recipient.avatar.length > 1 && recipient.user_name.length > 1 ) { #>
			<a <# if ( ! ( recipient.is_user_suspended || recipient.is_user_blocked ) ) { #>href="{{{recipient.user_link}}}"<# } #>>
				<img class="avatar" src="{{{recipient.avatar}}}" alt="{{recipient.user_name}}" />
				<# if ( typeof( recipient.user_presence ) != "undefined" && recipient.user_presence !== null && recipient.user_presence.length > 1 ) { #>
					{{{recipient.user_presence}}}
				<# } #>
				<# if ( 0 === recipient.is_deleted ) {
					if ( recipient.is_user_blocked ) { #>
						<i class="user-status-icon bb-icon-f bb-icon-cancel"></i>
				<# } else if ( recipient.is_user_blocked_by || false === data.can_user_send_message_in_thread ) { #>
						<i class="user-status-icon bb-icon-f bb-icon-lock"></i>
				<# } } #>
			</a>
			<# } #>
			<# } #>
			<# } #>
		</div>
		<a href="#" class="bp-back-to-thread-list"><span class="bb-icon-f bb-icon-arrow-left"></span></a> <# if ( undefined !== other_recipients ) { #>
		<dl class="thread-participants">
			<dt>
				<# if ( data.group_name.length > 1 && data.is_group_thread ) { #>
					<span class="participants-name">
						<# if ( data.is_deleted ) { #>
							{{data.group_name}}
						<# } else { #>
							<a href="{{data.group_link}}">{{data.group_name}}</a>
						<# } #>
					</span>
				<# } else { #>

				<# if ( data.toOthers ) { #>
				<a href="#message-members-list" id="view_more_members" class="view_more_members view_more_members_cls"
					data-thread-id="{{data.id}}"
					data-tp="{{data.recipients.total_pages}}"
					data-tc="{{data.recipients.count}}"
					data-pp="{{data.recipients.per_page}}"
					data-cp="1"
					data-action="bp_view_more">
				<# } #>

					<# for ( i in first_four ) { #>
						<span class="participants-name">
							<# if ( other_recipients[i].is_deleted ) { #>{{other_recipients[i].user_name}}<#
							} else if ( other_recipients[i].user_link && ( ! data.toOthers || data.toOthers == '' ) ) { #><a href="{{other_recipients[i].user_link}}">{{other_recipients[i].user_name}}</a><#
							} else { #>{{ other_recipients[i].user_name }}<# }
							if ( i != first_four.length - 1  || ( i == first_four.length -1 && data.toOthers ) ) { #><?php esc_html_e( ',', 'buddyboss' ); ?><# } #>
						</span>
					<# } #>

					<# if ( data.toOthers ) { #>
						<span class="num-name">{{data.toOthers}}</span>
					<# } #>

					<# if ( data.toOthers ) { #>
					</a>
					<# } #>

				<# } #>
			</dt>

			<#
			if ( 1 < data.group_name.length && data.is_group_thread ) {
				if ( 1 < data.group_joined_date.length ) {
				#>
				<dd>
					<span class="thread-date"><?php esc_html_e( 'Joined', 'buddyboss' ); ?> {{data.group_joined_date}}</span>
				</dd>
				<#
				}
			} else { #>
				<dd>
					<span class="thread-date"><?php esc_html_e( 'Started', 'buddyboss' ); ?> {{data.started_date}}</span>
				</dd>
			<# } #>
		</dl>
		<# } #>
		<div class="actions">
			<?php
			if ( bp_current_user_can( 'bp_moderate' ) ) {
				?>
				<div class="message_actions">
					<a href="#" class="message_action__anchor"> <i class="bb-icon-f bb-icon-ellipsis-h"></i> </a>
					<div class="message_action__list" data-bp-thread-id="{{ data.id }}">
						<ul>
							<li class="unread">
								<a data-bp-action="unread" href="#" data-mark-read-text="<?php esc_html_e( 'Mark as read', 'buddyboss' ); ?>"  data-mark-unread-text="<?php esc_html_e( 'Mark as unread', 'buddyboss' ); ?>"><?php esc_html_e( 'Mark as unread', 'buddyboss' ); ?></a>
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
							<# }

							if ( data.is_group_thread || other_recipients.length > 2 ) { #>
							<li class="view_members">
								<a href="#message-members-list" id="view_more_members" class="view_more_members"
								   data-thread-id="{{data.id}}"
								   data-tp="{{data.recipients.total_pages}}"
								   data-tc="{{data.recipients.count}}"
								   data-pp="{{data.recipients.per_page}}"
								   data-cp="1"
								   data-action="bp_view_more"><?php esc_html_e( 'View members', 'buddyboss' ); ?></a>
							</li>
							<# } #>
							<?php if ( bp_is_active( 'moderation' ) && bp_is_moderation_member_blocking_enable() ) { ?>
								<# if ( data.recipients.count > 1 ) { #>
									<li class="report_thread">
										<a id="mass-block-member" href="#mass-user-block-list" class="mass-block-member" data-thread-id="{{data.id}}" data-cp="1" data-text="<?php esc_html_e( 'Block a Member', 'buddyboss' ); ?>"><?php esc_html_e( 'Block a member', 'buddyboss' ); ?></a>
									</li>
								<# } else if ( other_recipients.length == 1 && other_recipients[0].is_user_blocked ) { #>
									<li class="reported_thread">
										<a href="#"><?php esc_html_e( 'Blocked', 'buddyboss' ); ?></a>
									</li>
								<# } else if ( other_recipients.length == 1 && true == other_recipients[0].can_be_blocked && 1 !== other_recipients[0].is_deleted ) { #>
									<li class="report_thread">
										<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>-{{other_recipients[0].id}}" href="#block-member" class="block-member" data-bp-content-id="{{other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>"><?php esc_html_e( 'Block member', 'buddyboss' ); ?></a>
									</li>
								<# } #>
							<?php } ?>

							<?php if ( bp_is_active( 'moderation' ) && bb_is_moderation_member_reporting_enable() ) { ?>
								<# if ( data.recipients.count > 1 ) { #>
									<li class="report_member_thread">
										<a id="mass-report-member" href="#mass-user-block-list" class="mass-report-member" data-thread-id="{{data.id}}" data-cp="1"><?php esc_html_e( 'Report a member', 'buddyboss' ); ?></a>
									</li>
								<# } else if ( other_recipients.length == 1 && other_recipients[0].is_user_reported && true !== other_recipients[0].is_user_suspended ) { #>
									<li class="report_member_thread">
										<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>-{{other_recipients[0].id}}" href="#reported-content" class="reported-content" reported_type="{{other_recipients[0].reported_type}}" item_id="{{other_recipients[0].id}}" item_type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>"><?php esc_html_e( 'Report member', 'buddyboss' ); ?></a>
									</li>
								<# } else if ( other_recipients.length == 1 && true == other_recipients[0].can_be_report && 1 !== other_recipients[0].is_deleted && true !== other_recipients[0].is_user_suspended ) { #>
									<li class="report_member_thread">
										<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>-{{other_recipients[0].id}}" href="#content-report" class="report-content" data-bp-content-id="{{other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>" reported_type="{{other_recipients[0].reported_type}}"><?php esc_html_e( 'Report member', 'buddyboss' ); ?></a>
									</li>
								<# } #>
							<?php } ?>

							<li class="delete_messages">
								<a data-bp-action="delete" href="#"><?php esc_html_e( 'Delete your messages', 'buddyboss' ); ?></a>
							</li>
							<li class="delete_thread">
								<a data-bp-action="delete_thread" href="#"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></a>
							</li>
						</ul>
					</div>
				</div>
				<?php
			} else {

				$old_user = false;
				if ( class_exists( 'BP_Core_Members_Switching' ) ) {
					$old_user = BP_Core_Members_Switching::get_old_user();
				}
				?>
				<div class="message_actions">
					<a href="#" class="message_action__anchor"> <i class="bb-icon-f bb-icon-ellipsis-h"></i> </a>
					<div class="message_action__list" data-bp-thread-id="{{ data.id }}">
						<ul>
							<li class="unread">
								<a data-bp-action="unread" href="#" data-mark-read-text="<?php esc_html_e( 'Mark as read', 'buddyboss' ); ?>"  data-mark-unread-text="<?php esc_html_e( 'Mark as unread', 'buddyboss' ); ?>">
									<?php esc_html_e( 'Mark as unread', 'buddyboss' ); ?>
								</a>
							</li>
							<# if ( data.is_thread_archived ) { #>
								<li class="unhide_thread">
									<a data-bp-action="unhide_thread" href="#">
										<?php esc_html_e( 'Unarchive', 'buddyboss' ); ?>
									</a>
								</li>
							<# } else { #>
								<li class="hide_thread">
									<a data-bp-action="hide_thread" href="#">
										<?php
										esc_html_e(
											'Archive',
											'buddyboss'
										);
										?>
									</a>
								</li>
							<# }

							if ( data.is_group_thread || other_recipients.length > 2 ) { #>
							<li class="view_members">
								<a href="#message-members-list" id="view_more_members" class="view_more_members"
								   data-thread-id="{{data.id}}"
								   data-tp="{{data.recipients.total_pages}}"
								   data-tc="{{data.recipients.count}}"
								   data-pp="{{data.recipients.per_page}}"
								   data-cp="1"
								   data-action="bp_view_more"><?php esc_html_e( 'View members', 'buddyboss' ); ?></a>
							</li>
							<# } #>
							<?php if ( bp_is_active( 'moderation' ) && bp_is_moderation_member_blocking_enable() ) { ?>
								<# if ( data.recipients.count > 1 ) { #>
									<li class="report_thread">
										<a id="mass-block-member" href="#mass-user-block-list" class="mass-block-member" data-thread-id="{{data.id}}" data-cp="1" data-text="<?php esc_html_e( 'Block a Member', 'buddyboss' ); ?>"><?php esc_html_e( 'Block a member', 'buddyboss' ); ?></a>
									</li>
								<# } else if ( other_recipients.length == 1 && other_recipients[0].is_user_blocked ) { #>
									<li class="reported_thread">
										<a href="#"><?php esc_html_e( 'Blocked', 'buddyboss' ); ?></a>
									</li>
								<# } else if ( other_recipients.length == 1 && true == other_recipients[0].can_be_blocked && 1 !== other_recipients[0].is_deleted ) { #>
								<li class="report_thread">
									<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>-{{other_recipients[0].id}}" href="#block-member" class="block-member" data-bp-content-id="{{other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>"><?php esc_html_e( 'Block member', 'buddyboss' ); ?></a>
								</li>
								<# } #>
							<?php } ?>

							<?php if ( bp_is_active( 'moderation' ) && bb_is_moderation_member_reporting_enable() ) { ?>
								<# if ( data.recipients.count > 1 ) { #>
								<li class="report_member_thread">
									<a id="mass-report-member" href="#mass-user-block-list" class="mass-report-member" data-thread-id="{{data.id}}" data-cp="1"><?php esc_html_e( 'Report a member', 'buddyboss' ); ?></a>
								</li>
								<# } else if ( other_recipients.length == 1 && other_recipients[0].is_user_reported && true !== other_recipients[0].is_user_suspended ) { #>
								<li class="report_member_thread">
									<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>-{{other_recipients[0].id}}" href="#reported-content" class="reported-content" reported_type="{{other_recipients[0].reported_type}}" item_id="{{other_recipients[0].id}}" item_type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>"><?php esc_html_e( 'Report member', 'buddyboss' ); ?></a>
								</li>
								<# } else if ( other_recipients.length == 1 && true == other_recipients[0].can_be_report && 1 !== other_recipients[0].is_deleted && true !== other_recipients[0].is_user_suspended ) { #>
								<li class="report_member_thread">
									<a id="report-content-<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>-{{other_recipients[0].id}}" href="#content-report" class="report-content" data-bp-content-id="{{other_recipients[0].id}}" data-bp-content-type="<?php echo esc_attr( BP_Moderation_Members::$moderation_type_report ); ?>" data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>" reported_type="{{other_recipients[0].reported_type}}"><?php esc_html_e( 'Report member', 'buddyboss' ); ?></a>
								</li>
								<# } #>
							<?php } ?>

							<li class="delete_messages" data-bp-action="delete">
								<a data-bp-action="delete" href="#"><?php esc_html_e( 'Delete your messages', 'buddyboss' ); ?></a>
							</li>
							<?php
							if ( ! empty( $old_user ) ) {
								?>
								<li class="delete_thread">
									<a data-bp-action="delete_thread" href="#"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></a>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php if ( bp_is_active( 'moderation' ) && ( bp_is_moderation_member_blocking_enable() || bb_is_moderation_member_reporting_enable() ) ) { ?>
			<div id="mass-user-block-list" class="mass-user-block-list moderation-popup mfp-hide">
				<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
					<div class="modal-wrapper">
						<div class="modal-container">
							<header class="bb-model-header">
								<h4><?php esc_html_e( 'Block a Member', 'buddyboss' ); ?></h4>
								<button title="<?php esc_attr_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close"></button>
							</header>
							<div id="moderated_user_list"></div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		<div id="message-members-list" class="message-members-list member-popup mfp-hide">
			<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
				<div class="modal-wrapper">
					<div class="modal-container">
						<header class="bb-model-header">
							<h4><?php esc_html_e( 'Members', 'buddyboss' ); ?></h4>
							<button title="<?php esc_attr_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close"></button>
						</header>
						<div id="members_list"></div>
					</div>
				</div>
			</div>
		</div>
	</header>
</script>
