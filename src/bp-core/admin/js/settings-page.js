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

		var bpPages = $('body.buddypress.buddyboss_page_bp-pages .card').length;
		if ( bpPages > 1 ) {
			$('.create-background-page').click(function () {
				var dataPage = $(this).attr('data-name');

				$.ajax({
					'url' : BP_ADMIN.ajax_url,
					'method' : 'POST',
					'data' : {
						'action' : 'bp_core_admin_create_background_page',
						'page' : dataPage
					},
					'success' : function() {
						window.location.reload( true );
					}
				});
			});
		}

		// Set active class on Integration tab while /wp-admin/admin.php?page=bp-appboss page.
		if ( $('body.buddypress.buddyboss_page_bp-appboss').length ) {
			$('body.buddypress.buddyboss_page_bp-appboss #wpwrap #wpcontent #wpbody #wpbody-content .wrap .nav-tab-wrapper .bp-integrations').addClass('nav-tab-active');
		}

	});
}());
