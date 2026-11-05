<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to our platform! 🎉</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #007bff;
        }

        .content {
            font-size: 16px;
            line-height: 1.6;
        }

        .highlight {
            background-color: #f1f8ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-weight: bold;
            color: #0056b3;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>📢 Welcome to our platform! 🎉</h1>
        </div>

        <!-- Body -->
        <div class="content">
            <p>Hello <strong>{{ $tenantName }}</strong>,</p>
            <p class="highlight">
                {{ $messageBody }}
            </p>
            <p>If you have already addressed this reminder, you may disregard this message.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2026 DormDash. All rights reserved.</p>
            <p>
                Questions? Email us at <a href="supportdormdash@gmail.com">support@dormdash.com</a>
            </p>
        </div>
    </div>
</body>

</html>
