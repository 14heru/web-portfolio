<?php
// ==========================================================================
// config/mail.example.php - TEMPLATE KONFIGURASI SMTP GMAIL
// ==========================================================================
// CARA PENGGUNAAN:
//   1. Salin berkas ini menjadi config/mail.php
//      (di Windows: copy config\mail.example.php config\mail.php)
//      (di Linux/Mac: cp config/mail.example.php config/mail.php)
//   2. Isi nilai GMAIL_APP_PASSWORD dengan 16 digit App Password Google Anda.
//   3. Berkas config/mail.php sudah dikecualikan dari Git (.gitignore)
//      sehingga kredensial Anda tidak akan terekspos ke GitHub.
// ==========================================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Muat autoload Composer untuk library PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

// --------------------------------------------------------------------------
// 1. PENGATURAN SERVER SMTP GMAIL
// --------------------------------------------------------------------------
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);            // TLS menggunakan port 587
define('SMTP_ENCRYPTION', 'tls');

// --------------------------------------------------------------------------
// 2. KREDENSIAL AKUN GMAIL PENGIRIM
// --------------------------------------------------------------------------
define('GMAIL_USER', 'GANTI_DENGAN_EMAIL_GMAIL_ANDA@gmail.com');

// Cara mendapatkan App Password:
//   1. Aktifkan Verifikasi 2 Langkah di: https://myaccount.google.com/security
//   2. Buka: https://myaccount.google.com/apppasswords
//   3. Beri nama (contoh: "Web Portfolio"), lalu klik Buat.
//   4. Google akan menampilkan 16 karakter - salin dan tempel di bawah.
define('GMAIL_APP_PASSWORD', 'GANTI_DENGAN_16_DIGIT_APP_PASSWORD');

// --------------------------------------------------------------------------
// 3. PENERIMA EMAIL NOTIFIKASI PESAN MASUK
// --------------------------------------------------------------------------
define('MAIL_RECIPIENT',      'GANTI_DENGAN_EMAIL_TUJUAN@gmail.com');
define('MAIL_RECIPIENT_NAME', 'Nama Pemilik Portofolio');

/**
 * Mengirim email notifikasi pesan masuk ke inbox pemilik web.
 *
 * @param string $senderName     Nama pengirim dari formulir kontak
 * @param string $senderEmail    Email pengirim dari formulir kontak
 * @param string $subject        Subjek pesan
 * @param string $messageContent Isi pesan
 * @return array ['sent' => bool, 'message' => string]
 */
function sendContactNotification($senderName, $senderEmail, $subject, $messageContent) {
    // Jika App Password masih berupa placeholder, lewati pengiriman email
    if (
        empty(GMAIL_APP_PASSWORD) ||
        GMAIL_APP_PASSWORD === 'GANTI_DENGAN_16_DIGIT_APP_PASSWORD' ||
        GMAIL_APP_PASSWORD === 'isi_app_password_gmail_disini'
    ) {
        return [
            'sent'    => false,
            'message' => 'App Password Gmail belum dikonfigurasi. Pesan tetap tersimpan di database.'
        ];
    }

    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USER;
        $mail->Password   = str_replace(' ', '', GMAIL_APP_PASSWORD); // Hapus spasi jika ada
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;

        // Pengirim & Penerima
        $mail->setFrom(GMAIL_USER, 'Notifikasi Portofolio Web');
        $mail->addAddress(MAIL_RECIPIENT, MAIL_RECIPIENT_NAME);
        $mail->addReplyTo($senderEmail, $senderName);

        // Konten Email
        $mail->isHTML(true);
        $emailSubject = !empty($subject)
            ? "[Pesan Portofolio] " . $subject
            : "[Pesan Portofolio] Pesan Baru dari " . $senderName;
        $mail->Subject = $emailSubject;

        // Template HTML Email
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                <div style='background-color: #2563eb; color: #ffffff; padding: 24px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 20px; font-weight: 700;'>Pesan Masuk Baru</h2>
                    <p style='margin: 6px 0 0; font-size: 14px; opacity: 0.9;'>Formulir Kontak Web Portofolio</p>
                </div>
                <div style='padding: 24px;'>
                    <p style='margin-top: 0;'>Halo <strong>" . htmlspecialchars(MAIL_RECIPIENT_NAME) . "</strong>,</p>
                    <p>Seseorang mengirim pesan melalui situs portofolio Anda. Berikut detailnya:</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 14px;'>
                        <tr><td style='padding: 10px; border-bottom: 1px solid #f1f5f9; color: #64748b; width: 130px; font-weight: 600;'>Nama</td><td style='padding: 10px; border-bottom: 1px solid #f1f5f9; font-weight: 600;'>: " . htmlspecialchars($senderName) . "</td></tr>
                        <tr><td style='padding: 10px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-weight: 600;'>Email</td><td style='padding: 10px; border-bottom: 1px solid #f1f5f9;'>: <a href='mailto:" . htmlspecialchars($senderEmail) . "' style='color: #2563eb; text-decoration: none;'>" . htmlspecialchars($senderEmail) . "</a></td></tr>
                        <tr><td style='padding: 10px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-weight: 600;'>Subjek</td><td style='padding: 10px; border-bottom: 1px solid #f1f5f9;'>: " . htmlspecialchars($subject ?: '(Tanpa Subjek)') . "</td></tr>
                        <tr><td style='padding: 10px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-weight: 600;'>Waktu</td><td style='padding: 10px; border-bottom: 1px solid #f1f5f9;'>: " . date('d M Y - H:i') . " WIB</td></tr>
                    </table>
                    <div style='background-color: #f8fafc; border-left: 4px solid #2563eb; border-radius: 6px; padding: 16px; margin-bottom: 20px;'>
                        <div style='font-size: 12px; font-weight: 700; color: #2563eb; text-transform: uppercase; margin-bottom: 8px;'>Isi Pesan:</div>
                        <div style='font-size: 14px; line-height: 1.6; white-space: pre-wrap;'>" . nl2br(htmlspecialchars($messageContent)) . "</div>
                    </div>
                    <div style='text-align: center;'>
                        <a href='mailto:" . htmlspecialchars($senderEmail) . "?subject=Re: " . urlencode($subject ?: 'Pesan Portofolio') . "' style='display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 10px 22px; border-radius: 50px; font-size: 14px; font-weight: 600;'>Balas Pengirim</a>
                    </div>
                </div>
                <div style='background-color: #f1f5f9; padding: 14px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                    Notifikasi otomatis dari Web Portofolio " . htmlspecialchars(MAIL_RECIPIENT_NAME) . "
                </div>
            </div>
        </body>
        </html>";

        // Teks polos (fallback non-HTML)
        $mail->AltBody = "Pesan Masuk Baru\n\nNama: $senderName\nEmail: $senderEmail\nSubjek: $subject\n\nPesan:\n$messageContent\n\nWaktu: " . date('d M Y - H:i') . " WIB";

        $mail->send();
        return ['sent' => true, 'message' => 'Email notifikasi berhasil terkirim.'];

    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return ['sent' => false, 'message' => 'Gagal mengirim email: ' . $mail->ErrorInfo];
    }
}

