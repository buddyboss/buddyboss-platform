#	wp bp notification delete

Delete a notification.

## OPTIONS

&lt;notification-id&gt;...
: ID or IDs of notification to delete.

[--yes]
: Answer yes to the confirmation message.

## EXAMPLES

    $ wp bp notification delete 520 --yes
    Success: Deleted notification 520.

    $ wp bp notification delete 55654 54564 --yes
    Success: Deleted notification 55654.
    Success: Deleted notification 54564.

    $ wp bp notification delete $(wp bp notification list --format=ids) --yes
    Success: Deleted notification 35456465.
    Success: Deleted notification 46546546.
    Success: Deleted notification 46465465.
