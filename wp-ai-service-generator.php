<?php
/**
 * Plugin Name: AI Hizmet Sayfası Oluşturucu (Local SEO Enhanced)
 * Plugin URI: https://github.com/Antigravity/wp-ai-service-generator
 * Description: Google Gemini API kullanarak Lokal SEO uyumlu, Schema destekli hizmet yazıları oluşturun.
 * Version: 1.0.0
 * Author: Antigravity
 * Text Domain: wp-ai-service-generator
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPAISG_VERSION', '1.0.7' );
define( 'WPAISG_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPAISG_URL', plugin_dir_url( __FILE__ ) );

class WPAIServiceGenerator {
	private static $instance = null;
	private $options;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Initialize hooks
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		
        add_action( 'wp_ajax_wpaisg_generate_content', array( $this, 'wpaisg_handle_ajax_generate' ) );
        add_action( 'wp_ajax_wpaisg_check_updates', array( $this, 'wpaisg_handle_ajax_check_updates' ) );
        add_action( 'wp_ajax_wpaisg_perform_update', array( $this, 'wpaisg_handle_ajax_perform_update' ) );
        add_action( 'wp_ajax_wpaisg_test_api', array( $this, 'wpaisg_handle_ajax_test_api' ) );
    }

    public function enqueue_admin_scripts( $hook ) {
        // Load on ALL pages that belong to this plugin (both Generator and Settings)
        if ( strpos( $hook, 'wp-ai-service-generator' ) === false ) {
            return;
        }

        wp_enqueue_style( 'wpaisg-admin-css', WPAISG_URL . 'assets/css/admin.css', array(), WPAISG_VERSION );
        wp_enqueue_script( 'wpaisg-admin-js', WPAISG_URL . 'assets/js/admin.js', array( 'jquery' ), WPAISG_VERSION, true );
        wp_localize_script( 'wpaisg-admin-js', 'wpaisg_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wpaisg-generate-nonce' )
        ) );
    }

	public function add_admin_menu() {
        // Top Level Menu
		add_menu_page(
			'AI Hizmet Oluşturucu',
			'AI Hizmet Oluşturucu',
			'manage_options',
			'wp-ai-service-generator',
			array( $this, 'render_dashboard_page' ),
			'dashicons-superhero',
			6
		);

        // Dashboard Submenu
        add_submenu_page(
            'wp-ai-service-generator',
            'Oluşturucu',
            'Oluşturucu',
            'manage_options',
            'wp-ai-service-generator',
            array( $this, 'render_dashboard_page' )
        );

        // Settings Submenu
		add_submenu_page(
			'wp-ai-service-generator',
			'Ayarlar',
			'Ayarlar',
			'manage_options',
			'wp-ai-service-generator-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		// General Settings
		register_setting( 'wpaisg_settings_group', 'wpaisg_gemini_api_key' );
		register_setting( 'wpaisg_settings_group', 'wpaisg_model' );
        register_setting( 'wpaisg_settings_group', 'wpaisg_language' );

		// NAP Settings
		register_setting( 'wpaisg_settings_group', 'wpaisg_company_name' );
		register_setting( 'wpaisg_settings_group', 'wpaisg_company_address' );
		register_setting( 'wpaisg_settings_group', 'wpaisg_company_phone' );

        // GitHub Settings
        register_setting( 'wpaisg_settings_group', 'wpaisg_github_repo' );
        register_setting( 'wpaisg_settings_group', 'wpaisg_github_token' );
	}

    public function wpaisg_handle_ajax_generate() {
        // Clear any previous output to ensure clean JSON response
        if (ob_get_length()) {
            ob_clean();
        }
        
        check_ajax_referer( 'wpaisg-generate-nonce', 'nonce' );

        $service = sanitize_text_field( $_POST['service'] );
        $location = sanitize_text_field( $_POST['location'] );
        $keywords = sanitize_text_field( $_POST['keywords'] );
        $category_id = intval( $_POST['category'] );

        // Get Settings
        $api_key = get_option( 'wpaisg_gemini_api_key' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => 'API Anahtarı eksik. Lütfen ayarlardan ekleyin.' ) );
        }

        $company_name = get_option( 'wpaisg_company_name' );
        $company_address = get_option( 'wpaisg_company_address' );
        $company_phone = get_option( 'wpaisg_company_phone' );
        $language = get_option( 'wpaisg_language', 'Turkish' );
        $company_phone = get_option( 'wpaisg_company_phone' );
        $language = get_option( 'wpaisg_language', 'Turkish' );
        $model = trim( get_option( 'wpaisg_model', 'gemini-2.5-flash' ) ); // Default to 2.5 Flash

        // Construct Prompt
        $prompt = "You are an expert Local SEO Copywriter. Write a WordPress Service Page content in {$language}.\n";
        $prompt .= "Service: {$service}\n";
        $prompt .= "Location: {$location}\n";
        $prompt .= "Keywords: {$keywords}\n";
        $prompt .= "Company Name: {$company_name}\n";
        $prompt .= "Address: {$company_address}\n";
        $prompt .= "Phone: {$company_phone}\n\n";

        $prompt .= "Requirements:\n";
        $prompt .= "1. Return ONLY a JSON object with two keys: 'title' and 'content'.\n";
        $prompt .= "2. 'title': A catchy, SEO-optimized title including the Service and Location.\n";
        $prompt .= "3. 'content': The full HTML body content (do NOT include <html> or <body> tags, just the inner content).\n";
        $prompt .= "4. Content Structure:\n";
        $prompt .= "   - Engaging Introduction (mentioning the location).\n";
        $prompt .= "   - Why Choose {$company_name}? (Bulleted list).\n";
        $prompt .= "   - Service Details (H2 headers).\n";
        $prompt .= "   - Frequently Asked Questions (FAQ) relevant to the service.\n";
        $prompt .= "   - A Call to Action section with the phone number.\n";
        $prompt .= "5. IMPORTANT: Include a <script type='application/ld+json'> block at the end with LocalBusiness Schema markup properly filled with the company details and service info.\n";
        $prompt .= "6. Use HTML tags like <h1>, <h2>, <p>, <ul>, <li>, <strong>.\n";
        $prompt .= "7. Do NOT include markdown formatting (```json) in the response, just raw JSON.\n";

        // Call Gemini API
        $response = $this->call_gemini_api( $api_key, $model, $prompt );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $result = json_decode( $response, true );
        
        // Fallback if JSON decode fails (sometimes AI adds markdown)
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            // Try to strip markdown code blocks
            $response = preg_replace('/^```json\s*|\s*```$/', '', $response);
            $result = json_decode( $response, true );
            
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                 // If still fails, assume it returned just text content and create a generic title
                 $result = array(
                     'title' => "$service - $location",
                     'content' => $response
                 );
            }
        }

        if ( empty( $result['content'] ) ) {
            wp_send_json_error( array( 'message' => 'AI boş içerik döndürdü.' ) );
        }

        // Create Post
        $post_data = array(
            'post_title'    => sanitize_text_field( $result['title'] ),
            'post_content'  => $result['content'],
            'post_status'   => 'draft',
            'post_type'     => 'post',
            'post_category' => array( $category_id )
        );

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Yazı oluşturulamadı: ' . $post_id->get_error_message() ) );
        }

        // Add Keywords as Tags
        if ( ! empty( $keywords ) ) {
            wp_set_post_tags( $post_id, $keywords, true );
        }

        wp_send_json_success( array( 
            'post_id' => $post_id,
            'title'   => sanitize_text_field( $result['title'] ),
            'edit_url' => get_edit_post_link( $post_id, 'raw' ) 
        ) );
	}

    private function call_gemini_api( $api_key, $model, $prompt ) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 4000
            ) 
        );

        $args = array(
            'body'        => json_encode( $body ),
            'headers'     => array( 'Content-Type' => 'application/json' ),
            'timeout'     => 120, // Increased timeout for long content generation
            'method'      => 'POST',
            'data_format' => 'body',
            'sslverify'   => apply_filters( 'https_local_ssl_verify', true ) // Allow overriding for local dev
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code !== 200 ) {
            error_log( 'WPAISG API Error: ' . $code . ' - ' . $body );
            $error_message = "API Hatası ($code).";
            
            // Try to extract detailed message from Google API response
            $response_json = json_decode( $body, true );
            if ( isset( $response_json['error']['message'] ) ) {
                $error_message .= " Detay: " . $response_json['error']['message'];
            } else {
                $error_message .= " Yanıt: " . strip_tags( substr($body, 0, 200) ) . "...";
            }

            return new WP_Error( 'api_error', $error_message );
        }

        $data = json_decode( $body, true );

        if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return new WP_Error( 'api_parse_error', 'API yanıtı ayrıştırılamadı.' );
    }

	public function render_dashboard_page() {
		?>
		<div class="wrap">
			<h1>AI Hizmet ve Makale Oluşturucu</h1>
            <p class="description">Google Gemini AI kullanarak Lokal SEO uyumlu hizmet yazıları oluşturun.</p>
            <hr>
            
            <div class="wpaisg-container">
                <!-- LEFT PANEL: FORM -->
                <div class="wpaisg-left-panel">
                    <h3>İçerik Ayarları</h3>
                    <form id="wpaisg-generator-form">
                        <table class="form-table" style="margin-top: 0;">
                            <tr valign="top">
                                <th scope="row"><label for="wpaisg-service">Hizmet Adı</label></th>
                                <td>
                                    <input type="text" id="wpaisg-service" name="service" class="large-text" placeholder="Örn: Klima Tamiri, Evden Eve Nakliyat" required />
                                    <p class="description">Ana hizmet konusu.</p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="wpaisg-location">Lokasyon (İl/İlçe)</label></th>
                                <td>
                                    <input type="text" id="wpaisg-location" name="location" class="large-text" placeholder="Örn: Bayrampaşa, İstanbul" required />
                                    <p class="description">Hedeflenen bölge.</p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="wpaisg-category">Kategori</label></th>
                                <td>
                                    <?php
                                    wp_dropdown_categories( array(
                                        'name' => 'category',
                                        'id'   => 'wpaisg-category',
                                        'show_option_none' => 'Kategori Seçin',
                                        'class' => 'regular-text',
                                        'hide_empty' => 0,
                                    ) );
                                    ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="wpaisg-keywords">SEO Anahtar Kelimeler</label></th>
                                <td>
                                    <textarea id="wpaisg-keywords" name="keywords" class="large-text" rows="3" placeholder="Örn: klima servisi, klima montajı, acil tamir"></textarea>
                                </td>
                            </tr>
                        </table>
                        <p class="submit" style="padding-top: 10px;">
                            <button type="submit" class="button button-primary button-large" style="width: 100%;">İçerik Oluştur</button>
                        </p>
                    </form>
                </div>

                <!-- RIGHT PANEL: RESULT -->
                <div class="wpaisg-right-panel">
                    <div class="wpaisg-preview-header">Sonuç / Önizleme</div>
                    <div id="wpaisg-result">
                        <p class="description">Oluşturulan içerik ve durum bilgisi burada görünecektir.</p>
                    </div>
                </div>
            </div>
		</div>
		<?php
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Ayarlar - AI Hizmet Oluşturucu</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'wpaisg_settings_group' ); ?>
				<?php do_settings_sections( 'wpaisg_settings_group' ); ?>
				
                <h2 class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active" onclick="wpaisg_switch_tab(event, 'general')">Genel Ayarlar</a>
                    <a href="#nap" class="nav-tab" onclick="wpaisg_switch_tab(event, 'nap')">Firma Bilgileri (Local SEO)</a>
                    <a href="#github" class="nav-tab" onclick="wpaisg_switch_tab(event, 'github')">Güncellemeler</a>
                </h2>

                <div id="tab-general" class="wpaisg-tab-content">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Gemini API Key</th>
                            <td>
                                <input type="text" name="wpaisg_gemini_api_key" value="<?php echo esc_attr( get_option('wpaisg_gemini_api_key') ); ?>" class="regular-text" />
                                <p class="description"><a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a> üzerinden alabilirsiniz.</p>
                                <button type="button" id="wpaisg-test-api" class="button button-secondary" style="margin-top: 5px;">Bağlantıyı Test Et</button>
                                <div id="wpaisg-api-test-result" style="margin-top: 5px; font-weight: bold;"></div>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Model</th>
                            <td>
                                <select name="wpaisg_model">
                                    <option value="gemini-2.5-flash" <?php selected( get_option('wpaisg_model'), 'gemini-2.5-flash' ); ?>>Gemini 2.5 Flash (Önerilen - En Hızlı)</option>
                                    <option value="gemini-3-flash-preview" <?php selected( get_option('wpaisg_model'), 'gemini-3-flash-preview' ); ?>>Gemini 3 Flash Preview (En Yeni)</option>
                                    <option value="gemini-2.5-pro" <?php selected( get_option('wpaisg_model'), 'gemini-2.5-pro' ); ?>>Gemini 2.5 Pro (Yüksek Kalite)</option>
                                </select>
                                <p class="description">Gemini 2.5 Flash hızlı ve ekonomiktir (2026 güncel modeller).</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Varsayılan Dil</th>
                            <td>
                                <input type="text" name="wpaisg_language" value="<?php echo esc_attr( get_option('wpaisg_language', 'Turkish') ); ?>" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tab-nap" class="wpaisg-tab-content" style="display:none;">
                    <p class="description">Bu bilgiler oluşturulan içeriklerde Local SEO sinyalleri (NAP) ve Schema verisi için kullanılacaktır.</p>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Firma Adı</th>
                            <td><input type="text" name="wpaisg_company_name" value="<?php echo esc_attr( get_option('wpaisg_company_name') ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Adres</th>
                            <td><textarea name="wpaisg_company_address" class="large-text" rows="3"><?php echo esc_textarea( get_option('wpaisg_company_address') ); ?></textarea></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Telefon</th>
                            <td><input type="text" name="wpaisg_company_phone" value="<?php echo esc_attr( get_option('wpaisg_company_phone') ); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div id="tab-github" class="wpaisg-tab-content" style="display:none;">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">GitHub Deposu</th>
                            <td>
                                <input type="text" name="wpaisg_github_repo" value="<?php echo esc_attr( get_option('wpaisg_github_repo') ); ?>" class="regular-text" placeholder="user/repo" />
                                <p class="description">Örn: <code>Antigravity/wp-ai-service-generator</code></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Access Token (Opsiyonel)</th>
                            <td>
                                <input type="password" name="wpaisg_github_token" value="<?php echo esc_attr( get_option('wpaisg_github_token') ); ?>" class="regular-text" />
                                <p class="description">Özel depolar için gereklidir.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Mevcut Sürüm</th>
                            <td><code><?php echo WPAISG_VERSION; ?></code></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Güncellemeler</th>
                            <td>
                                <button type="button" id="wpaisg-check-updates" class="button button-secondary wpaisg-check-updates-btn">Güncellemeleri Şimdi Kontrol Et</button>
                                <div id="wpaisg-update-result" style="margin-top: 10px;"></div>
                            </td>
                        </tr>
                    </table>
                </div>

				<?php submit_button(); ?>
			</form>
            
            <script>
            function wpaisg_switch_tab(evt, tabName) {
                evt.preventDefault();
                var i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("wpaisg-tab-content");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("nav-tab");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
                }
                document.getElementById("tab-" + tabName).style.display = "block";
                evt.currentTarget.className += " nav-tab-active";
            }
            </script>
		</div>
		<?php
	}

    public function wpaisg_handle_ajax_check_updates() {
        check_ajax_referer( 'wpaisg-generate-nonce', 'nonce' );

        $repo = get_option( 'wpaisg_github_repo' );
        if ( empty( $repo ) ) {
            wp_send_json_error( array( 'message' => 'GitHub deposu ayarlanmamış.' ) );
        }

        // Clean the repo input - remove any URLs, just get username/repo
        $repo = trim( $repo );
        $repo = str_replace( 'https://github.com/', '', $repo );
        $repo = str_replace( 'http://github.com/', '', $repo );
        $repo = rtrim( $repo, '/' );

        // Validate format: must be "username/reponame"
        if ( substr_count( $repo, '/' ) !== 1 ) {
            wp_send_json_error( array( 'message' => 'Geçersiz repo formatı. Örnek: "kullanıcıadı/repo-adı"' ) );
        }

        $token = get_option( 'wpaisg_github_token' );
        $url = "https://api.github.com/repos/{$repo}/releases/latest";

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'WordPress-Plugin-Updater'
            )
        );

        if ( ! empty( $token ) ) {
            $args['headers']['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Bağlantı hatası: ' . $response->get_error_message() ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 404 ) {
            wp_send_json_error( array( 'message' => 'Depo bulunamadı. Repo adını kontrol edin: "' . esc_html($repo) . '"' ) );
        }

        if ( $code !== 200 ) {
            $body = wp_remote_retrieve_body( $response );
            $error_data = json_decode( $body, true );
            $error_msg = isset( $error_data['message'] ) ? $error_data['message'] : 'Bilinmeyen hata';
            wp_send_json_error( array( 'message' => "GitHub Hatası ({$code}): {$error_msg}" ) );
        }
        
        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( ! $release || isset( $release['message'] ) ) {
            $error_msg = isset( $release['message'] ) ? $release['message'] : 'Bilinmeyen hata';
            
            // Check if it's "Not Found" error (no releases)
            if ( strpos( $error_msg, 'Not Found' ) !== false ) {
                wp_send_json_error( array( 
                    'message' => 'Bu repo\'da henüz hiç Release yok. GitHub\'da bir release oluşturmanız gerekiyor. Detaylar için "github_release_guide.md" dosyasına bakın.' 
                ) );
            }
            
            wp_send_json_error( array( 'message' => 'Sürüm bilgisi alınamadı: ' . $error_msg ) );
        }

        $latest_version = $release['tag_name'];
        $current_version = WPAISG_VERSION;

        if ( version_compare( $latest_version, $current_version, '>' ) ) {
            wp_send_json_success( array( 
                'status' => 'update_available',
                'version' => $latest_version,
                'message' => "Yeni sürüm mevcut: <strong>{$latest_version}</strong>",
                'zip_url' => $release['zipball_url']
            ) );
        } else {
             wp_send_json_success( array( 
                'status' => 'up_to_date',
                'version' => $latest_version,
                'message' => "Eklentiniz güncel (v{$current_version})."
            ) );
        }
    }

    public function wpaisg_handle_ajax_perform_update() {
        check_ajax_referer( 'wpaisg-generate-nonce', 'nonce' );

        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ) );
        }

        $zip_url = sanitize_url( $_POST['zip_url'] );
        if ( empty( $zip_url ) ) {
            wp_send_json_error( array( 'message' => 'İndirme adresi bulunamadı.' ) );
        }

        // 1. Download
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        
        WP_Filesystem();
        global $wp_filesystem;

        $temp_file = download_url( $zip_url );

        if ( is_wp_error( $temp_file ) ) {
            wp_send_json_error( array( 'message' => 'İndirme hatası: ' . $temp_file->get_error_message() ) );
        }

        // 2. Unzip
        $upgrade_folder = $wp_filesystem->wp_content_dir() . 'upgrade/wpaisg_temp/';
        $wp_filesystem->delete( $upgrade_folder, true );
        
        $unzip_result = unzip_file( $temp_file, $upgrade_folder );
        unlink( $temp_file );

        if ( is_wp_error( $unzip_result ) ) {
             wp_send_json_error( array( 'message' => 'Arşiv açılamadı: ' . $unzip_result->get_error_message() ) );
        }

        // 3. Move/Replace
        $files = $wp_filesystem->dirlist( $upgrade_folder );
        if ( empty( $files ) ) {
             wp_send_json_error( array( 'message' => 'İndirilen paket boş çıktı.' ) );
        }

        $source_dir = $upgrade_folder . array_key_first( $files ) . '/';
        $destination_dir = WPAISG_PATH;

        $copy_result = copy_dir( $source_dir, $destination_dir );
        
        $wp_filesystem->delete( $upgrade_folder, true );

        if ( is_wp_error( $copy_result ) ) {
            wp_send_json_error( array( 'message' => 'Dosyalar kopyalanamadı: ' . $copy_result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => 'Eklenti başarıyla güncellendi! Sayfa yenileniyor...' ) );
    }

    public function wpaisg_handle_ajax_test_api() {
        check_ajax_referer( 'wpaisg-generate-nonce', 'nonce' );

        $api_key = get_option( 'wpaisg_gemini_api_key' );
        $model = trim( get_option( 'wpaisg_model', 'gemini-1.5-flash' ) );

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => 'API Anahtarı girilmemiş.' ) );
        }

        // Simple prompt to test connection
        $prompt = "Hello, are you active? Reply with 'Yes'.";
        
        $response = $this->call_gemini_api( $api_key, $model, $prompt );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => "Bağlantı Başarılı! ($model yanıt verdi)" ) );
    }
}

// Initialize the plugin
function wp_ai_service_generator_init() {
	WPAIServiceGenerator::get_instance();

    // Initialize GitHub Updater if settings are present
    $repo = get_option( 'wpaisg_github_repo' );
    $token = get_option( 'wpaisg_github_token' );
    
    if ( ! empty( $repo ) ) {
        new WPAISG_GitHub_Updater( $repo, $token, WPAISG_VERSION );
    }
}
add_action( 'plugins_loaded', 'wp_ai_service_generator_init' );

class WPAISG_GitHub_Updater {
    private $repo;
    private $token;
    private $version;
    private $slug;
    private $plugin_file;

    public function __construct( $repo, $token, $version ) {
        $this->repo = $repo;
        $this->token = $token;
        $this->version = $version;
        $this->slug = 'wp-ai-service-generator';
        $this->plugin_file = 'wp-ai-service-generator/wp-ai-service-generator.php';

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
    }

    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_latest_release();
        
        if ( $release && version_compare( $release['tag_name'], $this->version, '>' ) ) {
            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->plugin = $this->plugin_file;
            $obj->new_version = $release['tag_name'];
            $obj->url = $release['html_url'];
            $obj->package = $release['zipball_url']; // GitHub provides zipball by default

            if ( ! empty( $this->token ) ) {
                // If private repo, we might need a different approach for package URL or add headers to the download request
                // For simplicity, standard GitHub releases usually work if the user has access. 
                // A more complex implementation handles 'upgrader_pre_download' to add Authorization header.
            }

            $transient->response[ $this->plugin_file ] = $obj;
        }

        return $transient;
    }

    public function plugin_popup( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || $args->slug !== $this->slug ) {
            return $result;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        $obj = new stdClass();
        $obj->name = 'AI Hizmet Oluşturucu';
        $obj->slug = $this->slug;
        $obj->version = $release['tag_name'];
        $obj->author = '<a href="https://github.com/Antigravity">Antigravity</a>';
        $obj->homepage = $release['html_url'];
        $obj->requires = '5.0';
        $obj->tested = '6.4';
        $obj->download_link = $release['zipball_url'];
        $obj->sections = array(
            'description' => $release['body'],
            'changelog'   => $release['body']
        );

        return $obj;
    }

    private function get_latest_release() {
        $url = "https://api.github.com/repos/{$this->repo}/releases/latest";
        $args = array( 'timeout' => 10 );
        
        if ( ! empty( $this->token ) ) {
            $args['headers'] = array( 'Authorization' => "token {$this->token}" );
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }
}


