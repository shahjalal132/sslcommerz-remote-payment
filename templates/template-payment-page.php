<?php
/**
 * Payment Page Template
 *
 * This template is used for the payment page.
 * Payment processing is handled by Payment_Receiver class via template_redirect hook.
 */

// If no data parameter, show error
if ( ! isset( $_GET['data'] ) || empty( $_GET['data'] ) ) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f5f5f5;
            }
            .error-container {
                text-align: center;
                padding: 40px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #d32f2f;
                margin-bottom: 20px;
            }
            p {
                color: #666;
                font-size: 16px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>Payment Error</h1>
            <p>Missing payment data. Please contact support if you believe this is an error.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// If we reach here, payment processing should have been handled by Payment_Receiver
// This is just a fallback in case template_redirect didn't catch it
?>
<!DOCTYPE html>
<html>
<head>
    <title>Processing Payment...</title>
    <meta http-equiv="refresh" content="2">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .loading-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <h1>Processing Payment...</h1>
        <p>Please wait while we redirect you to the payment gateway.</p>
    </div>
</body>
</html>
