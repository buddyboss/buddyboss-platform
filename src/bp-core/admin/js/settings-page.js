(function() {
	var $ = jQuery.noConflict();

	$(function() {
		$("[data-run-js-condition]").each(function() {
			var id = $(this).attr('id');
			var tag= $(this).prop('tagName');

			$(this).on('change.run-condition', function(e) {
				if (tag == 'SELECT' && $(this).is(":visible")) {
					var selector = id + '-' + $(this).val();
					$("[class*='js-show-on-" + id + "-']:not(.js-show-on-" + selector + ")").hide();
					$(".js-show-on-" + selector).show();
					return true;
				}

				var checked = $(this).prop('checked');
				if (checked) {
					$(".js-hide-on-" + id).hide();
					$(".js-show-on-" + id).show();
				} else {
					$(".js-hide-on-" + id).show();
					$(".js-show-on-" + id).hide();
				}
			}).trigger('change.run-condition');
		});
	});
}());
