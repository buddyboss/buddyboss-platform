# wp bp activity post_update

Post an activity update.

## OPTIONS

--user-id=&lt;user&gt;
: ID of the user. If none is provided, a user will be randomly selected.

--content=&lt;content&gt;
: Activity content text. If none is provided, default text will be generated.

[--porcelain]
: Output only the new activity id.

## EXAMPLES

	$ wp bp activity post_update --user-id=50 --content="Content to update"
	Success: Successfully updated with a new activity item (ID #13165)
	
	$ wp bp activity post_update --user-id=140
	Success: Successfully updated with a new activity item (ID #4548)
