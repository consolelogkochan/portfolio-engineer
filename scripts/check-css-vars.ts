import { join, relative } from 'path';
import { existsSync, readFileSync, readdirSync } from 'fs';

// CSS の未定義の変数（カスタムプロパティ）を見つける（7-8b-5）。
// var(--名前) の名前を間違えても、ビルド・lint・型の検査・テストのどれも止めず、ブラウザでは指定が無視される。
// 定義があるかは、app.css ではなくビルドの成果物（ブラウザに届く CSS）で判定する。
// Tailwind はクラスで使われている変数だけを成果物に出すため、@theme に定義があっても届かないことがある。
// 代わりの値を書いた var(--名前, …) は、定義が無くても代わりの値が使われるので対象にしない。
// Tailwind のクラス名の書き間違いは見つけない。

const ROOT = new URL('..', import.meta.url).pathname;
const BUILD_ASSETS = join(ROOT, 'public/build/assets');
const SOURCE_DIR = join(ROOT, 'resources/js');

// 宣言（{ や ; の直後の --名前:）と @property --名前
const DEFINITION = /(?:[{;]\s*|@property\s+)(--[A-Za-z0-9_-]+)\s*[:{]/g;
// 代わりの値の無い var(--名前)
const USE_WITHOUT_FALLBACK = /var\(\s*(--[A-Za-z0-9_-]+)\s*\)/g;

function collectFiles(dir: string, extensions: string[]): string[] {
  if (!existsSync(dir)) return [];
  return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const path = join(dir, entry.name);
    if (entry.isDirectory()) return collectFiles(path, extensions);
    return extensions.some((ext) => entry.name.endsWith(ext)) ? [path] : [];
  });
}

type Use = { name: string; file: string; line: number; column: number };

function collectUses(file: string, text: string): Use[] {
  const lines = text.split('\n');
  return lines.flatMap((lineText, i) =>
    [...lineText.matchAll(USE_WITHOUT_FALLBACK)].map((m) => ({
      name: m[1],
      file: relative(ROOT, file),
      line: i + 1,
      column: (m.index ?? 0) + 1,
    }))
  );
}

const cssFiles = collectFiles(BUILD_ASSETS, ['.css']);
if (cssFiles.length === 0) {
  console.error(
    `\n✗ ビルドの成果物の CSS が無い（${relative(ROOT, BUILD_ASSETS)}）。先に npm run build を行う。\n`
  );
  process.exit(1);
}

const defined = new Set<string>();
const uses: Use[] = [];

for (const file of cssFiles) {
  const text = readFileSync(file, 'utf-8');
  for (const m of text.matchAll(DEFINITION)) defined.add(m[1]);
  uses.push(...collectUses(file, text));
}

for (const file of collectFiles(SOURCE_DIR, ['.ts', '.tsx'])) {
  uses.push(...collectUses(file, readFileSync(file, 'utf-8')));
}

const undefinedUses = uses.filter((u) => !defined.has(u.name));

if (undefinedUses.length > 0) {
  console.error(`\n✗ 定義の無い CSS の変数が ${undefinedUses.length}件 使われています:\n`);
  for (const { name, file, line, column } of undefinedUses) {
    console.error(`  ${name}  ${file}:${line}:${column}`);
  }
  console.error('');
  process.exit(1);
}

console.log(
  `✓ CSS の変数はすべて定義されています（定義 ${defined.size}件、代わりの値の無い参照 ${uses.length}件）。`
);
