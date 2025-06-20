@if (Sentinel::check() && Sentinel::getUser()->hasAccess(['admin.access']))
    <script
            async defer
        src="https://code.jquery.com/jquery-3.4.1.js"
        integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU="
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="/packages/linecore/builder/fontawesome-pro-5.12.0-web/css/all.min.css?7">
    <script async defer src="/packages/linecore/builder/js/froala.js"></script>
    <link rel="stylesheet" href="/packages/linecore/builder/css/froala.css">
    <script async defer src="/packages/linecore/builder/js/quick_edit.js"></script>
@endif
