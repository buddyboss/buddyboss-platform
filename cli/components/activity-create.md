# wp bp activity create

Create an activity item.

## OPTIONS

[--component=<component>]
: The component for the activity item (groups, activity, etc). If
none is provided, a component will be randomly selected from the
active components.

[--type=<type>]
: Activity type (activity_update, group_created, etc). If none is
provided, a type will be randomly chose from those natively
associated with your <component>.

[--action=<action>]
: Action text (eg "Joe created a new group Foo"). If none is
provided, one will be generated automatically based on other params.

[--content=<content>]
: Activity content text. If none is provided, default text will be
generated.

[--primary-link=<primary-link>]
: URL of the item, as used in RSS feeds. If none is provided, a URL
will be generated based on passed parameters.

[--user-id=<user>]
: ID of the user associated with the new item. If none is provided,
a user will be randomly selected.

[--item-id=<item-id>]
: ID of the associated item. If none is provided, one will be
generated automatically, if your activity type requires it.

[--secondary-item-id=<secondary-item-id>]
: ID of the secondary associated item. If none is provided, one will
be generated automatically, if your activity type requires it.

[--date-recorded=<date-recorded>]
: GMT timestamp, in Y-m-d h:i:s format.
\---
Default: Current time
\---

[--hide-sitewide=<hide-sitewide>]
: Whether to hide in sitewide streams.
\---
Default: 0
\---

[--is-spam=<is-spam>]
: Whether the item should be marked as spam.
\---
Default: 0
\---

[--silent]
: Whether to silent the activity creation.

[--porcelain]
: Output only the new activity id.

## EXAMPLES

    $ wp bp activity create --is-spam=1
    Success: Successfully created new activity item (ID #5464)

    $ wp bp activity add --component=groups --user-id=10
    Success: Successfully created new activity item (ID #48949)

