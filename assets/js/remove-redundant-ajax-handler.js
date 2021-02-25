jQuery(document).ready(function() {
    jQuery('.th_m_remove-redundant-button-js').click(function (event) {
        event.preventDefault();

        jQuery.ajax({
            url: remove_redundant_ajax_handler.ajaxurl,
            type: 'POST',
            data: 'action=th_m_remove_redundant_thumbnails',
            beforeSend: function(xhr) {
                jQuery('.th_m_remove-redundant-button-js').text('Removing...');
            },
            success: function(data) {
                jQuery('.th_m_remove-redundant-button-js').text('Remove');
            }
        });
    });
});