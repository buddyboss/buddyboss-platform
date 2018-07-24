Feature: Manage BuddyBoss Messages

  Scenario: Message CRUD Operations
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {BOB_ID}

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SALLY_ID}

    When I run `wp bp message send-notice --subject="Important Notice" --content="Notice Message"`
    Then STDOUT should contain:
      """
      Success: Notice was successfully sent.
      """

    When I run `wp bp message create --from={BOB_ID} --to={SALLY_ID} --subject="Message" --content="Message Content"  --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {THREAD_ID}

    When I run `wp bp message star-thread {THREAD_ID} --user-id={SALLY_ID}`
    Then STDOUT should contain:
      """
      Success: Thread was successfully starred.
      """

    When I run `wp bp message unstar-thread {THREAD_ID} --user-id={SALLY_ID}`
    Then STDOUT should contain:
      """
      Success: Thread was successfully unstarred.
      """

    When I run `wp bp message delete-thread {THREAD_ID} --user-id={SALLY_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Thread successfully deleted.
      """

  Scenario: Message list
    Given a BP install

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    And save STDOUT as {BOB_ID}

    When I run `wp user create testuser3 testuser3@example.com --porcelain`
    And save STDOUT as {SALLY_ID}

    When I try `wp bp message list --user-id={BOB_ID} --fields=id`
    Then the return code should be 1

    When I try `wp bp message list --user-id={SALLY_ID} --fields=id`
    Then the return code should be 1

    When I run `wp bp message create --from={BOB_ID} --to={SALLY_ID} --subject="Test Thread" --content="Message one" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {THREAD_ID}

    When I run `wp bp message create --from={SALLY_ID} --thread-id={THREAD_ID} --subject="Test Answer" --content="Message two"`
    Then STDOUT should contain:
      """
      Success: Message successfully created.
      """

    When I run `wp bp message create --from={BOB_ID} --thread-id={THREAD_ID} --subject="Another Answer" --content="Message three"`
    Then STDOUT should contain:
      """
      Success: Message successfully created.
      """

    When I run `wp bp message list --user-id={BOB_ID} --fields=sender_id`
    Then STDOUT should be a table containing rows:
      | sender_id  |
      | {BOB_ID}   |
      | {SALLY_ID} |

    When I run `wp bp message list --user-id={SALLY_ID} --fields=thread_id,sender_id,subject,message`
    Then STDOUT should be a table containing rows:
      | thread_id   | sender_id  | subject         | message        |
      | {THREAD_ID} | {BOB_ID}   | Test Thread     | Message one    |
      | {THREAD_ID} | {SALLY_ID} | Test Answer     | Message two    |
      | {THREAD_ID} | {BOB_ID}   | Another Answer  | Message three  |

    When I run `wp user create testuser4 testuser4@example.com --porcelain`
    And save STDOUT as {JOHN_ID}

    When I try `wp bp message list --user-id={JOHN_ID} --fields=id`
    Then the return code should be 1

    When I run `wp bp message create --from={JOHN_ID} --to={SALLY_ID} --subject="Second Thread" --content="Message four" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ANOTHER_THREAD_ID}

    When I run `wp bp message create --from={SALLY_ID} --thread-id={ANOTHER_THREAD_ID} --subject="Final Message" --content="Final Message"`
    Then STDOUT should contain:
      """
      Success: Message successfully created.
      """

    When I run `wp bp message list --user-id={JOHN_ID} --fields=thread_id,sender_id,subject,message`
    Then STDOUT should be a table containing rows:
      | thread_id           | sender_id  | subject        | message      |
      | {ANOTHER_THREAD_ID} | {JOHN_ID}  | Second Thread  | Message four |
