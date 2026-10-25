import { z } from 'zod';

// 日付の項目（publishedAt・updatedAt）の形。作品（work.ts）と開発ログ（log.ts）で共通（7-8c）
export const isoDate = z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'YYYY-MM-DD形式で記述');
