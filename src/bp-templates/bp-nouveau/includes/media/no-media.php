<aside class="bp-feedback bp-messages info">
    <span class="bp-icon" aria-hidden="true"></span>
    <p><?php ( bp_is_active( 'video' ) && ( bp_is_profile_video_support_enabled() && bp_is_user_albums() ) || ( bp_is_group_video_support_enabled() && bp_is_group_albums() ) ) ? esc_html_e( 'Sorry, no photos or videos were found.', 'buddyboss' ) : esc_html_e( 'Sorry, no photos were found.', 'buddyboss' ); ?></p>
</aside>
