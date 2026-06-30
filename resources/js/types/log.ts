import { z } from 'zod';

export const LogSchema = z.object({
  title: z.string(),
  publishedAt: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'YYYY-MM-DD形式で記述'),
  updatedAt: z
    .string()
    .regex(/^\d{4}-\d{2}-\d{2}$/, 'YYYY-MM-DD形式で記述')
    .optional(),
  summary: z.string(),
  tags: z.array(z.string()).default([]),
  relatedWork: z.string().optional(),
  thumbnail: z.string().optional(),
  draft: z.boolean().default(false),
});

export type Log = z.infer<typeof LogSchema>;

export type LogSummary = Pick<Log, 'title' | 'publishedAt' | 'summary' | 'tags'> & {
  slug: string;
};
