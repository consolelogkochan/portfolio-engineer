# サーバー構築手順書（7-3a：ConoHa VPS契約とサーバーの安全化）

この文書は、本番サーバーを構築した際の手順を再現可能な形で記録したものです。
サーバーを作り直す必要が生じたとき、上から順に実行すれば同じ状態に到達できることを目標としています。

伏せている情報は次の通りです。実際の値には置き換えず、プレースホルダのまま参照してください。

- IPアドレス → `<SERVER_IP>`
- 作業用ユーザー名 → `<USER>`
- 公開鍵の実体 → `<PUBLIC_KEY>`
- パスワード・パスフレーズは本文中に一切記載していません

ホスト名（`portfolio`）や設定の中身そのものは、手順書として機能させるため伏せていません。

---

## 1. 前提

- ConoHa VPS 3.0 / 2GB / 東京リージョン / Ubuntu 26.04 LTS
- 料金プラン：まとめトク6ヶ月
  - 理由：クレジットカードの更新時期を契約期間の内側に抱え込み、更新イベントの回数を減らすため
  - 注意：入金が確認できず契約満了を迎えるとサーバーが削除される（詳細は12節）

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

---

## 12. 運用上の注意（恒久的に発生すること）

- 通常更新（`-updates`）は自動適用されない。月1回程度、手動で `apt update && apt upgrade` を実行する必要がある
- ポートを開けるときはConoHaセキュリティグループとufwの2箇所を見る
- まとめトクは契約満了の7日前に自動更新・自動決済される。入金が確認できないとサーバーが削除される。クレジットカードの有効期限を管理し、満了日をカレンダーに入れる
- 監視は未設定。サーバーやサービスが停止しても検知されない
- バックアップは未設定。サーバー設定はこの手順書にのみ存在するため、手順書と実物のずれがそのまま復元できない範囲になる

---

## 13. 未実施・保留

- ESM（Ubuntu Pro）：未有効。標準サポート期間中の追加価値が未確認のため保留
- 監視：未設定。回収先は7-3dまたは7-10
