<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Rubik', Arial, sans-serif;
            line-height: 1.6;
            color: #3A383A;
        }

        .container {
            width: 600px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }

        .header {
            padding: 32px 24px;
            background: #F9F9F9;
            border-bottom: 1px solid #DBDADE;
            text-align: center;
        }

        .header img {
            width: 107.88px;
            height: 24px;
            margin-bottom: 24px;
        }

        .header-text {
            font-size: 16px;
            font-weight: 500;
        }

        .content {
            padding: 48px 84px;
        }

        .greeting {
            margin-bottom: 24px;
            line-height: 20px;
            font-size: 14px;
        }

        .info-container {
            margin-bottom: 24px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
            line-height: 20px;
        }

        .info-label {
            width: 140px;
        }

        .info-separator {
            margin: 0 8px;
        }

        .button {
            display: inline-block;
            padding: 10px 24px;
            background: #0095F6;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            line-height: 20px;
            margin: 24px 0;
        }

        .link-info {
            font-size: 14px;
            line-height: 20px;
            margin-bottom: 8px;
        }

        .link {
            color: #0095F6;
            text-decoration: underline;
            font-size: 14px;
            line-height: 20px;
        }

        .footer {
            padding: 32px 84px;
            background: #F9F9F9;
            font-size: 14px;
            line-height: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            {{-- <img src="{{ asset('images/logo-name.png') }}" alt="Logo"> --}}
            <div class="header-text">You have a new SPK Record waiting for your approval.</div>
        </div>

        <div class="content">
            <div class="greeting">
                Halo <strong>{{ $approver->name }}</strong>,<br><br>
                Anda diminta untuk menyetujui sebuah permintaan. Berikut adalah informasi singkatnya.
            </div>

            <div class="info-container">
                <div class="info-row">
                    <div class="info-label">Nomor SPK</div>
                    <div class="info-separator">:</div>
                    <div>{{ $spkRecord->recordid }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nama pembuat</div>
                    <div class="info-separator">:</div>
                    <div>{{ $spkRecord->approvalRecord->createdBy->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Department</div>
                    <div class="info-separator">:</div>
                    <div>{{ $spkRecord->approvalRecord->department->name }}</div>
                </div>
            </div>

            <div>Untuk melihat detail lengkap dan melakukan persetujuan, silakan klik tombol berikut:</div>

            <a href="{{ config('app.url') }}/maintenance-database-system/department-request/detail?record_id={{ $spkRecord->recordid }}&to_approve=1"
                class="button">Lihat Detail</a>

            <div class="link-info">Jika tombol diatas tidak berfungsi, kamu dapat membuka detail melalui link berikut:
            </div>
            <a href="{{ config('app.url') }}/maintenance-database-system/department-request/detail?record_id={{ $spkRecord->recordid }}&to_approve=1"
                class="link">{{ config('app.url') }}/maintenance-database-system/department-request/detail?record_id={{ $spkRecord->recordid }}&to_approve=1</a>
        </div>

        <div class="footer">
            Mohon untuk segera meninjau dan memberikan keputusan agar pekerjaan dapat terus berjalan. Terima kasih atas
            perhatian dan kerjasama.
        </div>
    </div>
</body>

</html>
