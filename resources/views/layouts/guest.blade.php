<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Custom Dark Theme Styles -->
        <style>
            body {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
                color: #e2e8f0;
                min-height: 100vh;
            }
            
            .auth-container {
                background: rgba(30, 30, 60, 0.9);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            }
            
            .auth-logo {
                filter: brightness(0) invert(1);
                opacity: 0.8;
            }
            
            /* Form styling */
            input[type="email"], input[type="password"], input[type="text"] {
                background: rgba(30, 30, 60, 0.6) !important;
                border: 1px solid rgba(255, 255, 255, 0.2) !important;
                color: #e2e8f0 !important;
            }
            
            input[type="email"]:focus, input[type="password"]:focus, input[type="text"]:focus {
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
            }
            
            input::placeholder {
                color: rgba(226, 232, 240, 0.6) !important;
            }
            
            label {
                color: #e2e8f0 !important;
            }
            
            /* Button styling */
            .btn-primary {
                background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%) !important;
                border: none !important;
                color: white !important;
            }
            
            .btn-primary:hover {
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            }
            
            /* Links */
            a {
                color: #60a5fa !important;
            }
            
            a:hover {
                color: #93c5fd !important;
            }
            
            /* Checkbox */
            input[type="checkbox"] {
                background: rgba(30, 30, 60, 0.6) !important;
                border: 1px solid rgba(255, 255, 255, 0.2) !important;
            }
            
            input[type="checkbox"]:checked {
                background: #3b82f6 !important;
                border-color: #3b82f6 !important;
            }
            
            /* Error messages */
            .text-red-600 {
                color: #f87171 !important;
            }
            
            /* Status messages */
            .text-green-600 {
                color: #34d399 !important;
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current auth-logo" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 auth-container overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
