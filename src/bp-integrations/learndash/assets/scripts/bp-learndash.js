(function($) {
	var BP_LD_Report = {
		$report_tables  : null,
		$report_selects : null,

		init: function() {
			this.$report_tables  = jQuery('.bp_ld_report_table');

			this.fetch_table_data();
		},

		fetch_table_data: function() {
			this.$report_tables.each(this.fetch_data.bind(this));
		},

		fetch_data: function( i, table ) {
			var type =  $("[data-report-filter='step']").val();
			var columns = BP_LD_REPORTS_DATA.table_columns[type];

			var args = {
				// data: response.data.results,
				columns      : this.adjustTableColumns(columns, table),
				processing   : true,
				serverSide   : true,
				searching    : false,
				lengthChange : false,
				info         : false,
				pageLength   : BP_LD_REPORTS_DATA.config.perpage,
				language     : {
					processing : BP_LD_REPORTS_DATA.text.processing,
					emptyTable : BP_LD_REPORTS_DATA.text.emptyTable,
					paginate: {
						first    : BP_LD_REPORTS_DATA.text.paginate_first,
						last     : BP_LD_REPORTS_DATA.text.paginate_last,
						next     : BP_LD_REPORTS_DATA.text.paginate_next,
						previous : BP_LD_REPORTS_DATA.text.paginate_previous,
				    },
				},
	        	ajax: {
					url  : BP_LD_REPORTS_DATA.ajax_url,
					type : 'POST',
					data : function(d) {
						$("[data-report-filter]").each(function() {
							var name = $(this).data('report-filter');
							d[name]  = $(this).val();
						});

						d.nonce     = BP_LD_REPORTS_DATA.nonce;
						d.action    = 'bp_ld_group_get_reports';
						d.group     = BP_LD_REPORTS_DATA.current_group;
						d.completed = $(table).data('completed')? 1 : 0;
					}
				}
			};

			$(table)
				.on('xhr.dt', function(e, settings, json, xhr) {
					if (json.data.length > 0) {
						$(e.target).closest('.bp_ld_report_table_wrapper').removeClass('no-data hidden').addClass('has-data');
					} else {
						$(e.target).closest('.bp_ld_report_table_wrapper').removeClass('has-data').addClass('no-data hidden');
					}
				})
				.DataTable(args);
		},

		adjustTableColumns: function(columns, table) {
			var removedKey = $(table).data('completed')? 'updated_date' : 'completion_date';
			var newColumns = [];

			$(columns).each(function(i, column) {
				if (column.name != removedKey) {
					newColumns.push(column);
				}
			});

			return newColumns;
		}
	}

	$.fn.dataTable.ext.classes.sPageButton = 'button';

	jQuery( document ).ready( function ( $ ) {
		BP_LD_Report.init();
	} );
})(jQuery);
