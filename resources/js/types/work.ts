import { z } from 'zod';

const MetricSchema = z.object({
  label: z.string(),
  value: z.string(),
  unit: z.string().optional(),
});

const GalleryItemSchema = z.object({
  // 詳細(Gallery)ではh-96(384px)固定・object-containで全体表示（切れない・余白は許容）。
  // 加工前は横長1600x900(16:9)程度で用意する（images:optimizeでmax_width=1200に縮小・webp化される）。
  // containのため比率を厳密に合わせる必要はない。
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
    // 省略時はWorks/Showで「継続中」として表示される運用（進行中の案件はendを書かない）。
    end: z.string().optional(),
  }),
  role: z.array(z.string()),
  technologies: z.array(z.string()).min(1),
  aiTools: z.array(z.string()).default([]),
  liveUrl: z.url().optional(),
  repoUrl: z.url().optional(),
  // 一覧(WorkCard)ではh-96(384px)固定・object-coverで中央クリップ表示される。
  // カード幅はグリッド列数で変わる（PC3列で幅270px程度〜モバイル1列で画面幅）ため、
  // 正方形に近い比率（例: 1200x1200程度）・被写体は中央寄せで用意すると崩れにくい。
  thumbnail: z.string(),
  hero: z.string().optional(),
  gallery: z.array(GalleryItemSchema).default([]),
  metrics: z.array(MetricSchema).optional(),
});

export type Work = z.infer<typeof WorkSchema>;

export type WorkSummary = Pick<
  Work,
  | 'title'
  | 'category'
  | 'status'
  | 'summary'
  | 'publishedAt'
  | 'thumbnail'
  | 'technologies'
  | 'featured'
> & { slug: string };
