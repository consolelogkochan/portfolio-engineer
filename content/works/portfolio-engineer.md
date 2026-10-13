---
title: "portfolio-engineer"
category: "個人開発"
status: "公開中"
featured: true
# summary は OGP既定画像（public/images/og/site.png）に写している。変えたら画像も作り直す（7-7b）
summary: "AIと実装し、判断の理由を記録しながら、設計から本番の運用までを進めたポートフォリオサイト。このサイト自身が作品です。"
publishedAt: "2026-10-05"
period:
  start: "2026-06-14"
role:
  - "企画"
  - "設計"
  - "開発"
  - "運用"
technologies:
  - "Laravel"
  - "Inertia.js"
  - "React"
  - "TypeScript"
  - "Tailwind CSS"
  - "Ubuntu"
  - "Nginx"
  - "GitHub Actions"
aiTools:
  - "Claude Code"
  - "Claude"
repoUrl: "https://github.com/consolelogkochan/portfolio-engineer"
thumbnail: "/images/works/portfolio-engineer/thumbnail.webp"
gallery:
  - src: "/images/works/portfolio-engineer/gallery/top-v2.webp"
    alt: "トップページのファーストビュー。キャッチコピー「AIと作る。判断は、自分で。」と、注目の作品のショーケースの上端を表示"
    caption: "トップページ（サンプルデータでの表示）"
  - src: "/images/works/portfolio-engineer/gallery/works-v2.webp"
    alt: "Works一覧。portfolio-engineerと、3件のサンプルの作品のカードを並べて表示"
    caption: "Works一覧（サンプルデータでの表示）"
  - src: "/images/works/portfolio-engineer/gallery/work-detail-v2.webp"
    alt: "作品詳細ページ。作品名、ギャラリー（開発ログの画面を表示中）、その下に数値・概要とケーススタディの冒頭を表示"
    caption: "作品詳細：ギャラリーと、概要・ケーススタディ"
  - src: "/images/works/portfolio-engineer/gallery/log-detail.webp"
    alt: "開発ログの詳細ページ。タイトル「開発ログ：構想から本番運用までの判断と失敗」と本文の冒頭を表示"
    caption: "開発ログ：判断と失敗をフェーズごとに記録"
  - src: "/images/works/portfolio-engineer/gallery/about.webp"
    alt: "Aboutページ。アバター、名前、肩書き、スキルと本文の冒頭を表示"
    caption: "About：経歴とスキル"
  - src: "/images/works/portfolio-engineer/gallery/contact.webp"
    alt: "お問い合わせページ。名前・メールアドレス・件名・内容の入力フォーム（入力前）を表示"
    caption: "Contact：問い合わせフォーム"
# metrics は実態の写し。CIの検査の項目（.github/workflows/ci.yml）・テストの件数（php artisan test）・
# 手順書の節（docs/server-setup.md）を変えたら直す。作品詳細の gallery の画像にも写っている（7-7b）
metrics:
  - label: "CIの自動検査"
    value: "13"
    unit: "項目"
  - label: "テスト"
    value: "29"
    unit: "件"
  - label: "手順書"
    value: "41"
    unit: "節"
---

## 背景

エンジニアとしての実績を伝えるポートフォリオサイトが欲しかったが、以前WordPressで作ったサイトは更新のたびにGUIを操作する必要があり、手間がかかっていた。また、既存のテンプレートは**設計や運用の過程**を伝えるのに向いていなかった。そこで、実装はAIと進めながら、一から構築することにした。

## コンセプト

1. **判断の理由を残す**：なぜその設計にしたか、いつ見直すかを記録する
2. **更新しやすい**：作品ごとにMarkdownで本文を書き、Gitで管理する
3. **運用まで自分で担う**：本番環境の構築からデプロイ、障害への対応までを自分で行う

## 技術選定

学習していた技術の中心がLaravelとReactだったため、この2つを組み合わせた。このサイトは、問い合わせフォームのようにサーバー側の処理が必要な部分はあるものの、ほとんどは読むだけのページである。そのため、APIを別に用意する方式よりも、Inertia.jsでサーバーから画面へデータを直接渡す方式のほうがシンプルに実装できると判断した。

日常的な運用を想定し、デプロイはGitHub Actionsから行えるようにした。インフラまわりを一度学びたかったため、VPSにUbuntu + Nginx + PHP-FPMを自分でセットアップしている。

## 工夫した点

1. **判断の理由を残す**：サーバーの設定とその理由を手順書にまとめ、「変えない」と決めたことにも見直す条件を添えた
2. **更新しやすい**：作品の情報（front matter）はZodでスキーマを検証し、コミット前とCIの両方で検査する。書き間違いは公開の前に見つかり、1件が壊れてもサイト全体は落とさない
3. **運用まで自分で担う**：ビルドはCIで行い、サーバーには成果物だけを送る形にした。サーバー上でビルドすると、ビルドに使う多数の外部パッケージのコードを本番で動かすことになり、セキュリティの面で構造的な弱点になる。あわせて、サーバー上で管理するツール（Node.js）が減り、運用の負担も軽くなった

> AIの出力はその場限り。だから判断の理由を記録し、手順を作ってきた。このサイトは、その仕組みを試しながら作った作品である。
