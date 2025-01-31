<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #007bff;
        }

        .content {
            padding: 20px;
            background-color: #ffffff;
        }

        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
            background-color: #f8f9fa;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }

        .details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .info-row {
            margin: 10px 0;
        }

        .label {
            font-weight: bold;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>SPK Record Approval Request</h1>
        </div>

        <div class="content">
            <p>Dear {{ $approver->name }},</p>

            <p>You have a new SPK Record waiting for your approval.</p>

            <div class="details">
                <div class="info-row">
                    <span class="label">Record ID:</span>
                    <span>{{ $spkRecord->recordid }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Requester:</span>
                    <span>{{ $spkRecord->orderempname }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Shop:</span>
                    <span>{{ $spkRecord->ordershop }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Machine:</span>
                    <span>{{ $spkRecord->machinename }} ({{ $spkRecord->machineno }})</span>
                </div>
                <div class="info-row">
                    <span class="label">Title:</span>
                    <span>{{ $spkRecord->ordertitle }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Request Date:</span>
                    <span>{{ \Carbon\Carbon::parse($spkRecord->orderdatetime)->format('Y-m-d H:i') }}</span>
                </div>
            </div>

            <center>
                <a href="{{ config('app.url') }}/approvals/{{ $spkRecord->recordid }}" class="button">
                    View Approval Request
                </a>
            </center>

            <p>Please review and process this request at your earliest convenience.</p>

            <p>Best regards,<br>{{ config('app.name') }}</p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
