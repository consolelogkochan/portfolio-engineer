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

if (failures.length > 0) {
  console.error(`\n✗ ${failures.length}件の検証エラーがあります:\n`);
  for (const { file, message } of failures) {
    console.error(`[${file}]\n${message}\n`);
  }
  process.exit(1);
}

console.log('✓ すべてのコンテンツが検証を通過しました。');
