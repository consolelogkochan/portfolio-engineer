import { z } from 'zod';

// サーバー（ContactRequest）が信頼境界の本体。
// このスキーマはクライアント側の親切な二層目として機能する。
// 外部入力（ユーザー入力）は両側で守るのが原則（4-3の内部コンテンツ検証とは文脈が異なる）。
export const ContactSchema = z.object({
  name: z
    .string()
    .min(1, 'お名前を入力してください。')
    .max(100, 'お名前は100文字以内で入力してください。'),
  email: z
    .string()
    .min(1, 'メールアドレスを入力してください。')
    .email('有効なメールアドレスを入力してください。')
    .max(255, 'メールアドレスは255文字以内で入力してください。'),
  subject: z
    .string()
    .min(1, '件名を入力してください。')
    .max(150, '件名は150文字以内で入力してください。'),
  message: z
    .string()
    .min(1, 'お問い合わせ内容を入力してください。')
    .max(2000, 'お問い合わせ内容は2000文字以内で入力してください。'),
});

export type ContactInput = z.infer<typeof ContactSchema>;
