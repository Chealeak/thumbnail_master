jQuery(document).ready(function() {
    jQuery('.th_m_regenerate-button-js').click(function () {
        jQuery.ajax({
            url: regenerate_ajax_handler.ajaxurl,
            type: 'POST',
            data: 'action=th_m_regenerate_thumbnails&testParam=' + regenerate_ajax_handler.testParamName,
            beforeSend: function(xhr) {
                jQuery('.th_m_regenerate-button-js').text('Generating...');
            },
            success: function(data) {
                jQuery('.th_m_regenerate-button-js').text('Generate');
                //alert( data );
            }
        });
    });
});