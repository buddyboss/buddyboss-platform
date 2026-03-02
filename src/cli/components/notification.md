#	wp bp notification

Manage Notification.

## Examples

	$ wp bp notification create --component=messages --action=update_reply --user-id=523
	Success: Successfully created new notification. (ID #5464)
	
	$ wp bp notification add --component=groups --action=comment_reply --user-id=10
	Success: Successfully created new notification (ID #48949)
	
	$ wp bp notification delete 520 --yes
	Success: Deleted notification 520.
	
	$ wp bp notification delete 55654 54564 --yes
	Success: Deleted notification 55654.
	Success: Deleted notification 54564.
	
	$ wp bp notification delete $(wp bp notification list --format=ids) --yes
	Success: Deleted notification 35456465.
	Success: Deleted notification 46546546.
	Success: Deleted notification 46465465.
	
	$ wp bp notification generate --count=50
	
	$ wp bp notification get 500
	$ wp bp notification get 56 --format=json
	
	$ wp bp notification list --format=ids
	15 25 34 37 198
	
	$ wp bp notification list --format=count
	10
	
	$ wp bp notification list --fields=id,user_id
	| id     | user_id  |
	| 66546  | 656      |
	| 54554  | 646546   |
