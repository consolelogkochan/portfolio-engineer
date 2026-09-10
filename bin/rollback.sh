#!/usr/bin/env bash
#
# 本番サーバーで、ビルド成果物を前の世代へ戻す。
# 人がサーバーに入って実行する。CI からは実行されない。
#
# 使い方
#   ./bin/rollback.sh            保持している世代を一覧表示する
#   ./bin/rollback.sh <世代>     その世代へ切り替え、キャッシュを作り直す
#
# 設計の要点（詳細は30節）
#   ・ビルドをしない。サーバーに Node.js は無い
#   ・このスクリプトが戻すのは public/build（ビルド成果物）だけである。
#     PHP のコードは git の管理下にあるので、コードごと戻すには別途
#     git checkout が必要になる。各世代がどのコミットのものかは一覧に表示される
{
  set -euo pipefail
  umask 027

  cd "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.."
  root="$PWD"

  current="$(readlink "${root}/public/build" 2>/dev/null || true)"

  if [ $# -eq 0 ]; then
    echo "保持している世代（新しい順）:"
    ls -1d "${root}/releases"/*/ 2>/dev/null | sort -r | while read -r dir; do
      name="$(basename "$dir")"
      commit="$(cat "${dir}COMMIT" 2>/dev/null || echo "(コミット不明)")"
      if [ "$current" = "../releases/${name}/build" ]; then
        echo "  ${name}  ${commit}  <- 現在"
      else
        echo "  ${name}  ${commit}"
      fi
    done
    echo
    echo "戻すには: ./bin/rollback.sh <世代>"
    exit 0
  fi

  target="$1"
  if [ ! -d "${root}/releases/${target}/build" ]; then
    echo "releases/${target}/build がありません。" >&2
    echo "引数なしで実行すると、保持している世代を一覧表示します。" >&2
    exit 1
  fi

  echo "==> public/build を ${target} に切り替え"
  ln -sfn "../releases/${target}/build" "${root}/public/build.new"
  mv -T "${root}/public/build.new" "${root}/public/build"
  echo "public/build -> $(readlink "${root}/public/build")"

  echo "==> キャッシュの作り直し"
  php artisan config:cache
  php artisan route:cache
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

  echo "=== rollback finished (${target}) ==="
  exit 0
}
