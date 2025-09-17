#	wp bp notification create

Create a notification item.

## OPTIONS

[--component=&lt;component&gt;]
: The component for the notification item (groups, activity, etc). If
none is provided, a component will be randomly selected from the
active components.

[--action=&lt;action&gt;]
: Name of the action to associate the notification. (comment_reply, update_reply, etc).

[--user-id=&lt;user&gt;]
: ID of the user associated with the new notification.

[--item-id=&lt;item&gt;]
: ID of the associated notification.

[--secondary-item-id=&lt;item&gt;]
: ID of the secondary associated notification.

[--date=&lt;date&gt;]
: GMT timestamp, in Y-m-d h:i:s format.
\---
default: Current time
\---

[--silent]
: Whether to silent the notification creation.

[--porcelain]
: Output only the new notification id.

## EXAMPLES

    $ wp bp notification create --component=messages --action=update_reply --user-id=523
    Success: Successfully created new notification. (ID #5464)

    $ wp bp notification add --component=groups --action=comment_reply --user-id=10
    Success: Successfully created new notification (ID #48949)
