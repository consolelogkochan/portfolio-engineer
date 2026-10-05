---
title: "portfolio-engineer"
category: "個人開発"
status: "公開中"
featured: true
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
thumbnail: "/images/works/portfolio-engineer/thumbnail-test.webp"
gallery:
  - src: "/images/works/portfolio-engineer/gallery/portfolio-ver2-1.webp"
    alt: "portfolio-engineerのトップページ。ヒーローセクションに「Kotaro's Lab」のロゴとキャッチコピーを表示"
    caption: "トップページ：キャッチコピーで自己紹介"
  - src: "/images/works/portfolio-engineer/gallery/portfolio-ver2-2.webp"
    alt: "Worksセクション。開発リソース集約ダッシュボード「Dev-Cockpit」など6件の作品カードを一覧表示"
    caption: "Works一覧：制作実績をカード形式で紹介"
  - src: "/images/works/portfolio-engineer/gallery/portfolio-ver2-3.webp"
    alt: "About meセクション。プロフィールアイコンと経歴・強みを紹介する文章"
    caption: "About me：経歴と強みを紹介"
  - src: "/images/works/portfolio-engineer/gallery/portfolio-ver2-4.webp"
    alt: "My Skills Setセクション。BackendとFrontendに分けた技術スタック一覧"
    caption: "Skills：Backend/Frontendの技術スタック一覧"
  - src: "/images/works/portfolio-engineer/gallery/portfolio-ver2-5.webp"
    alt: "Contact meセクション。name・mail・messageの入力フォーム"
    caption: "Contact：問い合わせフォーム"
metrics:
  - label: "CIの自動検査"
    value: "13"
    unit: "項目"
  - label: "テスト"
    value: "21"
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
