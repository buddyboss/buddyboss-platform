#	wp bp group delete

Delete a group.


## OPTIONS

&lt;group-id&gt;...
: Identifier(s) for the group(s). Can be a numeric ID or the group slug.

[--yes]
: Answer yes to the confirmation message.

## EXAMPLES

    $ wp bp group delete 500
    Success: Group successfully deleted.

    $ wp bp group delete group-slug --yes
    Success: Group successfully deleted.
