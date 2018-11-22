$ = jQuery;
jQuery( document ).ready( function ( $ ) {
	/**
	 * Fire when user change the course or member dropdown.
	 */
	$( '.ls-bp-group-reports .ls_bp_member_id, .ls-bp-group-reports .ls_bp_courses_id' ).change( function () {
		ld_bp_group_courses_report_update_sub_menu();
	} );


	/**
	 * Fire when user click on export CSV button
	 */
	$( '.ls-bp-group-courses-export-csv a.export-csv' ).click( function () {

		var $this = this;

		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'ls_bp_group_courses_export_csv',
				csv: $( this ).closest( '.ls-bp-group-courses-export-csv' ).find( '.csv' ).val()
			},
			success: function ( response ) {

				if ( typeof response.status == "undefined" ) {
					var blobby = new Blob( [response], {type: 'text/csv'} );

					$( ls_bp_group_courses_export_csv_download ).attr( {
						'download': $( $this ).data( 'filename' ),
						'href': window.URL.createObjectURL( blobby ),
						'target': '_blank'
					} );

					ls_bp_group_courses_export_csv_download.click();
				} else {
					alert( ld_bp_courses_reports.export_csv_error );
				}
			}
		} );


		return false;
	} );
} );


function ld_bp_group_courses_report_update_sub_menu() {

	var member_id = $( '.ls-bp-group-reports .ls_bp_member_id' ).val(),
		courses_id = $( '.ls-bp-group-reports .ls_bp_courses_id' ).val();

	$( '.ls-bp-courses-menu li.selected a' ).attr( 'href', $( '.ls-bp-courses-menu li.selected a' ).attr( 'url' ) + '&courses_id=' + courses_id + '&student_id=' + member_id );
	$( '.ls-bp-courses-menu li.selected a' ).trigger( "click" );
	$( '.ls-bp-courses-menu li.selected a' )[0].click();


}