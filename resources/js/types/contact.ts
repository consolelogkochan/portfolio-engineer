import { z } from 'zod';

// サーバー（ContactRequest）が信頼境界の本体。
// このスキーマはクライアント側の親切な二層目として機能する。
// 外部入力（ユーザー入力）は両側で守るのが原則（4-3の内部コンテンツ検証とは文脈が異なる）。
export const ContactSchema = z.object({
  name: z
    .string()
    .min(1, 'お名前を入力してください。')
    .max(100, 'お名前は100文字以内で入力してください。'),
  // Zod 4 の書き方（z.email()）。空の欄で最初に出る文言を「入力してください」に保つため、
  // 空かどうかを先に確かめ、空でないときだけ形式と長さを見る（pipe は前が通ったときだけ後ろを見る）。
  // 形式と長さは z.string().min(1).email().max(255) と同じく両方を見て、形式の文言を先に出す（7-8c）
  email: z
    .string()
    .min(1, 'メールアドレスを入力してください。')
    .pipe(
      z
        .email('有効なメールアドレスを入力してください。')
        .max(255, 'メールアドレスは255文字以内で入力してください。')
    ),
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
