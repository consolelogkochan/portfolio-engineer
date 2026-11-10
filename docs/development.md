# 開発者向けの構成と様式の一覧

## この文書について

**意義**：コードのレビューとリファクタリングの土台である。依存の更新などの前に、コードの様式と、問題になりうる箇所を一目で確かめるために使う。読み手には AI も含む（「`docs/development.md` の様式に従う」と指示できるようにする）。

**読み方**：

- 3節「事実の正の置き場所」と4節「同期が要る箇所」は、コードを変える前に見る。変える事実の正がどこにあり、ほかに直す場所があるかが分かる
- 5節「様式の規則」は、規則ごとに、それを確認するツールを書いた。「ツールでの確認は無い」と書いた規則は、レビューで見る
- 7節「注意」は、知ったうえで受け入れて残しているもの。直す前に、受け入れた理由と再検討の条件を見る

**更新の規則**：

- 構成や様式を変えるコミットでは、この文書も同じコミットで直す
- 今の状態の一覧なので、コードの中身や値は写さず、場所（ファイル・シンボルの名前）と規則を指す。規則の中身（例：字下げの幅）を書くときも、正の設定のファイルを指す
- 現在形で書くのは、確かめた事実だけ
- ほかの文書との分担：ツールと版は [`docs/environment.md`](environment.md)、持ち越しと受け入れの再検討の条件は [`docs/ledger.md`](ledger.md)、サーバーの設定とその判断の経緯は [`docs/server-setup.md`](server-setup.md)、公開の後の運用の一覧は [`docs/operations.md`](operations.md)。この文書は、それらを写さずに指す

## 1. 全体の流れ

- **コンテンツ**：`content/` の Markdown（front matter 付き）→ `app/Services/ContentParser.php`（front matter と本文を分ける）→ `app/Services/ContentRepository.php`（一覧・詳細・公開の判定・既定値）→ `app/Http/Controllers/` → Inertia の props → `resources/js/Pages/`
- **本文の HTML**：Controller が `app/Services/MarkdownRenderer.php` で HTML にし、`bodyHtml` として渡す。Pages の TSX が `dangerouslySetInnerHTML` で出す
- **メタ情報**：Controller が `app/Services/PageMetaBuilder.php` で組み立て、`withViewData` の `pageMeta` として `resources/views/app.blade.php` に渡す。`<title>` だけは React の側（`resources/js/Components/PageMeta.tsx`）も出す
- **問い合わせ**：`resources/js/Pages/Contact/Index.tsx`（Zod で先に確かめる）→ `app/Http/Requests/ContactRequest.php`（バリデーションとスパムの判定）→ `app/Http/Controllers/ContactController.php` → `app/Mail/ContactMail.php`（訪問者の本文は `app/Services/UntrustedMarkdownRenderer.php` で HTML にする）
- **内容の検証**：`scripts/validate-content.ts` が `resources/js/types/` の Zod のスキーマで `content/` を確かめる（pre-commit と CI。6節）

## 2. 構成と責務

| 場所 | 受け持つもの | 受け持たないもの |
| --- | --- | --- |
| `app/Services/` | Markdown の読み込み（`ContentParser`）、コンテンツの取得と公開の判定（`ContentRepository`）、本文の HTML への変換（著者の本文は `MarkdownRenderer`、訪問者の本文は `UntrustedMarkdownRenderer`）、メタ情報の組み立て（`PageMetaBuilder`）、画像の変換（`ImageService`） | 値の正当性の検証（Zod の仕事。`ContentParser` は形の最低限だけを見る） |
| `app/Http/Controllers/` | ページごとに、Repository と Builder を呼んで props と `pageMeta` を渡す。サイトマップ（`SitemapController`） | Markdown のファイルを直接読むこと（`ContentRepository` を経由する。`ContentRepository::getAbout` の説明） |
| `app/Http/Requests/ContactRequest.php` | 問い合わせのバリデーションと、スパムの判定（`detectSpam`） | 判定の後の応答（静かに弾くか、エラーを返すかは `ContactController`。分け方は `SpamReason::isSilent`） |
| `app/Http/Middleware/HandleInertiaRequests.php` | すべてのページに共有する props（flash の文言、サイト名） | — |
| `app/Http/Middleware/ContentSecurityPolicy.php` | `Content-Security-Policy` のヘッダ（10節）。全体のミドルウェアとして `bootstrap/app.php` で登録する | 通信の方針のヘッダ（HSTS など。Nginx が付ける。10節） |
| `app/Mail/` | 問い合わせのメール | — |
| `app/Enums/` | スパムの判定の理由（`SpamReason`） | — |
| `app/Exceptions/` | コンテンツの例外（無い・読めない、壊れている、非公開） | — |
| `app/Console/Commands/` | 画像の WebP への変換（`images:optimize`。手で動かす） | — |
| `bootstrap/app.php` | ルートとミドルウェアの登録、例外の応答（送信の上限を超えたときの応答、エラーのページ） | — |
| `routes/web.php` | URL とコントローラの対応。robots.txt（クロージャ） | — |
| `config/page_meta.php`・`config/spam.php`・`config/image.php` | 自前の設定（サイト名とページのメタ情報、スパムの判定、画像の変換） | ほかの `config/` のファイルは Laravel が用意したもの |
| `resources/js/Pages/` | Inertia のページ（ページ名とファイルの対応は `resources/js/app.tsx` の `resolve`。すべてのページを最初にまとめて読む。9節） | — |
| `resources/js/Components/` | 画面の部品。`ui/` は見た目だけの汎用の部品（`Button`・`Card`・`Tag`） | — |
| `resources/js/Layouts/BaseLayout.tsx` | ヘッダー・ナビ・フッター（`app.tsx` がすべてのページに付ける） | — |
| `resources/js/hooks/useCarousel.ts` | 画像の切り替え（embla）の動きの配線：状態の同期とキーボード | カウンター・ボタン・ドット・中身の描き方と位置（`Gallery.tsx`・`FeaturedShowcase.tsx` がそれぞれ持つ） |
| `resources/js/types/` | Zod のスキーマ（作品・開発ログ・問い合わせ）と、その型 | — |
| `resources/js/navigation.ts` | ナビに並べるページ | — |
| `resources/css/app.css` | 色の変数（base 層と semantic 層）、フォント、本文（`.case-study`）の見た目 | — |
| `resources/views/` | 最初の HTML（`app.blade.php`）、メールのテンプレート（`mail/`）、メールの部品（`vendor/mail/` の `html/`・`text/`） | — |
| `content/` | 作品・開発ログ・About の本文と front matter。開発ログは作品と同じ名前のファイルで対応づける | — |
| `public/images/` | 画像。`og/` は og:image 用で、`images:optimize` の対象の外 | — |
| `scripts/` | 内容の検証（`validate-content.ts`）と、CSS の未定義の変数の検査（`check-css-vars.ts`） | — |
| `tests/Feature/` | テスト（すべて Feature。Unit のテストスイートは `phpunit.xml` に無い。Unit のテストを書くときに戻す） | — |
| `bin/` | デプロイと巻き戻しのスクリプト（`deploy.sh`・`rollback.sh`。サーバーで動く。手順は `docs/server-setup.md`）と、デプロイの後の応答確認のスクリプト（`check-site.sh`。`deploy.yml` の `verify` と、`ci.yml` の本番と同じ依存構成での表示の確認が動かす。引くパスの一覧はここだけにある。手元からも動かせる） | — |
| `docs/images/` | README の画像（`readme-top.webp`。中身は gallery の `top-v3.webp` と同じ） | サイトが配る画像（`public/images/`。名前の規則は手順書40節）。README の画像は gallery を直接参照しない（gallery の差し替えで黙って切れるため） |
| `.github/workflows/` | CI（`ci.yml`）とデプロイ（`deploy.yml`） | — |
| `database/` | Laravel が用意したもの。このアプリは DB を使わない | — |

## 3. 事実の正の置き場所

| 事実 | 正 | ほかの場所の引き方 |
| --- | --- | --- |
| サイト名 | `config/page_meta.php` の `site_name` | `PageMetaBuilder::siteName()` を経由する。画面には `HandleInertiaRequests::share()` の `siteName` で届く。エラーのページには `bootstrap/app.php` が直接渡す |
| トップの meta の説明 | `config/page_meta.php` の `pages` の `home` の `description`（先頭はトップの h1 の写し。4節） | `HomeController` が `PageMetaBuilder::build('home')` で読み、`resources/views/app.blade.php` が `description`・`og:description`・`twitter:description` に出す。ほかの固定のページ（Works 一覧・Contact）も、同じ形で `pages` の各キーから読む |
| OGP の既定画像 | `config/page_meta.php` の `default_og_image` | `PageMetaBuilder::build()` が、上書きの `ogImage` が無いときに使い、`absoluteUrl()` で絶対 URL にする。`app.blade.php` が `og:image`・`twitter:image` に出す。作品の詳細は作品の `ogImage`、開発ログの詳細は同じ slug の作品の `ogImage` があれば、それを使う（`WorkController::show()`・`LogController::show()`）。トップ・一覧・About・Contact・エラーのページは既定のまま |
| 作品の `summary` | `content/works/*.md` の front matter の `summary` | 画面：Works 一覧のカード（`WorkCard.tsx`）、トップのショーケース（`FeaturedShowcase.tsx`）、作品詳細の「概要」（`Works/Show.tsx`）。meta：`WorkController::show()` が `description` として `PageMetaBuilder::build()` に渡し、`app.blade.php` が `description`・`og:description`・`twitter:description` に出す。開発ログの詳細の meta は、開発ログ自身の `summary` を使う（`LogController::show()`）。og:image の既定の画像の中の文字にも写っている（4節） |
| ページのタイトルの区切り | `PageMetaBuilder::titleSuffix()` | エラーのページは `titleSuffix` を props で受け取り、結合だけを行う（`ErrorPage.tsx`） |
| サイトの絶対 URL | `PageMetaBuilder::absoluteUrl()`（`config('app.url')` を基点にする） | サイトマップ（`SitemapController`）と robots.txt（`routes/web.php`）もここを呼ぶ |
| スパムの判定の秒数と URL の数、画像の幅と品質 | `config/spam.php`・`config/image.php` | 呼び出し側（`ContactRequest`・`OptimizeImages`）は既定値を書かずに読む。キーがあることを `ConfigKeysTest` が確かめる。テスト（`ContactTest`）も設定から値を作る |
| スパムの判定の理由と、静かに弾くかどうか | `app/Enums/SpamReason.php`（`isSilent()`） | `ContactRequest::detectSpam()` が返し、`ContactController` が分ける。ログには `value` の文字列を残す |
| 問い合わせの成功の文言 | `ContactController::SUCCESS_MESSAGE` | 本当に送ったときと、静かに弾いたときの2か所で使う（ボットに違いを見せないため） |
| 作品の公開の状態の値 | PHP は `ContentRepository::STATUS_PUBLISHED` と `isPublishedWork()` | TS 側は `types/work.ts` の `status` の `z.enum`（4節） |
| 公開の判定と、新しい順の並べ替え | `ContentRepository` の `isPublishedWork()`・`isPublicLog()`・`sortByPublishedAtDesc()`・`existsAndIsPublic()` | 一覧・詳細・存在の確かめは、どれもこれらを呼ぶ |
| 日付の形（YYYY-MM-DD） | `resources/js/types/common.ts` の `isoDate` | 作品と開発ログのスキーマが引く |
| ナビのページ | `resources/js/navigation.ts` の `NAV_LINKS` | `BaseLayout.tsx` と `Home.tsx` が引く |
| 画像の切り替えの動き | `resources/js/hooks/useCarousel.ts` | `Gallery.tsx`・`FeaturedShowcase.tsx` が呼ぶ。見た目は各部品が持つ |
| 色 | `resources/css/app.css` の `@theme`（base 層の生の値と、semantic 層の用途名） | 部品は semantic 層の変数だけを使う（`app.css` の semantic 層の説明、`ui/Card.tsx`・`ui/Tag.tsx` の冒頭） |
| 画面幅の区切り | Tailwind の `--breakpoint-*`（既定の値） | クラスの `md:` など。`app.css` の中も `@variant md` で引く |
| 内容（front matter）の形 | `resources/js/types/work.ts`・`log.ts` の Zod のスキーマ | `scripts/validate-content.ts` が確かめる。PHP の側は形の最低限だけを見る（4節） |
| 問い合わせの入力の形 | サーバー：`ContactRequest::rules()`・`messages()`。クライアント：`types/contact.ts` | 2か所にある（4節） |
| 本文の Markdown の安全の説明 | `MarkdownRenderer` のクラスの説明 | Controller と Pages の TSX の `dangerouslySetInnerHTML` のところは、そこを指す |
| 確認の道具の範囲 | ESLint は `eslint.config.js` の `ignores`、Prettier は `.prettierignore`、`strict_types` は `StrictTypesTest::OWN_PATHS` | `package.json` の `lint`・`format`・`format:check` と、lint-staged は、同じ範囲を見る（6節） |

## 4. 同期が要る箇所

正が2つ以上あり、まとめずに残しているもの。

【注記（2026-11-10）】正が1つで、写しが README・GitHub の画面・画像の中にあり、ずれに気づく経路が無いものも、この表に置く（7-10e）。正を変えるコミットで、この表を見て写しも直す。GitHub の画面の欄はリポジトリの外にあり、今の中身は 2026-11-10 には確かめていない

| 事実 | 場所 | 片方を変えたら | まとめない理由 | 一致を保つ方法 |
| --- | --- | --- | --- | --- |
| 作品と開発ログの既定値（`.default(...)`） | `types/work.ts`・`types/log.ts` と、`ContentRepository` の `WORK_DEFAULTS`・`LOG_DEFAULTS` | もう片方も同じ値にする | TS は内容の検証と型のため、PHP は画面に渡す値のため。既定値を JSON のファイルに置いて両方から読む案は、少ない値のために読み込みの仕組みが増え、見合わないとして採らなかった（7-8b-5）。再検討の条件：TS と PHP の値が食い違うことが起きたとき、または既定値のある項目が増えたとき | PHP の値はテストで保つ（`ContentDefaultsTest`）。2か所の一致は、**仕組みは無い。人が同じコミットで直す** |
| 問い合わせのバリデーションの規則と文言 | `ContactRequest::rules()`・`messages()` と `types/contact.ts` | もう片方も直す | 言語をまたぎ、一致を確かめるテストを書きにくい（7-5c-2）。再検討の条件：文言を変えるとき、または項目を増やすとき | **仕組みは無い。人が同じコミットで直す** |
| 作品の公開の状態の値 | `ContentRepository::STATUS_PUBLISHED` と `types/work.ts` の `status` | もう片方も直す | 言語をまたぐ | **仕組みは無い。人が同じコミットで直す**（`STATUS_PUBLISHED` の説明が TS 側を指す） |
| 日付の扱い | PHP：`ContentParser`（引用符の無い日付も受け付ける）。TS：`isoDate`（文字列だけを受け付け、引用符の無い日付は Date になって止まる） | 日付の扱いを変えるときは両方を見る | TS は門番（CI と pre-commit）、PHP は本番での備え。PHP は文字列でない日付を壊れた1件として飛ばす | **一致させていない（意図して食い違わせている）**。TS が先に止めるので、CI を通った内容では表に出ない |
| front matter の型（`title`・`ogImage` など） | Zod のスキーマと、PHP の型つきの引数（`PageMetaBuilder::fullTitle()`・`absoluteUrl()`） | スキーマを緩めるときは、PHP の受け取り方も見る | 型の正当性は Zod に任せる分担 | CI と pre-commit の内容の検証（Zod）。Zod を通らない内容は PHP で `TypeError` になる（7節） |
| CI の Node の版の決まり方（`setup-node` の設定） | `.github/workflows/ci.yml` と `deploy.yml` の `actions/setup-node`（`node-version-file: package.json`・`check-latest: true`） | もう片方も同じにする | 検査と、本番に載るビルドが、別のワークフローに分かれている（21節）。版の範囲そのものは `package.json` の `engines` の1か所 | **仕組みは無い。人が同じコミットで直す**（2つのファイルのコメントが、両方に置くことを書いている。2026-10-27） |
| og:image を png・jpg にすること | `types/work.ts` の `ogImage` の正規表現と、`OptimizeImages::SCAN_DIRS` から `public/images/og` を外していること | もう片方も直す | 型の検証と、画像の変換の対象は別の道具 | `PageMetaTest` が、og:image が png か jpg であることと、ファイルがあることを確かめる |
| トップの h1 の文面 | `resources/js/Pages/Home.tsx` の h1 と、`config/page_meta.php` の `home` の `description` | もう片方も直す | 表示の文面と meta の説明は届き先が違う | **仕組みは無い。人が同じコミットで直す**（両方のコメントが互いを指す。台帳 41-12 の写しの行） |
| 固定のページのパス | `routes/web.php` と、`SitemapController::index()`・`bin/check-site.sh` の一覧（CI の表示の確認と `deploy.yml` の `verify` が使う）・`FaviconTest` | ページを足したら、サイトマップに足すかを決める | 目的が違う（サイトマップは全部を載せる。CI とテストは、壊れたら困るものだけを引く） | サイトマップは、**仕組みは無い。人が同じコミットで直す**。CI とテストは、無くなったパスで落ちる |
| タイトルの組み立ての規則 | `PageMetaBuilder::fullTitle()`・`titleSuffix()` と、`PageMetaTest` の中の組み立て | 規則を変えたらテストも直す | テストは本体と別に組み立てて照らすことに意味がある | テスト（食い違えば落ちる） |
| local・testing ではエラーのページを描かない、という説明 | `bootstrap/app.php` の `respond()`、`PageMetaTest`・`SharedPropsTest` の説明 | 3か所の説明を直す | どのファイルから読んでも分かるようにするため | **仕組みは無い。人が同じコミットで直す**（コメント） |
| 別名 `@`（`resources/js`） | `vite.config.js`・`tsconfig.json`・`scripts/tsconfig.json` | 3か所とも直す | 道具ごとに設定の置き場所が決まっている | `vite.config.js` は CI のビルド、`tsconfig.json` は CI の `tsc`。`scripts/tsconfig.json` は CI で型の確認をしていない（6節） |
| 本物のコンテンツの slug（`portfolio-engineer`）に頼るテスト | `WorkVisibilityTest`・`PageMetaTest`・`FaviconTest`・`ContentDefaultsTest` | slug を変えたらテストも直す | コンテンツの置き場所を差し替える仕組みが無い（`WorkVisibilityTest` の説明。`BrokenFrontMatterTest` だけは、アプリの基点のパスを一時ディレクトリに替えている） | テスト（slug が変われば落ちる） |
| キャッシュを作って確かめる手順 | `bin/deploy.sh` と `bin/rollback.sh` | もう片方も直す | それぞれをサーバーで単独で読めることを優先する | **仕組みは無い。人が同じコミットで直す**（CI の shellcheck は書き方だけを見る） |
| CI とデプロイのビルドの手順（`npm` のコマンドと段の構成） | `.github/workflows/ci.yml` の `npm ci`・`npm run build` と、`deploy.yml` の `build` ジョブ | もう片方も同じにする | 検査と、本番に載るビルドが、別のワークフローに分かれている（手順書21節・30節）。ずれると、CI が緑でも本番に載る成果物が検証したものと違いうる | **仕組みは無い。人が同じコミットで直す**（手順書40節の維持依存の索引、21節） |
| 作品の `metrics` の数（CI の自動検査・テスト） | `content/works/portfolio-engineer.md` の `metrics` と、`ci.yml`・`php artisan test`（数え方は6節） | テストや CI の段を変えるコミットで、同じく `metrics` の数も直す。数が変わっただけでは、作品詳細の gallery の画像は撮り直さない（画面の写真は撮った時点のもの） | ページの構成として、数があったほうが視覚の印象が違う。数の裏づけは6節の一覧（人。7-9g） | **仕組みは無い。人が同じコミットで直す** |
| 公開の後の運用 | 正は手順書（`docs/server-setup.md`）と台帳（`docs/ledger.md`）。一覧は `docs/operations.md` | `docs/operations.md` も直す | 一目で網羅を点検するための索引で、正の記録の置き場所とは目的が違う | **仕組みは無い。人が同じコミットで直す** |
| README の「技術スタック」 | 正：`composer.json`・`package.json`。写し：`README.md` の「技術スタック」 | 大きな版を上げたら（Laravel・PHP・React・TypeScript・Tailwind CSS など）、写しも直す | 読む人への案内で、正のファイルをそのまま見せる形にしていない | **無い。正を変えるときに、この行を見て直す** |
| README の「構成」（フォルダの木と、`docs/` の各文書の説明） | 正：リポジトリのフォルダと各文書。写し：`README.md` の「構成」 | フォルダや文書を足す・消す・役目を変えたら、写しも直す | 同上 | **無い。正を変えるときに、この行を見て直す** |
| README の画像 | 正：サイトのトップの画面。写し：`docs/images/readme-top.webp`（中身は gallery の `top-v3.webp` と同じ。2節） | トップの見た目を変えたら、撮り直すかを決める | 画面の写真は、撮った時点のもの | **無い。正を変えるときに、この行を見て直す** |
| GitHub のリポジトリの説明（リポジトリの外） | 正：`README.md` の冒頭の説明の文（見出しの下の1文目）。写し：GitHub のリポジトリの説明（その文を短くしたもの） | 正を変えたら、写しも直す（`README.md` の冒頭のコメントが、この行を指す） | GitHub の画面の欄で、リポジトリのファイルからは作られない | **無い。正を変えるときに、この行を見て直す** |
| GitHub のリポジトリの Web サイトの欄と、プロフィールの URL の欄（リポジトリの外） | 正：サイトの URL（本番の `.env` の `APP_URL`。手順書19節）。写し：2つの欄と、`README.md` の冒頭の「サイト：」の行 | サイトの URL を変えたら、3か所とも直す | 同上。README の行は、読む人への案内 | **無い。正を変えるときに、この行を見て直す** |
| GitHub のプロフィールの Bio（リポジトリの外） | 正：`content/about.md` の「取り組んでいること」「どう進めてきたか」。写し：GitHub のプロフィールの Bio | 正を変えたら、写しも直す（`content/about.md` の front matter のコメントが、この行を指す） | GitHub の画面の欄で、リポジトリのファイルからは作られない | **無い。正を変えるときに、この行を見て直す** |
| og:image の既定の画像の中の文字 | 正：`content/works/portfolio-engineer.md` の `title` と `summary`。写し：`public/images/og/site.png` の中の文字 | 正を変えたら、画像を作り直す（手順書40節） | 画像の中の文字は、テストも検査も見ない（手順書40節が受け入れている） | **無い。正を変えるときに、この行を見て直す** |
| 作品詳細の gallery の画面の写真 | 正：サイトの画面。写し：作品の front matter の `gallery` の画像 | 撮り直すかは、手順書40節の撮り直しの条件で決める（数が変わっただけでは撮り直さない。上の `metrics` の行） | 画面の写真は、撮った時点のもの | **無い。正を変えるときに、この行を見て直す** |

## 5. 様式の規則

| 対象 | 規則 | 確認するツール |
| --- | --- | --- |
| PHP | 自前のファイルには `declare(strict_types=1);` を付ける。範囲は `tests/Feature/StrictTypesTest.php` の `OWN_PATHS`。Laravel が用意したファイル（ほかの `config/`・`bootstrap/providers.php`・`database/`・`public/index.php`）には付けない | テスト（`StrictTypesTest`。範囲の外は見ない） |
| PHP | 整形は Pint の既定（Laravel の preset。`pint.json` は無い） | pre-commit（lint-staged の `**/*.php`）と CI（`pint --test`） |
| PHP | クラスの定数は型を付ける（`private const array …`） | ツールでの確認は無い（レビューで見る） |
| PHP | 字下げは4（`.editorconfig` の `[*]`） | Pint |
| TS・JS | 書き方は ESLint（`eslint.config.js`）、整形は Prettier（`.prettierrc`。字下げは2）。対象から外すものは `.prettierignore` に理由と一緒に書く | pre-commit と CI（`npm run lint`・`format:check`） |
| TS | 型は strict（`tsconfig.json`） | CI（`tsc --noEmit`。`scripts/` は見ない） |
| TS | Zod は今の書き方（`z.email()`・`z.url()` のような最上位の形） | ツールでの確認は無い（レビューで見る） |
| 字下げ（エディタ） | `.editorconfig`：Prettier が扱うもの（js・ts・tsx・json・css・`.prettierrc`）と yml は2、`composer.json` と `compose.yaml` は4、そのほかは4 | Prettier が整形する範囲は Prettier。ほかは、ツールでの確認は無い（レビューで見る） |
| CSS | 色は semantic 層の変数を使う。部品で色を直接書かない | ツールでの確認は無い（レビューで見る）。未定義の変数だけは `check:css-vars`（pre-commit と CI） |
| CSS | 画面幅の区切りは Tailwind の区切り（クラスの `md:`、CSS の中は `@variant md`）。`@media` に幅を手で書かない | ツールでの確認は無い（レビューで見る） |
| コメント | 日本語で書く。今の事実を書き、作業のメモや予定（「〜に変更」「〜で要判断」）を残さない。事実と違うコメントを残さない。同じ説明を写さず、正の1か所を指す | ツールでの確認は無い（レビューで見る） |
| 命名 | PHP：クラスは PascalCase、メソッドは camelCase、定数は大文字のスネークケース、enum の case は PascalCase。TS：部品のファイルと名前は PascalCase、フックは `use` で始める。front matter のキーは camelCase。slug はファイル名（拡張子なし） | ツールでの確認は無い（レビューで見る。フックの使い方は ESLint の react-hooks の規則が見る） |
| エラーの扱い | 外から来るデータ（ファイル・front matter・リクエスト）の形が違うときは、黙って補わず、壊れたものとして扱う。コンテンツは `ContentParseException` にして、一覧ではその1件を飛ばす | テスト（`BrokenFrontMatterTest`） |
| エラーの扱い | 設定は、呼び出し側で既定値を書かずに読む（キーを消したことを隠さないため） | テスト（`ConfigKeysTest`。今あるキーだけを見る） |
| 本文の HTML | 著者が書く本文は `MarkdownRenderer`、運営者以外が書く本文は `UntrustedMarkdownRenderer` で変換する | 問い合わせのメールは `ContactMailTest`。どちらを使うかは、ツールでの確認は無い（レビューで見る） |

## 6. 品質の確認

何を、何で、どこまで確かめているかの一覧である。何がどんな意図で確かめているか、何を見ていないかを書く（7-9g で広げた。2026-11-04）。抜け漏れと一貫性は、この節で点検する。README の「品質の確認」は、この節を指す。

- 意図の正は、各行の「意図の正の場所」にある。ここには要点だけを書き、理由の全文は写さない
- 確かめの道具・範囲・段を変えるコミットでは、この節も同じコミットで直す（この文書の「更新の規則」）

### 6-1. 自動で動くもの

**CI（`.github/workflows/ci.yml`）**：`main` への push と PR で動く。段は上から順に動き、1つが失敗すればそこで止まる。ジョブの上限は15分で、権限はリポジトリの読み取りだけ（`ci.yml` のコメント）。

| 段 | 確かめること | 失敗の条件 | 意図 | 意図の正の場所 |
| --- | --- | --- | --- | --- |
| `shellcheck --severity=warning bin/*.sh` | `bin/` のシェルの書き方 | warning 以上が1件でも | シェルの誤りを、Node・PHP の準備より先に見つける。info は版によって出たり出なかったりするので対象にしない | `ci.yml` のコメント、手順書30節 |
| `npx tsc --noEmit` | `resources/js` の型（`tsconfig.json`。`scripts/` は見ない） | 型の誤り | 7-9g で後から書いた意図（2026-11-04。当時の記録は無い）：型の誤りを、動かす前に見つける。ビルド（Vite）は型を確かめずに変換するので、型の誤りがあってもビルドは通る | この表 |
| `npm run lint` | ESLint（`eslint.config.js`） | error | 7-9g で後から書いた意図（2026-11-04。当時の記録は無い）：型では見つからない書き方の誤り（React のフックの使い方など。5節）を見つける | この表 |
| `npm run format:check` | Prettier の整形 | 整形されていないファイル | 様式（5節）。対象の外は下の 6-6 | `.prettierignore` |
| `./vendor/bin/pint --test` | PHP の整形（Blade は見ない。6-6） | 整形されていないファイル | 様式（5節） | 5節 |
| `composer audit` | PHP の依存の勧告 | 1件でも | 勧告のある依存を本番に載せない | 手順書21節 |
| `npm audit --omit=dev --audit-level=high` | 本番に載る npm の依存の勧告 | high 以上 | 本番に載るものは厳しく見る | `ci.yml` のコメント、手順書21節 |
| `npm audit --audit-level=critical` | 開発の依存を含む全部の勧告 | critical | 開発だけのものは緩く見る | 同上 |
| `npm run build` | ビルドが通る | ビルドの失敗 | 7-9g で後から書いた意図（2026-11-04。当時の記録は無い）：本番に載る成果物を、同じ手順で作れることを確かめる。後の CSS の変数の検査とテストは、この成果物を使う（`ci.yml` のコメント） | この表、`ci.yml` のコメント（テストの段） |
| `npm run check:css-vars` | CSS の未定義の変数（`var(--名前)`） | 1件でも | 名前を間違えると、ほかの検査を通り、画面が黙って崩れる | `ci.yml` のコメント、手順書30節 |
| `php artisan test` | テスト（6-2） | 1件でも失敗 | 6-2 | 各テストのファイル |
| `npx tsx scripts/validate-content.ts` | `content/` の front matter（Zod のスキーマ）と、作品の front matter の画像（`thumbnail`・`ogImage`・`gallery`）が `public/` にあること | 1件でも | 7-9g で後から書いた意図（2026-11-04。当時の記録は無い）：front matter の形の誤りを、本番に出す前に止める。PHP の側は形の最低限しか見ず、Zod を通らない内容は詳細のページを500 にしうる（7節）。画像を見る意図は、差し替えのたびに参照を書き換えるので書き間違いの機会があるため（スクリプトのコメント） | この表、スクリプトのコメント（画像の分） |
| `composer install --no-dev` と、本番と同じ依存での表示の確認 | 本番と同じ依存で手元にサーバーを立て、`bin/check-site.sh` の一覧のパスが `200` を返す | `200` でないパス | `require` と `require-dev` の振り分けの誤りは、開発の環境では気づけず、本番でだけ500 になる（実際に起きた） | `ci.yml` のコメント、手順書31節 |

- 作品の `metrics` の「CI の自動検査 14」は、`ci.yml` の `run:` の18個から、準備の4つ（`composer install`・`.env` の写し・`key:generate`・`npm ci`）を除いた数。上の表の最後の行は、`run:` の2つ（入れ直しと表示の確認）にあたる
- 引くパスの一覧は `bin/check-site.sh` の1か所にあり、`deploy.yml` の `verify` と同じものを引く。固定のページに加えて、作品と開発ログの詳細を1件ずつ引く（理由はスクリプトのコメント）

**pre-commit**（`.husky/pre-commit` が Sail の中で lint-staged を動かす。対象は `lint-staged.config.js`）：コミットの前に、ステージしたファイルについて動く。

| 対象 | 動くもの |
| --- | --- |
| js・ts・tsx | ESLint（`--fix`）と Prettier |
| json・yml・yaml・css | Prettier |
| php | Pint |
| `content/` の md | 内容の検証（全件） |
| `resources/` の css・ts・tsx | ビルドと CSS の変数の検査 |

- pre-commit で動くものは、どれも CI でも動く。型・テスト・shellcheck・依存の検査・本番と同じ依存での表示の確認は、CI だけで動く

**デプロイ（`.github/workflows/deploy.yml`）**：人が CI の緑を確かめてから、手で起動する（手順書30節）。`build` はテストや検査を動かさない（CI の役目）。

| ジョブ | 確かめること | 失敗の条件 | 正の場所 |
| --- | --- | --- | --- |
| `build` | `npm ci` とビルドが通る。成果物に、ビルドしたコミットのハッシュを添える | ビルドの失敗 | 手順書30節 |
| `deploy` | `bin/deploy.sh` の終了コード。成果物とコミットの一致、作業ツリーとブランチ、`vendor/` のずれ、キャッシュが作られたかを見る | 0 でない | 手順書30節、`bin/deploy.sh` |
| `verify` | `bin/check-site.sh` で、公開の URL の一覧のパスが `200` を返す（接続できないときと 5xx は、3回まで引き直す） | `200` でないパス | 手順書30節の「デプロイの後の応答確認（7-9c）」 |

### 6-2. テスト

`php artisan test`（PHPUnit。`tests/Feature/`）。件数は PHPUnit の数え方で、データの組を含む（2026-11-04 に59件）。

| まとまり | 件数 | 確かめること | ファイル |
| --- | --- | --- | --- |
| 問い合わせ | 16 | 送信、スパムの判定（静かに捨てる・エラーを返す）、送信の失敗のときの文面とログ、`invalid_token` の文面、Reply-To、件名と名前の改行がヘッダを足さないこと、訪問者の本文の Markdown の変換（リンク・画像・生の HTML を外す） | `ContactTest`・`ContactMailHeaderTest`・`ContactMailTest` |
| ページのメタ情報 | 14 | 各ページの meta タグ、og:image のファイルがあることと png・jpg であること、作品の ogImage の差し替え、robots.txt | `PageMetaTest`・`RobotsTxtTest` |
| 内容 | 15 | 壊れた front matter を1件として飛ばすこと、日付の扱い、PHP の既定値、公開の状態（非公開の作品が404 で、一覧・サイトマップ・開発ログのリンクに出ない） | `BrokenFrontMatterTest`・`ContentDefaultsTest`・`WorkVisibilityTest` |
| 画面の共有とアイコン | 7 | サイト名がページとエラーのページ（ルートに一致しない要求）に届くこと、アイコンのリンクとファイル | `SharedPropsTest`・`FaviconTest` |
| 応答ヘッダ | 5 | CSP のヘッダが付くことと中身、開発サーバーの動いているときは付けないこと | `ContentSecurityPolicyTest` |
| 様式と設定 | 2 | 自前の PHP の `strict_types`、既定値を書かずに読む設定のキーがあること | `StrictTypesTest`・`ConfigKeysTest` |

- テストは、開発の依存（`require-dev` あり）と testing の環境で動く（6-5）
- 本物のコンテンツの slug に頼るテストがある（4節）
- テストの関数54個のうち34個に、名前の上の説明のコメントが無い（2026-11-04）。受け入れる。テストの名前と、上のまとまりの表で、何を確かめるかを追えるため（人。7-9g）

### 6-3. 手で動かすもの

| 道具 | いつ | 見るもの | 正の場所 |
| --- | --- | --- | --- |
| `npx tsx scripts/validate-content.ts` | 内容を書いたとき（pre-commit と CI でも動く） | 6-1 の内容の検証と同じ | — |
| `npm run check:css-vars` | CSS を変えたとき（ビルドの後。pre-commit と CI でも動く） | 6-1 と同じ | 手順書30節 |
| `bin/check-site.sh <URL>` | 手元のサーバー（`http://localhost`）や本番に向けて、表示を確かめたいとき | 一覧のパスが `200` を返すか。本番に向けると、パスの数だけ読み取りの要求を送る | スクリプトのコメント |
| `docker run --rm -v "$PWD:/mnt" -w /mnt koalaman/shellcheck:stable --severity=warning bin/*.sh` | `bin/` のスクリプトを変えたとき（手元に shellcheck が無いので、Docker のイメージで動かす） | 6-1 の shellcheck と同じ。版は CI と違いうる（2026-11-04 は 0.11.0） | — |
| `npx tsc -p scripts/tsconfig.json` | `scripts/` を変えたとき | `scripts/` の型（CI に無い。6-6） | — |
| `composer audit` と `npm audit`（CI と同じ引数） | 毎月1日と17日の点検（手元のコンテナで） | push しない期間に出た勧告 | 手順書40節、[`docs/operations.md`](operations.md) |
| ブラウザでの CSP の違反の確かめ | 外の読み込み先を足すとき、zod などの大きな版を上げるとき | 本番と同じ形のビルドでページを開き、CSP の違反が出ないか（やり方は10節。7-9e で行ったときのスクリプトはリポジトリの外） | 10節 |

### 6-4. 人が見るもの

| 何を | いつ | 正の場所 |
| --- | --- | --- |
| ブラウザでトップを開いて表示を見る。コンソールに CSP のエラーが出ていないことを見る | デプロイの後（`verify` に足す確かめ） | 手順書30節、[`docs/operations.md`](operations.md) の2節 |
| 問い合わせを1通送り、届くことを見る | 依存、問い合わせ、メールの処理を変えたデプロイの後 | 手順書30節、[`docs/operations.md`](operations.md) の2節 |
| サイトが開けること、転送、問い合わせを1通送ることなど | 月次の点検 | [`docs/operations.md`](operations.md) の1節 |

### 6-5. 見ていないもの（受け入れた穴）

| 見ていないもの | 受け入れた理由 | 再検討の条件 | 正の場所 |
| --- | --- | --- | --- |
| README の画像（`docs/images/readme-top.webp`）の参照が切れても、気づかない（内容の検証は `content/` だけを見る） | README の画像は gallery から切り離してあり、壊れるのは `docs/images/` のファイルを動かしたときだけ（人。7-9g） | README の画像を増やすとき | 台帳 41-9 |
| ブラウザで CSP の違反を拾う仕組みが CI に無い。テストは、ヘッダが付くことと中身だけを見る | ブラウザを CI で動かす仕組みを持ち続けることになる（人。7-9g）。代わりに、デプロイの後に人がコンソールを見る（6-4） | 外の読み込み先を足すとき、zod などの大きな版を上げるとき | 10節、台帳 41-9 |
| テストは開発の依存（`require-dev` あり）と testing の環境で動き、本番の構成でだけ起きる欠けは見ない。アプリのコードが `require-dev` の部品を使っていても、テストは通り、本番でだけ500 になる（手順書31節で実際に起きた形） | 本番と同じ依存（`--no-dev`）では PHPUnit が消えるので、テストを動かせない（`ci.yml` のコメント）。今ある確かめ：本番と同じ依存での表示の確認が、`require-dev` を外して8つのページを開く。31節の件のように、ページを開いた時点で壊れる形はこれで捕まる。残る穴：8つのページを開くだけでは通らない処理（問い合わせの送信、メールの組み立て、エラーのときの処理など）。これは、依存・問い合わせ・メールの処理を変えたデプロイの後に人が送る1通（6-4）と、毎月17日の点検の1通で埋める。この穴から壊れるのは、依存を足したり、`require` と `require-dev` の間で動かしたりしたときだけ（人。7-9g） | `require` と `require-dev` の間で依存を足したり動かしたりするとき | `ci.yml` のコメント、手順書31節 |
| 表示の確認と `verify` は状態コードだけを見る。表示の中身は見ない。詳細のページは作品と開発ログの1件ずつ | 中身はデプロイの後に人が見る（手順書30節の判断）。slug を全件並べると、作品を足すたびに直すことになる | 作品ごとに詳細のページの作りが違うようになったとき | 手順書30節、`bin/check-site.sh` のコメント |
| `verify` の時間の上限（`timeout-minutes`）の根拠は、パスの数で変わる（`deploy.yml` のコメント）。パスを足しても、上限が合っているかを確かめる仕組みは無い | 超えれば run が失敗するので、黙って通ることは無い（人。7-9g） | — | `deploy.yml` のコメント |
| メンテナンスモード（`php artisan down`）の503 で、サイト名が届くかを確かめていない。テストも足さない | デプロイはメンテナンスモードを使っていない（`bin/deploy.sh`、手順書30節） | デプロイで `php artisan down` を使うようにするとき | 台帳 41-7 |

### 6-6. 道具の対象の外

| 範囲 | 理由 |
| --- | --- |
| Markdown（`content/`・`docs/`・README など）の整形 | 書く人が形を決める文章のため（`.prettierignore`） |
| `composer.json`・`package-lock.json`・`compose.yaml` の整形 | ほかの道具が書く、または雛形のため（`.prettierignore`） |
| `scripts/` の型の確認（`tsc -p scripts/tsconfig.json`） | CI の手順に無い。手元で動かす（6-3） |
| Blade（`resources/views/`）の整形 | 規則が無い。Prettier は扱わない。pre-commit は `**/*.php` に当たる Blade も Pint に渡すが、CI の `pint --test` は Blade を見ない（`pint --test` が見るファイルの数が、Blade を除いた PHP のファイルの数と同じだった） |
| Laravel が用意した PHP の `strict_types` | 付けない（5節） |
| テスト・型の確認・shellcheck・依存の検査 | pre-commit では動かさない。CI だけ |

### 6-7. 書かないと決めたもの

| もの | 理由 | 再検討の条件 | 正の場所 |
| --- | --- | --- | --- |
| 作品詳細の開発ログと GitHub へのリンクのテスト | リンクは出ないことが正しい場合があり、黙って切れうるのはコードではなくデータの側（slug の不一致、開発ログの `draft`） | 作品または開発ログの slug を変えるとき、開発ログのファイルを移すとき | 台帳 41-7 |

### 6-8. 一致を保つ方法

確かめの道具に関わるものだけを挙げる。表の全体は4節。

| 事実 | 一致を保つ方法 |
| --- | --- |
| 作品と開発ログの既定値（TS と PHP） | PHP の値はテストで保つ（`ContentDefaultsTest`）。2か所の一致は、**仕組みは無い。人が同じコミットで直す**（4節） |
| 問い合わせのバリデーションの規則と文言（PHP と TS） | **仕組みは無い。人が同じコミットで直す**（4節） |
| 作品の `metrics` の数（CI の自動検査・テスト） | **仕組みは無い。人が同じコミットで直す**（テストや CI の段を変えるコミット。4節） |
| 表示の確認で引くパス（CI と `verify`） | 1か所だけなので要らない（`bin/check-site.sh`） |
| `bin/check-site.sh` の詳細のページの slug と、`content/` のファイルの名前 | slug を変えて直さなければ、404 で CI が赤になる |
| 本物のコンテンツの slug に頼るテスト | テスト（slug が変われば落ちる。4節） |

### 6-9. 決まり

- ゲートの設定（`ci.yml` の手順や順番、lint-staged の対象）を変えたときは、わざと壊して止まることを確かめる。規則は台帳 41-7 のゲートの行にある
- テストや CI の段を変えるコミットでは、作品の `metrics` の数も直す（4節）
- デプロイは、人が CI の緑を確かめてから起動する（手順書30節）

### 6-10. 理由の記録が無いもの

| もの | 扱い |
| --- | --- |
| `.npmrc` の `ignore-scripts=true`（CI と開発の環境の `npm ci` にも効く） | 7-10 で扱う（台帳 41-8。7節） 【注記（2026-11-09）】7-10d で維持すると決め、理由と再検討の条件を記録した（7節の `.npmrc` の行）。理由の記録が無いものではなくなった |
| CI の `tsc`・lint・ビルド・内容の検証の段を入れた意図 | 当時の記録は無い。6-1 の表に、7-9g で後から書いた意図を書いた（2026-11-04） |

## 7. 注意

知ったうえで受け入れて残しているもの。

| 場所 | 何か | 受け入れる理由 | 再検討の条件 |
| --- | --- | --- | --- |
| `PageMetaBuilder::fullTitle()`・`absoluteUrl()` | front matter の `title`・`ogImage` が数や真偽値だと `TypeError` になり、その詳細のページが500になる（一覧のように1件を飛ばす形ではない） | Zod が CI と pre-commit で止める。黙って誤った値を出すよりよい | Zod を通らない内容を本番に置く経路ができたとき |
| `ContentParser::parse()`（`file_get_contents` の結果） | 読み取りが失敗して false が来ると `TypeError` になる。`scanAll` が受け止める例外の型に入らないので、一覧がページごと500になる | 起きるのは、存在と読み取りの確かめの後に読めなくなったときだけ | 実際に起きたとき |
| `ContentParser`（必須の項目の確かめ） | `title` が `"0"` だと、`empty()` で「無い」と扱われる | 7-8c で、実害が小さい、または今は起きないとして受け入れた | — |
| `routes/web.php` の `{slug}` | ルートに制約が無い。`..%2F` を含む4つの形を開発サーバーで試し、どれも404になった。404になる仕組みは確かめていない | 7-8c で、実害が小さい、または今は起きないとして受け入れた | slug の扱いを変えるとき |
| `ContactRequest::detectSpam()`（時間の確かめのトークン） | トークンに期限が無く、一度取ったものを使い回せる | 7-8c で、実害が小さい、または今は起きないとして受け入れた | — |
| `ImageService`（Spatie の最適化） | 道具が無いと、黙って何もしない可能性がある（確かめていない） | 7-8c で、実害が小さい、または今は起きないとして受け入れた | 画像を足すとき（台帳 41-7 の画像の道具の行） |
| `resources/js/app.tsx` の `resolve` | 名前に合うページが無いと `TypeError` になる | 7-8c で、実害が小さい、または今は起きないとして受け入れた | — |
| `ContactController`（スパムのときのログ） | スパムと判定したときに、IP アドレスと User-Agent をログに残す。ログは本番で35日で消える（`docs/environment.md` の `LOG_DAILY_DAYS`） | 誤検知の把握と分析のため。入力の内容は残さない | — |
| `.npmrc` の `ignore-scripts=true` | `npm install`・`npm ci` は `prepare`（husky）を動かさない。新しく環境を作ったら、pre-commit のフックを手で入れる（`npm run prepare` で入ると見込んでいる。確かめていない）。husky は Git の設定の `core.hooksPath` でフックを効かせ、その設定は clone し直すと無くなる。`composer.json` の `setup` も `--ignore-scripts` で、フックは入らない。`post-create-project-cmd` は SQLite を作って migrate する（このアプリは DB を使わない） | 入れた理由は記録が無い。効果と代償は台帳 41-8 にある 【更新（2026-11-09）】7-10d で維持すると決めた（人）。Laravel の雛形（`laravel/laravel` の 13.x）の `.npmrc` と同じ文面である（2026-11-09 に確かめた。このリポジトリに入った 2026-06-15 の時点の雛形は確かめていない）。インストールのときに動くスクリプトは、悪意のあるパッケージがよく使う経路で、それを止める。今の代償はほぼ無い。本番の依存に、インストールのときのスクリプトを持つものは0件で、開発の依存の `esbuild` はスクリプト無しで動いている（どちらも 2026-11-09 にコンテナで確かめた）。フックが入らないことは、この行の左の欄のとおり | 台帳 41-8（維持するか外すかが、まだ決まっていない） 【更新（2026-11-09）】インストールのときのスクリプトを要るパッケージを足すとき（症状：ネイティブのバイナリが無いという誤り）／Laravel の雛形からこの設定が消えたとき |
| `resources/css/app.css` の `@variant md` | 区切りは `48rem`。ブラウザの既定の文字の大きさを変えた人では、768px と違う幅で切り替わる（クラスの `md:` と同じ幅になる） | クラスの `md:` と同じ条件にするため | — |
| `resources/js/Components/ui/` の `Button`・`Card`・`Tag` | クラスを結合する1行が3か所にある | 共通の部分は1行だけで、抽象化に見合わないとして保留した（`Card.tsx` の冒頭） | — |
| `Pages/Works/Show.tsx`・`Pages/About.tsx` の `Field` | 同じ形の部品が2か所にある | 見出しの段（h3 と h2）を意図して変えている | — |
| `scripts/validate-content.ts`・`check-css-vars.ts` | ファイルを集める関数が2か所にある | 中身が違う（片方は再帰する） | — |
| `BaseLayout.tsx` のフッター | © の年は閲覧者の時計で決まる | ずれても年が1つ違うだけ | — |
| `OptimizeImages`（済んだかの判定） | `filemtime` で判定する。checkout などで時刻が変わると結果が変わる | 外れても、作り直すか飛ばすかだけ。`--force` がある | — |
| `ContactRequest`・`OptimizeImages` の `catch (Throwable)` | 捕まえる範囲が広い | `ContactRequest` は復号の失敗をすべて `invalid_token` にするため、`OptimizeImages` は1件の失敗で全体を止めないため（どちらもコメントにある） | — |
| `.github/workflows/` の action | 版をタグで指定している。`upload-artifact` と `download-artifact` で版の数字が違う | 公式の action だけ。ハッシュに固定すると更新の手間が増える 【更新（2026-11-09）】7-10d で、タグのまま指すと決めた。理由は手順書30節の「CI ワークフローの整理（7-4g）」の【追記（2026-11-09）】。`shivammathur/setup-php` は GitHub 自身が作る action ではない（秘密の値を使わない `ci.yml` にだけある） | 依存の更新の方針を決めるとき（台帳 41-12） 【更新（2026-11-09）】秘密の値を使うワークフローに、ほかの作り手の action を足すとき／使っている action が付け替えられた事件の告知を見たとき |
| `bin/rollback.sh` | 引数の世代名を確かめない | 人がサーバーで手で動かす。指定した世代の `build` が無ければ止まる | — |
| `bin/deploy.sh` | 取り込みの途中のディレクトリ（`.incoming-*`）をすべて消す。同時に動かないことは `deploy.yml` の `concurrency` に頼る | 起動の経路は GitHub Actions のデプロイだけ | — |
| `SitemapController` | slug を URL エンコードせずに入れる（XML のエスケープだけ） | slug はファイル名で、英数字とハイフンだけで付けている | slug に別の文字を使うとき |
| `ContentRepository::listPublishedLogs()` の説明 | 「sitemap 生成のため」とあるが、テストからも使う | 本体の用途の説明として正しい | — |
| `bootstrap/app.php`（送信の上限を超えたとき） | どのルートでも、問い合わせの文言になる | 送信の上限があるのは問い合わせだけ | ほかのルートに上限を付けるとき |
| `Pages/Home.tsx` | リポジトリの名前を TSX に直接書いている | サイト名ではなく、作品の名前としての表示 | — |
| メール（Laravel の既定のテーマ） | 見出しと末尾のサイト名は `APP_NAME`、見出しの画像は Laravel の画像を外から読む | 受け取るのは運営者だけ | — |
| 台帳 41-7 の、コードに関わる行 | 本文の Markdown の生の HTML、404 の回避策、503 でサイト名が届くか、`invalid_token` の後のトークン、送信の失敗の型、コードブロックの横あふれ、作品詳細のリンクのテスト、問い合わせを同期で送ること | 理由と再検討の条件は台帳にある（この表には写さない） | 台帳 41-7 |

## 8. 雛形として残すもの

| もの | 枠組みのどこから使われているか |
| --- | --- |
| `app/Models/User.php` | `config/auth.php` の `providers.users.model`。アプリのコードからは使っていない。消すと、認証の仕組みを使ったときに、無いクラスを読みにいくと見込んでいる（確かめていない） |
| `app/Providers/AppServiceProvider.php` | `bootstrap/providers.php` に登録されている。中身は空 |
| `HandleInertiaRequests::version()` | Inertia のミドルウェアの雛形にあり、親の `version()` を呼ぶだけ。消しても親の同じ処理が使われる（読んだだけ）。資産の版を変える場所が目に見えるように残す |

## 9. 表示の速さに関わる判断

7-9d（2026-11-01）で決めたもの。サーバーが付けるキャッシュの期限は Nginx の設定の話なので、手順書39節に置く。

**フォント**

- 本文（`--font-sans`）は端末のフォントで描く。並びと理由は `resources/css/app.css` の `--font-sans` とその上のコメント。等幅（`--font-mono`）は JetBrains Mono のまま（`@fontsource/jetbrains-mono`。届くのは latin の 400・700 の2つで、各約22KB。2026-11-01 の測定の通信の一覧）
- 7-9d までは Noto Sans JP を自前で配っていた。日本語の2つで約2MB あった。`font-display: swap` で preload は無く、実際のブラウザでは最初の描画を止めていなかった。替えて得たのは、通信の量、遅い回線での文字の切り替わりが無くなること、Lighthouse の点数
- 人の理由：このプロジェクトの目的や姿勢に立ち返り、フォントのこだわりよりも機能的な使いやすさやスマートさを優先した
- 見た目は端末ごとに変わる。Windows と Android での見た目は確かめていない
- `@fontsource/noto-sans-jp` は外した（`4766071`）

**ページの読み込み方**

- すべてのページを最初にまとめて読む（`resources/js/app.tsx` の `resolve`。`import.meta.glob` の `eager: true`）。そのため、問い合わせのページの検証が使う `zod` も、最初に読む束に入っている
- ページごとに分けて読む形を試し（`1be6e73`）、取り消した（`30ae59f`）。人の理由：ページを移るときの待ちが増え、実際の端末で気になった。最初の表示の差（手元の模擬で約0.3秒）より、移るときの使い心地を優先する

**SSR**

- 使わない。最初に採らないと決めたのは 7-2（`docs/environment.md` の Inertia の行が理由の場所を指す）。7-9d でも採らないと決めた。人の理由：「本番でNode.jsのプロセスを常に動かしておく必要があり、サーバーで管理するものと、本番で動く外部のコードが増える」
- `config/inertia.php` の既定は `env('INERTIA_SSR_ENABLED', true)` で有効だが、SSR のバンドルが無いので SSR は動かず、SSR のサーバーにもつなぎに行かない（`inertiajs/inertia-laravel` v3.5.1 の `Ssr/HttpGateway.php` と `Ssr/BundleDetector.php` を読んだ。2026-11-01）。本番の `.env` の値は確かめていない。有効でも無効でも害が無く、判断が変わらないため

**測った値**

条件：Lighthouse 13.5.0、モバイル（既定）、`simulate`（往復150ms・下り約1.6Mbps・CPU を4倍遅くする仮定）、Performance だけ。1つのページにつき3回。結果のファイルはリポジトリに入れていない。

| いつ・どこ | トップの点数 | トップの FCP | 作品詳細の点数 | 作品詳細の FCP |
| --- | --- | --- | --- | --- |
| 本番、直す前（2026-11-01 07:24 UTC） | 55・55・55 | 12.0〜12.2秒 | 55・60・60 | 6.9〜12.0秒 |
| 手元、直す前（2026-11-01） | 55・55・55 | 14.3秒 | 66・56・66 | 3.8〜9.0秒 |
| 手元、フォントを替えた後 | 81・81・81 | 3.6秒 | 77・77・77 | 3.6秒 |
| 手元、フォントとページごとの読み込みの後 | 83・83・83 | 3.2〜3.3秒 | 79・80・79 | 3.3秒 |
| 本番、フォントとページごとの読み込みの後（2026-11-01、手元の時計で 08:26 UTC） | 96・99・99 | 1.65〜1.73秒 | 97・98・97 | 1.64〜1.66秒 |
| 本番、ページごとの読み込みを取り消した後 | 測っていない | — | — | — |

- 読み方：模擬の値は上の仮定のもとでの計算で、実際の利用者の待ち時間ではない（直す前の本番で、模擬を掛けない観測の FCP は約1.5〜2.6秒）。比べるのは、同じ条件の前と後だけ。手元は gzip が無く、本番とは値が違う
- 取り消した後の本番は測っていない。手元の模擬の差が約0.3秒なので、大きくは変わらないと見込んでいる（推測）

## 10. 応答ヘッダの判断

7-9e（2026-11-02）で決めたもの。

**役割の分担**

- 名前ごとの通信の方針と、どの応答にも付けるヘッダは、Nginx が付ける。`Strict-Transport-Security`・`X-Content-Type-Options`・`Referrer-Policy`・`X-Frame-Options` の4つ。設定の写しは手順書36節、判断は39節
- ページが何を読み込むかの方針（`Content-Security-Policy`）は、アプリが付ける。ページに何を載せるかを決めるのはアプリで、同じ場所で読めて、CI のテストで確かめられるため（人）
- 方針は、それを変える理由が生まれる場所の近くに置く

**CSP**

- 場所：`App\Http\Middleware\ContentSecurityPolicy`。値は同じファイルの `POLICY`
- 登録：全体のミドルウェア（`bootstrap/app.php` の `$middleware->append`）。`web` のグループに入れると、ルートに一致しない 404 の応答を通らない（テストで確かめた）。HTML 以外の応答にも付くが、害は無い
- 中身
  - 自分のサイト（`'self'`）だけを許す
  - スタイルだけ、インライン（`'unsafe-inline'`）を許す。Inertia が実行時に差し込む `<style>`（進捗の表示）のため
  - `object-src 'none'`・`base-uri 'self'`・`form-action 'self'`・`frame-ancestors 'none'`
- `default-src` にまとめ、`script-src`・`img-src` などは個別に書かない。並べると、新しい種類の書き漏らしが起きうるため。`default-src` で代わりにならない3つ（`base-uri`・`form-action`・`frame-ancestors`）は書く
- JavaScript から要素の `style` のプロパティに代入すること（embla・Inertia）は、`style-src` では止まらない（MDN の `style-src`。2026-11-02 に読んだ）
- 開発のとき（`Vite::isRunningHot()`、`public/hot` がある）は付けない。開発サーバーのスクリプトと WebSocket、`@viteReactRefresh` のインラインのスクリプトが止まるため
- 入れ方：Report-Only を使わず、手元の本番と同じ形のビルドで、ページの移動・gallery・空の送信・404 の違反が0件であることを確かめてから、止める形で入れた（人）
- zod：`resources/js/types/contact.ts` の `z.config({ jitless: true })`。zod が起動のときに行う `new Function` の試しが、CSP の違反としてコンソールに毎回出るのを止める。CSP の下では試しが失敗して同じ検証の道を使うので、動きは変わらない。コンソールにいつもエラーが出ると、人の確かめで本当のエラーと区別できなくなるため（人）。`'unsafe-eval'` を許す案は、守りを弱めるので取らない（人）
- テスト：`tests/Feature/ContentSecurityPolicyTest.php` の5件。トップ・作品詳細・問い合わせ・404 で値が全文一致すること、開発サーバーが動いているときに付かないこと。期待の値は定数を参照せず、文字列で書いている。実際のブラウザで違反を拾う仕組みは無い（受け入れた。6節の 6-5）
- 再検討の条件：訪問者が書いたものを表示するようになったとき

**ブラウザでの違反の確かめ（手で行う）**

CI には入れない（6-5）。外の読み込み先を足すとき、zod などの大きな版を上げるときに行う。7-9e（2026-11-02）では、手元のヘッドレスの Chrome をスクリプトで動かして行った。スクリプトはリポジトリに入れない。人のブラウザで同じことをしてもよい。

1. 本番と同じ形にする。開発サーバー（`npm run dev`）を止め、`public/hot` が無いことを見てから、`npm run build` を行う。`public/hot` があると CSP が付かない（上の「開発のとき」）
2. 応答に `Content-Security-Policy` のヘッダが付いていることを見る（ブラウザの開発者ツールのネットワーク、または `curl -sI`）
3. 次を開いて操作する：トップ、Works 一覧、作品の詳細（gallery の前後のボタンとドットを押す）、開発ログの詳細、About、問い合わせ（空のまま送信して、エラーの文面を出す。実際の送信はしない）、存在しないパス（404）
4. 拾うもの：コンソールのエラーのうち、CSP によるもの（「Refused to …」「Content Security Policy」を含むもの）と、ページの `securitypolicyviolation` のイベント（どの指示〈`script-src` など〉が、何〈`eval`・URL など〉を止めたか）
5. 期待は、どのページでも0件。出たら、出どころ（読み込みか、ライブラリの中の処理か）を調べてから、方針を緩めるかコードを直すかを決める

**同じことを2か所で言っているもの**

- 他のサイトの枠の中に表示させないことは、Nginx の `X-Frame-Options: DENY` と、CSP の `frame-ancestors 'none'` の両方で言っている。両方に対応するブラウザでは `frame-ancestors` が優先される（推測。仕様は読んでいない）。`frame-ancestors` を読まない古いブラウザのために `X-Frame-Options` を残す（人の判断。理由の向きは、古いブラウザは `X-Frame-Options` だけを読むという読み。確かめていない）
- 片方を変えるときは、もう片方も見る

## 11. 問い合わせの失敗のときの振る舞い

7-9f（2026-11-03）で確かめて決めたもの。手順書19節の【追記（2026-11-03）】にも要点がある。

**汎用の枠（`errors.general`）に出る3種類**（`ContactController::store`）

- `invalid_token`：フォームを表示した時刻を `APP_KEY` で暗号化した値（`form_token`。`ContactController::create`）が、送信のときに復号できない（`ContactRequest::detectSpam`）。値が欠けた・書き換えられた、または `APP_KEY` が変わったときに起きると読む。**CSRF のトークンではなく、期限も無く、セッションとも関係しない**
  - 文面は「…お手数ですが、入力した内容をコピーしてから、ページを再読み込みして、もう一度お試しください。」。再読み込みすると入力が消えるため（手元で確かめた）。再読み込みせずに送り直せる仕組みは、まれなことに対して仕組みが増えるので作らない（人）
- `too_many_urls`：本文のリンクが多すぎる
- 送信の失敗（`TransportExceptionInterface`）
- 3つとも文面が違い、区別できる。入力は残る（`withInput`）
- 送信の上限（1分に3回）を超えたときは、別の枠（`flash.rate_limited`）に出る

**419（CSRF の不一致）**

- 今の Laravel（13.34.0）の `PreventRequestForgery` は、ブラウザが付ける `Sec-Fetch-Site: same-origin` があれば、トークンを見ずに通す。そのため、今のブラウザからは、セッションが切れていても送信は成功する（手元で確かめた）。419 になるのは、このヘッダが無いか `cross-site` のとき（`curl` で確かめた）
- 起きたときは、`bootstrap/app.php` の `respond` が ErrorPage に変えない状態コードなので、Laravel の既定の HTML が返り、Inertia が失敗の枠（`srcdoc`）に英語の「419 | Page Expired」を出す。CSP の違反は出ない。枠を閉じれば入力は残る（応答を差し込んで確かめた）
- 自前のエラーページには変えない（人）。ページが置き換わり、入力が消えるため（推測）

**通信が切れたとき**

- 送信は同期（メールを送ってから302で戻す）。メールを送った後、応答が届く前に切れると、Inertia は通信の失敗として扱い、このページにその処理が無いので、画面には何も出ず、入力は残る（コードからの推測）
- もう一度送ると、同じメールがもう1通届く。重複を防ぐ仕組みは無い（2通届く形は手元で確かめた）
- 今のまま（人）。再検討の条件：重複や「送れたか分からない」という声が届いたとき
