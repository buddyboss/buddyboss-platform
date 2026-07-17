<?php
/**
 * BP Profile Search - default template
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// 1st section: set the default value of the template options

if ( ! isset( $options['theme'] ) ) {
	$options['theme'] = 'base';
}
if ( ! isset( $options['collapsible'] ) ) {
	$options['collapsible'] = 'Yes';
}

// 2nd section: display the form to select the template options

if ( is_admin() ) {
	?>
	<p><strong><?php esc_html_e( 'jQuery UI Theme', 'buddyboss-platform' ); ?></strong></p>
	<select name="options[theme]">
	<?php foreach ( bp_ps_jquery_ui_themes() as $theme => $name ) { ?>
		<option value="<?php echo esc_attr( $theme ); ?>" <?php selected( $options['theme'], $theme ); ?>><?php echo esc_html( $name ); ?></option>
	<?php } ?>
	</select>

	<p><strong><?php esc_html_e( 'Collapsible Form', 'buddyboss-platform' ); ?></strong></p>
	<select name="options[collapsible]">
		<option value='Yes' <?php selected( $options['collapsible'], 'Yes' ); ?>><?php esc_html_e( 'Yes', 'buddyboss-platform' ); ?></option>
		<option value='No' <?php selected( $options['collapsible'], 'No' ); ?>><?php esc_html_e( 'No', 'buddyboss-platform' ); ?></option>
	</select>
	<?php
	return 'end_of_options';
}

// 3rd section: display the search form

$F = bp_ps_escaped_form_data( $version = '4.9' );

if ( ! empty( $options['theme'] ) ) {
	$accordion = 'bp_ps_accordion_' . $F->unique_id;
	wp_enqueue_script( 'jquery-ui-accordion' );
	// phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- Legacy profile-search accordion theme; admin-selected jQuery-UI theme name. Bundling all jQuery-UI themes locally is a pending asset decision.
	wp_enqueue_style( 'jquery-ui-theme', 'https://code.jquery.com/ui/1.12.1/themes/' . esc_attr( $options['theme'] ) . '/jquery-ui.min.css' );
	?>
<script>
	jQuery(function($) {
		$('#<?php echo esc_js( $accordion ); ?>').accordion({
			icons: {"header": "ui-icon-plus", "activeHeader": "<?php echo ( $options['collapsible'] == 'Yes' ) ? 'ui-icon-minus' : 'ui-icon-blank'; ?>"},
			active: false,
			collapsible: <?php echo ( $options['collapsible'] == 'Yes' ) ? 'true' : 'false'; ?>,
		});
	});
</script>

<style>
	.bp-ps-form label {display: inline;}
	.bp-ps-form input {display: inline;}
</style>

<div id="<?php echo esc_attr( $accordion ); ?>">
	<span class="bp-ps-form-title"> <?php echo esc_html( $F->title ); ?></span>
	<?php
}
?>
	<form action="<?php echo esc_url( $F->action ); ?>" method="<?php echo esc_attr( $F->method ); ?>" id="<?php echo esc_attr( $F->unique_id ); ?>" class="bp-ps-form">

<?php
foreach ( $F->fields as $f ) {
	$id      = $f->unique_id;
	$name    = $f->html_name;
	$value   = $f->value;
	$display = $f->display;

	if ( $display == 'none' ) {
		continue;
	}
	if ( $display == 'hidden' ) {
		?>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $value pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?>">
		<?php
		continue;
	}
	?>
		<div id="<?php echo esc_attr( $id ); ?>_wrap" class="bp-ps-<?php echo esc_attr( $display ); ?>">
			<label for="<?php echo esc_attr( $id ); ?>" class="bp-ps-label"><?php echo wp_kses_post( $f->full_label ); ?></label><br>
	<?php
	switch ( $display ) {
		case 'range':
			?>
			<input type="text" style="width: 5em;" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[min]' ); ?>" value="<?php echo esc_attr( $value['min'] ); ?>">
			<span> - </span>
			<input type="text" style="width: 5em;" name="<?php echo esc_attr( $name . '[max]' ); ?>" value="<?php echo esc_attr( $value['max'] ); ?>"><br>
			<?php
			break;

		case 'range-select':
			?>
			<select style="width: 5em;" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[min]' ); ?>">
			<?php foreach ( $f->options as $option ) { ?>
				<option <?php selected( $value['min'], $option ); ?> value="<?php echo $option; ?>"><?php echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $option pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?> </option>
			<?php } ?>
			</select>
			<span> - </span>
			<select style="width: 5em;" name="<?php echo esc_attr( $name . '[max]' ); ?>">
			<?php foreach ( $f->options as $option ) { ?>
				<option <?php selected( $value['max'], $option ); ?> value="<?php echo $option; ?>"><?php echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $option pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?> </option>
			<?php } ?>
			</select><br>
			<?php
			break;

		case 'textbox':
			?>
			<input type="search" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $value pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?>"><br>
			<?php
			break;

		case 'number':
			?>
			<input type="number" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $value pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?>"><br>
			<?php
			break;

		case 'distance':
			$of          = __( 'of', 'buddyboss-platform' );
			$km          = __( 'km', 'buddyboss-platform' );
			$miles       = __( 'miles', 'buddyboss-platform' );
			$placeholder = __( 'Start typing, then select a location', 'buddyboss-platform' );
			$icon_url    = buddypress()->plugin_url . 'bp-core/profile-search/templates/members/locator.png';
			$icon_title  = __( 'get current location', 'buddyboss-platform' );
			?>
			<input type="number" min="1" style="width: 5em;" name="<?php echo esc_attr( $name . '[distance]' ); ?>" value="<?php echo esc_attr( $value['distance'] ); ?>">
			<select name="<?php echo esc_attr( $name . '[units]' ); ?>">
				<option value="km" <?php selected( $value['units'], 'km' ); ?>><?php echo esc_html( $km ); ?></option>
				<option value="miles" <?php selected( $value['units'], 'miles' ); ?>><?php echo esc_html( $miles ); ?></option>
			</select>
			<span><?php echo esc_html( $of ); ?></span>
			<input type="search" style="width: 90%;" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[location]' ); ?>" value="<?php echo esc_attr( $value['location'] ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>">
			<img id="<?php echo esc_attr( $id ); ?>_icon" style="cursor: pointer;" src="<?php echo esc_url( $icon_url ); ?>" title="<?php echo esc_attr( $icon_title ); ?>"><br>
			<input type="hidden" id="<?php echo esc_attr( $id ); ?>_lat" name="<?php echo esc_attr( $name . '[lat]' ); ?>" value="<?php echo esc_attr( $value['lat'] ); ?>">
			<input type="hidden" id="<?php echo esc_attr( $id ); ?>_lng" name="<?php echo esc_attr( $name . '[lng]' ); ?>" value="<?php echo esc_attr( $value['lng'] ); ?>">

			<script>
				jQuery(function($) {
					bp_ps_autocomplete('<?php echo esc_js( $id ); ?>', '<?php echo esc_js( $id ); ?>_lat', '<?php echo esc_js( $id ); ?>_lng');
					$('#<?php echo esc_js( $id ); ?>_icon').click(function () {
						bp_ps_locate('<?php echo esc_js( $id ); ?>', '<?php echo esc_js( $id ); ?>_lat', '<?php echo esc_js( $id ); ?>_lng')
					});
				});
			</script>
			<?php
			break;

		case 'selectbox':
			?>
			<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
			<?php foreach ( $f->options as $key => $label ) { ?>
				<option
				<?php
				if ( $key == $value ) {
					echo 'selected="selected"';}
				?>
				 value="<?php echo $key; ?>"><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $key/$label pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?> </option>
			<?php } ?>
			</select><br>
			<?php
			break;

		case 'multiselectbox':
			?>
			<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[]' ); ?>" multiple="multiple">
			<?php foreach ( $f->options as $key => $label ) { ?>
				<option
				<?php
				if ( in_array( $key, $f->values ) ) {
					echo 'selected="selected"';}
				?>
				 value="<?php echo $key; ?>"><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $key/$label pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?></option>
			<?php } ?>
			</select><br>
			<?php
			break;

		case 'radio':
			?>
			<?php foreach ( $f->options as $key => $label ) { ?>
				<label><input type="radio"
				<?php
				if ( $key == $value ) {
					echo 'checked="checked"';}
				?>
					name="<?php echo esc_attr( $name ); ?>" value="<?php echo $key; ?>"> <?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $key/$label pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?></label><br>
			<?php } ?>
			<a href="javascript:bp_ps_clear_radio('<?php echo esc_attr( $id ); ?>_wrap')"><?php esc_html_e( 'Clear', 'buddyboss-platform' ); ?></a><br>
			<?php
			break;

		case 'checkbox':
			?>
			<?php foreach ( $f->options as $key => $label ) { ?>
				<label><input type="checkbox"
				<?php
				if ( in_array( $key, $f->values ) ) {
					echo 'checked="checked"';}
				?>
					name="<?php echo esc_attr( $name . '[]' ); ?>" value="<?php echo $key; ?>"> <?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $key/$label pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?></label><br>
			<?php } ?>
			<?php
			break;

		default:
			?>
			<p class="bp-ps-error"><?php echo 'BP Profile Search: unknown display <em>' . esc_html( $display ) . '</em> for field <em>' . esc_html( $f->name ) . '</em>.'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static HTML wrapper with esc_html()-escaped interpolated values. ?></p>
			<?php
			break;
	}
	?>
			<em class="bp-ps-description"><?php echo $f->description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped via esc_attr() in bp_ps_escaped_form_data47(). ?></em>
		</div><br>
	<?php
}
?>
		<div>
			<input type="submit" value="<?php esc_html_e( 'Search', 'buddyboss-platform' ); ?>">
		</div>
	</form>

<?php
if ( ! empty( $options['theme'] ) ) {
	?>
</div><br>
	<?php
}

return 'end_of_template';
