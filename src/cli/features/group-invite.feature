Feature: Manage BuddyPress Group Invites

  Scenario: Group Invite CRUD Operations
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp user create inviter inviter@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {INVITER_ID}

    When I run `wp bp group create --name="Cool Group" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp group invite create --group-id={GROUP_ID} --user-id={MEMBER_ID} --inviter-id={INVITER_ID}`
    Then STDOUT should contain:
      """
      Success: Member invited to the group.
      """

    When I run `wp bp group invite remove --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: User uninvited from the group.
      """

    When I run `wp bp group invite accept --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: User is now a "member" of the group.
      """

  Scenario: Group Invite list
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ONE_ID}

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_TWO_ID}

    When I run `wp bp group create --name="Group 1" --slug=group1 --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ONE_ID}

    When I run `wp bp group create --name="Group 2" --slug=group2 --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_TWO_ID}

    When I run `wp bp group invite create --group-id={GROUP_ONE_ID} --user-id={MEMBER_ONE_ID} --inviter-id={MEMBER_TWO_ID}`
    Then the return code should be 0

    When I run `wp bp group invite create --group-id={GROUP_TWO_ID} --user-id={MEMBER_TWO_ID} --inviter-id={MEMBER_ONE_ID}`
    Then the return code should be 0

    When I try `wp bp group invite list`
    Then the return code should be 1
