#	wp bp group member create

Add a member to a group.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

[--role=&lt;role&gt;]
: Group member role (member, mod, admin).
\---
Default: member
\---

[--porcelain]
: Return only the added group member id.

## EXAMPLES

    $ wp bp group member add --group-id=3 --user-id=10
    Success: Added user #3 to group #3 as member.

    $ wp bp group member create --group-id=bar --user-id=20 --role=mod
    Success: Added user #20 to group #45 as mod.
