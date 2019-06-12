jQuery(document).ready( function() {
    if ( typeof window.Tagify !== 'undefined' ) {
        var input = document.querySelector('input[name=bbp_topic_tags]');

        if ( input != null ) {
            var tagify = new window.Tagify(input);

            // "remove all tags" button event listener
            jQuery( 'body' ).on('click', '.js-modal-close', tagify.removeAllTags.bind(tagify));
        }
    }
});