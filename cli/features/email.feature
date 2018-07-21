Feature: Manage BuddyPress Emails

  Scenario: BuddyPress reinstall emails
    Given a BP install

    When I run `wp bp email reinstall --yes`
    Then STDOUT should contain:
      """
      Success: Emails have been successfully reinstalled.
      """
