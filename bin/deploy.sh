#!/usr/bin/env bash
#
# 本番サーバーのデプロイ手順。docs/server-setup.md の30節に対応する。
#
# 設計の要点（詳細は30節）
#   ・sudo を使わない。権限は setgid（22節）と umask 027 により、
#     作られる時点で満たす
#   ・失敗したら止まる。自動では巻き戻さない
#   ・「このスクリプトの成功」と「サイトの動作」は別。応答確認は含めない
#
# 全体を { } で囲んでいるのは、git pull がこのファイル自身を書き換えても、
# bash が読み進める途中で内容が変わらないようにするため。
# bash は { } の中を先に読み切ってから実行する。
{
  set -euo pipefail
  umask 027

  cd "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.."

  echo "==> 作業ツリーの確認"
  if [ -n "$(git status --porcelain)" ]; then
    echo "作業ツリーがクリーンではありません。デプロイを中止します。" >&2
    git status --short >&2
    exit 1
  fi

  echo "==> git pull"
  git pull --ff-only

  echo "==> composer install"
  composer install --no-dev --optimize-autoloader --no-interaction

  echo "==> npm ci"
  npm ci

  echo "==> npm run build"
  npm run build

  echo "==> config:cache"
  php artisan config:cache

  echo "==> route:cache"
  php artisan route:cache

  echo "==> view:cache"
  php artisan view:cache

  echo "==> キャッシュの確認"
  cache_status="$(php artisan about --only=cache --no-ansi)"
  echo "$cache_status"
  for item in Config Routes Views; do
    if ! printf '%s\n' "$cache_status" \
         | grep -qE "^[[:space:]]*${item}[[:space:]]+\.+[[:space:]]+CACHED[[:space:]]*$"; then
      echo "${item} がキャッシュされていません。" >&2
      exit 1
    fi
  done

  echo "=== deploy finished ==="
  exit 0
}
