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

        <!-- Dark Theme Styles -->
        <style>
            body {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
                color: #e2e8f0;
                min-height: 100vh;
            }
            
            .app-container {
                background: transparent;
            }
            
            .app-header {
                background: rgba(30, 30, 60, 0.9);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            }
            
            .app-header h2 {
                color: #e2e8f0;
                font-weight: 600;
            }
            
            /* Navigation styles */
            .navigation {
                background: rgba(30, 30, 60, 0.9);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .nav-link {
                color: rgba(226, 232, 240, 0.8) !important;
                text-decoration: none;
                transition: all 0.3s ease;
            }
            
            .nav-link:hover {
                color: #60a5fa !important;
            }
            
            .nav-link.active {
                color: #60a5fa !important;
                background: rgba(30, 58, 138, 0.2) !important;
            }
            
            /* Logo styling */
            .app-logo {
                filter: brightness(0) invert(1);
                opacity: 0.8;
            }
            
            /* Main content */
            .main-content {
                background: transparent;
            }
            
            .content-card {
                background: rgba(30, 30, 60, 0.8);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
            }
            
            /* Form elements */
            input[type="text"], input[type="email"], input[type="password"], textarea, select {
                background: rgba(30, 30, 60, 0.6) !important;
                border: 1px solid rgba(255, 255, 255, 0.2) !important;
                color: #e2e8f0 !important;
            }
            
            input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, textarea:focus, select:focus {
                border-color: #1e3a8a !important;
                box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.3) !important;
            }
            
            label {
                color: #e2e8f0 !important;
            }
            
            /* Buttons */
            .btn-primary {
                background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%) !important;
                border: none !important;
                color: white !important;
            }
            
            .btn-primary:hover {
                background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%) !important;
            }
            
            .btn-secondary {
                background: rgba(75, 85, 99, 0.8) !important;
                border: 1px solid rgba(255, 255, 255, 0.2) !important;
                color: #e2e8f0 !important;
            }
            
            .btn-secondary:hover {
                background: rgba(55, 65, 81, 0.9) !important;
            }
            
            /* Dropdown */
            .dropdown-content {
                background: rgba(30, 30, 60, 0.95) !important;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4) !important;
            }
            
            .dropdown-item {
                color: rgba(226, 232, 240, 0.8) !important;
            }
            
            .dropdown-item:hover {
                background: rgba(30, 58, 138, 0.2) !important;
                color: #60a5fa !important;
            }
            
            /* Красивые скроллбары */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            
            ::-webkit-scrollbar-track {
                background: rgba(15, 15, 35, 0.8);
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb {
                background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
                border-radius: 4px;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%);
            }
            
            ::-webkit-scrollbar-corner {
                background: rgba(15, 15, 35, 0.8);
            }
            
            /* Firefox scrollbars */
            * {
                scrollbar-width: thin;
                scrollbar-color: #1e3a8a rgba(15, 15, 35, 0.8);
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen app-container">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="app-header shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="main-content">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
