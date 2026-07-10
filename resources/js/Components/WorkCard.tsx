// Tag を使うドメイン部品（汎用UIではなく作品一覧専用）
// オーバーレイパターン：画像がカード全面、テキストは画像上のグラデーション上に重ねる。
// カード全体クリックは擬似要素パターン：リンクはタイトル1つに保ち、
// after:content-[''] after:absolute after:inset-0 でクリック領域をカード全体に広げる
//
// Card(5-5) を使わない理由：
//   1. Card は <div> 固定だが WorkCard は <article>（セマンティクス）が必要
//   2. Card の p-6 は全面画像レイアウトと相性が悪く、p-0 上書きは Tailwind v4 では優先度非保証
//   3. Card に overflow-hidden がなく、絶対配置画像を rounded-lg でクリップできない
// border・border-border・rounded-lg は Card と同じトークンを直接参照し、デザイン言語は統一している。
import Tag from '@/Components/ui/Tag';
import { WorkSummary } from '@/types/work';
import { Link } from '@inertiajs/react';

type Props = { work: WorkSummary };

export default function WorkCard({ work }: Props) {
  return (
    <article className="relative overflow-hidden rounded-lg border border-border hover:border-primary transition-colors h-96">
      {work.featured && (
        <span className="absolute top-3 right-3 bg-linear-to-br from-primary-hover via-primary-light to-primary text-background text-xs font-mono px-2 py-0.5 rounded-sm z-10 shadow-[inset_0_1px_1px_rgba(255,255,255,0.25)]">
          ★ 注目
        </span>
      )}

      {work.thumbnail ? (
        <img
          src={work.thumbnail}
          alt={work.title}
          className="absolute inset-0 w-full h-full object-contain bg-surface"
        />
      ) : (
        <div className="absolute inset-0 bg-surface" />
      )}

      {/* グラデーションオーバーレイ：画像下部を暗くしてテキストを読みやすくする */}
      <div className="absolute inset-x-0 bottom-0 top-1/3 bg-linear-to-t from-background/90 via-background/60 to-transparent flex flex-col justify-end p-4">
        <div className="flex items-start justify-between gap-2 mb-1">
          <h2 className="text-base font-bold leading-snug">
            <Link
              href={`/works/${work.slug}`}
              className="text-text after:content-[''] after:absolute after:inset-0"
            >
              {work.title}
            </Link>
          </h2>
        </div>
        <p className="text-text-muted text-xs font-mono mb-2">
          {work.category} · {work.publishedAt}
        </p>
        <p className="text-sm text-text-muted line-clamp-2 mb-3">{work.summary}</p>
        <div className="flex flex-wrap gap-2">
          {work.technologies.map((t) => (
            <Tag key={t}>{t}</Tag>
          ))}
        </div>
      </div>
    </article>
  );
}
