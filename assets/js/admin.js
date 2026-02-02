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
                var content = response.data.content || '';
                var postId = response.data.post_id;

                var message = '<h3>Taslak Oluşturuldu!</h3>' +
                    '<p><strong>' + title + '</strong></p>' +
                    '<p><a href="' + editUrl + '" target="_blank" class="button button-primary">WordPress\'te Aç</a></p>';
                $result.html(message).addClass('wpaisg-result-box wpaisg-success-box');

                // Load into editor
                $('#wpaisg-editor-title').text(title);
                $('#wpaisg-current-post-id').val(postId);

                // Set content in TinyMCE
                if (typeof tinymce !== 'undefined' && tinymce.get('wpaisg_editor')) {
                    tinymce.get('wpaisg_editor').setContent(content);
                } else {
                    $('#wpaisg_editor').val(content);
                }

                $('#wpaisg-editor-section').slideDown();
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

    // Perform Update Handler
    $(document).on('click', '.wpaisg-perform-update', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var zipUrl = $btn.data('zip');
        var $result = $('#wpaisg-update-result');

        if (!confirm('Eklenti güncellenecek ve sayfa yenilenecek. Onaylıyor musunuz?')) {
            return;
        }

        $btn.prop('disabled', true).html('<span class="wpaisg-spinner"></span> Güncelleniyor...');

        // Visual feedback stages
        $result.append('<p class="exclude-result"><em>Paket indiriliyor...</em></p>');

        $.post(wpaisg_ajax.ajax_url, {
            action: 'wpaisg_perform_update',
            nonce: wpaisg_ajax.nonce,
            zip_url: zipUrl
        }, function (response) {
            if (response && response.success) {
                $result.find('.exclude-result').remove();
                $result.append('<p style="color:green; font-weight:bold;">' + response.data.message + '</p>');
                setTimeout(function () {
                    location.reload();
                }, 2000);
            } else {
                var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Güncelleme hatası.';
                $result.html('<div class="wpaisg-result-box wpaisg-error-box" style="padding:10px; margin:0;"><p>' + errorMsg + '</p></div>');
                $btn.prop('disabled', false).text('Tekrar Dene');
            }
        }).fail(function () {
            $result.html('<div class="wpaisg-result-box wpaisg-error-box" style="padding:10px; margin:0;"><p>Sunucu hatası (Zaman aşımı olabilir).</p></div>');
            $btn.prop('disabled', false).text('Tekrar Dene');
        });
    });

    // Test API Handler
    $('#wpaisg-test-api').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $result = $('#wpaisg-api-test-result');
        var originalText = $btn.text();

        $btn.prop('disabled', true).html('<span class="wpaisg-spinner"></span>');
        $result.html('');

        $.post(wpaisg_ajax.ajax_url, {
            action: 'wpaisg_test_api',
            nonce: wpaisg_ajax.nonce
        }, function (response) {
            $btn.prop('disabled', false).text(originalText);

            if (response && response.success) {
                $result.html('<span style="color: green;">' + response.data.message + '</span>');
            } else {
                var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Hata oluştu.';
                $result.html('<span style="color: red;">Hata: ' + (response.data ? response.data.message : 'Bilinmeyen hata') + '</span>');
            }
        }).fail(function () {
            $btn.prop('disabled', false).text(originalText);
            $result.html('<span style="color: red;">Sunucu ile bağlantı kurulamadı.</span>');
        });
    });

    // Save Content Button
    $('#wpaisg-save-content').on('click', function () {
        var $btn = $(this);
        var originalText = $btn.text();
        var postId = $('#wpaisg-current-post-id').val();
        var title = $('#wpaisg-editor-title').text();
        var content = '';

        // Get content from TinyMCE
        if (typeof tinymce !== 'undefined' && tinymce.get('wpaisg_editor')) {
            content = tinymce.get('wpaisg_editor').getContent();
        } else {
            content = $('#wpaisg_editor').val();
        }

        $btn.prop('disabled', true).text('Kaydediliyor...');

        $.post(wpaisg_ajax.ajax_url, {
            action: 'wpaisg_update_post',
            nonce: wpaisg_ajax.nonce,
            post_id: postId,
            title: title,
            content: content
        }, function (response) {
            $btn.prop('disabled', false).text(originalText);

            if (response && response.success) {
                $('#wpaisg-save-result').html('<span style="color: green;">' + response.data.message + '</span>');
                setTimeout(function () {
                    $('#wpaisg-save-result').html('');
                }, 3000);
            } else {
                $('#wpaisg-save-result').html('<span style="color: red;">Hata: ' + (response.data ? response.data.message : 'Bilinmeyen hata') + '</span>');
            }
        }).fail(function () {
            $btn.prop('disabled', false).text(originalText);
            $('#wpaisg-save-result').html('<span style="color: red;">Sunucu ile bağlantı kurulamadı.</span>');
        });
    });

    // Open in WordPress Editor Button
    $('#wpaisg-open-editor').on('click', function () {
        var postId = $('#wpaisg-current-post-id').val();
        if (postId) {
            window.open(wpaisg_ajax.ajax_url.replace('admin-ajax.php', 'post.php?post=' + postId + '&action=edit'), '_blank');
        }
    });
});
