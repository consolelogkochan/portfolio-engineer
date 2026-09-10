#!/usr/bin/env bash
#
# 本番サーバーのデプロイ手順。docs/server-setup.md の30節に対応する。
#
# このスクリプトは CI（GitHub Actions）からのみ実行される。
# authorized_keys の command= により、CI 専用の鍵で ssh されると必ずこれが起動する。
# 標準入力に、CI がビルドした成果物の tar.gz が流れてくる。
#
# 設計の要点（詳細は30節）
#   ・sudo を使わない。権限は setgid（22節）と umask 027 により、
#     作られる時点で満たす
#   ・ビルドをしない。サーバーに Node.js は無い
#   ・失敗したら止まる。自動では巻き戻さない
#   ・「このスクリプトの成功」と「サイトの動作」は別。応答確認は含めない
#   ・人が手で巻き戻すときは bin/rollback.sh を使う
#
# 全体を { } で囲んでいるのは、git merge がこのファイル自身を書き換えても、
# bash が読み進める途中で内容が変わらないようにするため。
# bash は { } の中を先に読み切ってから実行する。
{
  set -euo pipefail
  umask 027

  cd "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.."
  root="$PWD"

  keep=5
  stamp="$(date -u +%Y%m%d-%H%M%S)"
  incoming="${root}/releases/.incoming-$$"

  cleanup() {
    rm -rf "$incoming"
  }
  trap cleanup EXIT

  echo "==> 成果物の受け取り"
  # 標準入力は最初に読み切る。読まないと送り手が待たされるため。
  mkdir -p "${root}/releases"
  rm -rf "${root}/releases"/.incoming-*
  mkdir -p "$incoming"
  tar xzf - -C "$incoming"

  if [ ! -f "${incoming}/build/manifest.json" ]; then
    echo "受け取った成果物に build/manifest.json がありません。" >&2
    exit 1
  fi
  if [ ! -f "${incoming}/COMMIT" ]; then
    echo "受け取った成果物に COMMIT がありません。" >&2
    exit 1
  fi
  expected="$(cat "${incoming}/COMMIT")"
  echo "$(find "${incoming}/build" -type f | wc -l) ファイル / コミット ${expected}"

  echo "==> 配置の確認"
  if [ -e "${root}/public/build" ] && [ ! -L "${root}/public/build" ]; then
    echo "public/build が実体のディレクトリです。30節の移行手順を実行してください。" >&2
    exit 1
  fi

  echo "==> 作業ツリーの確認"
  if [ -n "$(git status --porcelain)" ]; then
    echo "作業ツリーがクリーンではありません。デプロイを中止します。" >&2
    git status --short >&2
    exit 1
  fi
  if [ "$(git rev-parse --abbrev-ref HEAD)" != "main" ]; then
    echo "main ブランチではありません。デプロイを中止します。" >&2
    exit 1
  fi

  echo "==> git fetch"
  git fetch --prune origin

  echo "==> コミットの照合"
  target="$(git rev-parse origin/main)"
  if [ "$expected" != "$target" ]; then
    echo "成果物のコミットと、取り込もうとしているコミットが一致しません。" >&2
    echo "  成果物:      ${expected}" >&2
    echo "  origin/main: ${target}" >&2
    echo "何も変更せずに中止します。" >&2
    exit 1
  fi

  echo "==> git merge"
  git merge --ff-only "$target"

  echo "==> composer install"
  composer install --no-dev --optimize-autoloader --no-interaction

  echo "==> 成果物の配置"
  release="${root}/releases/${stamp}"
  mv "$incoming" "$release"
  ln -sfn "../releases/${stamp}/build" "${root}/public/build.new"
  mv -T "${root}/public/build.new" "${root}/public/build"
  echo "public/build -> $(readlink "${root}/public/build")"

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

  # 掃除の失敗はデプロイの失敗ではない。終了コードには畳まず、報告だけする。
  echo "==> 古い世代の掃除"
  if ! ls -1d "${root}/releases"/*/ 2>/dev/null | sort -r | tail -n +$((keep + 1)) \
       | while read -r old; do
           echo "削除: ${old}"
           rm -rf "$old"
         done
  then
    echo "古い世代の掃除に失敗しました。デプロイ自体は完了しています。" >&2
  fi

  echo "=== deploy finished (${stamp}) ==="
  exit 0
}
