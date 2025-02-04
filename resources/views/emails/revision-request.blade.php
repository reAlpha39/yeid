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
            background-color: #fff3cd;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #ffc107;
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

        .revision-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #ffc107;
            color: #000 !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>SPK Record Revision Required</h1>
        </div>

        <div class="content">
            <p>Dear {{ $spkRecord->orderempname }},</p>

            <p>Your SPK Record requires revision. Please review the details below.</p>

            <div class="details">
                <div class="info-row">
                    <span class="label">Record ID:</span>
                    <span>#{{ $spkRecord->recordid }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Reviewer:</span>
                    <span>{{ $reviewer->name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Department:</span>
                    <span>{{ $reviewer->department->name }}</span>
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
            </div>

            <div class="revision-note">
                <strong>Revision Note:</strong>
                <p>{{ $note }}</p>
            </div>

            <center>
                <a href="{{ config('app.url') }}/spk-records/{{ $spkRecord->recordid }}/edit" class="button">
                    Revise Request
                </a>
            </center>

            <p>Please update your request based on the revision notes provided. Once updated, the approval process will
                restart.</p>

            <p>Best regards,<br>{{ config('app.name') }}</p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
