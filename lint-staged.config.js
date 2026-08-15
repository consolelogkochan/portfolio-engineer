export default {
    // resources/js 配下の TypeScript / React ファイル
    'resources/js/**/*.{ts,tsx}': ['eslint --fix', 'prettier --write'],

    // PHPファイル（Laravel標準設定でPint整形）
    '**/*.php': ['./vendor/bin/pint'],

    // content/**/*.md が変更されたら全件を検証スクリプトで検証
    // 変更ファイルを引数に渡さず固定コマンドを返す関数形式
    'content/**/*.md': () => 'npx tsx scripts/validate-content.ts',
};
