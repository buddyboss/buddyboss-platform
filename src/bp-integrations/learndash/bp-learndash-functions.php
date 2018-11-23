<?php

function bp_learndash_path($path = '') {
    return trailingslashit( buddypress()->integrations['learndash']->path ) . trim($path, '/\\');
}

function bp_learndash_url($path = '') {
    return trailingslashit( buddypress()->integrations['learndash']->url ) . trim($path, '/\\');
}
