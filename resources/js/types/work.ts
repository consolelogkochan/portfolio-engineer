import { z } from 'zod';

const MetricSchema = z.object({
  label: z.string(),
  value: z.string(),
  unit: z.string().optional(),
});

const GalleryItemSchema = z.object({
  src: z.string(),
  alt: z.string(), // 必須：アクセシビリティを型で強制
  caption: z.string().optional(),
});

export const WorkSchema = z.object({
  title: z.string(),
  category: z.enum(['個人開発', '受託', '新規事業']),
  status: z.enum(['公開中', '開発中']),
  featured: z.boolean().default(false),
  summary: z.string(),
  publishedAt: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'YYYY-MM-DD形式で記述'),
  period: z.object({
    start: z.string(),
    end: z.string().optional(),
  }),
  role: z.array(z.string()),
  technologies: z.array(z.string()).min(1),
  aiTools: z.array(z.string()).default([]),
  liveUrl: z.url().optional(),
  repoUrl: z.url().optional(),
  thumbnail: z.string(),
  hero: z.string().optional(),
  gallery: z.array(GalleryItemSchema).default([]),
  metrics: z.array(MetricSchema).optional(),
});

export type Work = z.infer<typeof WorkSchema>;
