---
title: "portfolio-engineer"
category: "個人開発"
status: "公開中"
featured: true
summary: "エンジニアとしてのポートフォリオサイト。設計・実装の過程を含めて公開しています。"
publishedAt: "2026-03-01"
period:
  start: "2026-03-01"
role:
  - "設計"
  - "実装"
technologies:
  - "Laravel"
  - "Inertia.js"
  - "React"
  - "TypeScript"
  - "Tailwind CSS"
aiTools:
  - "Claude Code"
thumbnail: "/images/works/portfolio-engineer/thumbnail-test.webp"
---

<!-- ダミーデータ：表示確認用（本文タイポグラフィ確認用サンプル） -->

## 背景

エンジニアとしての実績を伝えるポートフォリオサイトが欲しかったが、既存のテンプレートは**設計や実装の過程**を伝えるのに向いていなかった。そこで、Laravel + Inertia.js + React構成で一から構築することにした。

このサイトで特に意識したのは以下の点。

- 設計判断の理由を残すこと
- 作品ごとにMarkdownで本文を管理できること
- ダークテーマを基調にした一貫したデザイントークン

### 技術選定

バックエンドはLaravel、フロントはInertia.js経由のReact + TypeScriptを採用した。理由は*学習コストを抑えつつ*、SPAらしい体験を実現できるため。

コンテンツはGit管理のMarkdownファイル（`content/works/*.md`）とし、CMSは導入していない。

## 工夫した点

1. デザイントークンをCSS変数で一元管理し、コンポーネントはsemantic層のみ参照する
2. `WorkCard`は画像全面オーバーレイパターンを採用し、クリック領域は擬似要素で拡張した
3. 本文の整形は `@tailwindcss/typography` を使わず自前CSSで対応し、既存のダーク＋緑トークンと衝突しないようにした

> 大切なのは、見た目を作り込むことよりも「なぜその設計にしたか」を残すこと。
> 完成度よりも判断の過程を優先した。
