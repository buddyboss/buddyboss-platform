Feature: Manage BuddyBoss Connections

  Scenario: Connections CRUD Operations
    Given a BP install

    When I try `wp user get bogus-user`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {BOB_ID}

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SALLY_ID}

    When I run `wp user create testuser3 testuser3@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {JOHN_ID}

    When I run `wp bp friend create {BOB_ID} {SALLY_ID} --force-accept`
    Then STDOUT should contain:
      """
      Success: Connection successfully created.
      """

    When I run `wp bp friend check {BOB_ID} {SALLY_ID}`
    Then STDOUT should contain:
      """
      Success: Yes, they are friends.
      """

    When I run `wp bp friend create {BOB_ID} {JOHN_ID} --force-accept`
    Then STDOUT should contain:
      """
      Success: Connection successfully created.
      """

    When I run `wp bp friend list {BOB_ID} --fields=friend_user_id,is_confirmed`
    Then STDOUT should be a table containing rows:
      | friend_user_id | is_confirmed |
      | {SALLY_ID}     | 1            |
      | {JOHN_ID}      | 1            |

    When I run `wp bp friend remove {BOB_ID} {SALLY_ID}`
    Then STDOUT should contain:
      """
      Success: Connection successfully removed.
      """

    When I run `wp bp friend remove {BOB_ID} {JOHN_ID}`
    Then STDOUT should contain:
      """
      Success: Connection successfully removed.
      """
