# wp bp activity generate


Generate random activity items.

## OPTIONS

[--count=&lt;number&gt;]
: How many activity items to generate.
\---
default: 100
\---

[--skip-activity-comments=&lt;skip-activity-comments&gt;>]
: Whether to skip activity comments. Recording activity_comment items requires a resource-intensive tree rebuild.
\---
default: 1
\---

## EXAMPLE

    $ wp bp activity generate --count=50
