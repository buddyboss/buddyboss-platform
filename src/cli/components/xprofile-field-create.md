#	wp bp xprofile field create

Create an XProfile field.

## OPTIONS

--type=&lt;type&gt;
: Field type.
\---
default: textbox
\---

--field-group-id=&lt;field-group-id&gt;
: ID of the field group where the new field will be created.

--name=&lt;name&gt;
: Name of the new field.

[--porcelain]
: Output just the new field id.

## EXAMPLES

    $ wp bp xprofile field create --type=checkbox --field-group-id=508 --name="Field Name"
    Success: Created XProfile field "Field Name" (ID 24564).

    $ wp bp xprofile field add --type=checkbox --field-group-id=165 --name="Another Field"
    Success: Created XProfile field "Another Field" (ID 5465).
