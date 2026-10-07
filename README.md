# portfolio-engineer

Kotaro Izaki のポートフォリオサイトのソースコードです。このサイト自体が、設計から本番の運用までを記録しながら作った作品の1つです。

[![CI](https://github.com/consolelogkochan/portfolio-engineer/actions/workflows/ci.yml/badge.svg)](https://github.com/consolelogkochan/portfolio-engineer/actions/workflows/ci.yml)

## このプロジェクトについて

AIの出力はその場限りです。このプロジェクトでは、実装をAIと進めながら、判断の理由を記録し、手順を作り、プロジェクトを進める仕組みを試してきました。

- **判断の理由を残す**：なぜその設計にしたか、いつ見直すかを記録する
- **更新しやすい**：作品ごとにMarkdownで本文を書き、Gitで管理する
- **運用まで自分で担う**：本番環境の構築からデプロイ、障害への対応までを自分で行う

判断の理由を記録した例として、本番サーバーの構築と運用の手順書（[docs/server-setup.md](docs/server-setup.md)）を公開しています。設定ごとに、なぜそうしたか、いつ見直すかを書いています。

## 技術スタック

| 領域 | 使っているもの |
| --- | --- |
| バックエンド | Laravel 13（PHP 8.5） |
| フロントエンド | Inertia.js、React 19、TypeScript、Tailwind CSS v4 |
| コンテンツ | Markdown（front matter 付き）、Zod によるスキーマの検証 |
| 本番環境 | VPS（Ubuntu）、Nginx、PHP-FPM |
| CI・デプロイ | GitHub Actions |
| 開発 | Laravel Sail（Docker）、Claude Code、Claude |

Inertia.js は、サーバー側のアダプタ（inertia-laravel）が v2、React 側（@inertiajs/react）が v3 系です。

## 構成

コンテンツはデータベースを使わず、リポジトリ内のMarkdownファイルで管理しています。作品を追加するときは、ファイルを書いてpushするだけです。

```
content/          # サイトのコンテンツ（Markdown）
  works/          # 作品（1作品 = 1ファイル）
  logs/           # 開発ログ（作品と同じ名前のファイルで対応づける）
  about.md        # About ページ
app/
  Services/       # Markdownの読み込みとHTMLへの変換、メタ情報の組み立て、画像の最適化など
resources/js/
  Pages/          # Inertiaのページ
  Components/     # 画面の部品
scripts/          # コンテンツの検証（Zod）
docs/
  server-setup.md # 本番サーバーの構築と運用の手順書（判断の理由と、見直す条件を含む）
bin/              # デプロイと巻き戻しのスクリプト
.github/workflows/ # CI とデプロイ
```

## 品質の確認

コミットの前とCIで、型の検査、lint、整形の確認、テスト、コンテンツの検証、依存の脆弱性の検査を行います。デプロイは、CIが緑であることを確かめてから、GitHub Actions から手動で起動します。

## 利用について

このリポジトリには、オープンソースのライセンスを付けていません。コードの閲覧・参考はご自由にどうぞ。無断での複製・転用はご遠慮ください。

© 2026 Kotaro Izaki
