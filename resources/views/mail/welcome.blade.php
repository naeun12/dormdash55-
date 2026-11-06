<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP for DormDash</title>
    <style>
        /* Reset default styles */
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

        .header img {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .content {
            font-size: 16px;
            line-height: 1.6;
        }

        .otp-code {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f1f8ff;
            border-radius: 4px;
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
    <!-- Email Container -->
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <!-- Replace with your logo URL -->
            {{-- <img src="https://your-domain.com/images/Logo/logo.png" alt="DormDash Logo"> --}}
            <h1 style="font-size: 24px; margin: 0;">Welcome to DormDash!</h1>
        </div>

        <!-- Content Section -->
        <div class="content">
            <p>Thank you for signing up with DormDash. To verify your email address, please use the One-Time Password
                (OTP) below:</p>
            <div class="otp-code">{{ $otp }}</div>
            <p>If you did not request this verification, please disregard this email.</p>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; 2026 DormDash. All rights reserved.</p>
            <p>
                If you have any questions, feel free to contact us at
                <a href="mailto:kassandraolvis@gmail.com">kassandraolvis@gmail.com</a>.
            </p>
        </div>
    </div>
</body>

</html>
