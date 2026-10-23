import { join } from 'path';
import { existsSync, readFileSync, readdirSync } from 'fs';
import matter from 'gray-matter';
import { z } from 'zod';
import { WorkSchema } from '@/types/work';
import { LogSchema } from '@/types/log';

const ROOT = new URL('..', import.meta.url).pathname;

function collectMdFiles(dir: string): string[] {
  if (!existsSync(dir)) return [];
  return readdirSync(dir)
    .filter((f) => f.endsWith('.md'))
    .map((f) => join(dir, f));
}

type Failure = { file: string; message: string };
const failures: Failure[] = [];

function validate(filePath: string, schema: z.ZodTypeAny) {
  const raw = readFileSync(filePath, 'utf-8');
  const { data } = matter(raw);
  const result = schema.safeParse(data);
  if (!result.success) {
    failures.push({
      file: filePath.replace(ROOT, ''),
      message: z.prettifyError(result.error),
    });
  }
}

const workFiles = collectMdFiles(join(ROOT, 'content/works'));
const logFiles = collectMdFiles(join(ROOT, 'content/logs'));

for (const file of workFiles) validate(file, WorkSchema);
for (const file of logFiles) validate(file, LogSchema);

// 画像の参照が public/ の下に実在するかを確かめる（7-7b）。
// 差し替えのたびに新しい名前で置いて参照を書き換える（40節）ため、書き間違いの機会がある。
// thumbnail と gallery は webp を指すので、images:optimize の実行忘れ（webp が無い）にも気づける。
// og:image の実ファイルは PageMetaTest も検査するが、作品の ogImage を持つページが無い間は通らないので、ここでも見る。
function checkImage(file: string, field: string, path: unknown) {
  if (typeof path !== 'string' || path === '') return; // 型の誤りはスキーマの検証が報告する
  if (!existsSync(join(ROOT, 'public', path))) {
    failures.push({
      file: file.replace(ROOT, ''),
      message: `${field} の画像が public/ に存在しない: ${path}`,
    });
  }
}

for (const file of workFiles) {
  const { data } = matter(readFileSync(file, 'utf-8'));
  checkImage(file, 'thumbnail', data.thumbnail);
  checkImage(file, 'ogImage', data.ogImage);
  if (Array.isArray(data.gallery)) {
    data.gallery.forEach((g: { src?: unknown }, i: number) =>
      checkImage(file, `gallery[${i}].src`, g?.src)
    );
  }
}

const aboutFile = join(ROOT, 'content/about.md');
if (existsSync(aboutFile)) {
  const { data } = matter(readFileSync(aboutFile, 'utf-8'));
  checkImage(aboutFile, 'avatar', data.avatar);
}

if (failures.length > 0) {
  console.error(`\n✗ ${failures.length}件の検証エラーがあります:\n`);
  for (const { file, message } of failures) {
    console.error(`[${file}]\n${message}\n`);
  }
  process.exit(1);
}

console.log('✓ すべてのコンテンツが検証を通過しました（画像の参照の実在を含む）。');
