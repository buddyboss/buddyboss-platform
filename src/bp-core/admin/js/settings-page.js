(function() {
	var $ = jQuery.noConflict();

	$(function() {
		$("[data-run-js-condition]").each(function() {
			var id = $(this).data('run-js-condition');
			var tag= $(this).prop('tagName');

			$(this).on('change.run-condition', function(e) {
				if (tag == 'SELECT' && $(this).is(":visible")) {
					var selector = id + '-' + $(this).val();
					$("[class*='js-show-on-" + id + "-']:not(.js-show-on-" + selector + ")").hide();
					$(".js-show-on-" + selector).show().find('[data-run-js-condition]').trigger('change.run-condition');
					return true;
				}

				var checked = $(this).prop('checked');
				if (checked && $(this).is(":visible")) {
					$(".js-hide-on-" + id).hide();
					$(".js-show-on-" + id).show().find('[data-run-js-condition]').trigger('change.run-condition');
				} else {
					$(".js-hide-on-" + id).show();
					$(".js-show-on-" + id).hide().find('[data-run-js-condition]').trigger('change.run-condition');
				}
			}).trigger('change.run-condition');
		});
	});
}());
