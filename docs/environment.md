# 環境の一覧

開発（Sail）・CI・本番で、何がどの版で、なぜそうなっているかの索引である。

- 判断の理由は書かない。手順書（`docs/server-setup.md`）の節（「n節」と書く）と、台帳（`docs/ledger.md`）の小節を指すだけにする
- 値には、実測の日付と出どころを添える。「実測」は、その日にコマンドで見た値。「記載」は、手順書や設定ファイルに書かれている値（実測ではない）
- 分からない値は「分からない」と書く
- 更新するとき：ツールを入れる・外すとき、版の方針を変えるとき、月次の点検（40節）で版を確かめたとき

## 開発機に要るもの

開発機（Mac）に直接要るのは、次の3つだけである。

| 道具 | 版（開発機で実測 2026-10-22） | 役目 | 意図 |
| --- | --- | --- | --- |
| Docker（Docker Desktop） | Docker 29.1.3、Docker Compose v2.40.3-desktop.1 | コンテナを動かす（Sail） | 開発環境のコマンドはコンテナの中で実行する決まり（7-8b-2）。台帳 41-7 |
| git | 2.51.0 | リポジトリの操作と、フック（pre-commit）の起点 | フックは開発機の git から始まり、`./vendor/bin/sail` でコンテナの中の lint-staged を呼ぶ（7-8b-2） |
| SSH のクライアント | OpenSSH_10.3p1 | 本番への接続 | 2節・11節 |

## 表1：ツール

| ツール | 開発（Sail） | CI | 本番 | 版と固定のしかた | 入れ方 | 意図 | 誰が使うか |
| --- | --- | --- | --- | --- | --- | --- | --- |
| OS | Ubuntu 26.04（`docker/8.5/Dockerfile` の `FROM ubuntu:26.04`。記載） | `ubuntu-latest`（`ci.yml`・`deploy.yml`。記載）。実際の版は分からない | Ubuntu 26.04.1 LTS（実測 2026-10-21） | 開発：イメージを作った時点で固まる。CI：GitHub が割り当てる版。本番：LTS の中で更新 | 開発：Docker のイメージ。本番：ConoHa のイメージ | 1節、台帳 41-9（7-8b-4） | すべて |
| PHP（本体） | 8.5.4（`php8.5-cli` `8.5.4-0ubuntu1.5`。実測 2026-10-22） | setup-php の `'8.5'`（記載）。実際の版は分からない | 8.5.4（`php8.5-fpm`・`php8.5-cli` `8.5.4-0ubuntu1.5`。実測 2026-10-22） | 開発：イメージを作った時点で固まる。CI：実行のたびに 8.5 の最新。本番：自動のセキュリティ更新（9節）と月次の更新（40節）で上がる | 開発・本番：Ubuntu の配布の `php8.5-*`（apt）。CI：`shivammathur/setup-php` | 9節・13節・32節、台帳 41-9（7-8b-4） | 開発：`artisan serve`・テスト・`images:optimize`。CI：テストと表示の確認。本番：PHP-FPM |
| PHP の拡張 | 本番と同じ組み合わせ（`php -m` の違いは0件。実測 2026-10-22） | setup-php の既定（`extensions` の指定なし。記載）。中身は分からない | `php8.5-cli`・`-common`・`-curl`・`-fpm`・`-gd`・`-intl`・`-mbstring`・`-readline`・`-xml`・`-zip`（実測 2026-10-22） | 本体と同じ | 本体と同じ。開発は `php8.5-fpm` を入れない | 13節 | gd：`images:optimize`（Intervention の Gd ドライバ）とテスト。そのほかは Laravel と依存のパッケージ |
| Composer | 2.9.5（`2.9.5-1`。実測 2026-10-22） | 指定なし（記載）。実際の版は分からない | 2.9.5（`2.9.5-1`。実測 2026-10-21） | 開発・本番：apt の版。CI：固定しない | 開発・本番：apt の `composer`。CI：setup-php の既定 | 18節・32節 | 開発：依存の操作。CI：`composer install`・`composer audit`。本番：`bin/deploy.sh` の `composer install --no-dev` |
| Node・npm | Node v24.22.1、npm 12.2.1（実測 2026-10-22） | `package.json` の `engines`（`^24`）の最新（記載）。24.20.0（実測 2026-09-15、21節） | 無し（意図どおり） | 開発：イメージを作った時点の 24 の最新で固まる。CI：実行のたびに 24 の最新 | 開発：nodesource の 24 と `npm install -g npm`。CI：`actions/setup-node` の `node-version-file` | 21節 | 開発・CI：ビルド、lint、型の検査、内容の検証、lint-staged |
| GitHub Actions の action | — | `actions/checkout@v7`・`actions/setup-node@v7`・`shivammathur/setup-php@v2`・`actions/upload-artifact@v7`・`actions/download-artifact@v8`（記載） | — | メジャーの版のタグで、その中の最新 | `ci.yml`・`deploy.yml` | 21節・30節 | CI とデプロイ |
| shellcheck | `koalaman/shellcheck:stable` の 0.11.0（実測 2026-10-22。開発機に既にあったイメージで、取り直していない） | `ubuntu-latest` に最初から入っているもの。0.9.0-1（ランナーの公開資料、2026-09-15、30節） | — | 開発：`stable` のタグ。CI：ランナーのイメージ。版の差は受け入れ、CI を正とする（7-8b-4） | 開発：Docker のイメージを手で動かす。CI：ランナーに内蔵 | 30節 | CI：`bin/*.sh` の検査（閾値は warning） |
| Nginx | — | — | 1.28.3（実測 2026-10-21） | apt の版 | apt | 12節・14節・25節・36節・39節 | 本番：HTTPS の終端と静的ファイルの配信、PHP-FPM への受け渡し |
| certbot | — | — | 5.8.0（実測 2026-10-21） | snap が自動で上げる | snap | 35節・37節 | 本番：証明書の取得と更新 |
| `fail2ban` | — | — | 動いている（実測 2026-10-21）。`1.1.0-9`（実測 2026-10-22） | apt の版 | ConoHa のイメージに設定つきで入っていた（推測、8節） | 8節・32節、台帳 41-7 | 本番：SSH の認証の失敗の見張り（`sshd` だけ） |
| ufw | — | — | 22/tcp が `LIMIT IN`（実測 2026-10-21）。`0.36.2-9build1`（実測 2026-10-22） | apt の版 | Ubuntu の既定 | 8節・12節・36節 | 本番：受け付けるポートの制限 |
| unattended-upgrades | — | — | 有効（9節）。2026-10-22 の実行の記録がある（9節）。`2.12ubuntu9`（実測 2026-10-22） | apt の版 | Ubuntu の既定 | 9節・40節 | 本番：セキュリティ更新の自動の適用と自動の再起動 |
| 画像の最適化の道具（`cwebp`・`jpegoptim`・`pngquant`・`optipng`） | `webp` 1.5.0、`jpegoptim` 1.4.7、`pngquant` 2.18.0、`optipng` 7.9.1（26.04 の候補の版。arm64 で実測 2026-10-22） | — | 無し（意図どおり） | イメージを作った時点で固まる | apt（Dockerfile の画像の最適化のブロック） | 13節、台帳 41-7 | 開発：`images:optimize`（`spatie/laravel-image-optimizer` が呼ぶ） |

開発の CPU は arm64（開発機）、本番は x86_64 である。開発の版は、arm64 の置き場所から入った版である。

## 表1b：アプリケーションの土台

| 名前 | 動く場所 | 版の制約（記載） | 版の正の置き場所 | 意図・判断 |
| --- | --- | --- | --- | --- |
| Laravel（`laravel/framework`） | 開発・CI・本番のサーバー | `composer.json` の `require`：`^13.8` | `composer.lock` | 記録なし |
| Inertia のサーバー側（`inertiajs/inertia-laravel`） | 開発・CI・本番のサーバー | `composer.json` の `require`：`^3.0` | `composer.lock` | v3 に上げた（7-6d。台帳 41-9）。SSR を採らない（理由は開発ログ `content/logs/portfolio-engineer.md`。38節と台帳 41-7 は、その結果の制限として触れる） |
| Inertia のクライアント側（`@inertiajs/react`） | 開発・CI のビルドの時だけ。本番には成果物だけが届く | `package.json` の `dependencies`：`^3.4.0` | `package-lock.json` | v3 系（台帳 41-9） |
| React（`react`・`react-dom`） | 開発・CI のビルドの時だけ。本番には成果物だけが届く | `package.json` の `dependencies`：`^19.2.7` | `package-lock.json` | 記録なし |
| TypeScript（`typescript`） | 開発・CI のビルドの時だけ | `package.json` の `devDependencies`：`^6.0.3` | `package-lock.json` | 記録なし |
| Tailwind CSS（`tailwindcss`・`@tailwindcss/vite`） | 開発・CI のビルドの時だけ。本番には成果物だけが届く | `package.json` の `devDependencies`：`^4.0.0` | `package-lock.json` | 記録なし |
| Vite（`vite`） | 開発・CI のビルドの時だけ | `package.json` の `devDependencies`：`^8.0.0` | `package-lock.json` | 記録なし |

本番に Node は無く、ビルドは CI で行う。本番に届くのは `public/build` の JS と CSS だけである（21節）。具体的な版は、ロックファイルを正とし、ここには写さない。確かめるときは、コンテナの中で `composer show <名前>` と `npm ls <名前>` を使う。

## 表2：依存のうち、環境に関わるもの

| 名前 | 置き場所 | 環境との関わり | 意図 |
| --- | --- | --- | --- |
| `laravel/sail` | `composer.json` の `require-dev` | pre-commit のフック（`./vendor/bin/sail npx lint-staged`）と、コンテナの操作が使う。開発用の依存でも、消すとフックが動かない | 台帳 41-9（7-8b-2） |
| `tsx` | `package.json` の `devDependencies`（`^4.23.15`） | lint-staged と CI の内容の検証（`npx tsx scripts/validate-content.ts`）と、CSS の変数の検査（`npm run check:css-vars`）が使う。依存に無いと、`npx` がその都度取りに行く | 台帳 41-9（7-8b-3） |
| `package.json` の `name` | `package.json`（`portfolio-engineer`） | 無いと、コンテナの中で npm を動かしたとき、コンテナのフォルダ名がロックファイルに入る | 台帳 41-9（7-8b-3） |

## 表3：`.env` の開発と本番の差

キーの単位で並べる。値は、秘密を含まないキーだけを書く。開発は開発の `.env`、本番は19節の表と手順にあるキー（本番の `.env` の一部。手順書に無いキーが本番に無いとは限らない）。調べた日は 2026-10-21（開発の `MAIL_HOST`・`MAIL_PORT` と `.env.example` の `INERTIA_USE_SCRIPT_ELEMENT_FOR_INITIAL_PAGE` は 2026-10-22 に直した）。

**片方にしか無いキー**

| キー | 開発 | `.env.example` | 本番（19節） |
| --- | --- | --- | --- |
| `LOG_DAILY_DAYS` | 無し | 無し | ある |
| `MAIL_TIMEOUT` | 無し | 無し | ある |
| `SESSION_COOKIE` | 無し | 無し | ある |
| `SESSION_SECURE_COOKIE` | 無し | 無し | ある（38節） |
| `PHP_CLI_SERVER_WORKERS` | ある | 無し | 無し |
| `REDIS_CLIENT`・`REDIS_HOST`・`REDIS_PASSWORD`・`REDIS_PORT` | 無し（7-8b-2 で消した） | ある | 無し |

**値が違うキー（意図して違うもの）**

| キー | 開発 | 本番 | 意図 |
| --- | --- | --- | --- |
| `APP_ENV` | `local` | `production` | 19節 |
| `APP_DEBUG` | `true` | `false` | 19節 |
| `LOG_STACK` | `single` | `daily` | 19節・40節 |
| `LOG_DAILY_DAYS` | 無し | `35` | 19節・40節 |
| `MAIL_MAILER` | `log` | `smtp` | 19節 |
| `MAIL_SCHEME` | `null` | `smtps` | 19節 |

`MAIL_HOST`・`MAIL_PORT` も開発と本番で値が違うが、意図した差ではない。開発は `.env.example` の値のままで、メールの送り方が `log` のため使われない（2026-10-22 に、開発の `.env` を `.env.example` の値に揃えた）。

キャッシュ・セッション・キュー・DB（`CACHE_STORE`・`SESSION_DRIVER`・`QUEUE_CONNECTION`・`DB_CONNECTION`）は、開発・`.env.example`・本番とも `file`・`file`・`sync`・`sqlite` で同じ。

## 表4：本番で動いているサービス

2026-10-21 の実測（`systemctl list-units --type=service --state=running --no-pager`）の27件である。

| サービス | 意図 |
| --- | --- |
| `nginx` | 12節 |
| `php8.5-fpm` | 13節・14節 |
| `ssh` | 7節 |
| `unattended-upgrades` | 9節 |
| `fail2ban` | 8節（意図して入れたものではない。残す判断） |
| `snapd` | 35節（certbot の実行の基盤） |
| `chrony` | OS の既定。手順書に記述なし |
| `acpid` | OS の既定 |
| `cron` | OS の既定 |
| `dbus` | OS の既定 |
| `networkd-dispatcher` | OS の既定 |
| `polkit` | OS の既定 |
| `qemu-guest-agent` | OS の既定 |
| `rsyslog` | OS の既定 |
| `getty@tty1` | OS の既定 |
| `serial-getty@ttyS0` | OS の既定 |
| `systemd-journald` | OS の既定 |
| `systemd-logind` | OS の既定 |
| `systemd-networkd` | OS の既定 |
| `systemd-resolved` | OS の既定 |
| `systemd-udevd` | OS の既定 |
| `user@1000` | OS の既定 |
| `ModemManager` | OS の既定。意図は無い。止めない（7-8b-4 の判断） |
| `fwupd` | OS の既定。意図は無い。止めない（7-8b-4 の判断） |
| `multipathd` | OS の既定。意図は無い。止めない（7-8b-4 の判断） |
| `udisks2` | OS の既定。意図は無い。止めない（7-8b-4 の判断） |
| `upower` | OS の既定。意図は無い。止めない（7-8b-4 の判断） |

「意図は無い。止めない」の5件の再検討の条件：資源の不足や脆弱性が問題になったとき。

## 揃えない差

- CPU：開発は arm64、本番は x86_64。揃えられない
- Composer の CI の版：固定しない（7-8b-4）
- shellcheck の版：開発と CI の差は受け入れ、CI を正とする（7-8b-4、30節）
- Node：本番に無い。意図どおり（21節）
