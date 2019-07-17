# BuddyBoss Platform Contributing Guide

Before submitting your contribution, please make sure to take a moment and read through the following guidelines.

## Pull Request Guidelines

- Checkout a topic branch from `dev` and merge back against `dev`.
    - If you are not familiar with branching please read [_A successful Git branching model_](http://nvie.com/posts/a-successful-git-branching-model/) before you go any further.
- Follow the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/coding-standards/).
- Make sure the default grunt task passes.
- If adding a new feature:
    - Branch name should be like `feature-YOUR-BRANCH-NAME`
- If fixing a bug:
    - Branch name should be like `fix-YOUR-BRANCH-NAME`
 - If fixing a small bug:
    - Branch name should be like `hotfix-YOUR-BRANCH-NAME` (this will only be in release)
- After creating branch, make a code review request for that branch first so that we know you are working on this feature or fix.
- Release branch will only contain latest `dev` branch changes and whatever changes done for the hotfixes for the release.