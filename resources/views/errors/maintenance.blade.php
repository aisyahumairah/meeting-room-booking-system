<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - MRBS</title>
    <link href="{{ asset('assets/vendor/css/core.css') }}" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .maintenance-card {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .maintenance-icon {
            font-size: 5rem;
            color: #667eea;
            margin-bottom: 1.5rem;
        }

        h1 {
            color: #333;
            margin-bottom: 1rem;
        }

        p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-retry {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: opacity 0.2s ease;
        }

        .btn-retry:hover {
            opacity: 0.9;
            color: white;
        }

        .btn-logout {
            background: #ff0000;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: opacity 0.2s ease;
        }

        .btn-logout:hover {
            opacity: 0.8;
            color: white;
        }
    </style>
</head>

<body>
    <div class="maintenance-card">
        <div class="maintenance-icon">
            <i class='bx bx-wrench'></i>
        </div>
        <h1>System Under Maintenance</h1>
        <p>
            We're currently performing scheduled maintenance to improve your experience.
            Please check back shortly.
        </p>
        <a href="{{ url('/') }}" class="btn-retry">
            <i class='bx bx-refresh'></i> Try Again
        </a>
        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn-logout">
                <i class='bx bx-log-out'></i> Logout
            </button>
        </form>
    </div>
</body>

</html>
