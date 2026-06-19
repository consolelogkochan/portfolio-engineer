export default {
    // resources/js 配下の TypeScript / React ファイル
    'resources/js/**/*.{ts,tsx}': ['eslint --fix', 'prettier --write'],

    // TODO: content 配下の Markdown ファイルの検証（例: markdownlint）
    // 'content/**/*.md': [
    //   'markdownlint --fix',
    // ],
};
