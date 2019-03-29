<?php
/**
 * BP Nouveau Messages main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with user's private messages.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>
<div class="subnav-filters filters user-subnav bp-messages-filters" id="subsubnav"></div>

<input type="hidden" id="thread-id" value="" />
<div class="bp-messages-feedback"></div>
<div class="bp-messages-threads-list"></div>
<div class="bp-messages-content"></div>

<?php
    /**
     * Split each js template to its own file. Easier for child theme to
     * overwrite individual parts.
     *
     * @version Buddyboss 1.0.0
     */
    $template_parts = apply_filters( 'bp_messages_js_template_parts', [
        'parts/bp-message-feedback.php',
        'parts/bp-message-hook.php',
        'parts/bp-messages-form.php',
        'parts/bp-messages-editor.php',
        'parts/bp-messages-paginate.php',
        'parts/bp-messages-filters.php',
        'parts/bp-messages-thread.php',
        'parts/bp-messages-single-header.php',
        'parts/bp-messages-single-load-more.php',
        'parts/bp-messages-single-list.php',
        'parts/bp-messages-single.php',
    ] );

    foreach ( $template_parts as $template_part ) {
        bp_get_template_part( 'common/js-templates/messages/' . $template_part );
    }
?>







