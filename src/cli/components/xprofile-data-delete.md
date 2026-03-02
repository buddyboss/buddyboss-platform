#	wp bp xprofile data delete

Delete XProfile data for a user.

## OPTIONS

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

[--field-id=&lt;field&gt;]
: Identifier for the field. Accepts either the name of the field or a numeric ID.

[--delete-all]
: Delete all data for the user.

[--yes]
: Answer yes to the confirmation message.

## EXAMPLES

    $ wp bp xprofile data delete --user-id=45 --field-id=120 --yes
    Success: XProfile data removed.

    $ wp bp xprofile data remove --user-id=user_test --delete-all --yes
    Success: XProfile data removed.
