# BuddyBoss Events Plugin

## What This Is

A commercial WordPress plugin that adds a full-featured events management system to BuddyBoss-powered community sites. Site admins can run in-person, virtual, and recurring events with multi-tier ticketing and Stripe Connect payments. BuddyBoss (the company) takes a sliding commission from all ticket sales, with the rate determined by the site admin's BuddyBoss plan tier.

## Core Value

Site admins on BuddyBoss can create, manage, and monetize events — deeply embedded in their community's groups, activity feeds, and member profiles — without needing a third-party events plugin.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Event creation: in-person, virtual, and recurring event types
- [ ] Multiple ticket tiers per event (early bird, VIP, general, custom)
- [ ] Stripe Connect integration — ticket revenue goes to organizer, BuddyBoss takes application fee commission
- [ ] Tiered commission rates based on site admin's BuddyBoss plan (free > pro > plus > ultimate)
- [ ] Admin control panel: configure who can create events, commission rates, payment settings
- [ ] Main site-wide calendar view
- [ ] Group-specific calendar — each BuddyBoss group gets its own events/calendar tab
- [ ] Activity feed integration — event creation, RSVPs, ticket purchases post to BuddyBoss activity feeds
- [ ] Group member invites — organizers can invite group members directly from group roster
- [ ] Member profile integration — events attended/hosted visible on member profiles
- [ ] Admin-configurable event creation permissions (admins only / group organizers / all members / tiered by plan)

### Out of Scope

- Mobile app — web/WordPress plugin first
- Custom streaming infrastructure — use external links (Zoom, Meet) for virtual events
- Offline/cash payments — Stripe only for v1

## Context

- Built as a WordPress plugin distributed through BuddyBoss's ecosystem
- Requires BuddyBoss (the platform plugin) to be installed — this is a BuddyBoss add-on
- Net new functionality: no existing events solution to replace or migrate from
- Commission percentages TBD — plugin must support configurable rates per plan tier
- Stripe Connect application fees are the mechanism for platform commission capture

## Constraints

- **Tech Stack**: WordPress plugin architecture (PHP, WP hooks/filters, REST API)
- **Dependency**: BuddyBoss Platform plugin must be active — hooks into BP groups, activity, profiles
- **Payments**: Stripe Connect only — organizer connects their Stripe account, BuddyBoss takes application fee
- **Distribution**: Commercial plugin sold through BuddyBoss — must meet WP plugin quality standards
- **Commission model**: Rates are configurable but tiered by BuddyBoss plan (free/pro/plus/ultimate)

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Stripe Connect application fees for commission | Cleanest way to split revenue — organizer gets paid directly, BuddyBoss takes cut automatically | — Pending |
| BuddyBoss add-on (not standalone) | Deep integration is the differentiator vs The Events Calendar et al | — Pending |
| Full BuddyBoss integration in v1 | Integration IS the product — partial integration weakens the value proposition | — Pending |

---
*Last updated: 2026-03-10 after initialization*
