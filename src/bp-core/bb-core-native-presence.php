<?php
// @bbwt-com-start compat-layer
// bbwt-rename:file-stub — this file now lives at src/bboss-core/bboss-core-native-presence.php (decisions 0009/0011/0015); kept so code that
// requires the old path keeps working on buddyboss.com sites. Not part of the WordPress.org build.
// Kept for: API plugin settings endpoint hands the App this URL as the native presence endpoint; a forwarder avoids a redirect on every heartbeat.
require_once __DIR__ . '/../bboss-core/bboss-core-native-presence.php';
// @bbwt-com-end compat-layer
