<?php
/**
 * Readylaunch - Messages right panel template.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */
?>
<script type="text/html" id="tmpl-bp-messages-right-panel">
    <#
    if (data.is_group_thread && data.group_name && data.group_name.length > 1) { #>
        <!-- Group Thread -->
        <div class="bb-rl-message-group-thread">
            <# if (data.group_cover) { #>
            <div class="bb-rl-message-group-thread-cover">
                <img decoding="async" src="{{data.group_cover}}">
            </div>
            <# } #>
            <div class="bb-rl-message-group-thread-content">
                <div class="bb-rl-message-group-thread-avatar">
                    <a href="{{data.group_link}}">
                        <img decoding="async" class="avatar" src="{{data.group_avatar}}">
                    </a>
                </div>
                <div class="bb-rl-message-group-thread-name">
                    {{data.group_name}}
                </div>
                <div class="bb-rl-message-profile-meta">
                    <# if (data.group_status) { #>
                        {{{data.group_status}}}
                    <# } #>
                    <# if (data.group_last_active) { #>
                        <span class="bb-rl-message-profile-meta-item">{{data.group_last_active}}</span>
                    <# } #>
                </div>
            </div>
        </div>
    <# } else if (data.recipients && data.recipients.count > 2) { #>
        <!-- Multiple Members Thread -->
        <div class="bb-rl-message-multiple-threads">
                <div class="bb-rl-message-multiple-threads-avatar">
                    <# var count = 0; #>
                    <# _.each( data.recipients.members, function( member ) { #>
                        <# if (count < 2) { #>
                            <a href="{{{ member.user_link }}}">
                                <img class="avatar" src="{{{ member.avatar }}}">
                            </a>
                        <# } #>
                        <# count++; #>
                    <# }); #>
                </div>
                <div class="bb-rl-message-multiple-threads-content">
                    <# 
                    var members = data.recipients.members;
                    var names = [];
                    var count = 0;
                    var totalMembers = _.size(members);
                    
                    _.each(members, function(member) {
                        if (count < 2) {
                            names.push(member.user_name);
                        }
                        count++;
                    });

                    if (totalMembers <= 3) {
                        var allNames = [];
                        _.each(members, function(member) {
                            allNames.push(member.user_name);
                        });
                        print(allNames.join(', '));
                    } else {
                        print(names.join(', ') + ' + ' + (totalMembers - 2));
                    } 
                    #>
                </div>
            </div>
    <# } else {
        #>
        <!-- Single Thread -->
        <div class="bb-rl-message-profile">
        <# var recipient = _.find(data.recipients.members, function(item) { return !item.is_you; }); #>
        <# if (!recipient) { #>
            <# recipient = _.find(data.recipients.members, function(item) { return item.is_you; }); #>
        <# } #>
            <# 
            if (recipient) {
                #>
                <div class="bb-rl-message-profile-avatar">
                    <a href="{{recipient.user_link}}">
                        <img decoding="async" class="avatar" src="{{recipient.avatar}}" alt="{{recipient.user_name}}">
                    </a>
                </div>
                <# if (recipient.role) { #>
                <div class="bb-rl-message-profile-type">
                    {{recipient.role}}
                </div>
                <# } #>
                <div class="bb-rl-message-profile-name">
                    <# if (recipient.is_you) { #>
                    You
                    <# } else { #>
                    {{recipient.user_name}}
                    <# } #>
                </div>
                <div class="bb-rl-message-profile-meta">
                    <# if (recipient.joined_date) { #>
                    <span class="bb-rl-message-profile-meta-item">{{recipient.joined_date}}</span>
                    <# } #>
                    <# if (recipient.followers_count) { #>
                    <span class="bb-rl-message-profile-meta-item">{{recipient.followers_count}} followers</span>
                    <# } #>
                </div>
                <# if (recipient.last_active) { #>
                <div class="bb-rl-message-profile-meta">
                    <span class="bb-rl-message-profile-meta-item">{{recipient.last_active}}</span>
                </div>
                <# } #>
            <# } #>
        </div>
    <# } #>

    <div class="bb-rl-message-right-panel-inner">
        <!-- Tab navigation -->
        <div class="bb-rl-message-right-tabs">
            <button class="bb-rl-tab-item active" data-tab="participants">Participants</button>
            <button class="bb-rl-tab-item" data-tab="media">Media</button>
            <button class="bb-rl-tab-item" data-tab="files">Files</button>
        </div>
        
        <!-- Tab content -->
        <div class="bb-rl-message-right-content">
            <!-- Participants tab -->
            <div class="bb-rl-tab-content active" id="participants-tab">
                <div class="bb-rl-message-right-loading">
                    <div class="bb-rl-loading-spinner"></div>
                </div>
            </div>
            
            <!-- Media tab -->
            <div class="bb-rl-tab-content" id="media-tab">
                <div class="bb-rl-message-right-loading">
                    <div class="bb-rl-loading-spinner"></div>
                </div>
            </div>
            
            <!-- Files tab -->
            <div class="bb-rl-tab-content" id="files-tab">
                <div class="bb-rl-message-right-loading">
                    <div class="bb-rl-loading-spinner"></div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/html" id="tmpl-bp-messages-right-panel-participants">
    <# _.each( data, function( participant ) { #>
        <div class="bb-rl-participant-item">
            <div class="bb-rl-participant-avatar">
                <a href="{{participant.profile_url}}">
                    <img src="{{participant.avatar}}" alt="{{participant.name}}">
                </a>
            </div>
            <div class="bb-rl-participant-info">
                <h4 class="bb-rl-participant-name">
                    <a href="{{participant.profile_url}}">{{participant.name}}</a>
                </h4>
            
                <# if (participant.role) { #>
                    <span class="bb-rl-participant-role">{{participant.role}}</span>
                <# } #>
                    
                <div class="bb-rl-participant-meta">
                    <# if (participant.joined_date) { #>
                        <span>Joined {{participant.joined_date}}</span>
                    <# } #>
                    <# if (participant.last_active) { #>
                        <span>{{participant.last_active}}</span>
                    <# } #>
                </div>
            </div>
        </div>
    <# }); #>
</script>

<script type="text/html" id="tmpl-bp-messages-right-panel-media">
    <# _.each( data, function( item ) { #>
        <div class="bb-rl-media-item">
            <a href="{{item.url}}" class="bb-open-media">
                <img src="{{item.url}}" alt="{{item.title}}">
            </a>
        </div>
    <# }); #>
</script>

<script type="text/html" id="tmpl-bp-messages-right-panel-files">
    <# _.each( data, function( file ) { #>
        <div class="bb-rl-file-item">
            <div class="bb-rl-file-icon bb-rl-file-{{file.extension}}">
                {{file.extension.toUpperCase()}}
            </div>
            <div class="bb-rl-file-info">
                <h4 class="bb-rl-file-name">
                    <a href="{{file.url}}" target="_blank">{{file.title}}</a>
                </h4>
            </div>
        </div>
    <# }); #>
</script>