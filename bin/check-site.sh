#!/usr/bin/env bash
#
# 与えた URL を外から引いて、主なパスが 200 を返すことを確かめる。
# 2か所から呼ぶ。手元からも実行できる。
#   ・deploy.yml の verify ジョブ：デプロイの後に、公開の URL に向けて
#   ・ci.yml の本番と同じ依存構成での表示確認：CI の中で立てたサーバーに向けて
#
# 使い方
#   ./bin/check-site.sh https://new.ikshowcase.site
#
# 設計の要点
#   ・「bin/deploy.sh の成功」と「サイトの動作」は別。こちらは訪問者と同じ形で外から見る
#   ・引くパスの一覧は、下の paths の1か所だけに置く（2つの呼び出し元で同じものを引く）
#   ・どれか1つでも 200 でなければ、パスと状態コードを出して終了コード1
#   ・引き直すのは、接続できなかった（000）ときと 5xx のときだけ。
#     4xx（401・404 など）は待っても変わらないので、引き直さない
#   ・認証の情報は渡さない。リダイレクトは追わない（3xx も失敗にする）
set -euo pipefail

if [ $# -ne 1 ]; then
  echo "使い方: $0 <URL（末尾の / なし）>" >&2
  exit 2
fi
base="$1"

# 回数と間に実測の根拠は無い。bin/deploy.sh は public/build の付け替えと
# キャッシュの作り直しを終えてから戻るので、デプロイ由来の揺れは見込んでいない。
# 通信の一時的な失敗に備えるだけの値。
attempts=3
delay=5

# /about は Markdown のレンダリングの経路、/contact は front matter を通らない暗号化の経路を通す。
# 固定のページに加えて、作品と開発ログの詳細を1件ずつ引く。詳細のページは
# front matter と本文の変換を通る別の経路で、ここが無いと詳細だけが壊れても気づかない。
# slug を並べると作品を足すたびに直すことになるので、全件ではなく代表の1件にしている。
# /sitemap.xml は作品と開発ログを全件走査するので、内容が増えても追随する。
# 作品か開発ログの slug（portfolio-engineer）を変えたら、ここも直す。直さなければ
# 404 で CI が赤になるので、気づける。
paths=(
  /
  /works
  /works/portfolio-engineer
  /logs/portfolio-engineer
  /about
  /contact
  /sitemap.xml
  /robots.txt
)

failed=0
for path in "${paths[@]}"; do
  for i in $(seq 1 "$attempts"); do
    code="$(curl -s -o /dev/null -w '%{http_code}' --connect-timeout 5 --max-time 15 "${base}${path}" || true)"
    if { [ "$code" = "000" ] || [[ "$code" == 5?? ]]; } && [ "$i" -lt "$attempts" ]; then
      echo "${path}  ${code}（${delay}秒後に引き直す）" >&2
      sleep "$delay"
      continue
    fi
    break
  done
  echo "${path}  ${code}"
  if [ "$code" != "200" ]; then
    echo "FAILED: ${path} -> ${code}" >&2
    failed=1
  fi
done

exit "$failed"
