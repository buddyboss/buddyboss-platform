#	wp bp email create

Create a new email post connected to an email type.

## OPTIONS

--type=&lt;type&gt;
: Email type for the email (should be unique identifier, sanitized like a post slug).

--type-description=&lt;type-description&gt;
: Email type description.

--subject=&lt;subject&gt;
: Email subject line. Email tokens allowed. View https://codex.buddypress.org/emails/email-tokens/ for more info.

[--content=&lt;content&gt;]
: Email content. Email tokens allowed. View https://codex.buddypress.org/emails/email-tokens/ for more info.

[--plain-text-content=&lt;plain-text-content&gt;]
: Plain-text email content. Email tokens allowed. View https://codex.buddypress.org/emails/email-tokens/ for more info.

[&lt;file&gt;]
: Read content from <file>. If this value is present, the
    `--content` argument will be ignored.

  Passing `-` as the filename will cause post content to
  be read from STDIN.

[--edit]
: Immediately open system's editor to write or edit email content.

  If content is read from a file, from STDIN, or from the `--content`
  argument, that text will be loaded into the editor.

## EXAMPLES

    # Create email post
    $ wp bp email create --type=new-event --type-description="Send an email when a new event is created" --subject="[{{{site.name}}}] A new event was created" --content="<a href='{{{some.custom-token-url}}}'></a>A new event</a> was created" --plain-text-content="A new event was created"
    Success: Email post created for type "new-event".

    # Create email post with content from given file
    $ wp bp email create ./email-content.txt --type=new-event --type-description="Send an email when a new event is created" --subject="[{{{site.name}}}] A new event was created" --plain-text-content="A new event was created"
    Success: Email post created for type "new-event".
