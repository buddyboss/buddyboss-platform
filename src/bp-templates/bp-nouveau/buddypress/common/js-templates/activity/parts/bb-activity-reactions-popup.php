<?php
/**
 * The template for displaying activity reactions in popup modal.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-reactions-popup.php
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

?>
<script type="text/html" id="tmpl-activity-reacted-popup-heading">
	<h4>
		{{ data.popup_heading }}
		<# if ( data.reacted_tabs.length === 0 ) { #>
				{{ ' (' + data.popup_heading_count + ')' }}
		<# } #>
	</h4>
</script>

<script type="text/html" id="tmpl-activity-reacted-popup-tab">
	<#
	// console.log( data );
	if ( data.reaction_mode && data.reaction_mode === 'emotions' && data.reacted_tabs.length > 1 ) { #>
		<ul>
			<#
			var isFirstItem = true;
			jQuery.each( data.reacted_tabs, function( key, reaction ) {
			// console.log( reaction );
			#>
			<li>
				<a href="javascript:void(0);" class="{{ isFirstItem ? 'active' : '' }}" data-tab="activity-state_{{key}}" reaction-id="{{ reaction.id ? reaction.id : '0' }}">
					<# if ( reaction.type === 'all' ) { #>
					{{ reaction.icon_text }}
					<# } else if ( reaction.type === 'bb-icons' ) { #>
					<i class="bb-icon-{{ reaction.icon }}" style="font-weight:200;color:{{ reaction.icon_color }};"></i>
					<span>{{ reaction.total_count }}</span>
					<# } else if ( reaction.icon_path !== '' ) { #>
					<img src="{{ reaction.icon_path }}" class="{{ reaction.type }}" alt="{{ reaction.icon_text }}" />
					<span>{{ reaction.total_count }}</span>
					<# } else { #>
					<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
					<span>{{ reaction.total_count }}</span>
					<# } #>
				</a>
			</li>
			<#
			isFirstItem = false;
			});
			#>
		</ul>
	<# } #>
</script>

<script type="text/html" id="tmpl-activity-reacted-popup-tab-content">
	<#
	isFirstItem = true;
	users = data.reacted_users;
	var data_reaction_id = data.reaction_id ? data.reaction_id : 0;

	if ( data.reacted_tabs.length === 0 ) {
		#>
		<div class="activity-state-popup_tab_item activity-state_ active" data-reaction-id="0" data-paged="1" data-total-pages="0">

		<# if( users.length > 0 ) { #>
			<ul class="activity-state_users">
			<# jQuery.each( users, function( key, user ) { #>
			<li class="activity-state_user">
				<div class="activity-state_user__avatar">
					<a href="{{ user.profile_url }}">
						<img class="avatar" src="{{ user.avatar }}" alt="{{ user.name }}" />
						<div class="activity-state_user__reaction">
							<# if ( user.reaction.type === 'bb-icons' ) { #>
							<i class="bb-icon-{{ user.reaction.icon }}" style="font-weight:200;color:{{ user.reaction.icon_color }};"></i>
							<# } else if ( user.reaction.icon_path !== '' ) { #>
							<img src="{{ user.reaction.icon_path }}" class="{{ user.reaction.type }}" alt="{{ user.reaction.icon_text }}" />
							<# } else { #>
							<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
							<# } #>
						</div>
					</a>
				</div>
				<div class="activity-state_user__name">
					<a href="{{ user.profile_url }}">{{ user.name }}</a>
				</div>
				<# if ( user.member_type && user.member_type.label ) { #>
				<div class="activity-state_user__role" style="color:{{user.member_type.color.text}}; background-color:{{user.member_type.color.background}};">
					{{ user.member_type.label }}
				</div>
				<# } #>
			</li>
			<# }); #>
		</ul>
		<# } else { #>
			<ul class="activity-state_users"></ul>
		<# } #>
	</div>
		<#
	} else {
		jQuery.each( data.reacted_tabs, function( key, reaction ) {
		var current_reaction_id = reaction.id ? reaction.id : 0;
		#>
		<div class="activity-state-popup_tab_item activity-state_{{key}} {{isFirstItem ? 'active' : ''}}" data-reaction-id="{{reaction.id ? reaction.id : '0'}}" data-paged="{{reaction.paged}}" data-total-pages="{{reaction.total_pages}}">

			<# if( data_reaction_id === current_reaction_id ) { #>
				<# if( users.length > 0 ) { #>
				<ul class="activity-state_users">
					<# jQuery.each( users, function( key, user ) { #>
					<li class="activity-state_user">
						<div class="activity-state_user__avatar">
							<a href="{{ user.profile_url }}">
								<img class="avatar" src="{{ user.avatar }}" alt="{{ user.name }}" />
								<div class="activity-state_user__reaction">
									<# if ( user.reaction.type === 'bb-icons' ) { #>
									<i class="bb-icon-{{ user.reaction.icon }}" style="font-weight:200;color:{{ user.reaction.icon_color }};"></i>
									<# } else if ( user.reaction.icon_path !== '' ) { #>
									<img src="{{ user.reaction.icon_path }}" class="{{ user.reaction.type }}" alt="{{ user.reaction.icon_text }}" />
									<# } else { #>
									<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
									<# } #>
								</div>
							</a>
						</div>
						<div class="activity-state_user__name">
							<a href="{{ user.profile_url }}">{{ user.name }}</a>
						</div>
						<# if ( user.member_type && user.member_type.label ) { #>
						<div class="activity-state_user__role" style="color:{{user.member_type.color.text}}; background-color:{{user.member_type.color.background}};">
							{{ user.member_type.label }}
						</div>
						<# } #>
					</li>
					<# }); #>
				</ul>
				<# } else { #>
				<div class="activity-state_no_users">
					{{ data.no_users }}
				</div>
				<# } #>
			<# } else { #>
			<ul class="activity-state_users"></ul>
			<# } #>
		</div>
		<#
		isFirstItem = false;
		});
	}
	#>
</script>

<script type="text/html" id="tmpl-activity-reacted-item">
	<#
	users = data.reacted_users;
	jQuery.each( users, function( key, user ) { #>
	<li class="activity-state_user">
		<div class="activity-state_user__avatar">
			<a href="{{ user.profile_url }}">
				<img class="avatar" src="{{ user.avatar }}" alt="{{ user.name }}" />
				<div class="activity-state_user__reaction">
					<# if ( user.reaction.type === 'bb-icons' ) { #>
					<i class="bb-icon-{{ user.reaction.icon }}" style="font-weight:200;color:{{ user.reaction.icon_color }};"></i>
					<# } else if ( user.reaction.icon_path !== '' ) { #>
					<img src="{{ user.reaction.icon_path }}" class="{{ user.reaction.type }}" alt="{{ user.reaction.icon_text }}" />
					<# } else { #>
					<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
					<# } #>
				</div>
			</a>
		</div>
		<div class="activity-state_user__name">
			<a href="{{ user.profile_url }}">{{ user.name }}</a>
		</div>
		<# if ( user.member_type && user.member_type.label ) { #>
		<div class="activity-state_user__role" style="color:{{user.member_type.color.text}}; background-color:{{user.member_type.color.background}};">
			{{ user.member_type.label }}
		</div>
		<# } #>
	</li>
	<# }); #>
</script>

