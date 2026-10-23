import { z } from 'zod';

// .default(...) の値（tags・draft）は、画面に渡す値として ContentRepository の LOG_DEFAULTS にもある。
// 変えるときは2か所とも変える（7-8b-5）。
export const LogSchema = z.object({
  title: z.string(),
  publishedAt: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'YYYY-MM-DD形式で記述'),
  updatedAt: z
    .string()
    .regex(/^\d{4}-\d{2}-\d{2}$/, 'YYYY-MM-DD形式で記述')
    .optional(),
  summary: z.string(),
  tags: z.array(z.string()).default([]),
  draft: z.boolean().default(false),
});

export type Log = z.infer<typeof LogSchema>;
