export default {
    // resources/js 配下の TypeScript / React ファイル
    'resources/js/**/*.{ts,tsx}': ['eslint --fix', 'prettier --write'],

    // PHPファイル（Laravel標準設定でPint整形）
    '**/*.php': ['./vendor/bin/pint'],

    // content/**/*.md が変更されたら全件を検証スクリプトで検証
    // 変更ファイルを引数に渡さず固定コマンドを返す関数形式
    'content/**/*.md': () => 'npx tsx scripts/validate-content.ts',

    // CSS・TS・TSX が変わったら、ビルドしてから CSS の未定義の変数を検査する（30節）。
    // 定義があるかはビルドの成果物で判定するため。ビルドは作業ツリーの内容で行い、public/build を作り直す
    'resources/{css/**/*.css,js/**/*.{ts,tsx}}': () => ['npm run build', 'npm run check:css-vars'],
};
