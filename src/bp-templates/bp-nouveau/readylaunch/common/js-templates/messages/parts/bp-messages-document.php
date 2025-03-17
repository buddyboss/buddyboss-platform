<?php
/**
 * Readylaunch - Messages document template.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-messages-document">
    <div class="dropzone closed document-dropzone" id="messages-post-document-uploader"></div>
    <div class="message-post-document-template" style="display:none;">
        <div class="dz-preview dz-file-preview">
            <div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
            <div class="dz-details">
                <div class="dz-icon"><span class="bb-icon-l bb-icon-file"></span></div>
                <div class="dz-filename"><span data-dz-name></span></div>
                <div class="dz-size" data-dz-size></div>
            </div>
            <div class="dz-progress-ring-wrap">
                <i class="bb-icon-f bb-icon-file-attach"></i>
                <svg class="dz-progress-ring" width="54" height="54">
                    <circle class="progress-ring__circle" stroke="white" stroke-width="3" fill="transparent" r="24.5" cx="27" cy="27" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
                </svg>
            </div>
            <div class="dz-error-message"><span data-dz-errormessage></span></div>
        </div>
    </div>
</script>
