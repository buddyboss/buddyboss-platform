#Email Tokens

Site admins can easily edit the content of email notifications by using tokens which are placeholders for dynamic content. Some tokens can be used in any message while others are restricted due to context of each type of message. Tokens can we wrapped in two `{{ }}` or three `{{{ }}}` curly braces. Tokens wrapped in three curly braces are not escaped on merging which is important for including certain content such as links.

---

Global Tokens<a name="global-tokens"></a>
--------------

|Token|Description|
|---|---|
|`{{site.admin-email}}`|Email address of the site administrator.|
|`{{{site.url}}}`| Value of `home_url()`.|
|`{{site.description}}`|Value of 'blog description'.|
|`{{site.name}}`|Value of 'blog name'.|
|`{{recipient.email}}`|Email address of recipient.|
|`{{recipient.name}}`|Display name of recipient.|
|`{{recipient.username}}`|Username (login) of recipient.|
|`{{{unsubscribe}}}`|Link to the recipient's email notifications settings screen in his or her user profile.|
|`{{email.subject}}`|The subject line of the email.|

<a name="registration-tokens"></a>
[bp_docs_link text="Registration Tokens" slug="components/registration/registration-emails.md"]
--------------

### \[{{{site.name}}}\] Activate your account

**Situation:** Recipient has registered for an account.

|Token|Description|
|---|---|
|`{{{activate.url}}}`|Link to the site's membership activation page, including the user's activation key.|
|`{{key}}`|Activation key.|
|`{{user.email}}`|The new user's email address.|
|`{{user.id}}`|The new user's ID.|

### \[{{{site.name}}}\] Activate {{{user-site.url}}}

**Situation:** Recipient has registered for an account and site (multisite only).

|Token|Description|
|---|---|
|`{{{activate-site.url}}}`|Link to the site's membership and new blog activation page.|
|`{{{user-site.url}}}`|The link to the new blog created by the user.|
|`{{title}}`|The new blog's title.|
|`{{domain}}`|The new blog's domain.|
|`{{path}}`|The new blog's path.|
|`{{key_blog}}`|The activation key created in wpmu\_signup\_blog().|
|`{{user.email}}`|The new user's email address.|

### \[{{{site.name}}}\] Verify your new email address

**Situation:** Recipient has changed their email address.

|Token|Description|
|---|---|
|`{{{verify.url}}}`|Link used to verify the new email address.|
|`{{displayname}}`|Display name of recipient.|
|`{{old-user.email}}`|The user's previous email address.|
|`{{user.email}}`|The user's new email address.|


<a name="group-tokens"></a>
[bp_docs_link text="Group Tokens" slug="components/groups/group-emails.md"]
--------------

### \[{{{site.name}}}\] Group details updated

**Situation:** A group's details were updated.

|Token|Description|
|---|---|
|`{{changed_text}}`|Text describing the details of the change.|
|`{{{group.url}}}`|Link to the group.|
|`{{group.name}}`|Name of the group.|
|`{{group.id}}`|ID of the group.|
|`{{{group.description}}}`|Description of the group.|
|`{{{group.small_card}}}`|Group Card showing group photo and other details about the group.|


### \[{{{site.name}}}\] Membership request for group: {{group.name}}

**Situation:** A member has requested permission to join a group.

|Token|Description|
|---|---|
|`{{group.name}}`|Name of the group.|
|`{{{group-requests.url}}}`|Link to the group's membership requests management screen.|
|`{{requesting-user.name}}`|Display name of the user who is requesting membership.|
|`{{{profile.url}}`|User profile of the user who is requesting membership.|
|`{{admin.id}}`|ID of the group admin who is receiving this email.|
|`{{group.id}}`|ID of the group.|
|`{{membership.id}}`|ID of the membership object.|
|`{{requesting-user.id}}`|ID of the user who is requesting membership.|
|`{{{member.card}}}`|Profile Card showing profile photo and other details about the user who is requesting membership.|

### Title: \[{{{site.name}}}\] Membership request for group “{{group.name}}” accepted

**Situation:** Recipient had requested to join a group, which was accepted.

|Token|Description|
|---|---|
|`{{group.name}}`|Name of the group.|
|`{{{group.url}}}`|Link to the group.|
|`{{group.id}}`|ID of the group.|
|`{{requesting-user.id}}`|ID of the user who is requesting membership.|
|`{{{group.small_card}}}`|Group Card showing group photo and other details about the group.|

### Title: \[{{{site.name}}}\] Membership request for group “{{group.name}}” rejected

**Situation:** Recipient had requested to join a group, which was rejected.

|Token|Description|
|---|---|
|`{{group.name}}`|Name of the group.|
|`{{{group.url}}}`|Link to the group.|
|`{{group.id}}`|ID of the group.|
|`{{requesting-user.id}}`|ID of the user who is requesting membership.|
|`{{{group.small_card}}}`|Group Card showing group photo and other details about the group.|

### \[{{{site.name}}}\] You have been promoted in the group: “{{group.name}}”

**Situation:** Recipient's status within a group has changed.

|Token|Description|
|---|---|
|`{{group.name}}`|Name of the group.|
|`{{{group.url}}}`|Link to the group.|
|`{{promoted_to}}`|String describing new group responsibilitied. Possible values: 'an administrator' or 'a moderator'.|
|`{{group.id}}`|ID of the group.|
|`{{user.id}}`|ID of the promoted user.|
|`{{{group.small_card}}}`|Group Card showing group photo and other details about the group.|

### \[{{{site.name}}}\] You have an invitation to the group: “{{group.name}}”

**Situation:** A member has sent a group invitation to the recipient.

|Token|Description|
|---|---|
|`{{group.name}}`|Name of the group.|
|`{{{group.url}}}`|Link to the group.|
|`{{inviter.name}}`|Inviter's display name wrapped in a link to that user's profile.|
|`{{{inviter.url}}}`|Link to the profile of the user who extended the invitation.|
|`{{{invites.url}}}`|Link to the recipient's invitation management screen.|
|`{{{group.invite_message}}}`|Content of the group invitation message.|
|`{{{group.small_card}}}`|Group Card showing group photo and other details about the group.|

### \[{{{site.name}}}\] {{poster.name}} mentioned you in a group update

**Situation:** Recipient was mentioned in a group activity update.

|Token|Description|
|---|---|
|`{{usermessage}}`|The content of the activity update.|
|`{{{mentioned.url}}}`|Permalink to the activity item.|
|`{{poster.name}}`|Display name of activity item author.|
|`{{group.name}}`|Name of the group housing the activity update. Empty if not in a group.|
|`{{receiver-user.id}}`|The ID of the user who is receiving the update.|

<a name="forum-tokens"></a>
[bp_docs_link text="Forum Tokens" slug="components/forums/forum-emails.md"]
--------------

###\[{{{site.name}}}\] New discussion: {{discussion.title}}

**Situation:** A member has created a new forum discussion.

|Token|Description|
|---|---|
|`{{poster.name}}`|Name of the member who created the discussion, wrapped in a link to that user's profile.|
|`{{forum.url}}`|Link to the forum containing the discussion.|
|`{{forum.title}}`|Name of the forum containig the discussion.|
|`{{discussion.url}}`|Link to the newly created discussion.|
|`{{discussion.title}}`|Name of the newly created discussion.|
|`{{{discussion.content}}}`|Content of the newly created discussion.|


###\[{{{site.name}}}\] {{poster.name}} replied to one of your forum discussions

**Situation:** A member has replied to a forum discussion that the participant is following.

|Token|Description|
|---|---|
|`{{poster.name}}`|Name of the member who replied to the discussion, wrapped in a link to that user's profile.|
|`{{forum.url}}`|Link to the forum containing the discussion and reply.|
|`{{forum.title}}`|Name of the forum containig the discussion and reply.|
|`{{discussion.url}}`|Link to the discussion containing the reply.|
|`{{discussion.title}}`|Name of the discussion containing the reply.|
|`{{{reply.content}}}`|Content of the discussion reply.|

<a name="activity-tokens"></a>
[bp_docs_link text="Activity Tokens" slug="components/activity/activity-emails.md"]
--------------

### \[{{{site.name}}}\] {{poster.name}} mentioned you in a status update

**Situation:** Recipient was mentioned in an activity update.

|Token|Description|
|---|---|
|`{{usermessage}}`|The content of the activity update.|
|`{{{mentioned.url}}}`|Permalink to the activity item.|
|`{{poster.name}}`|Display name of activity item author.|
|`{{receiver-user.id}}`|The ID of the user who is receiving the update.|

### \[{{{site.name}}}\] {{poster.name}} replied to one of your updates

**Situation:** A member has replied to an activity update that the recipient posted.

|Token|Description|
|---|---|
|`{{usermessage}}`|The content of the comment.|
|`{{poster.name}}`|Display name of comment author.|
|`{{{thread.url}}}`|Permalink to the original activity item thread.|
|`{{comment.id}}`|The comment ID.|
|`{{commenter.id}}`|The ID of the user who posted the comment.|
|`{{original_activity.user_id}}`|The ID of the user who wrote the original activity update.

### \[{{{site.name}}}\] {{poster.name}} replied to one of your comments

**Situation:** A member has replied to a comment on an activity update that the recipient posted.

|Token|Description|
|---|---|
|`{{usermessage}}`|The content of the comment.|
|`{{poster.name}}`|Display name of comment author.|
|`{{{thread.url}}}`|Permalink to the original activity item thread.|
|`{{comment.id}}`|The comment ID.|
|`{{parent-comment-user.id}}`|The ID of the user who wrote the immediate parent comment.|
|`{{commenter.id}}`|The ID of the user who posted the comment.|

<a name="messaging-tokens"></a>
[bp_docs_link text="Private Messaging Tokens" slug="components/messaging/messaging-emails.md"]
--------------

### {{{site.name}}}\] New message from {{sender.name}}

**Situation:** Recipient has received a private message.

|Token|Description|
|---|---|
|`{{sender.name}}`|Display name of the message sender.|
|`{{{message}}}`|The content of the message.|
|`{{{message.url}}}`|Link to the message thread.|

<a name="connection-tokens"></a>
[bp_docs_link text="Connection Tokens" slug="components/connections/connection-emails.md"]
--------------

### \[{{{site.name}}}\] New request to connect from {{initiator.name}}

**Situation:** A member has sent a friend request to the recipient.

|Token|Description|
|---|---|
|`{{{friend-requests.url}}}`|Link to the user's friendship request management screen.|
|`{{{initiator.url}}}`|The initiator's user profile.|
|`{{initiator.name}}`|Display name of the initiator.|
|`{{friendship.id}}`|ID of the friendship object.|
|`{{friend.id}}`|ID of the request recipient.|
|`{{initiator.id}}`|ID of the user who initiated the request.
|`{{{member.card}}}`|Profile Card showing profile photo and other details about the user who initiated the request.|

### \[{{{site.name}}}\] {{friend.name}} accepted your request to connect

**Situation:** Recipient has had a friend request accepted by a member.

|Token|Description|
|---|---|
|`{{{friendship.url}}}`|Link to the request recipient's user profile.|
|`{{friend.name}}`|Display name of the request recipient.|
|`{{friendship.id}}`|ID of the friendship object.|
|`{{friend.id}}`|ID of the request recipient.|
|`{{initiator.id}}`|ID of the user who initiated the request.|
|`{{{member.card}}}`|Profile Card showing profile photo and other details about the request recipient.|

<a name="invites-tokens"></a>
[bp_docs_link text="Invites Tokens" slug="components/invites/invites-emails.md"]
--------------

###An invitation from {{inviter.name}} to join \[{{{site.name}}}\]

**Situation:** Recipient has been invited by a member to join the website.

|Token|Description|
|---|---|
|`{{inviter.name}}`|Inviter's display name.|
|`{{{site.url}}}`| Value of `home_url()`.| |
