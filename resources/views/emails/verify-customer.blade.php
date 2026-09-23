<!DOCTYPE html>
<html>

<head>
    <title>Verifikasi Email - Zillenial Action</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .header h1 {
            color: #5a2d67;
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 20px 0;
            text-align: center;
        }

        .content p {
            margin-bottom: 15px;
        }

        .button-container {
            text-align: center;
            margin-top: 20px;
        }

        .button {
            display: inline-block;
            padding: 12px 25px;
            background-color: #5a2d67;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }

        .footer {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Verifikasi Email Kamu</h1>
        </div>
        <div class="content">
            <p>Halo{{ $name ? ', ' . $name : '' }},</p>
            <p>Terima kasih sudah mendaftar akun Sostrip Zillenial Action. Sebelum bisa melihat pesanan dan tiket, verifikasi dulu alamat emailmu.</p>

            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="button" style="color: #f4f4f4">Verifikasi Email</a>
            </div>

            <p style="margin-top: 30px;">Jika tombol di atas tidak berfungsi, kamu bisa menyalin tautan berikut:</p>
            <p><small><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></small></p>

            <p style="margin-top: 30px;">Tautan ini berlaku selama 60 menit. Kalau kamu tidak merasa mendaftar akun ini, abaikan saja email ini.</p>
        </div>
        <div class="footer">
            <p>Hormat kami,<br>Tim ZillenialAction</p>
            <p>Email ini dikirim secara otomatis, mohon tidak membalas.</p>
        </div>
    </div>
</body>

</html>
