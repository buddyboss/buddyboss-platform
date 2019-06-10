#	wp bp group update

Update a group.


## OPTIONS

&lt;group-id&gt;...
: Identifier(s) for the group(s). Can be a numeric ID or the group slug.

[--&lt;field&gt;=&lt;value&gt;]
: One or more fields to update. See groups_create_group()

## EXAMPLE

    $ wp bp group update 35 --description="What a cool group!" --name="Group of Cool People"
