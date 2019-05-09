#	wp bp email

Manage BuddyPress Emails.

## EXAMPLES

	# Create email post
	$ wp bp email create --type=new-event --type-description="Send an email when a new event is created" --subject="[{{{site.name}}}] A new event was created" --content="<a href='{{{some.custom-token-url}}}'></a>A new event</a> was created" --plain-text-content="A new event was created"
	Success: Email post created for type "new-event".

	# Create email post with content from given file
	$ wp bp email create ./email-content.txt --type=new-event --type-description="Send an email when a new event is created" --subject="[{{{site.name}}}] A new event was created" --plain-text-content="A new event was created"
	Success: Email post created for type "new-event".
	
	# Output the post ID for the 'activity-at-message' email type
	$ wp bp email get-post activity-at-message --fields=ID
	
	$ wp bp email reinstall --yes
	Success: Emails have been successfully reinstalled.
