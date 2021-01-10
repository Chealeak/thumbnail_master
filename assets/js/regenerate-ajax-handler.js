jQuery(document).ready(function() {
    jQuery('.th_m_regenerate-button-js').click(function () {
        var bar = new ldBar("#th_m_progressbar");

        jQuery.ajax({
            url: regenerate_ajax_handler.ajaxurl,
            type: 'POST',
            data: 'action=th_m_regenerate_thumbnails&testParam=' + regenerate_ajax_handler.testParamName,
            beforeSend: function(xhr) {
                jQuery('.th_m_regenerate-button-js').text('Generating...');
            },
            success: function(data) {
                jQuery('.th_m_regenerate-button-js').text('Generate');
            },
            xhr: function() {
                var xhr = new XMLHttpRequest();
                xhr.onprogress = function(event) {
                    if (event.lengthComputable === false) {
                        bar.set((event.loaded / event.total) * 100);
                    } else {
                        bar.set(event.loaded);
                    }
                };
                return xhr;
            }
        });
    });
});