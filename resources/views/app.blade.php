<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Favicons -->
        <link href="/admin_assets/img/favicon.png" rel="icon">
        <link href="/admin_assets/img/apple-touch-icon.png" rel="apple-touch-icon">

        <!-- Google Fonts -->
        <link href="https://fonts.gstatic.com" rel="preconnect">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet" />

        <!-- Vendor CSS Files -->
        <link href="/admin_assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="/admin_assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
        <link href="/admin_assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
        <link href="/admin_assets/vendor/quill/quill.snow.css" rel="stylesheet">
        <link href="/admin_assets/vendor/quill/quill.bubble.css" rel="stylesheet">
        <link href="/admin_assets/vendor/remixicon/remixicon.css" rel="stylesheet">
        <link href="/admin_assets/vendor/simple-datatables/style.css" rel="stylesheet">

        <!-- Template Main CSS File -->
        <link href="/admin_assets/css/style.css" rel="stylesheet">

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="toggle-sidebar">
        @inertia

        <!-- Vendor JS Files -->
        <script src="/admin_assets/vendor/apexcharts/apexcharts.min.js"></script>
        <script src="/admin_assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="/admin_assets/vendor/chart.js/chart.umd.js"></script>
        <script src="/admin_assets/vendor/echarts/echarts.min.js"></script>
        <script src="/admin_assets/vendor/quill/quill.js"></script>
        <script src="/admin_assets/vendor/simple-datatables/simple-datatables.js"></script>
        <script src="/admin_assets/vendor/tinymce/tinymce.min.js"></script>
        <script src="/admin_assets/vendor/php-email-form/validate.js"></script>

        <!-- Template Main JS File -->
        <script src="/admin_assets/js/main.js"></script>
    </body>
</html>
