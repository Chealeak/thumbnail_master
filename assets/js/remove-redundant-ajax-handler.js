jQuery(document).ready(function() {
    jQuery('.th_m_remove-redundant-button-js').click(removeRedundant);
    jQuery('.th_m_remove-redundant-single-button-js').click(removeRedundant);
});

function removeRedundant(event) {
    event.preventDefault();

    const removeButton = jQuery('.' + remove_redundant_ajax_handler.prefix + 'remove-redundant-button-js');
    const resultElement = jQuery('.' + remove_redundant_ajax_handler.prefix + 'remove-redundant-result-js');
    const page = resultElement.attr('data-page');

    const thumbnailName = jQuery(this).attr('data-thumbnail-name');
    const thumbnailNameActionPart = thumbnailName ? ('&thumbnailName=' + thumbnailName) : '';

    jQuery.ajax({
        url: remove_redundant_ajax_handler.ajaxurl,
        type: 'POST',
        data: 'action=' + remove_redundant_ajax_handler.prefix + 'remove_redundant_thumbnails&page=' + page + thumbnailNameActionPart,
        dataType: 'json',
        beforeSend: function(xhr) {
            if (parseInt(page) !== 1) {
                removeButton.text('Removing...');
            }
        },
        success: function(data) {
            if (!data['completed']) {
                resultElement.attr('data-page', parseInt(page) + 1);
                removeRedundant(event);
            } else {
                removeButton.text('Remove');
                resultElement.text('Done!');
            }
        }
    });
}