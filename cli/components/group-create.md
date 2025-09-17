#	wp bp group create

Create a group.

## OPTIONS

--name=&lt;name>
: Name of the group.

[--slug=&lt;slug&gt;]
: URL-safe slug for the group. If not provided, one will be generated automatically.

[--description=&lt;description&gt;]
: Group description.
\---
Default: 'Description for group "[name]"'
\---

[--creator-id=&lt;creator-id&gt;]
: ID of the group creator.
\---
Default: 1
\---

[--slug=&lt;slug&gt;]
: URL-safe slug for the group.

[--status=&lt;status&gt;]
: Group status (public, private, hidden).
\---
Default: public
\---

[--enable-forum=&lt;enable-forum&gt;]
: Whether to enable legacy bbPress forums.
\---
Default: 0
\---

[--date-created=&lt;date-created&gt;]
: MySQL-formatted date.
\---
Default: current date.
\---

[--silent]
: Whether to silent the group creation.

[--porcelain]
: Return only the new group id.

## EXAMPLES

    $ wp bp group create --name="Totally Cool Group"
    Success: Group (ID 5465) created: http://example.com/groups/totally-cool-group/

    $ wp bp group create --name="Another Cool Group" --description="Cool Group" --creator-id=54 --status=private
    Success: Group (ID 6454)6 created: http://example.com/groups/another-cool-group/
