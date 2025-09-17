#	wp bp xprofile field delete

Delete an XProfile field.

## OPTIONS

&lt;field-id&gt;...
: ID or IDs for the field. Accepts either the name of the field or a numeric ID.

[--delete-data]
: Delete user data for the field as well.
\---
default: false
\---

[--yes]
: Answer yes to the confirmation message.

## EXAMPLES

    $ wp bp xprofile field delete 500 --yes
    Success: Deleted XProfile field "Field Name" (ID 500).

    $ wp bp xprofile field remove 458 --delete-data --yes
    Success: Deleted XProfile field "Another Field Name" (ID 458).
