jQuery(document).ready(function ($) {
    // Generate Content Handler
    $('#wpaisg-generator-form').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $result = $('#wpaisg-result');
        var $submitBtn = $form.find('button[type="submit"]');
        var originalBtnText = $submitBtn.text();

        // Clear previous results
        $result.html('').removeClass('notice notice-success notice-error wpaisg-result-box wpaisg-success-box wpaisg-error-box');

        // Show loading state
        $submitBtn.prop('disabled', true).html('<span class="wpaisg-spinner"></span> Oluşturuluyor...');

        var data = {
            action: 'wpaisg_generate_content',
            nonce: wpaisg_ajax.nonce,
            service: $('#wpaisg-service').val(),
            location: $('#wpaisg-location').val(),
            keywords: $('#wpaisg-keywords').val(),
            category: $('#wpaisg-category').val()
        };

        $.post(wpaisg_ajax.ajax_url, data, function (response) {
            $submitBtn.prop('disabled', false).text(originalBtnText);

            if (response && response.success) {
                var editUrl = response.data.edit_url;
                var title = response.data.title || 'Yeni Yazı';
                var message = '<h3>Taslak Oluşturuldu!</h3>' +
                    '<p><strong>' + title + '</strong></p>' +
                    '<p><a href="' + editUrl + '" target="_blank" class="button button-primary">Taslağı Düzenle</a></p>';
                $result.html(message).addClass('wpaisg-result-box wpaisg-success-box');
            } else {
                var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Bilinmeyen bir hata oluştu.';
                if (typeof response === 'string') {
                    console.error('Sunucu Yanıtı:', response);
                    errorMsg += ' (Sunucu geçersiz yanıt döndürdü)';
                }
                $result.html('<p><strong>Hata:</strong> ' + errorMsg + '</p>').addClass('wpaisg-result-box wpaisg-error-box');
            }
        }).fail(function (xhr, status, error) {
            $submitBtn.prop('disabled', false).text(originalBtnText);
            $result.html('<p><strong>Bağlantı Hatası:</strong> İşlem zaman aşımına uğramış veya sunucu hatası oluşmuş olabilir.</p>').addClass('wpaisg-result-box wpaisg-error-box');
        });
    });

    // Check Updates Handler
    $('#wpaisg-check-updates').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $result = $('#wpaisg-update-result');
        var originalText = $btn.text();

        $btn.prop('disabled', true).html('<span class="wpaisg-spinner"></span> Kontrol Ediliyor...');
        $result.html('');

        $.post(wpaisg_ajax.ajax_url, {
            action: 'wpaisg_check_updates',
            nonce: wpaisg_ajax.nonce
        }, function (response) {
            $btn.prop('disabled', false).text(originalText);

            if (response && response.success) {
                var data = response.data;
                var classBox = (data.status === 'update_available') ? 'wpaisg-info-box' : 'wpaisg-success-box';
                var html = '<div class="wpaisg-result-box ' + classBox + '" style="padding:10px; margin:0;">' +
                    '<p>' + data.message + '</p>';

                if (data.update_url) {
                    html += '<p><a href="' + data.update_url + '" class="button button-primary">Güncelleme Sayfasına Git</a></p>';
                }
                html += '</div>';

                $result.html(html);
            } else {
                var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Hata oluştu.';
                $result.html('<div class="wpaisg-result-box wpaisg-error-box" style="padding:10px; margin:0;"><p>' + errorMsg + '</p></div>');
            }
        }).fail(function () {
            $btn.prop('disabled', false).text(originalText);
            $result.html('<div class="wpaisg-result-box wpaisg-error-box" style="padding:10px; margin:0;"><p>Sunucu hatası.</p></div>');
        });
    });
});
