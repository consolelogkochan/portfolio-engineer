# 開発者向けの構成と様式の一覧

## この文書について

**意義**：コードのレビューとリファクタリングの土台である。依存の更新などの前に、コードの様式と、問題になりうる箇所を一目で確かめるために使う。読み手には AI も含む（「`docs/development.md` の様式に従う」と指示できるようにする）。

**読み方**：

- 3節「事実の正の置き場所」と4節「同期が要る箇所」は、コードを変える前に見る。変える事実の正がどこにあり、ほかに直す場所があるかが分かる
- 5節「様式の規則」は、規則ごとに、守っている道具を書いた。「道具で守っていない」と書いた規則は、レビューで見る
- 7節「注意」は、知ったうえで受け入れて残しているもの。直す前に、受け入れた理由と再検討の条件を見る

**更新の規則**：

- 構成や様式を変えるコミットでは、この文書も同じコミットで直す
- 今の状態の一覧なので、コードの中身や値は写さず、場所（ファイル・シンボルの名前）と規則を指す。規則の中身（例：字下げの幅）を書くときも、正の設定のファイルを指す
- 現在形で書くのは、確かめた事実だけ
- ほかの文書との分担：ツールと版は [`docs/environment.md`](environment.md)、持ち越しと受け入れの再検討の条件は [`docs/ledger.md`](ledger.md)、サーバーの設定とその判断の経緯は [`docs/server-setup.md`](server-setup.md)。この文書は、それらを写さずに指す

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
| `app/Mail/` | 問い合わせのメール | — |
| `app/Enums/` | スパムの判定の理由（`SpamReason`） | — |
| `app/Exceptions/` | コンテンツの例外（無い・読めない、壊れている、非公開） | — |
| `app/Console/Commands/` | 画像の WebP への変換（`images:optimize`。手で動かす） | — |
| `bootstrap/app.php` | ルートとミドルウェアの登録、例外の応答（送信の上限を超えたときの応答、エラーのページ） | — |
| `routes/web.php` | URL とコントローラの対応。robots.txt（クロージャ） | — |
| `config/page_meta.php`・`config/spam.php`・`config/image.php` | 自前の設定（サイト名とページのメタ情報、スパムの判定、画像の変換） | ほかの `config/` のファイルは Laravel が用意したもの |
| `resources/js/Pages/` | Inertia のページ（ページ名とファイルの対応は `resources/js/app.tsx` の `resolve`） | — |
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
| `bin/` | デプロイと巻き戻しのスクリプト（サーバーで動く。手順は `docs/server-setup.md`） | — |
| `.github/workflows/` | CI（`ci.yml`）とデプロイ（`deploy.yml`） | — |
| `database/` | Laravel が用意したもの。このアプリは DB を使わない | — |

## 3. 事実の正の置き場所

| 事実 | 正 | ほかの場所の引き方 |
| --- | --- | --- |
| サイト名 | `config/page_meta.php` の `site_name` | `PageMetaBuilder::siteName()` を経由する。画面には `HandleInertiaRequests::share()` の `siteName` で届く。エラーのページには `bootstrap/app.php` が直接渡す |
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

| 事実 | 場所 | 片方を変えたら | まとめない理由 | 同期を守るもの |
| --- | --- | --- | --- | --- |
| 作品と開発ログの既定値（`.default(...)`） | `types/work.ts`・`types/log.ts` と、`ContentRepository` の `WORK_DEFAULTS`・`LOG_DEFAULTS` | もう片方も同じ値にする | TS は内容の検証と型、PHP は画面に渡す値。台帳 41-7 の既定値の行 | PHP の値は `ContentDefaultsTest`。2か所の一致は**守っていない** |
| 問い合わせのバリデーションの規則と文言 | `ContactRequest::rules()`・`messages()` と `types/contact.ts` | もう片方も直す | 言語をまたぐ。台帳 41-7 の文言の行 | **守っていない**（台帳 41-7 に再検討の条件） |
| 作品の公開の状態の値 | `ContentRepository::STATUS_PUBLISHED` と `types/work.ts` の `status` | もう片方も直す | 言語をまたぐ | **守っていない**（`STATUS_PUBLISHED` の説明が TS 側を指す） |
| 日付の扱い | PHP：`ContentParser`（引用符の無い日付も受け付ける）。TS：`isoDate`（文字列だけを受け付け、引用符の無い日付は Date になって止まる） | 日付の扱いを変えるときは両方を見る | TS は門番（CI と pre-commit）、PHP は本番での備え。PHP は文字列でない日付を壊れた1件として飛ばす | **守っていない**（食い違ったまま。TS が先に止めるので、CI を通った内容では表に出ない） |
| front matter の型（`title`・`ogImage` など） | Zod のスキーマと、PHP の型つきの引数（`PageMetaBuilder::fullTitle()`・`absoluteUrl()`） | スキーマを緩めるときは、PHP の受け取り方も見る | 型の正当性は Zod に任せる分担 | CI と pre-commit の内容の検証（Zod）。Zod を通らない内容は PHP で `TypeError` になる（7節） |
| og:image を png・jpg にすること | `types/work.ts` の `ogImage` の正規表現と、`OptimizeImages::SCAN_DIRS` から `public/images/og` を外していること | もう片方も直す | 型の検証と、画像の変換の対象は別の道具 | `PageMetaTest` が、og:image が png か jpg であることと、ファイルがあることを確かめる |
| トップの h1 の文面 | `resources/js/Pages/Home.tsx` の h1 と、`config/page_meta.php` の `home` の `description` | もう片方も直す | 表示の文面と meta の説明は届き先が違う | **守っていない**（両方のコメントが互いを指す。台帳 41-12 の写しの行） |
| 固定のページのパス | `routes/web.php` と、`SitemapController::index()`・`ci.yml` の表示の確認・`FaviconTest` | ページを足したら、サイトマップに足すかを決める | 目的が違う（サイトマップは全部を載せる。CI とテストは、壊れたら困るものだけを引く） | サイトマップは**守っていない**。CI とテストは、無くなったパスで落ちる |
| タイトルの組み立ての規則 | `PageMetaBuilder::fullTitle()`・`titleSuffix()` と、`PageMetaTest` の中の組み立て | 規則を変えたらテストも直す | テストは本体と別に組み立てて照らすことに意味がある | テスト（食い違えば落ちる） |
| local・testing ではエラーのページを描かない、という説明 | `bootstrap/app.php` の `respond()`、`PageMetaTest`・`SharedPropsTest` の説明 | 3か所の説明を直す | どのファイルから読んでも分かるようにするため | **守っていない**（コメント） |
| 別名 `@`（`resources/js`） | `vite.config.js`・`tsconfig.json`・`scripts/tsconfig.json` | 3か所とも直す | 道具ごとに設定の置き場所が決まっている | `vite.config.js` は CI のビルド、`tsconfig.json` は CI の `tsc`。`scripts/tsconfig.json` は CI で型の確認をしていない（6節） |
| 本物のコンテンツの slug（`portfolio-engineer`）に頼るテスト | `WorkVisibilityTest`・`PageMetaTest`・`FaviconTest`・`ContentDefaultsTest` | slug を変えたらテストも直す | コンテンツの置き場所を差し替える仕組みが無い（`WorkVisibilityTest` の説明。`BrokenFrontMatterTest` だけは、アプリの基点のパスを一時ディレクトリに替えている） | テスト（slug が変われば落ちる） |
| キャッシュを作って確かめる手順 | `bin/deploy.sh` と `bin/rollback.sh` | もう片方も直す | それぞれをサーバーで単独で読めることを優先する | **守っていない**（CI の shellcheck は書き方だけを見る） |

## 5. 様式の規則

| 対象 | 規則 | 守っているもの |
| --- | --- | --- |
| PHP | 自前のファイルには `declare(strict_types=1);` を付ける。範囲は `tests/Feature/StrictTypesTest.php` の `OWN_PATHS`。Laravel が用意したファイル（ほかの `config/`・`bootstrap/providers.php`・`database/`・`public/index.php`）には付けない | テスト（`StrictTypesTest`。範囲の外は見ない） |
| PHP | 整形は Pint の既定（Laravel の preset。`pint.json` は無い） | pre-commit（lint-staged の `**/*.php`）と CI（`pint --test`） |
| PHP | クラスの定数は型を付ける（`private const array …`） | 道具で守っていない（レビューで見る） |
| PHP | 字下げは4（`.editorconfig` の `[*]`） | Pint |
| TS・JS | 書き方は ESLint（`eslint.config.js`）、整形は Prettier（`.prettierrc`。字下げは2）。対象から外すものは `.prettierignore` に理由と一緒に書く | pre-commit と CI（`npm run lint`・`format:check`） |
| TS | 型は strict（`tsconfig.json`） | CI（`tsc --noEmit`。`scripts/` は見ない） |
| TS | Zod は今の書き方（`z.email()`・`z.url()` のような最上位の形） | 道具で守っていない |
| 字下げ（エディタ） | `.editorconfig`：Prettier が扱うもの（js・ts・tsx・json・css・`.prettierrc`）と yml は2、`composer.json` と `compose.yaml` は4、そのほかは4 | Prettier が整形する範囲は Prettier。ほかは道具で守っていない |
| CSS | 色は semantic 層の変数を使う。部品で色を直接書かない | 道具で守っていない。未定義の変数だけは `check:css-vars`（pre-commit と CI） |
| CSS | 画面幅の区切りは Tailwind の区切り（クラスの `md:`、CSS の中は `@variant md`）。`@media` に幅を手で書かない | 道具で守っていない |
| コメント | 日本語で書く。今の事実を書き、作業のメモや予定（「〜に変更」「〜で要判断」）を残さない。事実と違うコメントを残さない。同じ説明を写さず、正の1か所を指す | 道具で守っていない |
| 命名 | PHP：クラスは PascalCase、メソッドは camelCase、定数は大文字のスネークケース、enum の case は PascalCase。TS：部品のファイルと名前は PascalCase、フックは `use` で始める。front matter のキーは camelCase。slug はファイル名（拡張子なし） | 道具で守っていない（フックの使い方は ESLint の react-hooks の規則が見る） |
| エラーの扱い | 外から来るデータ（ファイル・front matter・リクエスト）の形が違うときは、黙って補わず、壊れたものとして扱う。コンテンツは `ContentParseException` にして、一覧ではその1件を飛ばす | テスト（`BrokenFrontMatterTest`） |
| エラーの扱い | 設定は、呼び出し側で既定値を書かずに読む（キーを消したことを隠さないため） | テスト（`ConfigKeysTest`。今あるキーだけを見る） |
| 本文の HTML | 著者が書く本文は `MarkdownRenderer`、運営者以外が書く本文は `UntrustedMarkdownRenderer` で変換する | 問い合わせのメールは `ContactMailTest`。どちらを使うかは道具で守っていない |

## 6. 確認の道具と範囲

| 道具 | いつ | 見るもの |
| --- | --- | --- |
| lint-staged（`lint-staged.config.js`。`.husky/pre-commit` が Sail の中で動かす） | コミットの前（ステージしたファイル） | js・ts・tsx：ESLint（`--fix`）と Prettier。json・yml・yaml・css：Prettier。php：Pint。`content/` の md：内容の検証の全件。`resources/` の css・ts・tsx：ビルドと CSS の変数の検査 |
| CI（`.github/workflows/ci.yml`） | `main` への push と PR | shellcheck（`bin/*.sh`）、`tsc --noEmit`、`npm run lint`、`npm run format:check`、`pint --test`、`composer audit`、`npm audit`（2段）、ビルド、CSS の変数の検査、`php artisan test`、内容の検証、本番と同じ依存（`--no-dev`）での表示の確認（`ci.yml` の最後の手順が引くパス） |

**対象の外**：

| 範囲 | 理由 |
| --- | --- |
| Markdown（`content/`・`docs/`・README など）の整形 | 書く人が形を決める文章のため（`.prettierignore`） |
| `composer.json`・`package-lock.json`・`compose.yaml` の整形 | ほかの道具が書く、または雛形のため（`.prettierignore`） |
| `scripts/` の型の確認（`tsc -p scripts/tsconfig.json`） | CI の手順に無い。手元で動かす |
| Blade（`resources/views/`）の整形 | 規則が無い。Prettier は扱わない。pre-commit は `**/*.php` に当たる Blade も Pint に渡すが、CI の `pint --test` は Blade を見ない（`pint --test` が見るファイルの数が、Blade を除いた PHP のファイルの数と同じだった） |
| Laravel が用意した PHP の `strict_types` | 付けない（5節） |
| テスト・型の確認・shellcheck・依存の検査 | pre-commit では動かさない。CI だけ |

ゲートの設定（`ci.yml` の手順や順番、lint-staged の対象）を変えたときは、わざと壊して止まることを確かめる。規則は台帳 41-7 のゲートの行にある。

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
| `.npmrc` の `ignore-scripts=true` | `npm install`・`npm ci` は `prepare`（husky）を動かさない。新しく環境を作ったら、pre-commit のフックを手で入れる（`npm run prepare` で入ると見込んでいる。確かめていない）。husky は Git の設定の `core.hooksPath` でフックを効かせ、その設定は clone し直すと無くなる。`composer.json` の `setup` も `--ignore-scripts` で、フックは入らない。`post-create-project-cmd` は SQLite を作って migrate する（このアプリは DB を使わない） | 入れた理由は記録が無い。効果と代償は台帳 41-8 にある | 台帳 41-8（維持するか外すかが、まだ決まっていない） |
| `resources/css/app.css` の `@variant md` | 区切りは `48rem`。ブラウザの既定の文字の大きさを変えた人では、768px と違う幅で切り替わる（クラスの `md:` と同じ幅になる） | クラスの `md:` と同じ条件にするため | — |
| `resources/js/Components/ui/` の `Button`・`Card`・`Tag` | クラスを結合する1行が3か所にある | 共通の部分は1行だけで、抽象化に見合わないとして保留した（`Card.tsx` の冒頭） | — |
| `Pages/Works/Show.tsx`・`Pages/About.tsx` の `Field` | 同じ形の部品が2か所にある | 見出しの段（h3 と h2）を意図して変えている | — |
| `scripts/validate-content.ts`・`check-css-vars.ts` | ファイルを集める関数が2か所にある | 中身が違う（片方は再帰する） | — |
| `BaseLayout.tsx` のフッター | © の年は閲覧者の時計で決まる | ずれても年が1つ違うだけ | — |
| `OptimizeImages`（済んだかの判定） | `filemtime` で判定する。checkout などで時刻が変わると結果が変わる | 外れても、作り直すか飛ばすかだけ。`--force` がある | — |
| `ContactRequest`・`OptimizeImages` の `catch (Throwable)` | 捕まえる範囲が広い | `ContactRequest` は復号の失敗をすべて `invalid_token` にするため、`OptimizeImages` は1件の失敗で全体を止めないため（どちらもコメントにある） | — |
| `.github/workflows/` の action | 版をタグで指定している。`upload-artifact` と `download-artifact` で版の数字が違う | 公式の action だけ。ハッシュに固定すると更新の手間が増える | 依存の更新の方針を決めるとき（台帳 41-12） |
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
