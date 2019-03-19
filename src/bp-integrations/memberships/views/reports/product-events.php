<div class="wrap">
    <h1 class="wp-heading-inline">
        BuddyBoss Membership - Product Events
    </h1>

    <div class="post-body-content">

		<table id="variations" class="display" cellspacing="0" width="100%">

	    <thead>
	        <tr>
	            <th>#</th>
	            <th>Product</th>
	            <th>User Id</th>
	            <th>Course attached</th>
	            <th>Action</th>
	            <th>Created at</th>
	            <th>Updated at</th>
	            <th>Identifier</th>
	        </tr>
	    </thead>

	    <tfoot>
	        <tr>
	            <th>#</th>
	            <th class="left">Product</th>
	            <th>User Id</th>
	            <th>Course attached</th>
	            <th>Action</th>
	            <th>Created at</th>
	            <th>Updated at</th>
	            <th>Identifier</th>
	        </tr>
	    </tfoot>

	    <tbody>
		<?php foreach ($productEvents as $eventIdentifier => $eventMeta) {
	?>
			<tr id="<?php echo $eventMeta["product_id"]; ?>">
				<td> <?php echo $eventMeta["product_id"]; ?> </td>
				<td><?php echo get_post($eventMeta["product_id"], OBJECT)->post_title . ":"; ?>
					<a target="_blank" href="post.php?post=<?php echo $eventMeta["product_id"]; ?>&action=edit">
					edit
					</a>
				</td>
				<td><?php echo $eventMeta['user_id']; ?></td>
				<td><?php foreach (unserialize($eventMeta['course_attached']) as $key => $courseId) {
		$course = get_post($courseId, OBJECT);?>
				<?php echo $course->post_title; ?>
				<a target="_blank" href="post.php?post=<?php echo $courseId; ?>&action=edit" >
				view</a><br/>
				<?php }?> </td>
				<td><?php echo $eventMeta['grant_access'] ? "Grant access" : "Revoke access"; ?></td>
				<td><?php echo $eventMeta['created_at']; ?></td>
				<td><?php echo $eventMeta['updated_at']; ?></td>
				<td><?php echo $eventIdentifier; ?></td>
			</tr>
			<?php }?>
	    </tbody>

	</table>

 <h2>
        Use smart search functionality
    </h2>
    <ul>
        <li>
            <span style="color:blue">
                Limit (Top-left) :
            </span>
            Limit number of entries. Ex : 10, 25, 50, 100...
        </li>
        <li>
            <span style="color:blue">
                Pagination (Bottom-right) :
            </span>
            Jump to any page or navigate via next or previous button
        </li>
        <li>
            <span style="color:blue">
                Sort :
            </span>
            Click on particular column title to sort
        </li>
        <li>
            <span style="color:blue">
                Global Search(Top-right) :
            </span>
            Search at row level
        </li>
    </ul>

    </div>
</div>
<script type="text/javascript">

	jQuery(document).ready(function() {
		var table = jQuery('#variations').DataTable({
			stateSave: true,
			lengthMenu: [ [10, 25, 50, 100, -1], [10, 25, 50,100, "All"] ]
		});
	});

</script>
<style type="text/css">

/* Datatable related */
table.dataTable thead .sorting { background: url(<?php echo BBMS_URL . '/assets/images/sort_both.png'; ?>) no-repeat center right; }

table.dataTable thead .sorting_asc { background: url(<?php echo BBMS_URL . '/assets/images/sort_asc.png'; ?>) no-repeat center right; }

table.dataTable thead .sorting_desc { background: url(<?php echo BBMS_URL . '/assets/images/sort_desc.png'; ?>) no-repeat center right; }

td.more-info-control, td.more-info-control1 {
    background: url(<?php echo BBMS_URL . '/assets/images/details_open.png'; ?>) no-repeat center center;
    cursor: pointer;
}
tr.more-info-control-shown td.more-info-control, tr.more-info-control1-shown td.more-info-control1 {
    background: url(<?php echo BBMS_URL . '/assets/images/details_close.png'; ?>) no-repeat center center;
}

</style>