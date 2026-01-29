<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{{__cms('Документация')}}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="/packages/linecore/cms/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/packages/linecore/cms/css/documentation-page.min.css">
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self' 'unsafe-inline' data: https:; img-src 'self' data: https: *; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:">

    @yield('styles')
    @yield('scripts_header')

</head>
<body>
@yield('main')

@stack('scripts')
</body>
</html>