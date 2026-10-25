import { z } from 'zod';
import { isoDate } from './common';

// .default(...) の値（tags・draft）は、画面に渡す値として ContentRepository の LOG_DEFAULTS にもある。
// 変えるときは2か所とも変える（7-8b-5）。
export const LogSchema = z.object({
  title: z.string(),
  publishedAt: isoDate,
  updatedAt: isoDate.optional(),
  summary: z.string(),
  tags: z.array(z.string()).default([]),
  draft: z.boolean().default(false),
});

export type Log = z.infer<typeof LogSchema>;
