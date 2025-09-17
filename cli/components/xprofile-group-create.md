#	wp bp xprofile group create

Create an XProfile group.

## OPTIONS

--name=&lt;name&gt;
: The name for this field group.

[--description=&lt;description&gt;]
: The description for this field group.

[--can-delete=&lt;can-delete&gt;]
: Whether the group can be deleted.
\---
Default: true.
\---

[--porcelain]
: Output just the new group id.

## EXAMPLES

    $ wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"
    Success: Created XProfile field group "Group Name" (ID 123).

    $ wp bp xprofile group add --name="Another Group" --can-delete=false
    Success: Created XProfile field group "Another Group" (ID 21212).
