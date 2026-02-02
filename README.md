# AI Hizmet Sayfası Oluşturucu (Local SEO Enhanced)

Bu WordPress eklentisi, **Google Gemini API** kullanarak yerel SEO uyumlu, kapsamlı hizmet sayfaları oluşturmanızı sağlar. Özellikle yerel işletmeler (tesisatçı, nakliyat, tamirci vb.) için optimize edilmiştir.


## Özellikler

*   **Otomatik İçerik Üretimi:** Hizmet adı, lokasyon ve anahtar kelimeleri girerek saniyeler içinde taslak yazı oluşturun.
*   **İçerik Yapısı:**
    *   İlgi çekici giriş (Lokasyon odaklı)
    *   Neden [Firma Adı] Seçilmeli? (Maddeli liste)
    *   Hizmet Detayları (H2 başlıklar)
    *   Sıkça Sorulan Sorular (SSS)
    *   Harekete Geçirici Mesaj (CTA) - Telefon numarası ile
*   **Local SEO & Schema:** Oluşturulan her yazı, işletme bilgilerinizi içeren `LocalBusiness` Schema (JSON-LD) verisi ile birlikte gelir.
*   **Google Gemini Entegrasyonu:** Gemini 1.5 Flash (hızlı ve ekonomik) veya Pro modellerini destekler.
*   **GitHub Güncelleme Desteği:** Eklenti güncellemelerini doğrudan GitHub deposundan alabilir.

## Kurulum

1.  Bu depoyu `.zip` olarak indirin.
2.  WordPress panelinden **Eklentiler > Yeni Ekle > Eklenti Yükle** yolunu izleyin.
3.  Zipli dosyayı yükleyin ve etkinleştirin.

## Ayarlar

Eklentiyi etkinleştirdikten sonra **Ayarlar > AI Hizmet Oluşturucu** menüsüne gidin:

### 1. Genel Ayarlar
*   **Gemini API Key:** [Google AI Studio](https://aistudio.google.com/app/apikey) adresinden alacağınız API anahtarını girin.
*   **Model:** Tavsiye edilen model `gemini-1.5-flash`'tır.
*   **Varsayılan Dil:** İçeriklerin oluşturulacağı dil (Varsayılan: Turkish).

### 2. Firma Bilgileri (Local SEO)
Bu bilgiler oluşturulan içeriklerde ve Schema yapısında kullanılacaktır:
*   **Firma Adı:** İşletmenizin adı.
*   **Adres:** Tam açık adres.
*   **Telefon:** Müşterilerin arayacağı telefon numarası.

### 3. Güncellemeler (Opsiyonel)
Eğer bu eklentiyi özel bir GitHub deposundan yönetiyorsanız:
*   **GitHub Deposu:** `kullaniciadi/repo-adi` formatında.
*   **Access Token:** Özel depolar için gereklidir.

## Kullanım

1.  WordPress admin panelinde **AI Hizmet Oluşturucu** menüsüne tıklayın.
2.  **Hizmet Adı:** (Örn: Klima Montajı)
3.  **Lokasyon:** (Örn: Kadıköy, İstanbul)
4.  **Kategori:** Yazının ekleneceği kategori.
5.  **Anahtar Kelimeler:** Virgülle ayrılmış SEO kelimeleri.
6.  **"İçerik Oluştur"** butonuna basın.

Yazı **Taslak** olarak oluşturulacak ve düzenleme ekranına yönlendirme linki verilecektir.
