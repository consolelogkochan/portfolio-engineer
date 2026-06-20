# portfolio-engineer

エンジニアとしてのポートフォリオサイト。
「全体の設計ができた上で、AIによる開発の効率化・自動化を行える」ことを示すことを目的に、
このサイト自体を一つの作品として、設計・実装の過程を含めて公開しています。

## 技術スタック
- Laravel / Inertia.js
- React / TypeScript
- Tailwind CSS
- 補助: Claude Code

## 構成の特徴
コンテンツはデータベースではなくMarkdownファイルで管理する
フラットファイル構成を採用しています。（詳細は開発と共に追記予定）

## 技術選定の補足

### Tailwind CSS v4（@tailwindcss/vite プラグイン方式）
Tailwind CSS v4 を採用しています。v4 では設定方式が刷新されており、
`tailwind.config.js` を使う v3 式ではなく、
Vite 公式プラグイン（`@tailwindcss/vite`）を `vite.config.js` に追加するだけで動作します。
v3 式の設定（`tailwind.config.js`、`@tailwind` ディレクティブ等）は混在させません。

### Inertia.js v2（@inertiajs/react v3.x）を採用した理由
本プロジェクトの目的は「学習・手堅い土台づくりに基づくポートフォリオサイト作成」です。
最新の v3 より、運用実績・事例・情報量が豊富な v2 の方が
現時点のプロジェクト方針に合致すると判断し採用しました。

### Inertia.js v2 特有の設定対処
Inertia.js v2 の JavaScript コア（`@inertiajs/core` v2）は、
初期ページデータを以下の形式でのみ読み取ります。

```html
<script data-page="app" type="application/json">{"component":"...",...}</script>
```

Laravel の `@inertia` ディレクティブはデフォルトでは旧式の
`<div id="app" data-page="...">` 形式を出力するため、そのままでは
"Cannot read properties of null (reading 'component')" エラーが発生します。

`.env` に以下を設定することで新形式の出力に切り替えています。

```
INERTIA_USE_SCRIPT_ELEMENT_FOR_INITIAL_PAGE=true
```

## ディレクトリ構成

```
content/          # サイトのコンテンツ（Markdown）。DBの代わりにファイルで管理
  works/          # 作品（1作品 = 1 Markdownファイル）
  logs/           # ビルドログ（開発過程の記事）
app/
  Services/       # ビジネスロジック（Markdownのパース、画像処理など）
resources/js/
  Pages/          # Inertiaのページ（URLと1対1で対応）
  types/          # TypeScript型定義
scripts/          # コンテンツ検証など、ビルド補助スクリプト
```

## 利用について
このリポジトリはポートフォリオ閲覧・学習参考を目的に公開しています。
コードの閲覧・参考はご自由にどうぞ。無断での複製・転用はご遠慮ください。
