<?php

return [
    /*
    |--------------------------------------------------------------------------
    | サイト表示名
    |--------------------------------------------------------------------------
    | <title>のサフィックス・OGP等の「サイト名」として使う表示名。
    | content/about.md の name（本名："伊崎孝太郎"）とは意図的に異なる値であり、
    | 両者を同期させる必要はない（PageMetaBuilderが組み立てる完成形タイトルにのみ使う）。
    */
    'site_name' => 'Kotaro',

    /*
    |--------------------------------------------------------------------------
    | デフォルトOGP画像
    |--------------------------------------------------------------------------
    | ogImageが未指定のページで使うフォールバック画像（相対パス）。
    | PageMetaBuilder がconfig('app.url')基点で絶対URL化する。
    */
    'default_og_image' => '/images/og/default.png',

    /*
    |--------------------------------------------------------------------------
    | ページごとの固定文言
    |--------------------------------------------------------------------------
    | キーはルート名ベース（例: 'works.index'）とする。PageMetaBuilder::build()は
    | 該当キーが無くても例外を投げず、title=null・description=''として静かに扱う
    | （キーの綴りミス等で意図せずmetaが空になりうる点に注意）。
    |
    | title => null は「サフィックスを付けずサイト名のみを表示する」という意図的な指定。
    | works.show / logs.show のようにdescriptionが常にfrontmatter由来の動的値になる
    | ページは、ここに固定文言を持たない（Controller側でoverridesとして渡す）。
    |
    | 【注意：ドット記法の罠】キーには 'works.index' のようにドットを含むものがある。
    | config('page_meta.pages.works.index') のようにドット記法で個別キーを直接引いては
    | ならない。Laravelがpages→works→indexと階層探索してしまい、'works.index'という
    | 単一キーとは一致しない（意図せずnull/空にフォールバックする。前半の実装中に実際に
    | 発生し修正済み）。必ずconfig('page_meta.pages')でpages配列をまるごと取得してから、
    | 通常の配列アクセス（$pages['works.index']）でリテラルなキーとして引くこと。
    | このconfigの読み出しはapp/Services/PageMetaBuilder.phpに集約されており、
    | 他の場所（Controller等）から直接configを引かない設計になっている。
    */
    'pages' => [
        'home' => [
            'title' => null,
            'description' => 'AIと協働し、判断を重ねるエンジニアのポートフォリオ',
        ],

        'works.index' => [
            'title' => 'Works',
            'description' => '制作した作品の一覧です。',
        ],

        'about' => [
            // descriptionはcontent/about.mdのtitle（肩書き）フィールドを
            // AboutControllerが都度overridesとして渡すため、ここでは固定しない。
            'title' => 'About',
        ],

        'contact' => [
            'title' => 'Contact',
            'description' => 'お問い合わせはこちら',
        ],

        'error' => [
            'title' => 'エラー',
            'description' => 'ページが見つからないか、エラーが発生しました。しばらくしてから再度お試しください。',
        ],
    ],
];
