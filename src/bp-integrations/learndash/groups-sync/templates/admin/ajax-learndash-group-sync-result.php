<tr>
    <td><?php echo $id; ?></td>
    <td><?php echo get_the_title($id); ?></td>
    <td colspan="2">
        <?php
            printf(
                '<span style="color: #3c763d;">%s %s</span>',
                __('BuddyBoss group associated:', 'buddyboss'),
                sprintf(
                    '<a href="%s" target="_blank">%s (ID: %d)</a>',
                    bp_get_admin_url("admin.php?page=bp-groups&gid={$result->id}&action=edit"),
                    $result->name,
                    $result->id
                )
            );
        ?>
    </td>
    <td>
        <span class="dashicons dashicons-yes"  style="color: #3c763d;"></span>
    </td>
</tr>
