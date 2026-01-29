<form class="bp-learndash-reports-filters-form">
	<?php foreach ( $filters as $name => $filter ) : ?>
		<span class="bp-learndash-reports-filters">
			<select name="<?php echo $name; ?>" data-report-filter="<?php echo $name; ?>">
				<?php foreach ( $filter['options'] as $key => $value ) : ?>
					<?php $selected = isset( $_GET[ $name ] ) && $_GET[ $name ] == $key ? 'selected' : ''; ?>
					<option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	<?php endforeach; ?>

	<button class="button" type="submit"><?php _e( 'Filter', 'buddyboss' ); ?></button>
</form>
