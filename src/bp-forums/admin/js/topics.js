/* global wp */
jQuery( document ).ready(
	function() {
			jQuery( '#misc-publishing-actions' ).find( '.misc-pub-section' ).first().remove();
			jQuery( '#save-action' ).remove();
	}
);

window.addEventListener('DOMContentLoaded', function() {
	// Ensure the Gutenberg editor is available.
	if ('undefined' !== typeof wp.data && 'undefined' !== typeof wp.data.subscribe) {
		var wasSaving = false;
		wp.data.subscribe( function() {
			var isSaving = wp.data.select('core/edit-post').isSavingMetaBoxes();

			// Started finished saving. 
			if ( wasSaving && ! isSaving ) {
				var old_parent_id = document.querySelector('#bbp_topic_attributes #old_parent_id').value;
				var parent_id = document.querySelector('#bbp_topic_attributes #parent_id option:checked').value;

				// Check if the meta value has changed.
				if ( old_parent_id !== parent_id ) {
					location.reload();
				}
			}
			wasSaving = isSaving;
		});
	}
});
