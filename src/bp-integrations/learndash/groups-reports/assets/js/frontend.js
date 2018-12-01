$ = jQuery;
jQuery( document ).ready( function ( $ ) {
	/**
	 * Fire when user change the course or member dropdown.
	 */
	$( '.bp-learndash-group-reports .bp_learndash_member_id, .bp-learndash-group-reports .bp_learndash_courses_id' ).change( function () {
		ld_bp_group_courses_report_update_sub_menu();
	} );


	/**
	 * Fire when user click on export CSV button
	 */
	$( '.bp-learndash-group-courses-export-csv a.export-csv' ).click( function () {

		var $this = this;

		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'bp_learndash_group_courses_export_csv',
				csv: $( this ).closest( '.bp-learndash-group-courses-export-csv' ).find( '.csv' ).val()
			},
			success: function ( response ) {

				if ( typeof response.status == "undefined" ) {
					var blobby = new Blob( [response], {type: 'text/csv'} );

					$( bp_learndash_group_courses_export_csv_download ).attr( {
						'download': $( $this ).data( 'filename' ),
						'href': window.URL.createObjectURL( blobby ),
						'target': '_blank'
					} );

					bp_learndash_group_courses_export_csv_download.click();
				} else {
					alert( ld_bp_courses_reports.export_csv_error );
				}
			}
		} );


		return false;
	} );
} );


function ld_bp_group_courses_report_update_sub_menu() {

	var member_id = $( '.bp-learndash-group-reports .bp_learndash_member_id' ).val(),
		courses_id = $( '.bp-learndash-group-reports .bp_learndash_courses_id' ).val();

	$( '.bp-learndash-courses-menu li.selected a' ).attr( 'href', $( '.bp-learndash-courses-menu li.selected a' ).attr( 'url' ) + '&courses_id=' + courses_id + '&student_id=' + member_id );
	$( '.bp-learndash-courses-menu li.selected a' ).trigger( "click" );
	$( '.bp-learndash-courses-menu li.selected a' )[0].click();


}