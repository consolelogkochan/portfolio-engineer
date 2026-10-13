<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    {{-- サイトのアイコン（7-7c）。書き方は HTML の仕様の link rel=icon の例に従う。
         sizes・type は利用者の環境がアイコンを選ぶための手がかり（仕様上は助言）。
         favicon.ico と apple-touch-icon.png は、ブラウザや OS が決まったパスで取りに来るため、
         差し替えるときも同じ名前のまま置き換える（画像の名前の規則の例外。docs/server-setup.md の40節）。
         SVG は link からしか指さないので、差し替えるときは新しい名前で置き、ここを書き換える。 --}}
    <link rel="icon" href="/favicon.ico" sizes="16x16 32x32 48x48" type="image/vnd.microsoft.icon" />
    <link rel="icon" href="/favicon-v1.svg" sizes="any" type="image/svg+xml" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180" />
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
    {{-- canonical / og:url はクライアント側の画面遷移では更新されない（7-2でSSRを採らない判断をしているため）。
         ただし検索エンジンは各URLを直接取得するため、影響しない。 --}}
    <link rel="canonical" href="{{ $pageMeta['canonical'] }}" />
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