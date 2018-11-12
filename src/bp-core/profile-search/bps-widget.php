<?php

add_action ('widgets_init', 'bps_widget_init');
function bps_widget_init ()
{
	register_widget ('bps_widget');
}

class bps_widget extends WP_Widget
{
	function __construct ()
	{
		$widget_ops = array ('description' => __('A Profile Search form.', 'buddyboss'));
		parent::__construct ('bps_widget', __('Profile Search', 'buddyboss'), $widget_ops);
	}

	function widget ($args, $instance)
	{
		extract ($args);
		$title = apply_filters ('widget_title', $instance['title']);
		$form = $instance['form'];

		echo $before_widget;
		if ($title)
			echo $before_title. $title. $after_title;
		bps_display_form ($form, 'widget');
		echo $after_widget;
	}

	function update ($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['form'] = $new_instance['form'];
		return $instance;
	}

	function form ($instance)
	{
		$title = isset ($instance['title'])? $instance['title']: '';
		$form = isset ($instance['form'])? $instance['form']: '';
?>
	<p>
		<label for="<?php echo $this->get_field_id ('title'); ?>"><?php _e('Title:', 'buddyboss'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id ('title'); ?>" name="<?php echo $this->get_field_name ('title'); ?>" type="text" value="<?php echo esc_attr ($title); ?>" />
	</p>
	<p>
		<label for="<?php echo $this->get_field_id ('form'); ?>"><?php _e('Form:', 'buddyboss'); ?></label>
<?php
		$posts = get_posts (array ('post_type' => 'bps_form', 'orderby' => 'ID', 'order' => 'ASC', 'nopaging' => true));
		if (count ($posts))
		{
			echo "<select class='widefat' id='{$this->get_field_id ('form')}' name='{$this->get_field_name ('form')}'>";
			foreach ($posts as $post)
			{
				$id = $post->ID;
				$name = !empty ($post->post_title)? $post->post_title: __('(no title)');
				echo "<option value='$id'";
				if ($id == $form)  echo " selected='selected'";
				echo ">$name &nbsp;</option>\n";
			}
			echo "</select>";
		}
		else
		{
			echo '<br/>';
			_e('You have not created any form yet.', 'buddyboss');
		}
?>
	</p>
<?php
	}
}
