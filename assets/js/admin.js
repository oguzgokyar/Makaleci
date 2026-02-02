jQuery(document).ready(function($) {
    $('#wpaisg-generator-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $result = $('#wpaisg-result');
        var $submitBtn = $form.find('button[type="submit"]');

        // Clear previous results
        $result.html('').removeClass('notice notice-success notice-error');
        $submitBtn.prop('disabled', true).text('Oluşturuluyor... (Bu işlem 30-60sn sürebilir)');

        var data = {
            action: 'wpaisg_generate_content',
            nonce: wpaisg_ajax.nonce,
            service: $('#wpaisg-service').val(),
            location: $('#wpaisg-location').val(),
            keywords: $('#wpaisg-keywords').val(),
            category: $('#wpaisg-category').val()
        };

        $.post(wpaisg_ajax.ajax_url, data, function(response) {
            $submitBtn.prop('disabled', false).text('İçerik Oluştur');

            if (response.success) {
                var editUrl = response.data.edit_url;
                var message = '<strong>Başarılı!</strong> Yazı taslak olarak oluşturuldu. <a href="' + editUrl + '" target="_blank">Düzenlemek için tıklayın</a>';
                $result.html('<p>' + message + '</p>').addClass('notice notice-success');
            } else {
                var errorMsg = response.data.message || 'Bir hata oluştu.';
                $result.html('<p>Hata: ' + errorMsg + '</p>').addClass('notice notice-error');
            }
        }).fail(function() {
            $submitBtn.prop('disabled', false).text('İçerik Oluştur');
            $result.html('<p>Sunucu hatası veya zaman aşımı.</p>').addClass('notice notice-error');
        });
    });
});
