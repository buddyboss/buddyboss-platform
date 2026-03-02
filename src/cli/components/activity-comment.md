# wp bp activity comment

Add an activity comment.

## OPTIONS

&lt;activity-id&gt;
: ID of the activity to add the comment.

--user-id=&lt;user&gt;
: ID of the user. If none is provided, a user will be randomly selected.

--content=&lt;content&gt;
: Activity content text. If none is provided, default text will be generated.

[--skip-notification]
: Whether to skip notification.

[--porcelain]
: Output only the new activity comment id.

## EXAMPLES

    $ wp bp activity comment 560 --user-id=50 --content="New activity comment"
    Success: Successfully added a new activity comment (ID #4645)

    $ wp bp activity comment 459 --user-id=140 --skip-notification=1
    Success: Successfully added a new activity comment (ID #494)
