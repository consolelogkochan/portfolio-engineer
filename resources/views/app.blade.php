<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @viteReactRefresh
    @vite('resources/js/app.tsx')

    {{-- サーバーサイドで出力するメタ情報（7-2）。$pageMeta は各Controller/bootstrap/app.phpが
         withViewDataで渡す配列（title/description/ogImage/ogType/ogUrl）で、
         値の解決（デフォルトへのフォールバック等）はすべて app/Services/PageMetaBuilder.php 側で
         完了させ、ここでは受け取った値をそのまま出力するだけにする。
         <title>のみReact側（PageMeta.tsx）もdata-inertia付きで出力するが、Inertiaがdata-inertia無しの
         titleを強制削除するため重複しない。og:*/twitter:*/descriptionはReact側では出力しないこと
         （data-inertia属性を持たないためInertiaの管理外となり、重複して残ってしまう。実測で確認済み）。 --}}
    <title>{{ $pageMeta['title'] }}</title>
    <meta name="description" content="{{ $pageMeta['description'] }}" />

    <meta property="og:title" content="{{ $pageMeta['title'] }}" />
    <meta property="og:description" content="{{ $pageMeta['description'] }}" />
    <meta property="og:type" content="{{ $pageMeta['ogType'] }}" />
    <meta property="og:url" content="{{ $pageMeta['ogUrl'] }}" />
    <meta property="og:image" content="{{ $pageMeta['ogImage'] }}" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $pageMeta['title'] }}" />
    <meta name="twitter:description" content="{{ $pageMeta['description'] }}" />
    <meta name="twitter:image" content="{{ $pageMeta['ogImage'] }}" />

    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>