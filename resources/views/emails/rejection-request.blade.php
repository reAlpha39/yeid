<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Rubik', Arial, sans-serif;
            line-height: 1.6;
            color: #3A383A;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        .container {
            width: 600px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            padding: 32px 24px;
            background: #F9F9F9;
            border-bottom: 1px solid #DBDADE;
            text-align: center;
            border-radius: 8px 8px 0 0;
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

        .revision-note {
            background: #FFF3CD;
            border-left: 4px solid #FFC107;
            padding: 16px;
            margin: 24px 0;
            font-size: 14px;
            line-height: 20px;
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
            word-break: break-all;
        }

        .footer {
            padding: 32px 84px;
            background: #F9F9F9;
            font-size: 14px;
            line-height: 20px;
            border-radius: 0 0 8px 8px;
        }

        @media screen and (max-width: 640px) {
            body {
                padding: 10px;
            }

            .container {
                width: 100%;
            }

            .content {
                padding: 24px;
            }

            .footer {
                padding: 24px;
            }
        }
    </style>
</head>


<body>
    <div class="container">
        <div class="header">
            {{-- <img src="{{ asset('image/logo-name.png') }}" alt="Logo"> --}}
            <div class="header-text">SPK Record Ditolak</div>
        </div>

        <div class="content">
            <div class="greeting">
                Halo <strong>{{ $spkRecord->approvalRecord->createdBy->name }}</strong>,<br><br>
                SPK Record Anda telah ditolak. Berikut adalah informasi detailnya.
            </div>

            <div class="info-container">
                <div class="info-row">
                    <div class="info-label">Nomor SPK</div>
                    <div class="info-separator">:</div>
                    <div>#{{ $spkRecord->recordid }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Penolak</div>
                    <div class="info-separator">:</div>
                    <div>{{ $rejector->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Departement</div>
                    <div class="info-separator">:</div>
                    <div>{{ $rejector->department->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Shop</div>
                    <div class="info-separator">:</div>
                    <div>{{ $spkRecord->ordershop }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Machine</div>
                    <div class="info-separator">:</div>
                    <div>{{ $spkRecord->machinename }} ({{ $spkRecord->machineno }})</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Title</div>
                    <div class="info-separator">:</div>
                    <div>{{ $spkRecord->ordertitle }}</div>
                </div>
            </div>

            <div class="rejection-note">
                <strong>Alasan Penolakan:</strong><br>
                {{ $note }}
            </div>

            <div class="link-info">Untuk melihat detail lengkap, kamu dapat membuka detail melalui link berikut:</div>
            <a href="{{ config('app.url') }}/maintenance-database-system/department-request/detail?record_id={{ $spkRecord->recordid }}"
                class="link">{{ config('app.url') }}/maintenance-database-system/department-request/detail?record_id={{ $spkRecord->recordid }}</a>
        </div>

        <div class="footer">
            SPK Record ini telah ditolak dan tidak dapat diproses lebih lanjut. Jika Anda memiliki pertanyaan, silakan
            hubungi penolak yang tercantum di atas. Terima kasih atas perhatian dan kerjasama.
        </div>
    </div>
</body>

</html>
