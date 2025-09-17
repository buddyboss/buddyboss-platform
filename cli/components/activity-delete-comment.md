#	wp bp activity delete_comment

Delete an activity comment.

## OPTIONS

&lt;activity-id&gt;
: Identifier for the activity.

 --comment-id=&lt;comment-id&gt;
: ID of the comment to delete.

[--yes]
: Answer yes to the confirmation message.

## EXAMPLES

	 $ wp bp activity delete_comment 100 --comment-id=500
	 Success: Activity comment deleted.
	
	 $ wp bp activity delete_comment 165 --comment-id=35435 --yes
	 Success: Activity comment deleted.
