# サーバー構築手順書

この文書は、本番サーバーを構築した際の手順を再現可能な形で記録したものです。
サーバーを作り直す必要が生じたとき、上から順に実行すれば同じ状態に到達できることを目標としています。

伏せている情報は次の通りです。実際の値には置き換えず、プレースホルダのまま参照してください。

- IPアドレス → `<SERVER_IP>`
- サーバーのIPv6アドレス → `<SERVER_IPV6>`
- 旧サーバー（ロリポップ）のIPアドレス → `<LOLIPOP_IP>`
- 作業用ユーザー名 → `<USER>`
- 公開鍵の実体 → `<PUBLIC_KEY>`
- メールアドレス → `<EMAIL>`
- パスワード・パスフレーズは本文中に一切記載していません

ホスト名（`portfolio`）や設定の中身そのものは、手順書として機能させるため伏せていません。

---

## 1. 前提

- ConoHa VPS 3.0 / 2GB / 東京リージョン / Ubuntu 26.04 LTS
- 料金プラン：まとめトク6ヶ月
  - 理由：クレジットカードの更新時期を契約期間の内側に抱え込み、更新イベントの回数を減らすため
  - 注意：入金が確認できず契約満了を迎えるとサーバーが削除される（詳細は36節）

### オプションの選択と理由

| オプション | 選択 | 理由 |
|---|---|---|
| 自動バックアップ | 無効 | コード・コンテンツ・画像はすべてgitにあり、DBも無い。サーバー上に復元すべき固有データが存在しないため。※DBを載せる段階で再検討が必要 |
| 追加ストレージ | 使用しない | 標準SSDで十分 |
| スタートアップスクリプト | 使用しない | 何がどう入ったか分からない状態を避け、自分で1つずつ入れて役割を理解するため |

### セキュリティグループ

`IPv4v6-SSH` / `IPv4v6-Web` / `IPv4v6-ICMP` を選択。

- ICMPを含める理由：pingが通らないと、繋がらないときに「ネットワークの問題か、SSHの問題か」を切り分けられない。観測できる手段を自分から捨てない
- 注意：`default` グループのみでは外部から接続できない

---

## 2. SSH鍵の準備（作業端末側）

**目的**：サーバーへの鍵認証に使う鍵ペアを、作業端末上で生成する。

```bash
ssh-keygen -t ed25519 -C "portfolio-vps" -f ~/.ssh/<KEY_NAME>
ssh-add --apple-use-keychain ~/.ssh/<KEY_NAME>
```

**期待する出力**：`~/.ssh/<KEY_NAME>`（秘密鍵）と `~/.ssh/<KEY_NAME>.pub`（公開鍵）が生成される。`ssh-add` はKeychainへの登録メッセージを返す。

### 判断と注意

- ed25519 を選ぶ（RSAより短く安全）
- パスフレーズを設定し、パスワードマネージャに必ず保存する
  - ※ 実際にパスフレーズを失い、鍵を作り直す事態が発生した
- 鍵はConoHaのコントロールパネルで生成させず、必ず作業端末で生成する（秘密鍵を生成した場所から動かさないため）
- 秘密鍵のパーミッションは `600` であること

---

## 3. 復旧経路の確認（SSHを触る前に必ず実施）

**目的**：SSH設定を変更する前に、SSHを経由しない復旧手段が実際に機能することを確認しておく。

**手順**：

1. コントロールパネル → サーバー → サーバー名 → 「コンソール」ボタン
2. `root` と契約時のパスワードで実際にログインする
3. `whoami` を実行して `root` と返ることを確認
4. 「テキスト送信」機能の場所を確認する（通常のコピー＆ペーストは不可）

**期待する出力**：コンソールにログインでき、`whoami` が `root` を返すこと。

### なぜ先にやるのか

- SSH設定の変更は「自分が入れなくなる」リスクを常に伴う
- 備えは「あること」ではなく「動くことを確認済みであること」
- 実際にこの経路が必要になる事態が発生した（鍵のパスフレーズ喪失）
- 備えは原因（設定ミス）ではなく結果（入れない状態）に対して設計する

最後の手段として、サーバーの再構築も可能である。

---

## 4. 初回ログインと初期状態の確認

**目的**：ネットワーク層の疎通と、サーバーが何も入っていない初期状態であることを確認する。

```bash
ping -c 4 <SERVER_IP>
ssh -i ~/.ssh/<KEY_NAME> root@<SERVER_IP>
cat /etc/os-release
free -h
df -h
php -v
nginx -v
```

**期待する出力**：

- `ping` が応答すること（ネットワーク層が生きている確認）
- Ubuntu 26.04 であること
- `php` / `nginx` は「command not found」であること（何も入っていない状態から始まることの確認）

---

## 5. システム更新

**目的**：既知の脆弱性・不具合を解消した状態からサーバー構築を始める。

```bash
apt update
apt list --upgradable
apt upgrade -y
systemctl reboot
```

### 方針

- いきなり更新せず、まず何が変わるかを見る
- 「kept back」や設定ファイルの上書き確認が出た場合は止めて確認する
- 再起動後、バナーから「再起動が必要」の表示が消えることを確認する

---

## 6. 作業用ユーザーの作成

**目的**：root直接ログインに依存しない、日常運用用のユーザーを作る。

```bash
adduser <USER>
usermod -aG sudo <USER>
groups <USER>
mkdir -p /home/<USER>/.ssh
echo '<PUBLIC_KEY>' > /home/<USER>/.ssh/authorized_keys
chown -R <USER>:<USER> /home/<USER>/.ssh
chmod 700 /home/<USER>/.ssh
chmod 600 /home/<USER>/.ssh/authorized_keys
```

**期待する出力**：`groups <USER>` に `sudo` が含まれること。

### 判断と注意

- ユーザー名に `admin` / `user` / `ubuntu` / `deploy` などのありふれた名前を使わない
- パスワードを設定する。sudo実行時の確認が、「鍵でログインできる」と「root権限を使える」の間の壁になる
- `usermod` の `-aG` の `-a`（append）を忘れると既存グループから外れる
- `.ssh` は `700`、`authorized_keys` は `600`。これが正しくないとSSHが鍵を拒否する（初回構築で最も多い詰まり方）

### 検証（重要）

- root接続を維持したまま、別ターミナルで新ユーザーのログインを試す
- 新しい経路が通ることを確認してから、古い経路を閉じる

---

## 7. SSH の安全化

### 事前調査（触る前に構造を把握する）

```bash
sudo ls -la /etc/ssh/sshd_config.d/
sudo cat /etc/ssh/sshd_config.d/*.conf
sudo grep -nE "^Include|PermitRootLogin|PasswordAuthentication|PubkeyAuthentication" /etc/ssh/sshd_config
systemctl status ssh --no-pager
```

**判明した事実**：

- ConoHaのイメージには `50-cloud-init.conf` があり、`PasswordAuthentication no` が最初から設定されている
- `100-allowsshrsa.conf` があり、`PubkeyAcceptedAlgorithms=+ssh-rsa` として非推奨の ssh-rsa（SHA-1署名）が再有効化されている
- メイン設定は `PermitRootLogin yes` になっている
- SSHは `ssh.socket` による起動（ポート番号は socket 側で決まる）
- `ssh.service` には `ExecStartPre=/usr/sbin/sshd -t` があり、設定が壊れていると起動に失敗する安全装置になっている

### 設定ファイルの作成

```bash
sudo tee /etc/ssh/sshd_config.d/10-hardening.conf > /dev/null <<'EOF'
# 自分の判断による設定はこのファイルに集約する。
# sshd_config は「先に読まれた値が勝つ」ため、10- で始めて
# 50-cloud-init.conf や 100-*.conf より先に読ませる。

PermitRootLogin no
PasswordAuthentication no
KbdInteractiveAuthentication no
PubkeyAuthentication yes
AllowUsers <USER>
EOF

sudo mv /etc/ssh/sshd_config.d/100-allowsshrsa.conf \
        /etc/ssh/sshd_config.d/100-allowsshrsa.conf.disabled
```

### 判断の理由

- 配布元のファイルを編集せず、自分の設定を独立したファイルに集約する（どれが自分の判断で、どれが事業者の初期設定かを分離するため）
- `KbdInteractiveAuthentication` も `no` にする理由：`PasswordAuthentication no` だけではPAM経由の別経路が残る場合がある
- `AllowUsers` を使う理由：禁止するものを列挙するのではなく、許可するものだけを列挙する。将来アプリ実行用ユーザーが増えたとき、それらがログイン可能になることを構造的に防げる
- `100-allowsshrsa.conf` は削除せずリネームする（`Include` は `*.conf` のみ読む）。必要になれば戻せる

### 検証

```bash
sudo sshd -t                # 文法チェック。無出力が正常
sudo sshd -T | grep -iE "^(permitrootlogin|passwordauthentication|pubkeyauthentication|kbdinteractiveauthentication|allowusers|pubkeyacceptedalgorithms)"
sudo systemctl restart ssh
```

**期待する出力**：

```
permitrootlogin no
pubkeyauthentication yes
passwordauthentication no
kbdinteractiveauthentication no
allowusers <USER>
```

※ `pubkeyacceptedalgorithms` に `ssh-rsa`（単独）が含まれないこと。`rsa-sha2-256` / `rsa-sha2-512` はSHA-2署名なので残ってよい。

### 外部からの検証（3種類、既存接続を維持したまま実施）

```bash
ssh -i ~/.ssh/<KEY_NAME> <USER>@<SERVER_IP>        # → 成功すること
ssh -i ~/.ssh/<KEY_NAME> root@<SERVER_IP>          # → 拒否されること
ssh -o PubkeyAuthentication=no <USER>@<SERVER_IP>  # → パスワードを聞かれずに拒否
```

### 補足

sshdはユーザー名を先に判定し（`AllowUsers` / `PermitRootLogin`）、その後に鍵認証を行う。そのためroot接続の拒否は "Permission denied (publickey)" と表示されるが、実際は鍵ではなくユーザー名の段階で弾かれている。sshdは拒否理由を意図的に曖昧に返す。

---

## 8. ファイアウォール（ufw）

### 事前確認（ルールの状態把握）

```bash
sudo ufw status verbose
sudo ufw show added
grep IPV6 /etc/default/ufw     # IPV6=yes であること
```

### 設定

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw limit 22/tcp
```

### 事前確認（enable の前に必ず実施）

```bash
sudo ufw show added
```

**期待する出力**：自分が追加したルール（`limit 22/tcp`）のみが表示されること。

**補足**：イメージによっては "OpenSSH" というプロファイルのALLOWルールが最初から登録されている場合がある（このALLOWルールがいつから存在したかは確認できていない）。ufwのルールは上から順に評価され最初に一致したもので確定するため、ALLOWがLIMITより前にあるとレート制限が黙って無効化される。

`sudo ufw show added` の出力にALLOWのSSH関連ルールが含まれていた場合は、次で削除してから `enable` する。

```bash
sudo ufw delete allow OpenSSH     # v4/v6 の両方が削除される
```

**再確認**：

```bash
sudo ufw status verbose
```

ALLOWの行が無いことを確認してから、次に進む。

```bash
sudo ufw enable
```

**期待する最終状態**：

```
22/tcp        LIMIT IN    Anywhere
22/tcp (v6)   LIMIT IN    Anywhere (v6)
```

※ ALLOWの行が存在しないこと。

### 判断の理由

- incoming を既定で拒否し、通すものだけを明示する
- `allow` ではなく `limit` を使う（同一IPから30秒に6回以上の接続をブロック）。fail2banを導入しない代わりの、ufw標準機能によるレート制限
- SSHポートは22のまま変更しない。ConoHaのコントロールパネルは任意ポートを指定できないため、ポートを変更するとConoHa側の層を「全て許可」にせざるを得ず、多層防御の外層を失う。ポート変更で得られるのはログのノイズ削減という副次的な利益にとどまるため、天秤が合わない

### 外部からの検証

```bash
ssh <USER>@<SERVER_IP>              # → 接続できること
nc -zv -w 5 <SERVER_IP> 80          # → タイムアウトすること
```

※ "Connection refused" ではなくタイムアウトになるのが正しい。refusedは「届いたうえで拒否」、タイムアウトは「そもそも届いていない」。

**注意**：ポートを開けるときはConoHaのセキュリティグループとufwの2箇所を確認する必要がある。

---

## 9. 自動セキュリティ更新

### 現状確認

```bash
systemctl status unattended-upgrades --no-pager
cat /etc/apt/apt.conf.d/20auto-upgrades
grep -vE '^\s*(//|$)' /etc/apt/apt.conf.d/50unattended-upgrades
```

**記載すべき事実**：

- Ubuntu Serverでは `unattended-upgrades` が既定で有効
- `Allowed-Origins` に `-updates` が含まれないため、自動適用されるのは実質セキュリティ更新のみ
- 自動再起動は既定で無効

### 設定

```bash
sudo tee /etc/apt/apt.conf.d/52unattended-upgrades-local > /dev/null <<'EOF'
// 自分の判断による設定はこのファイルに集約する。
// APT の設定は後から読まれた値が勝つため 52-（50- より後）とする。

Unattended-Upgrade::Automatic-Reboot "true";
Unattended-Upgrade::Automatic-Reboot-Time "19:00";
Unattended-Upgrade::Automatic-Reboot-WithUsers "true";
EOF
```

### 判断の理由

- 自動再起動を有効にする理由：「気づいたら手動で再起動する」運用は続かない。忘れた分だけカーネルの脆弱性が放置される
- 19:00 UTC を選ぶ理由：日本の午前4時（閲覧者が最も少ない）かつ Phnom Penh の午前2時（運用者が作業していない）
- 自動適用の範囲はセキュリティ更新のみに留める（既定のまま）。通常更新まで自動化すると機能変更が予期せず入る

### 検証

```bash
apt-config dump | grep -i "unattended-upgrade::automatic"
sudo unattended-upgrade --dry-run --debug
```

---

## 10. タイムゾーンとホスト名

```bash
sudo timedatectl set-timezone UTC

OLD_HOSTNAME=$(hostname)
sudo hostnamectl set-hostname portfolio
sudo sed -i "s/${OLD_HOSTNAME}/portfolio/g" /etc/hosts
cat /etc/hosts

sudo tee /etc/cloud/cloud.cfg.d/99-preserve-hostname.cfg > /dev/null <<'EOF'
preserve_hostname: true
EOF
```

### 判断の理由

- UTC にする理由：運用者の所在地（UTC+7）とConoHa初期設定（UTC+9）がずれており、どちらに寄せても変換が必要になる。UTCという中立の基準にすれば、どこから見ても変換ルールが一定になる
- `/etc/hosts` も更新する理由：ホスト名の名前解決に失敗すると `sudo` の実行が数秒待たされる（原因が分かりにくい詰まり方）
- 変更前のホスト名を `OLD_HOSTNAME` に取ってから `hostnamectl set-hostname` する理由：変更前のホスト名はインスタンス固有の自動採番値であり、固定値でもプレースホルダでも書けない。**先に取得してから変更する順序**でなければ、`sed` の置換対象が失われる
- `preserve_hostname` を明示する理由：cloud-initの既定は `false` で、再起動時にホスト名をmetadataの値へ戻す可能性がある

### 【設定ファイルの優先順位について注意】

| 対象 | 優先されるほう |
|---|---|
| sshd | 先に読まれた値が勝つ |
| APT | 後に読まれた値が勝つ |
| cloud-init | 後に読まれた値が勝つ |

「番号が大きいほうが強い」と一律に覚えると間違える。ソフトウェアごとに確認すること。

### 検証

```bash
sudo systemctl reboot
# 再接続後
hostname
timedatectl
sudo ufw status verbose
```

再起動を跨いで設定が維持されることを確認する。

---

## 11. 接続の簡略化（作業端末側）

**目的**：毎回 `ssh -i ~/.ssh/<KEY_NAME> <USER>@<SERVER_IP>` と打たなくて済むようにする。

`~/.ssh/config` に `Host` エントリを追加し、`chmod 600` する。以降は `ssh <ホスト別名>` だけで接続できる。

### SSH接続の維持（ServerAliveInterval）

SSH接続が無操作で切断される問題への対処として、`~/.ssh/config` に以下を追加した。

```
ServerAliveInterval 60
ServerAliveCountMax 3
```

**理由**：作業中に切断されると、設定変更時に「既存の接続を命綱として保つ」手順が成立しなくなるため。60秒ごとに信号を送って接続を維持する。

---

## 12. Nginx のインストール

**目的**：Webサーバーを導入し、外部からHTTP経由で疎通できる状態を作る。

```bash
sudo apt update
sudo apt install -y nginx
systemctl status nginx --no-pager
systemctl is-enabled nginx
```

**期待する出力**：

- `Active: active (running)`
- `is-enabled` が `enabled`

### 判断の理由

- `is-enabled` の確認が重要な理由：自動セキュリティ更新（9節）で深夜に再起動する設定にしているため、自動起動が無効だと再起動後にサイトが落ちたままになる

### ufw への80番の追加

ConoHaのセキュリティグループは `IPv4v6-Web` で既に許可済みのため、この作業はufw側のみである。

```bash
sudo ufw allow 80/tcp
sudo ufw status verbose
```

**期待する出力**：`80/tcp` がIPv4とIPv6の両方に `ALLOW IN` として表示され、`22/tcp` は `LIMIT` のままであること。

### 検証

ブラウザで `http://<SERVER_IP>` を開き、"Welcome to nginx!" が表示されることを確認する。

この表示は、ConoHaセキュリティグループ → ufw → Nginx の3層がすべて通って初めて成立する。

---

## 13. PHP 8.5 と PHP-FPM のインストール

### 必要な拡張の特定（推測せず composer.json から逆算する）

composer.json に `ext-*` の明示的な記載はないため、パッケージから逆算した。

- `intervention/image` → GD または Imagick
- `league/commonmark` → mbstring
- `laravel/framework` → ctype / curl / dom / fileinfo / filter / hash / mbstring / openssl / pcre / pdo / session / tokenizer / xml
- `spatie/laravel-image-optimizer` → 外部のCLIツール（後述の判断2を参照）

### 判断1：Imagick ではなくGDを使う

`intervention/image` はどちらでも動くが、ImageMagickは依存が重く、過去に脆弱性の報告が多い。このサイトが必要とする処理にはGDで足りる。

### 判断2：画像最適化の外部ツール（jpegoptim / pngquant 等）は入れない

`spatie/laravel-image-optimizer` は、PHPの拡張ではなくサーバーにインストールされたコマンドを呼び出して動く。

しかし `OptimizeImages` コマンドは開発環境のプレコミットで実行され、生成されたwebpファイルはgitにコミットされ、デプロイ時点で揃っている。サーバーの役割は「置く」と「返す」であって「作る」ではない。

副次的な利益として、インストールするソフトウェアが減れば攻撃面が減り、更新対象も減る。

※ パッケージ自体は `composer install` で本番にも入るが、実行しないため害はない。

### 判断3：`php8.5-intl` は入れない

composer.json から要求されておらず、必要になれば後から追加できる。推測で先回りすると、後から棚卸ししたときに「なぜ入っているか」が説明できなくなる。必要ならインストール時か実行時に明確なエラーが出る。

【後日の訂正】この判断の後、Composerをaptで導入した際にphp8.5-intlが依存関係として自動的に導入された。

害はないが、「入れない」と記録した文書と実態が食い違うことになった。

教訓：「入れない」という判断は、自分が明示的に入れない場合にのみ成立する。依存関係として引き込まれる経路は判断の外にある。「入れない」と決めたものは、後で実際に入っていないかを確認する必要がある。

### パッケージ名の確認（推測せず実測する）

```bash
apt-cache search "^php8\.5-" | sort
```

存在しないパッケージ名を含めて `apt install` すると、コマンド全体が失敗するため、事前に名前を確認する。

### インストール

```bash
sudo apt install -y php8.5-fpm php8.5-cli php8.5-mbstring \
     php8.5-xml php8.5-curl php8.5-gd php8.5-zip
```

**注意**：`libapache2-mod-php8.5` は入れない。また `php` というメタパッケージを指定するとApacheが一緒に入ることがあるため、必ずバージョンと役割を明示したパッケージ名を使う。

### 確認

```bash
php -v
php -m
systemctl status php8.5-fpm --no-pager
systemctl is-enabled php8.5-fpm
```

**期待する出力**：

- PHP 8.5.x
- gd / mbstring / curl / dom / SimpleXML / zip が含まれる
- FPMが `active (running)` かつ `enabled`

**注意**：`php -v` と `php -m` はCLI側の情報である。PHPはCLIとFPMで別々の設定を持つため、FPM側の状態はphpinfo()をブラウザから確認する必要がある（OPcacheはCLIでは既定で無効）。

---

## 14. Nginx と PHP-FPM の連携

### 前提の確認

```bash
ls -la /etc/nginx/snippets/     # fastcgi-php.conf が存在すること
ls -la /run/php/                # php-fpm.sock が存在すること
```

`fastcgi-php.conf` は、NginxがPHP-FPMにリクエストを渡すときの共通設定をまとめたもの。自分で書くと漏れが出るため、ディストリビューションが用意したものを使う。

### ソケットの構造と注意点

```
php-fpm.sock -> /etc/alternatives/php-fpm.sock -> php8.5-fpm.sock
```

`php-fpm.sock` はalternatives機構を経由したシンボリックリンクである。

- `/run/php/php-fpm.sock` を設定に書く：PHPをアップグレードしても設定変更が不要。ただしどのバージョンに繋がっているかが間接的になる
- `/run/php/php8.5-fpm.sock` を書く：明示的だが、バージョンを上げると設定も変える必要がある

前者を採用した。ただしPHPをアップグレードする際は、この参照先が意図したバージョンを指しているか必ず確認すること。

なお実体のソケットは `srw-rw---- www-data:www-data` であり、www-data以外は読み書きできない。これも内部の防御層の一つになっている。

### 通信方法の判断：Unixソケット（TCPではなく）

- 同一マシン内の通信なので速い（TCPのオーバーヘッドがない）
- 外部から到達できない。ファイルシステム上の存在であり、ネットワーク越しには触れない。TCPだとローカルポートが開く
- 既定に従う（PHP-FPMの初期設定がUnixソケット）

TCPが必要になるのはPHP-FPMを別サーバーに分離する場合だが、その予定はない。

### 【落とし穴】既定のサイト設定ファイルの構造

`/etc/nginx/sites-available/default` は、先頭にコメントアウトされた設定例が置かれており、有効な `server` ブロックはその下にある。

エディタで開いて最初の1画面だけを見ると「全体がコメントアウトされている」と誤認する。

有効な行だけを確認するには次を使う：

```bash
grep -vE '^\s*(#|$)' /etc/nginx/sites-available/default
```

構成全体の把握には次を使う：

```bash
ls -la /etc/nginx/sites-enabled/
ls -la /etc/nginx/conf.d/
grep -nE "include|server \{|root|listen" /etc/nginx/nginx.conf
```

「見た範囲」と「全体」は別物である。

### 設定の追加

バックアップを取ってから、ファイルを書き換える。

```bash
sudo cp /etc/nginx/sites-available/default \
        /etc/nginx/sites-available/default.bak

sudo tee /etc/nginx/sites-available/default > /dev/null <<'EOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    root /var/www/html;
    index index.php index.html index.htm index.nginx-debian.html;

    server_name _;

    location / {
        try_files $uri $uri/ =404;
    }

    # .php で終わるリクエストを PHP-FPM に渡す
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
}
EOF
```

`index` に `index.php` を先頭で追加している。これがないと、ディレクトリにアクセスしたときにPHPファイルが選ばれない。

※ この設定は連携確認のための暫定である。アプリ用の `server` ブロックは、配置場所が決まる7-3cで作成し、そのときこの `default` は無効化する。

### 検証

```bash
sudo nginx -t
sudo systemctl reload nginx
```

`nginx -t` が `syntax is ok` と `test is successful` を返すことを確認してから `reload` する。エラーが出たら `reload` しない。

ブラウザ確認用に一時ファイルを置く。

```bash
echo '<?php phpinfo();' | sudo tee /var/www/html/info.php > /dev/null
```

`http://<SERVER_IP>/info.php` を開き、以下を確認する。

- PHP Version が 8.5.x であること
- Server API が `FPM/FastCGI` であること（CLIではないこと）→ これがNginxとPHP-FPMが連携している証拠
- Zend OPcacheのセクションが存在し、Opcode Cachingが `Up and Running`
- gd / mbstring / curl / dom / zip の各セクションが存在する

【重要】phpinfo() はサーバーの構成情報をすべて公開する。PHPのバージョン、拡張、ファイルパス、環境変数が誰にでも見える。確認後は必ず削除すること。

```bash
sudo rm /var/www/html/info.php
```

削除後、ブラウザで404になることを確認する。「消したつもり」ではなく「消えたことを外から確認する」。

---

## 15. 実行ユーザーとファイル所有権の設計

※ 実際の適用は7-3c（アプリ配置時）に行う。ここでは設計の記録のみ。

### プロセスの実行権限

| プロセス | 権限 |
|---|---|
| Nginx master | root（80番ポートを開くために必要） |
| Nginx worker | www-data |
| PHP-FPM master | root |
| PHP-FPM pool www | www-data |

Webサーバーは外部からの入力を受け取る最前線である。脆弱性を突かれたとき、rootで動いていればその瞬間にサーバー全体を掌握される。権限を限定したユーザーで動かせば、被害はその権限の範囲に留まる。

### ファイルの所有権（7-3cで適用する設計）

- 所有者：`<USER>`（デプロイする人）
- グループ：www-data
- `storage/` と `bootstrap/cache/` のみグループ書き込みを許可
- それ以外のコードはwww-dataから読み取りのみ

これにより、`<USER>` はgit pullでき、www-dataは必要な場所にだけ書ける。

### PHP-FPMを`<USER>`権限で動かさない理由

「`<USER>`権限で動かす」とは、そのプロセスに`<USER>`の権限を与えること。乗っ取られれば、攻撃者は`<USER>`の権限をそのまま手に入れる。

- コードベース全体への書き込みが可能になる
- `~/.ssh/authorized_keys` に自分の公開鍵を追加でき、以後SSHで入れる
- `~/.bashrc` を書き換え、`sudo` を偽コマンドに差し替えてパスワードを盗める
- sudoグループに所属しているため、パスワードが取れればroot

最前線のプロセスには、できるだけ何も持たせないのが原則である。www-dataはログインシェルもホームディレクトリの実体も持たない。

### 残るリスクと7-3cでの対処

`bootstrap/cache/config.php` はLaravelが起動時に読み込むPHPファイルである。ここに書き込めるということは、実質的に任意のコードを実行させられる。

対処：デプロイ時に`<USER>`が `php artisan config:cache` を実行してキャッシュを生成し、www-dataには読み取り権限だけを与える。実行時にPHPが書き込む必要がなくなる。

`storage/` は書き込みが必須なので残るが、ログとキャッシュのディレクトリであり、PHPとして実行されるファイルではない。

---

## 16. 本番向け PHP 設定の確認と調整

### 現状の確認方法

php.iniは2000行近くあり目で追えないため、有効な行だけを抽出する。

```bash
grep -vE '^\s*(;|$)' /etc/php/8.5/fpm/php.ini | grep -iE "display_errors|display_startup_errors|expose_php|memory_limit|max_execution_time|post_max_size|upload_max_filesize|disable_functions|open_basedir|error_reporting|log_errors"
```

### 確認結果：Ubuntuのfpm/php.iniは既に本番向けだった

| 項目 | 値 | 評価 |
|---|---|---|
| display_errors | Off | エラー内容がブラウザに出ない |
| display_startup_errors | Off | 同上 |
| expose_php | Off | X-Powered-By でバージョンを公開しない |
| log_errors | On | エラーはログに残る |
| error_reporting | E_ALL & ~E_DEPRECATED | 妥当 |
| memory_limit | 128M | 通常のWebリクエストには十分 |
| max_execution_time | 30 | 妥当 |
| post_max_size / upload_max_filesize | 8M / 2M | アップロード機能がないため十分 |
| disable_functions | 空 | 未設定（後述の判断で設定する） |
| open_basedir | 未設定 | 意図的に設定しない（後述） |

「本番向けに変更が必要だろう」と想定していたが、実測すると既に適切だった。推測で作業を始めず、まず現状を見ることの実例。

### OPcacheの状態

OPcacheはPHP本体に静的に組み込まれており、共有拡張としてconf.dに登録されてはいない（推測を含む）。fpm/php.iniの`[opcache]`セクションは全行がコメントアウトされているため、すべてPHPの組み込み既定値が効いている。

OPcacheが実際に稼働していることは、「Nginx と PHP-FPM の連携」の節でphpinfo()により確認済みである（Zend OPcacheのセクションが存在し、Opcode Cachingが`Up and Running`）。

以後、稼働状況とキャッシュの使用量を確認するには、一時的なPHPファイルから次を出力する。

```php
<?php
var_dump(opcache_get_status(false));
```

確認したい項目：

- `opcache_enabled` が`true`であること
- `num_cached_scripts`（キャッシュ済みのファイル数）
- `max_cached_keys` / `opcache.max_accelerated_files`（上限）に達していないこと
- `memory_usage`の`free_memory`が枯渇していないこと

phpinfo() ではなく必要な項目だけを出すこと。確認後は必ずファイルを削除し、404になることを確かめること。

判断：`opcache.validate_timestamps` は既定の`1`のままとする。

- `0` にするとPHPファイルの更新確認がなくなり速くなるが、デプロイのたびにPHP-FPMのreloadが必須になる
- 忘れると「デプロイしたのに反映されない」という原因の分かりにくい事象が起きる
- このサイトのトラフィックでは、更新確認のコストは無視できる
- デプロイを自動化してreloadを手順に組み込めば「忘れる」経路が消えるため、その時点で`0`を再検討する

未実測の項目：`opcache.max_accelerated_files` の既定は10000だが、Laravelはvendorを含めるとファイル数が多く、超える可能性がある。アプリ配置後に`opcache_get_status()`で実測すること。

### PHP-FPMのプロセス設定（現状のみ。調整は実測後）

```bash
grep -vE '^\s*(;|$)' /etc/php/8.5/fpm/pool.d/www.conf
ps -o pid,rss,cmd -C php-fpm8.5
free -m
```

現状：

```
user = www-data / group = www-data
listen = /run/php/php8.5-fpm.sock
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

アイドル時のメモリ：master 約35MB、poolプロセス各約13MB
サーバー全体：Mem 1961MB（うちavailable 1562MB）、Swap 2047MB

【メモリ設計の方針】

`pm.max_children` は、swapに依存しない前提で決める。

swapはディスク上の領域であり、実メモリより桁違いに遅い。常時稼働するPHP-FPMがswapに落ちると、レスポンスが恒常的に悪化する。swapは「落ちないための保険」であって「使う前提の容量」ではない。

したがって、次の式が実メモリの安全な範囲に収まるように決める。

```
pm.max_children × 1プロセスあたりの実使用量
```

一方、Viteのビルドのような一度きりの処理では、swapを保険として当てにしてよい。遅くても完了することが優先されるためである。

```
常時稼働するもの（PHP-FPM）  → swap に依存しない
一度きりの処理（ビルド）      → swap を保険として使ってよい
```

`pm.max_children` の調整は、アプリを動かしてから実測して行う。アイドル時の値では1リクエストあたりの実使用量が分からないため。

【注意】poolの`listen`は`/run/php/php8.5-fpm.sock`（バージョン固定）だが、Nginxの`fastcgi_pass`は`/run/php/php-fpm.sock`（alternatives経由）である。実体は同じだが参照の仕方が異なる。PHPをアップグレードする際は両方を確認すること。

### 判断：disable_functionsを設定する

```bash
sudo tee /etc/php/8.5/fpm/conf.d/99-hardening.ini > /dev/null <<'EOF'
; PHP-FPM の堅牢化
; 自分の判断による設定はこのファイルに集約する。
; conf.d は php.ini の後に読まれ、後の値が勝つ。
; fpm/conf.d に置いているため、CLI（php artisan / composer）には影響しない。

disable_functions = exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,dl
EOF
```

理由：

- Webリクエストから OS コマンドを実行できないようにする。攻撃者がPHPコードを実行できる状態になっても、できることが大きく狭まる
- このサイトはWebリクエスト中に外部コマンドを呼ばない（画像最適化は開発環境でのみ実行する）
- fpm/conf.dに置くためCLIには影響しない。「Webからはコマンドを実行できないが、デプロイ作業は普通にできる」状態になる

【影響範囲】

| 対象 | 影響 | 根拠 |
|---|---|---|
| queue:work のシグナル制御 | 影響なし | CLIはcli/php.iniを読む。本設定はfpm/conf.dにあるため届かない |
| php artisan / composer | 影響なし | 同上 |
| pcntl_exec | 対象が存在しない | pcntl拡張を導入していない。将来導入した場合の保険として列挙している |
| Webリクエスト中のProcessファサード等 | 未検証 | 本サイトでは使用していないと考えているが未確認。アプリ配置後に全ページを動かして確認すること |
| MAIL_MAILER=sendmail | 使用できない | proc_openを使うため |

この設定の要は「CLIには一切影響しない」ことである。fpm/conf.dに置いているため、Webからはコマンドを実行できないが、デプロイ作業（artisan / composer）は通常どおり行える。

【7-5への影響】`proc_open` を無効化したため、`MAIL_MAILER=sendmail` は使えない。Resendは SMTP または HTTP API を使うため問題ないが、送信方式を決める際の制約になる。

### 判断：open_basedirは設定しない

open_basedirはPHPからのあらゆるファイル操作の範囲を制限する設定であり、防御としての効果はある。しかし採用しない。

理由：

- 設定漏れがあるとアプリが壊れる。Laravelは`/tmp`や`/dev/urandom`にもアクセスする
- 壊れたときのエラーが「ファイルが開けません」という一般的なものになり、原因が設定なのかパスなのか権限なのか判別しにくい
- disable_functionsは壊れても「この関数は無効です」と名指しでエラーが出るのに対し、open_basedirは発見コストが高い
- PHP公式も、セキュリティ機能として完全ではないとしている

※「防御力」と「壊れる確率」だけでなく、「壊れたときに気づけるか」も判断材料になる、という例である。

将来、より機密性の高いものを扱うようになった場合は再検討の対象とする。

### 検証

設定の反映：

```bash
sudo php-fpm8.5 -t
sudo systemctl reload php8.5-fpm
```

**期待する出力**：`test is successful`

効いているかの確認には、phpinfo() ではなく必要な項目だけを出すファイルを使う。phpinfo() はサーバー構成をすべて公開するため、確認したい項目だけを出すほうが露出する情報が少ない。

```bash
sudo tee /var/www/html/check.php > /dev/null <<'EOF'
<?php
echo "disable_functions = ", ini_get('disable_functions'), "\n";
foreach (['exec', 'shell_exec', 'system', 'proc_open', 'popen'] as $f) {
    echo $f, ': ', function_exists($f) ? "ENABLED" : "disabled", "\n";
}
echo "opcache.validate_timestamps = ", ini_get('opcache.validate_timestamps'), "\n";
EOF
```

`http://<SERVER_IP>/check.php` を開き、各関数が`disabled`と表示されることを確認する。`function_exists()`で実際に呼べるかを見ているため、「設定に書いた」ではなく「実際に無効になっている」ことの確認になる。

確認後は必ず削除し、404になることをブラウザで確かめる。

```bash
sudo rm /var/www/html/check.php
```

---

## 17. アプリケーションの配置

### 判断：配置場所は /var/www/portfolio

Linuxには置き場所の慣習がある。

| 場所 | 性質 |
|---|---|
| /var/www/ | Webコンテンツの標準的な置き場所。Nginxの既定も/var/www/html |
| /srv/ | サービスのデータ置き場。仕様上はこちらも正しい |
| /home/`<USER>`/ | ユーザーのホーム |

ホームディレクトリを避ける理由：www-dataがファイルを読むには`/home/<USER>`を辿れる必要があり、ホームのパーミッションを緩めることになる。ホームには`.ssh`など他人に触らせたくないものが入っている。

### 判断：リポジトリの取得はHTTPS

リポジトリがパブリックなため認証が不要である。デプロイキーを作れば管理対象の鍵が1つ増えるが、いま増やす理由がない。将来プライベート化する場合に追加する。

### 手順

```bash
sudo mkdir -p /var/www/portfolio
sudo chown <USER>:<USER> /var/www/portfolio
git clone https://github.com/<OWNER>/<REPO>.git /var/www/portfolio
```

この時点では所有者を`<USER>:<USER>`にしておく。最終的な所有権はファイルが揃ってから一括で適用する（インストールで新しいファイルが増えるため）。

---

## 18. Composer のインストール

```bash
apt-cache policy composer      # Candidate が 2.x であることを確認
sudo apt install -y composer
composer --version
```

実測結果（この手順を実行した時点）：

```
Installed: (none)
Candidate: 2.9.5-1
```

インストール後の確認：

```
Composer version 2.9.5
PHP version 8.5.4 (/usr/bin/php8.5)
```

aptを選ぶ理由：36節の月次手動更新（apt upgrade）の対象になる。公式インストーラで導入すると、更新の経路がまったく無くなる。

【訂正（7-3d-2a）】当初この理由を「unattended-upgradesの自動セキュリティ更新の対象になる」と記載していたが、誤りである。

- 実測：`apt-cache policy composer` → resolute/universeから提供されている
- universeのパッケージは自動セキュリティ更新の対象外である（32節で実測済み）
- 原因は、一般則（aptなら自動更新される）を個別のケースに確認せず当てはめたこと
- 判断（aptを使う）自体は維持する。訂正が必要なのは理由の範囲のみ

Composer 1系では本プロジェクトは動かないため、Candidateが1.xの場合は公式インストーラを使う手順に切り替える。

【注意】この操作でphp8.5-intlとunzipが依存関係として導入される。

---

## 19. .env の作成

```bash
cd /var/www/portfolio
cp .env.example .env

sed -i 's|^APP_ENV=.*|APP_ENV=production|' .env
sed -i 's|^APP_DEBUG=.*|APP_DEBUG=false|' .env
sed -i 's|^APP_URL=.*|APP_URL=http://<SERVER_IP>|' .env
sed -i 's|^SESSION_DRIVER=.*|SESSION_DRIVER=file|' .env
sed -i 's|^CACHE_STORE=.*|CACHE_STORE=file|' .env
sed -i 's|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|' .env
```

【方針】本番に必要な値は、.env.exampleの既定値に依存せず、すべて明示的に設定する。

.env.exampleは開発環境のテンプレートでもあり、将来変わりうる。「既定値がこうだから設定不要」という前提を手順に持ち込むと、テンプレートが変わったときに手順が静かに壊れる。

なおSESSION_DRIVER / CACHE_STORE / QUEUE_CONNECTIONは、現在の.env.exampleの値と一致している（CACHE_STOREとQUEUE_CONNECTIONは7-3c-2で修正した）。sedは冪等なので、実行しても害はない。

【注意】Linuxのsedは`-i`の後に空文字が不要である（macOSとは書式が違う）。

設定値と理由：

| 項目 | 値 | 理由 |
|---|---|---|
| APP_ENV | production | 本番環境であることの宣言 |
| APP_DEBUG | false | trueのままだとエラー時にスタックトレースや環境変数がブラウザに表示される。.envの中身が実質的に見える |
| APP_URL | サーバーのアドレス | 絶対URL（og:url / og:image）の基点。ここが誤っているとOGPが壊れる |
| SESSION_DRIVER | file | DBを使わないため |
| CACHE_STORE | file | throttleミドルウェアがキャッシュを使う。databaseのままだとcacheテーブルが必要になり、問い合わせページが500エラーになる |
| QUEUE_CONNECTION | sync | キューを使わない方針を明示する |
| DB_CONNECTION | sqlite（変更せず） | どのコードもDBに触れないため。使わない設定が残っている状態である。唯一、明示的に設定しない項目である。DBを使わないため値に意味がなく、変更する根拠もないため |

APP_DEBUG=falseの副作用：エラーの詳細が画面に出なくなるため、問題が起きたときは`storage/logs/laravel.log`を読むことになる。

---

## 20. 依存パッケージのインストール

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

| オプション | 意味 |
|---|---|
| --no-dev | require-dev（PHPUnit、Pint、Sailなど）を入れない |
| --optimize-autoloader | クラスの場所を事前に一覧化する。実行時にファイルを探す処理が消える |
| --no-interaction | 対話プロンプトを出さない |

続けてAPP_KEYを生成する。

```bash
php artisan key:generate
php artisan about --only=environment
```

【重要】確認には`php artisan about`を使い、grepで.envのAPP_KEYを表示しないこと。APP_KEYは秘密情報であり、画面やログに残すべきではない。

APP_KEYはセッションとCookieの暗号化、および問い合わせフォームの時間トラップ（Crypt）に使われる。.envにしか存在せずgitには入らないため、サーバーを作り直すと新しい鍵になる。

【記録】laravel/tinkerとpsy/psyshはrequire（require-devではない）に入っているため本番にも導入される。CLIからしか使えないためWeb経由のリスクはないが、本番に不要なものが入っている状態である。require-devへの移動はLaravelの自動検出に影響するため、単純な移動では済まない。

---

## 21. Node.js とアセットのビルド

```bash
sudo apt install -y nodejs npm
node -v      # v22.x（開発環境は24。差の影響は下記で実測した）
npm -v

cd /var/www/portfolio
npm ci
npm run build
```

`npm install`ではなく`npm ci`を使う理由：package-lock.jsonの内容をそのまま再現し、ロックファイルを書き換えないため。本番では「開発環境と同じ依存を再現する」ことが目的である。

【重要】node_modulesを開発機からサーバーへコピーしてはならない。rolldown（Viteのバンドラ）はプラットフォームごとに異なるネイティブバイナリを使うため、必ずサーバー上でnpm ciを実行する。

### 実測結果

| 処理 | 最大メモリ | 所要時間 | swap使用 |
|---|---|---|---|
| npm ci | 251MB | 11.7秒 | 0 |
| npm run build | 294MB | 2.7秒（ビルド本体791ms） | 0 |

2GBのサーバーで、swapを一切使わずに完了した。「小さいVPSではビルドが落ちるのではないか」という懸念は否定された。

計測方法：

```bash
free -m
/usr/bin/time -v npm run build 2>&1 | tail -30
free -m
```

### 実測結果：ビルド成果物が環境をまたいで一致する

開発機（Node 24 / macOS / arm64）とサーバー（Node 22 / Linux / x86_64）でビルドし、成果物を比較した。

| 項目 | 開発機 | サーバー | 判定 |
|---|---|---|---|
| JSファイル名 | app-CYEMFStJ.js | app-CYEMFStJ.js | 一致 |
| JSサイズ | 416,091 bytes | 416,091 bytes | 一致 |
| CSSファイル名 | app-BqLPbuc1.css | app-BqLPbuc1.css | 一致 |
| CSSサイズ | 58,220 bytes | 58,220 bytes | 一致 |

Viteは内容から算出したハッシュでファイル名を決めるため、名前とサイズが一致することは中身が同一であることを意味する。

Nodeのメジャーバージョン差もCPUアーキテクチャの差も、ビルド結果に影響しないことが実測で確認できた。

【この実測の有効範囲】

この結果は、以下のバージョンの組み合わせに対するものである。

```
開発機 : Node 24.9.0（手動管理）
本番   : Node 22.22.1（apt。自動セキュリティ更新で上がりうる）
CI     : node-version: 24 の指定（実行時の最新 24.x が入る）
```

どの環境もバージョンが固定されていない。aptで導入したNodeはunattended-upgradesの対象であり、CIのsetup-nodeは実行のたびに最新のパッチ版を取得する。

【補足：npmのバージョンとUbuntuでのパッケージ構成】

npmのバージョンも記録しておく。

```
開発機 : npm 11.6.4
本番   : npm 9.2.0
```

Ubuntuではnodejsとnpmが別パッケージであり、npmパッケージが古い（9.2.0）。Node 22に本来同梱されるnpm 10系ではない。

したがってこの実測は「その時点での一致」を示すものであり、将来にわたって一致することを保証するものではない。

実害は小さいと考えられる。ビルド結果の決定性は主にバンドラとpackage-lock.jsonによって担保されており、Nodeのパッチ差が出力を変えることは稀である。ただし「保証されている」わけではない。

```
実測は時点の記録であって、将来の保証ではない。
```

【engines宣言との不一致について】

package.jsonは`"engines": { "node": "^24" }`を宣言しているが、本番サーバーではNode 22で`npm ci`を実行している。宣言と実態が食い違っている状態である。実行のたびに`npm WARN EBADENGINE`が表示される。

これは意図的な一時措置である。開発とCIを24に揃える一方、本番のNodeは一時的なものと位置づけたためである。

【解消の予定】デプロイをCIビルド＋成果物転送に切り替えた時点で、本番サーバーからNode.jsを削除する。その時点でこの不一致は解消される。

※ この不一致を「一時的だから」と放置すると、PHPのバージョン不一致が7ヶ月間残ったのと同じことになる。切り替えを行わない判断をした場合は、enginesを`>=22`に緩めるなど、宣言と実態を一致させる対応が必要である。

【npm依存の脆弱性について】

`.npmrc`に`audit=true`が設定されており、`npm ci`のたびに監査が走る。本手順の実行時点でhigh severityの脆弱性が6件報告された。

```bash
npm audit --omit=dev --audit-level=high
→ found 0 vulnerabilities
```

本番依存（dependencies）には脆弱性がなく、6件はすべてdevDependencies（ビルドツール）側である。ブラウザに配信される成果物には含まれない。

CIに`npm audit`のゲートを設ける件は別Issueとして管理している（PHP側には`composer audit`があるが、JavaScript側には同等のものがない）。

なお`npm audit fix`はサーバー上で実行してはならない。package-lock.jsonはリポジトリ管理下のファイルであり、サーバーで書き換えるとgit管理外の差分が生まれる。

---

## 22. 所有権とパーミッション

```bash
cd /var/www/portfolio
sudo chown -R <USER>:www-data .
sudo chmod -R u=rwX,g=rX,o= .
sudo chmod -R u=rwX,g=rwX,o= storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} \;
```

| 操作 | 内容 |
|---|---|
| chown | 所有者は`<USER>`（デプロイする人）、グループはwww-data（実行する人） |
| 1つ目のchmod | 所有者は読み書き、グループは読み取りのみ、その他は一切アクセス不可 |
| 2つ目のchmod | storageとbootstrap/cacheのみグループにも書き込みを許可 |
| find + g+s | この2つにsetgidを付ける |

大文字のXを使う理由：ディレクトリには中を辿るための実行権限が必要だが、通常のファイルには不要である。Xは「ディレクトリ、または既に実行権限があるファイル」にのみ実行権を付けるため、artisanなどを壊さない。

o=（その他は権限なし）にする理由：644にすると.envが誰でも読める。www-dataはグループとして読めるため、これで十分である。

setgidを付ける理由：このディレクトリに新しく作られるファイルが自動的にwww-dataグループになる。`<USER>`がstorageに書いたときにグループが`<USER>`になるとwww-dataが読めなくなる。

### 検証（権限の表示を読むのではなく、実際に試す）

```bash
sudo -u www-data test -r .env && echo "readable" || echo "NOT readable"
sudo -u www-data test -w storage/logs && echo "writable" || echo "NOT writable"
```

www-dataが.envを読めないとLaravelが起動せず、storage/logsに書けないとログが残らない。どちらも「500エラーだけ出て原因が分からない」典型的な詰まり方になる。

---

## 23. 各種キャッシュの生成

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan about --only=cache
```

【重要】config:cacheを実行すると、Laravelは以後.envを読まなくなる。すべての設定値がbootstrap/cache/config.phpにまとめられ、そこから読まれる。

.envを書き換えても、config:cacheをやり直すまで反映されない。APP_URLをドメインに変更する際なども、必ずconfig:cacheをやり直すこと。

route:cacheは、ルート定義に無名関数（クロージャ）が含まれていると「Unable to prepare route for serialization. Uses Closure.」で失敗する。その場合はクロージャをコントローラに移すか、route:cacheを使わない。

---

## 24. ファイル権限に関する重要な性質

config:cacheを実行した直後のbootstrap/cacheを確認すると、生成されたファイルは`-rw-rw-r--`（664）になっている。先に設定したo=（その他は権限なし）が効いていない。

理由：chmodは既存のファイルにしか効かない。新しく作られるファイルには、そのときのumaskに従った権限が付く。

```
ファイル単位の権限は、新規作成のたびにリセットされる。
ディレクトリの権限が、実質的な防御線になっている。
```

bootstrap/cacheは`drwxrws---`であり、その他はディレクトリを辿れない。中のファイルの権限がどうであれ、ディレクトリに入れなければ開けない。プロジェクトのルート自体もo=のため、そもそも中に入れない。

したがって現状で実害はないが、より明示的にするなら、デプロイ手順でumask 027を設定してからコマンドを実行する方法がある。

【注意：これは「その他のユーザー」に対する話である】

上記で「実害がない」としたのは、others（所有者でもグループでもないユーザー）に対する防御についてである。ディレクトリを辿れないため、中のファイルの権限がどうであれ開けない。

一方、www-dataに対する防御は別の話であり、まだ緩い状態にある。

bootstrap/cacheにはグループ（www-data）の書き込み権限が残っている。bootstrap/cache/config.phpはLaravelが起動時に読み込むPHPファイルであり、ここに書き込めるということは実質的に任意のコードを実行させられることを意味する。

これは「実行ユーザーとファイル所有権の設計」の節で「残るリスク」として挙げたものと同じである。

対処：デプロイ時に`<USER>`がキャッシュを生成し、www-dataには読み取り権限だけを与える。サイトの表示を確認した後に適用する（未実施・保留の節を参照）。

---

## 25. Nginx のサーバーブロック設定

14節で作成した暫定の`default`設定を、ここで本来のアプリ用serverブロックに置き換える（ソケットパスや`fastcgi-php.conf`の説明は14節を参照し、ここでは繰り返さない）。

7-3d-1で、1ファイル1ブロックから2ファイル2ブロックになった。最終形は以下。

`/etc/nginx/sites-available/portfolio`：

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name new.ikshowcase.site;

    # ドキュメントルートは public/ を指す。
    # これにより .env / app/ / vendor/ は Nginx から到達できない。
    root /var/www/portfolio/public;
    index index.php;

    charset utf-8;

    # Nginx のバージョンをレスポンスヘッダとエラーページから隠す
    server_tokens off;

    # 公開前の暫定措置。中身が揃い apex へ切り替える 7-9 で外す。
    auth_basic "Restricted";
    auth_basic_user_file /etc/nginx/.htpasswd;

    # 証明書の取得・更新はこの経路を通るため、認証の対象から外す。
    # 置き場所はリポジトリの外に置く。certbot がここへファイルを書くため、
    # public/ を継承させるとサーバー上のリポジトリが汚れる。
    location ^~ /.well-known/acme-challenge/ {
        auth_basic off;
        root /var/www/certbot;
    }

    # 静的ファイルが実在すればそれを返し、なければ index.php に渡す
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP は PHP-FPM に渡す
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    # ドットファイルへのアクセスを拒否する。
    # ただし .well-known は Let's Encrypt の証明書取得に必要なため除外する。
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

【前提となる節】本節の設定は次に依存する。上から順に再構築する場合は、これらを先に実施すること。

| 設定 | 依存先 |
|---|---|
| `auth_basic_user_file`が参照する`/etc/nginx/.htpasswd` | 33節 |
| acme-challengeの`root`が指す`/var/www/certbot` | 35節 |

（`nginx -t`を通過しても、これらが存在しなければ実行時にエラーになる可能性がある。この挙動は未検証である。）

`/etc/nginx/sites-available/default-deny`（新設）：

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    server_name _;

    return 444;
}
```

有効化：

```bash
sudo ln -s /etc/nginx/sites-available/default-deny \
           /etc/nginx/sites-enabled/default-deny
sudo nginx -t
sudo systemctl reload nginx
```

### 判断の理由（初期構築時）

- **`root`を`public/`に置くことが、構造による境界である。** アプリケーションコードや`.env`はNginxのドキュメントルートの外にあるため、設定ミスによる漏洩ではなく「そもそも到達経路がない」状態になる
- **`try_files $uri $uri/ /index.php?$query_string;`がフロントコントローラへの委譲である。** 実在するファイルはNginxが直接返し、それ以外は全てLaravelが受け取る
- **defaultサイトは削除ではなく`unlink`で無効化した。** `/var/www/html/index.nginx-debian.html`と`/etc/nginx/sites-available/default`はNginxパッケージが管理するファイルであり、手で削除するとパッケージ再インストール時に復活して追跡しにくい状態を作るため、参照経路を断つにとどめた。7-3d-1で受け皿のブロックを新設した際も、パッケージ管理下の`default`を書き換えるのではなく`default-deny`という別ファイルを作成した。同じ判断の延長である
- **意図的に入れなかった設定が2つある。** `location = /robots.txt`はLaravel側のルートで扱うため。`error_page 404 /index.php`は、botによる`.php`スキャンでPHPプロセスを起動させないため

### 判断の理由（7-3d-1 で追加）

- **`server_name _;`は「すべてに一致する」記号ではない。** 実際は「どのHostヘッダとも一致しない名前」であり、すべてが落ちてくるのは`default_server`の効果である。したがって名前を明示しただけでは不十分で、`default_server`を別ブロックへ移す操作が別途必要になる
- **一致しないリクエストは301ではなく444で切る。** 到達するのはIP直打ちか、第三者がこのサーバーへ向けた名前でのアクセスであり、ほぼ自動走査である。転送は「本物はこちらにある」と案内することになる。444は応答を返さず接続を閉じるため、消費する資源が最も少ない
- **`default_server`はIPv4とIPv6の両方に置く。** 片方だけだと、もう片方は引き続きポートフォリオ用ブロックに落ちる。8節でufwをv4/v6の両方で確認したのと同じ形の取りこぼしである。このサーバーには実在するIPv6アドレスが割り当てられているため、机上の話ではない
- **記述の順序に制約がある。** `default_server`は同じ待ち受けに1つしか置けない。ポートフォリオ側から外してから受け皿側に付けないと、`nginx -t`が`duplicate default server`で失敗する

### 検証

サーバー上（IPv6側）：

```bash
curl -s -o /dev/null -w '%{http_code}\n' -g 'http://[::1]/'
curl -s -o /dev/null -w '%{http_code}\n' -g -H 'Host: new.ikshowcase.site' 'http://[::1]/'
```

期待する出力：`000`、`401`

作業端末から（IPv4側）：

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://<SERVER_IP>/
curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: example.com' http://<SERVER_IP>/
curl -s -o /dev/null -w '%{http_code}\n' -u '<USER>' -H 'Host: new.ikshowcase.site' http://<SERVER_IP>/
```

期待する出力：`000`、`000`、`200`

`000`は「HTTPの応答コードが返らなかった」という意味であり、444が働いた証拠である（エラーではない）。

3つ目の検証（`Host`ヘッダを付けてIPへ直接アクセスする）の意味：DNSを経由せずサーバーを名指しで確かめる手段が残っている。1節でICMPを含めた「観測できる手段を自分から捨てない」と同じ判断である。

名前を経由した経路全体の確認は、DNSの設定が済んだ後に行う必要があるため、34節の検証に置いている。本節の検証は、DNSの状態に関わらずNginxの設定だけを対象とする。

### 暫定設定を元に戻す

14節で`/etc/nginx/sites-available/default`を書き換えたが、これはパッケージが管理している設定ファイルである。`sites-enabled`から外した後は、この内容が使われる経路が無い。元に戻す。

```bash
sudo cp /etc/nginx/sites-available/default.bak /etc/nginx/sites-available/default
dpkg -V nginx-common
sudo rm /etc/nginx/sites-available/default.bak
```

期待する出力：`dpkg -V`が無出力であること。

### 判断の理由（暫定設定を元に戻す）

- 自分の内容で保持し続けると、`nginx-common`が更新されるたびに上書き確認が出る。5節で「出たら止めて確認する」と決めた事象を、自分で作り続けることになる
- reloadは不要。`default`は`sites-enabled`にリンクされていないためNginxは読んでいない
- 検証：`dpkg -V`の無出力は、パッケージが記録している内容と実物が一致したことを意味する。`default.bak`がパッケージ本来の版と一字一句同じだったことの証明でもある（実測で確認済み）

---

## 26. メモリの実測と max_children の根拠

実測値（2026-08-23）：

| 測り方 | master | worker | 合計 |
|---|---|---|---|
| RSS | 37.9MB | 43.4MB / 47.4MB | 128.7MB |
| PSS | 16.8MB | 18.2MB / 22.1MB | 57.1MB |
| systemd cgroup | — | — | 60.2MB |

測定コマンド：

```bash
ps --no-headers -o rss,args -C php-fpm8.5
systemctl status php8.5-fpm --no-pager | head -20
for p in $(pgrep -x php-fpm8.5); do echo -n "$p "; sudo awk '/^Pss:/ {print $2, "kB"}' /proc/$p/smaps_rollup; done
```

【測定条件について】16節のメモリ実測（poolプロセス各約13MB）は、Laravelアプリを配置する前、PHP-FPMを入れた直後の値である。この節の実測（各43.4MB / 47.4MB）は、実アプリとOPcacheが稼働した後の値であり、前提が異なる。両者は同じ基準の数値ではないため、比較・参照の際は測定時点の違いに注意すること。

### 判断の理由

- **RSSで`max_children`を計算してはいけない。** RSSは共有ページ（共有ライブラリ、OPcacheの共有メモリ、copy-on-writeの未変更ページ）をプロセスごとに重複計上するため、workerあたりの増分を過大に見積もる
- **PSS合計57.1MBがcgroupの60.2MBとほぼ一致したことが、読み方の検算になった**
- **worker 1つあたりの実質増分は約20MB。** `max_children = 10`にするとPHP-FPM全体が約220MB、総メモリ1961MBの11%になる見込み（実際の変更は次節）
- **swapには依存しない前提で決めた**（16節の方針を参照）

---

## 27. PHP-FPM の pm.max_children を 5 → 10 に変更

前節の実測にもとづき、`/etc/php/8.5/fpm/pool.d/www.conf`を直接編集した。

```bash
sudo cp /etc/php/8.5/fpm/pool.d/www.conf /etc/php/8.5/fpm/pool.d/www.conf.bak
sudo sed -i 's/^pm\.max_children = 5$/pm.max_children = 10/' /etc/php/8.5/fpm/pool.d/www.conf
sudo php-fpm8.5 -t
sudo systemctl reload php8.5-fpm
sudo php-fpm8.5 -tt 2>&1 | grep -n "max_children"
```

### 判断の理由

- **pool設定は`conf.d/`に別ファイルを置く方式が使えない。** 同じ`[www]`プールを複数ファイルで定義できないため、`www.conf`を直接編集する。7-3aのsshdや16節（本番向けPHP設定）の`conf.d/99-hardening.ini`とは異なるパターンである
- **`php-fpm8.5 -t`は構文検査、`-tt`は解釈後の設定全体のダンプ。** `sshd -T`や`apt-config dump`と同じ「実効値を見る手段」
- **`.bak`を同じディレクトリに置いても読み込まれない。** includeのパターンが`pool.d/*.conf`のため

---

## 28. OPcache の実測

`public/`に一時ファイルを置き、`curl`で取得して即削除する方法で測定した（16節の`check.php`と同じ型）。

実測値（2026-08-23）：

| 項目 | 値 |
|---|---|
| used / free | 26.9MB / 100.9MB（既定`memory_consumption=128M`、使用率21%） |
| num_cached_scripts | 833 / 10000 |
| hits / misses | 14217 / 836（ヒット率94.4%） |
| oom_restarts | 0 |

### 判断の理由

- **misses 836とnum_cached_scripts 833がほぼ一致している。** missのほぼ全てが初回コンパイルであり、キャッシュからの追い出しによる再コンパイルが起きていない。よって既定値のままでよい（16節の判断が実測で裏付けられた）
- **OPcacheの128MBは全workerで共有される。** workerを増やしても増えない

---

## 29. bootstrap/cache の権限を締めた

7-3c-2の欠点指摘①（www-dataがグループ書き込みできる状態）を解消した。

```bash
sudo chmod g-w,o-rwx /var/www/portfolio/bootstrap/cache
sudo chmod g-w,o-rwx /var/www/portfolio/bootstrap/cache/*
sudo chmod g-w,o-rwx /var/www/portfolio/bootstrap/cache/.gitignore
```

### 判断の理由

- **bootstrap/cacheとstorageは求められる権限が異なる。** 前者はデプロイ時に`<USER>`が生成しwww-dataは読むだけでよい。後者はwww-dataが実行時に書く（セッション・キャッシュ・ログ）ため、ディレクトリのグループ書き込みを残す
- **chmodだけでは不十分。** `config:cache`のたびにファイルが作り直され、新しいファイルにはumaskに従った権限が付くため。既存ファイルは`chmod`、今後の生成は`umask`の両方が要る（umaskの適用は当初30節に記載したが、後日方針を変更した。詳細は本節末尾を参照）
- **chmodの対象に`*`を使うとドットファイルが漏れる。** `.gitignore`を個別に指定した
- **setgid（`s`）は`g-w`では消えない。** setgidの役割はグループの継承であり、書き込み許可とは別のもの

### 【2026-08-23の記録と、その後の方針転換】

同日、`(umask 027 && php artisan config:cache && php artisan route:cache && php artisan view:cache)`を実行し、`640`のキャッシュファイルを生成した。この事実は記録として残す。

しかしこの方式は継続採用しない。理由は30節に詳細を記載しているため、ここでは要点のみ記す。

- 二本立て（`chmod`と`umask`を対象ごとに使い分ける）は適用漏れを生む。実際に`config:cache`だけをumaskで守り、同じディレクトリの`packages.php` / `services.php`を守り損ねた
- 必要な権限は対象ごとに異なる（`vendor/`はwww-dataが読めなければならず、`bootstrap/cache`は書かせてはならず、`storage/`は書けなければならない）。一律のumaskではこれを表現できない

30節「更新時のデプロイ手順」には、生成後に一括で権限を再適用する方式（`chgrp` / `chmod`）を記載した。

---

## 30. 更新時のデプロイ手順

これまでの記録は初回構築の作業列であり、2回目以降の手順として整理されていなかった。実際に2026-08-23のデプロイで`config:cache`の再実行が漏れ、本番がconfigキャッシュなしで動く状態になった。

### 問題：umaskだけでは守りきれない

当初、`(umask 027 && php artisan config:cache && php artisan route:cache && php artisan view:cache)`という形で`umask 027`のサブシェルを使い、キャッシュ生成コマンドだけを囲んでいた（29節に記録）。

しかし`composer install`は`bootstrap/cache/packages.php`と`services.php`を新規作成するため、それらは`umask 027`の外側で実行され、サーバーの実際のumask（`0002`）に従って`664`（グループ書き込み可）で作られる。29節で締めた穴が、デプロイのたびに再び開いてしまう。

さらに実測により以下が判明した（2026-08-23）：

- サーバーのumaskは`0002`
- `<USER>`のプライマリグループは`<USER>`であり、**www-dataグループには属していない**
- `/var/www/portfolio`以下のディレクトリに**setgidは付いていない**（storageとbootstrap/cacheを除く）

したがって、新規作成されるファイルのグループは`<USER>`になる。

| デプロイ時のumask | 新規ファイル | グループ | www-dataから見ると |
|---|---|---|---|
| 002（現状） | 664 / 775 | `<USER>` | otherとして読める（動くが`o=`の設計に反する） |
| 027 | 640 / 750 | `<USER>` | **otherなので読めない → 500エラー** |

**`umask 027`を`composer install`まで広げると`vendor/`が読めなくなり本番が停止する。**

### 採用した方針

「作る側を変える（umask）」ではなく「作った後に直す（chgrp / chmod）」に統一する。`umask 027`のサブシェルは廃止する。

理由：

- **二本立ては適用漏れを生む。** 実際に`config:cache`だけをumaskで守り、同じディレクトリの`packages.php` / `services.php`を守り損ねた
- **必要な権限は対象ごとに異なる。** `vendor/`はwww-dataが読めなければならず、`bootstrap/cache`は書かせてはならず、`storage/`は書けなければならない。一律のumaskではこれを表現できない
- 「最後に必ず全体へ再適用する」なら、対象を列挙し忘れる経路が構造的に存在しない

### 手順

```bash
cd /var/www/portfolio
git pull
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chgrp -R www-data /var/www/portfolio
sudo chmod -R u=rwX,g=rX,o= /var/www/portfolio
sudo chmod -R g+w /var/www/portfolio/storage
sudo find /var/www/portfolio/storage /var/www/portfolio/bootstrap/cache -type d -exec chmod g+s {} \;
php artisan about --only=cache
```

### 判断の理由

- **`composer install`はconfigキャッシュを破棄する。** 実験で確認済み（`config:cache`実行後に`composer install --no-dev --optimize-autoloader`を流すと`bootstrap/cache/config.php`が消え、`packages.php`と`services.php`は再生成され、`routes-v7.php`は残る）。したがって`composer install`→`config:cache`の順序が必須
- **権限の再適用は、全ての生成が終わった後にまとめて実行する**
- **`find ... -exec chmod g+s`を入れているのは、`chmod -R u=rwX,g=rX,o=`がsetgidを落とす可能性があるため。** 落ちるかどうかは実装依存で未検証だが、明示的に付け直せばどちらでも同じ結果になる。setgidが落ちると、次に`view:cache`を実行したとき`storage/framework/views/`のファイルがグループ`<USER>`で作られ、www-dataから読めなくなる
- **完了確認は`php artisan about --only=cache`。** `Config` / `Routes` / `Views`が`CACHED`であること（`Events`は`NOT CACHED`でよい）

`npm ci` / `npm run build`は7-4でCIビルドへ移行する判断を予定しているため、その時点でこの手順から外れる可能性がある。

この権限再適用ブロックは2026-08-23に実機で実行し、以下を確認済み。

- `storage`系が`drwxrws---`
- `bootstrap/cache`が`drwxr-s---`、配下のファイルは`-rw-r-----` / `-rwxr-x---`
- `.env`が`-rw-r-----` `<USER>`:www-data
- 全ページと問い合わせフォームが正常に動作し、新規エラーなし

---

## 31. トラブルシューティング：--no-dev で欠ける暗黙依存

初回公開時に500エラーが発生した。原因は`symfony/yaml`が本番の`vendor/`に無かったこと。

- league/commonmarkのFrontMatter拡張は`symfony/yaml`を**suggest（推奨）**として宣言しており、必須依存ではない
- 開発環境では`laravel/sail`（`require-dev`）が間接的に引き込んでいた
- `composer install --no-dev`でその引き込みが消え、Markdownのfront matter解析が失敗した

対処：`composer require symfony/yaml`で`require`に明示追加した。

- **ホストのMacはPHP 8.4であり、composer.jsonの`php ^8.5`を満たさないため、composerをホストで実行できない。** Sailコンテナ内（PHP 8.5）で実行した。このリポジトリのcomposer操作は今後コンテナ内で行う
- `--ignore-platform-req=php`で押し通す方法は採らない。「本番と同じPHPで依存解決する」前提が壊れるため

---

## 32. ESM（Ubuntu Pro）を有効化しない判断

`pro security-status`で保留中の3件は`node-lodash` / `node-lodash-packages` / `node-shell-quote`であり、いずれも`apt install npm`が引き込んだuniverseのパッケージである。本番のWebリクエスト処理には無関係。

- **7-4でNode.jsをサーバーから削除すれば、universe依存の大半が消える**
- ESMは外部サービスへの依存を増やすため、それに見合う対象が存在しない現状では有効化しない

---

## 33. 公開範囲の制限（Basic認証）

**目的**：名前を配る前に、到達できる相手を自分だけに絞る。

### なぜ証明書取得より前に行うか

公的に信頼される証明書は、Certificate Transparencyという公開の追記専用ログに必ず記録される。ログは誰でも監視でき、新しいホスト名を拾って走査するボットが実在する。証明書を取得した時点で、`new.ikshowcase.site`の存在が公表される。DNSを引いただけの段階とは前提が変わるため、閉じるのが先になる。

### 手順

```bash
openssl passwd -6
```

プロンプトに応答してパスワードを入力し、`$6$`で始まるハッシュを得る。パスワードをコマンドラインに書かないのは、シェルの履歴に平文で残るため。

```bash
sudo tee /etc/nginx/.htpasswd > /dev/null <<'EOF'
<USER>:<PASSWORD_HASH>
EOF

sudo chown root:www-data /etc/nginx/.htpasswd
sudo chmod 640 /etc/nginx/.htpasswd
```

### 検証

```bash
ls -l /etc/nginx/.htpasswd
sudo -u www-data test -r /etc/nginx/.htpasswd && echo "readable" || echo "NOT readable"
```

期待する出力：`-rw-r----- 1 root www-data`、および`readable`

```bash
curl -s -o /dev/null -w '%{http_code}\n' \
     -H 'Host: new.ikshowcase.site' http://<SERVER_IP>/
curl -s -o /dev/null -w '%{http_code}\n' \
     -H 'Host: new.ikshowcase.site' http://<SERVER_IP>/.well-known/acme-challenge/test
curl -s -o /dev/null -w '%{http_code}\n' \
     -u '<USER>' -H 'Host: new.ikshowcase.site' http://<SERVER_IP>/
```

期待する出力：`401`、`404`、`200`

`Host`ヘッダを明示すれば、DNSの設定状況に関わらず、サーバー側の設定だけを検証できる。25節で`default_server`を444に振り分けた後はIP直打ちでは到達できず、DNS設定前は名前でも到達できないため、この書き方が唯一DNSに依存しない確認手段になる。

### 判断と注意

- **apache2-utils（htpasswdコマンド）は導入しない。** 既に入っているopensslで生成したSHA-512 crypt形式をNginxが検証できることを実測で確認した。13節でApache関連を避けた判断、32節の「導入するものを減らせば更新対象も攻撃面も減る」と整合する
- **所有権の設計は22節と同じ。** 所有者は変更する人（root）、グループは読む人（www-data）、その他はアクセス不可。権限の表示を読むのではなく、`sudo -u www-data`で実際に読めるかを試す
- **`/.well-known/acme-challenge/`の確認は404であること。** 401なら認証がかかったまま、404なら認証を抜けてファイルを探しに行った証拠。「設定に書いた」ではなく「実際に外れている」ことの確認であり、16節の`function_exists()`による確認と同じ考え方
- **HTTPS化前は、資格情報が経路上を平文で流れる。** したがって他で使用しているパスワードを流用してはならない。7-3d-2のHTTPS化で解消する
- この設定は暫定であり、7-9で撤去する

---

## 34. DNS の設定（ムームーDNS）

**目的**：`new.ikshowcase.site`が新サーバーを指すようにする。

### ムームーDNSの構造

ムームーDNSには2つの層があり、両方が同時に有効である。

| 区分 | 内容 |
|---|---|
| 設定1 | 「この名前を自社のどのサービスに割り当てるか」を選ぶ。IPアドレスは指定できない |
| 設定2（カスタム設定） | レコードを1本ずつ記述する。外部サービスを使う場合はこちら |

共存することは実測で確認した。apexとwwwのAレコードは設定2に存在し、apexのMXとSPFのTXTは設定2に存在しないが、いずれも実際に引ける。後者は設定1から生成されている。

設定2に既存の3本（Resend用。今回は触っていない）：

| サブドメイン | 種別 | 役割 |
|---|---|---|
| resend._domainkey | TXT | DKIMの公開鍵 |
| send | MX | 配信不能通知の受け取り先 |
| send | TXT | sendサブドメインのSPF |

### 制約

- TTLは3600秒で固定であり、変更できない
- 反映には最大1時間かかる（公式の記載）
- 優先度欄はMXとSRVのみが対象。空欄の場合は自動的に50が入る
- サブドメイン欄が空欄の場合、apex（ikshowcase.site）のレコードになる

### 追加した行

| サブドメイン | 種別 | 内容 | 優先度 |
|---|---|---|---|
| new | A | `<SERVER_IP>` | 空欄 |

### 検証

変更前と変更後で同じコマンドを実行し、照合する。

```bash
dig +noall +answer @dns01.muumuu-domain.com ikshowcase.site SOA
dig +noall +answer @dns01.muumuu-domain.com new.ikshowcase.site A
dig +noall +answer @dns01.muumuu-domain.com ikshowcase.site A
dig +noall +answer @dns01.muumuu-domain.com ikshowcase.site MX
dig +noall +answer @dns01.muumuu-domain.com ikshowcase.site TXT
dig +noall +answer @dns01.muumuu-domain.com www.ikshowcase.site A
```

変更前の状態（`<LOLIPOP_IP>`はロリポップのIP）：

| 対象 | 値 |
|---|---|
| NS | dns01.muumuu-domain.com / dns02.muumuu-domain.com |
| apex A | `<LOLIPOP_IP>` |
| apex MX | 50 mx01.lolipop.jp. |
| apex TXT | v=spf1 include:_spf.lolipop.jp ~all |
| www A | `<LOLIPOP_IP>` |
| new A | 存在しない |

変更後：newのAが`<SERVER_IP>`になり、他の4本は一切変化していない。

### 通常の経路での確認（権威サーバーでの確認が済んだ後）

```bash
dig +noall +answer new.ikshowcase.site A
curl -s -o /dev/null -w '%{http_code}\n' http://new.ikshowcase.site/
curl -s -o /dev/null -w '%{http_code}\n' -u '<USER>' http://new.ikshowcase.site/
```

期待する出力：`<SERVER_IP>`、`401`、`200`

レコードが引けることと、その名前でサーバーに到達できることは別である。権威サーバーへの直接問い合わせは反映の判定に使い、通常の経路でのdigとHTTPの応答で、名前→IP→Nginx→認証という経路全体が通っていることを確認する。反映には最大1時間かかるため、ここで引けない場合は時間をおいて再確認する。

### 判断の理由

- **権威サーバーに直接聞く（`@dns01.muumuu-domain.com`）。** 通常のdigは途中のキャッシュを経由した答えを返す。とくに**「存在しない」という答えもキャッシュされる**（キャッシュされる秒数はSOAレコードの最終フィールド。このzoneでは3600秒）。追加前に通常の経路で引いてしまうと、追加後もその時間だけ見えない可能性がある
- **反映の判定にはSOAのシリアルを使う。** 管理画面が「保存しました」と表示することと、権威サーバーの答えが変わることは別である。シリアルはUnix時刻であり、変更のたびに更新される
- **この検証の要点は、足した行が見えることではなく、足していない行が変わっていないことである。** MXとSPFが消えるとメールが停止するため、変更前の状態を先に記録し、後で照合する形にした
- **自社サービスへの割り当ては設定2で行わないことが推奨されている。** 現状はapexとwwwのAが設定2からロリポップを指しており、この推奨に反している。既存の状態であり今回は触らない

---

## 35. 証明書の取得（Let's Encrypt / certbot）

**目的**：証明書を取得する。Nginxへの適用は行わない。

### 判断：取得と適用を分ける

実測：`sudo certbot plugins`の出力に`nginx`が含まれることを確認した。certbotはNginxの設定を自動で書き換える機能を持つが、使わない。

使うと「証明書を取る」と「HTTPSを有効にする」が1つの操作に融合し、何が起きたかが見えなくなる。1節でスタートアップスクリプトを使わなかった判断、13節でphpメタパッケージを避けた判断と同じ形である。

分けることで安全にもなる。取得に失敗しても、動いているサイトは何も変わらない。

### 判断：certbotはsnapで導入する（aptを使わない）

- 実測：`apt-cache policy certbot` → resolute/universe、Candidateは`4.0.0-4`
- 実測：snapは導入済み。`systemctl is-active snapd.socket` → `active`、`snap list`が応答する

- 理由1：出典：Ubuntuの Stable Release Updates 方針では、リリース後の更新は限られた状況でのみ行われ、原則としてバグ修正とセキュリティ修正に限られる。ただし特定のパッケージ群には個別の例外規定が存在する（https://ubuntu.com/project/docs/SRU/stable-release-updates）。したがって「絶対に版が上がらない」のではなく、「原則として上がらず、上がる場合は個別の例外として定められている」が正確である。certbotにその例外が適用されているかは確認していない（推測：適用されていないと考えている）。aptで入れると、少なくとも原則の範囲では26.04のサポート期間中4.0.0のままになる
- 理由2：universeのため、セキュリティ更新も保証されない（32節で実測）
- 理由3：ACMEの仕様は変化し続けている。出典：証明書の有効期間は2027-02-10に64日、2028-02-16に45日へ短縮予定（https://letsencrypt.org/upcoming-features/）

代償：snapは自動更新されるため、予期しないタイミングで版が上がる。32節・1節の「自動で入るものを増やさない」とは正面からぶつかる。それでもsnapを選んだのは、止まった瞬間にサイトが開けなくなる種類の道具では、「古いまま止まる」ほうが「勝手に新しくなる」より危険だと判断したため。

実測：導入結果はcertbot 5.7.0（aptの4.0.0に対して）

```bash
sudo snap install core
sudo snap refresh core
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot
certbot --version
```

- `--classic`：snapは通常、隔離された環境でソフトを動かす。certbotは`/etc/letsencrypt/`に鍵と証明書を書き、Webサーバーの公開ディレクトリにも触るため、隔離を外す指定が要る
- `ln -s`：snapのコマンドは`/snap/bin/`に置かれる。PATHに含まれない場面があるため、標準の場所から参照できるようにする

### 判断：チャレンジの置き場所はリポジトリの外に置く

出典：https://letsencrypt.org/docs/challenge-types/

HTTP-01チャレンジは、認証局が指定した文字列をWebサーバーに置かせ、外から取りに来る方式である。locationの`root`を差し替えれば、この経路だけ別の場所を見せられる。

- ACMEクライアントが`http://<ドメイン>/.well-known/acme-challenge/<トークン>`にファイルを置き、Let's Encryptのサーバーが複数の場所から複数回取りに来る
- 80番ポートでしか行えない。任意のポートは指定できない
- リダイレクトは10段まで追う。`http:`と`https:`のみ受け付ける。HTTPSへリダイレクトした場合、その先の証明書は検証されない
- ワイルドカード証明書の発行には使えない

3つ目（リダイレクトの追跡）は7-3d-2bに直接関わるため、必ず記載する。80番を443へ転送する設計にしても、この経路は成立する。

「消し忘れないようにする」ではなく「そもそもリポジトリに到達しない」構造にする。25節で`root`を`public/`に置いた判断と同じ形である。

```bash
sudo mkdir -p /var/www/certbot/.well-known/acme-challenge
sudo chown -R root:www-data /var/www/certbot
sudo chmod -R 755 /var/www/certbot
```

22節の`o=`（その他はアクセス不可）を適用しない。置かれるのは認証局に取りに来てもらう一時ファイルであり、公開されることが前提である。隠す対象がないところに規則を機械的に当てると、certbotがディレクトリを作る際に噛み合わない余地を増やすだけになる。

### 検証：認証局と同じ方法で経路を試す

```bash
echo ok | sudo tee /var/www/certbot/.well-known/acme-challenge/test > /dev/null
curl -s -H 'Host: new.ikshowcase.site' http://<SERVER_IP>/.well-known/acme-challenge/test
curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: new.ikshowcase.site' http://<SERVER_IP>/
sudo rm /var/www/certbot/.well-known/acme-challenge/test
curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: new.ikshowcase.site' http://<SERVER_IP>/.well-known/acme-challenge/test
```

期待する出力：`ok` / `401` / `404`

- 設定を読んで確認するのではなく、実際にファイルを置いて取れるかを見ている
- 一時ファイルの作成・確認・削除を一続きで行い、消えたことまで確認する（14節・16節と同じ形）

### 取得：本番を消費しない形で先に試す

```bash
sudo certbot certonly --webroot -w /var/www/certbot -d new.ikshowcase.site --dry-run
```

期待する出力：`The dry run was successful.`

- 練習用のサーバーに対して同じ手順を最後まで通す。実際の証明書は作られず、本番の上限を消費しない
- それでいて、チャレンジの経路は本番とまったく同じように試される
- 出典：https://letsencrypt.org/docs/rate-limits/
  - 同一の識別子の組に対する発行は7日間で5件まで
  - 検証の失敗は1時間あたり5件まで
  - 練習用の環境は大幅に緩い制限を持つ（制限が無いわけではない）
- 設定を誤ったまま本番で繰り返すと上限に達し、数日待たされる

成功した場合のみ、本番を実行する。

```bash
sudo certbot certonly --webroot -w /var/www/certbot -d new.ikshowcase.site
```

メールアドレス（`<EMAIL>`）と規約への同意を聞かれる。練習用と本番はそれぞれ別の口座として扱われるため、両方で聞かれる。

### 確認

```bash
sudo certbot certificates
sudo ls -l /etc/letsencrypt/live/new.ikshowcase.site/
curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: new.ikshowcase.site' http://<SERVER_IP>/
```

実測結果：

| 項目 | 値 |
|---|---|
| certbotの版 | 5.7.0 |
| 鍵の種類 | ECDSA |
| 有効期限 | 取得時点から89日 |
| live/の中身 | 4つのシンボリックリンク。実体はarchive/の`...1.pem` |
| サイトの応答 | 401（変化なし） |

最後の401が本節の完了条件である。証明書は取得したが、まだ適用していないことの確認になる。

実測：現時点でlive/の4ファイルは、すべて`archive/new.ikshowcase.site/...1.pem`へのシンボリックリンクである。

推測：更新のたびに実体が`2.pem` `3.pem`と増え、リンクだけが最新を指し続けると理解している。このサーバーではまだ更新が発生していないため未確認。初回の更新時に確認すること。Nginxにはリンクの側を書く。

### 運用上の重要な事実

出典：Let's Encryptは、期限切れの警告メールを送るサービスを2025年6月に終了している（https://letsencrypt.org/2025/06/26/expiration-notification-service-has-ended）

したがって、更新に失敗しても認証局は何も知らせない。検知手段は自前で用意する必要がある。

---

## 36. 運用上の注意（恒久的に発生すること）

- 通常更新（`-updates`）は自動適用されない。月1回程度、手動で `apt update && apt upgrade` を実行する必要がある
- ポートを開けるときはConoHaセキュリティグループとufwの2箇所を見る
- まとめトクは契約満了の7日前に自動更新・自動決済される。入金が確認できないとサーバーが削除される。クレジットカードの有効期限を管理し、満了日をカレンダーに入れる
- 監視は未設定。サーバーやサービスが停止しても検知されない
- バックアップは未設定。サーバー設定はこの手順書にのみ存在するため、手順書と実物のずれがそのまま復元できない範囲になる
- PHPをアップグレードする際は、Nginxの設定が参照している `/run/php/php-fpm.sock` の指す先が意図したバージョンかを確認すること
- `php -v` / `php -m` はCLI側の情報である。FPM側の設定を確認するにはphpinfo()をブラウザから見る必要がある
- 自動セキュリティ更新が実際に動いたかは、外から試せない。次のコマンドで記録を確認する：`sudo tail -50 /var/log/unattended-upgrades/unattended-upgrades.log`
- ライブラリ（libcurlなど）が更新されると、needrestartがphp8.5-fpmやnginxを自動で再起動する。サービスの起動時刻が変わっていたら、この可能性を疑う
- aptで導入したパッケージ（Node.js、Composer、PHP、Nginx）はunattended-upgradesの対象であり、セキュリティ更新でバージョンが自動的に上がる。ビルド成果物の一致を検証した結果は、検証時点のバージョンに対するものであり、更新後も同じとは限らない
- ログインバナーが表示する更新可能パッケージ数は、生成時点のキャッシュである。実効値は`apt update`の後の`apt list --upgradable`で確認する。実際に、バナーが3件と表示していたときの実数は7件だった
- grubが更新対象に含まれる場合は、作業者が見ている時間帯に自分で再起動して、起動することを確認する。`/var/run/reboot-required`は「再起動が必要か」を示すが「再起動して問題ないか」は示さない。ブートローダの破損は次の起動まで表面化せず、9節の自動再起動は19:00 UTC（日本時間の午前4時）に無人で走る

---

## 37. 未実施・保留

- ESM（Ubuntu Pro）：未有効。標準サポート期間中の追加価値が未確認のため保留 → 解決：7-3c-3で「有効化しない」と判断（32節）
- 監視の未設定（36節を参照）→ 7-9で対応する。理由：監視が要るのは「落ちて困る状態」になったときであり、7-3dの時点では対象が存在しない。ただし証明書の自動更新の失敗は90日後に沈黙のまま起きるため、その検知手段は7-3d-2の完了条件に含める
- pm.max_childrenの調整（アプリ配置後に実測して決める） → 解決：7-3c-3で実測のうえ10に変更（26節・27節）
- opcache.max_accelerated_filesが足りているかの実測 → 解決：7-3c-3の実測で既定値のままでよいと確認（28節）
- opcache.validate_timestamps = 0の再検討（デプロイ自動化後）
- open_basedir（検討したうえで見送り）
- bootstrap/cacheのグループ書き込み権限を締める（7-3bの設計では「www-dataには読み取り権限だけを与える」としている。サイトが表示されることを確認してから締め、締めた後も動くことを確認するという順序にするため保留している） → 解決：7-3c-3で締めた（29節）
- デプロイ手順でのumask 027の適用 → 解決していない。7-3c-3では採用せず、「作った後に直す」方式（chgrp / chmod）を選んだ（30節参照）
- setgidの再帰付与とumask 027によるデプロイ（案B）の検討。`/var/www/portfolio`以下のディレクトリにsetgidを再帰付与すれば、新規ファイルのグループがwww-dataを継承するため、`umask 027`だけで正しい権限になり、デプロイのたびのchgrp / chmodが不要になる。ただしstorageの書き込み権限の扱い、php-fpm側のumask、既存ディレクトリへの一括付与という3つの設計判断を伴うため、7-4のデプロイ自動化と一体で検討する
- defaultがパッケージ管理下でありながら書き換えられている → 解決：25節末尾で元に戻した
- default.bakの要否 → 解決：削除した（25節）

### 7-3d-1で新たに判明した持ち越し項目

| 項目 | 回収先 |
|---|---|
| `/.well-known/acme-challenge/`の置き場所が未確定。現状はserverのroot（`public/`）を継承しており、certbotがここへ書くとリポジトリが汚れる | 解決：35節で対応（リポジトリ外の専用ディレクトリ`/var/www/certbot`に置いた） |
| 443側の受け皿（catch-all）が未設定 | 7-3d-2b |
| Basic認証のユーザー名がOSのユーザー名（SSHの`AllowUsers`対象）と同一。401はユーザー名を明かさず、SSHはパスワード認証を無効化済みのため実害は小さいが、7-3aでありふれない名前を選んだ判断とは整合しない | 7-9（認証撤去で解消） |
| pollinateが不要パッケージとして残っている（`apt autoremove`未実施） | 7-3d-1の区切り |
| Resendのレコードがapexベースで登録されている（`send`と`resend._domainkey`）。引き継ぎ資料の`mail.ikshowcase.site`という記載と一致しない | 7-5 |
| `new.ikshowcase.site`の廃止方法（レコード削除か、apexへの転送を残すか） | 7-9 |
| apexの切り替えは、設定2のAの行の内容を書き換えるだけで済む（設定1は触らない） | 7-9 |

### 7-3d-2aで新たに判明した持ち越し項目

| 項目 | 回収先 |
|---|---|
| live/のリンクが更新時に張り替わる挙動が未確認。初回更新時にarchive/の中身を確認する | 初回の実更新時 |
| Let's Encryptは期限切れの警告メールを送らない。更新失敗の検知手段を自前で用意する必要がある | 7-3d-2c |
| 手順書の判断の根拠が検証されていない（Issue登録済み） | 7-4の先頭 |
| 手順書の実行順序が通しで検証されていない（Issue登録済み） | 7-4の先頭 |
| 節を追加するたびに節番号の参照を修正する必要がある。番号ではなく名前で参照する形に変えるべきか | 7-4の先頭（上2件と同じカードで扱う） |
| バックアップの破棄時期が手順書に規定されていない。7-3d-2aでは「取得完了時に削除」と事前に決めて実行した | 7-4の先頭 |
