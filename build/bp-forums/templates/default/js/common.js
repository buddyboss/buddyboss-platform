jQuery(document).ready( function() {
    var input = document.querySelector('input[name=bbp_topic_tags]');
    tagify = new Tagify( input );

    // "remove all tags" button event listener
    document.querySelector('.js-modal-close').addEventListener('click', tagify.removeAllTags.bind(tagify))
});