<div class="wrap">
    <h2>
        Memberships Logs
    </h2>
   <a href="admin.php?page=bp-integrations" class="">Back to BuddyBoss</a>
    <div id="poststuff">
        <div class="metabox-holder columns-2" id="post-body">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="post">
                    <?php
$classObj->events_obj->prepare_items();
$classObj->events_obj->display();
?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
        </br>
    </div>
</div>
