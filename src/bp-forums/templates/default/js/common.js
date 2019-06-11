jQuery(document).ready( function() {
    if ( typeof window.Tagify !== 'undefined' ) {
        var input = document.querySelector('input[name=bbp_topic_tags]');

        if ( input != null ) {
            var tagify = new window.Tagify(input);

            // "remove all tags" button event listener
            jQuery('.js-modal-close').on('click', tagify.removeAllTags.bind(tagify));
        }
    }
});