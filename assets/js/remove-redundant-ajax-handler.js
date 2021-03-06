jQuery(document).ready(function() {
    jQuery('.th_m_remove-redundant-button-js').click(function (event) {
        event.preventDefault();
        sendAjaxQuery();
    });
});

function sendAjaxQuery() {
    let removeButton = jQuery('.' + remove_redundant_ajax_handler.prefix + 'remove-redundant-button-js');
    let resultElement = jQuery('.' + remove_redundant_ajax_handler.prefix + 'remove-redundant-result-js');
    let page = resultElement.attr('data-page');

    jQuery.ajax({
        url: remove_redundant_ajax_handler.ajaxurl,
        type: 'POST',
        data: 'action=' + remove_redundant_ajax_handler.prefix + 'remove_redundant_thumbnails&page=' + page,
        dataType: 'json',
        beforeSend: function(xhr) {
            if (parseInt(page) !== 1) {
                removeButton.text('Removing...');
            }
        },
        success: function(data) {
            if (!data['completed']) {
                resultElement.attr('data-page', parseInt(page) + 1);
                sendAjaxQuery();
            } else {
                removeButton.text('Remove');
                resultElement.text('Done!');
            }
        }
    });
}