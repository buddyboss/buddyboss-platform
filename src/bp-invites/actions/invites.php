<?php
// @bbwt-com-start compat-layer
// bbwt-rename:file-stub — this file now lives at src/bboss-invites/actions/invites.php (decisions 0009/0011/0015); kept so code that
// requires the old path keeps working on buddyboss.com sites. Not part of the WordPress.org build.
// Kept for: API plugin, class-bp-rest-invites-endpoint.php: require when bp_invites_kses_allowed_tags() is not loaded, which is every request outside the invites screen.
require_once __DIR__ . '/../../bboss-invites/actions/invites.php';
// @bbwt-com-end compat-layer
