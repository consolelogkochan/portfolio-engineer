#!/usr/bin/env bash
#
# 公開の URL を外から引いて、主なパスが 200 を返すことを確かめる。
# deploy.yml の verify ジョブが、デプロイの後に実行する。手元からも実行できる。
#
# 使い方
#   ./bin/check-site.sh https://new.ikshowcase.site
#
# 設計の要点
#   ・「bin/deploy.sh の成功」と「サイトの動作」は別。こちらは訪問者と同じ形で外から見る
#   ・引くパスは ci.yml の本番と同じ依存構成での表示確認と同じ6本
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

failed=0
for path in / /works /about /contact /sitemap.xml /robots.txt; do
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
