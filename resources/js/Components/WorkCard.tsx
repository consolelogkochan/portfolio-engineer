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
    // h-96は固定高。Tagsは1行に固定して折り返さない（7-7b）ため、タグが増えても文字の高さは変わらない
    // （5-17で見送っていた「折り返して見切れる」懸念はこれで解消）。
    <article className="relative overflow-hidden rounded-lg border border-border hover:border-primary transition-colors h-96">
      {work.featured && (
        <span className="absolute top-3 right-3 bg-linear-to-br from-primary-hover via-primary-light to-primary text-background text-xs font-mono px-2 py-0.5 rounded-sm z-10 shadow-[inset_0_1px_1px_rgba(255,255,255,0.25)]">
          ★ 注目
        </span>
      )}

      {/* サムネイルはカードの幅の正方形として上端に置く（7-7b）。縦長のカードに正方形の画像を
          object-cover で全面に敷くと、画像の縦が全部写り、中央の要素が下の文字と重なるため。
          上に寄せることで、画像の中央付近の要素がカードの上半分に来る（ショーケースは中央で切り抜く）。
          正方形でない画像も正方形に切り抜かれる。下に余った部分は背景色になり、文字が乗る。
          PC（3列、カード幅約272px）だけは枠を4:5にして、画像の左右を少し切る代わりに1.25倍に写す
          （正方形のままだと画像全体が272pxに縮んで小さいため。1200×1200のうち x≈120〜1080 が写る）。
          タブレット（2列）はカードが横長に近く、縦長の枠にすると要素が下の文字に重なるので正方形のまま。 */}
      {work.thumbnail ? (
        <img
          src={work.thumbnail}
          alt={work.title}
          loading="lazy"
          className="absolute inset-x-0 top-0 w-full aspect-square lg:aspect-[4/5] object-cover"
        />
      ) : (
        <div className="absolute inset-0 bg-surface" />
      )}

      {/* グラデーションオーバーレイ：画像下部を暗くしてテキストを読みやすくする（装飾のみ、Linkは含まない） */}
      {/* 始まりは上から1/2（7-7b。タグを1行にして文字が62.8%から始まるようになったため、1/3から下げた） */}
      <div className="absolute inset-x-0 bottom-0 top-1/2 bg-linear-to-t from-background/95 via-background/80 to-transparent" />

      {/* テキスト＆当たり判定コンテナ：inset-0でカード全体を占め、flexで内容を下寄せする（見た目はグラデーションと同じ位置のまま）。
          Linkのafter:inset-0はこのdiv（＝カード全体と同じ範囲）を基準にするため、当たり判定は常にカード全体になる
          （グラデーションの見た目top-1/3とは独立しており、暗黙の連動はない）。 */}
      <div className="absolute inset-0 flex flex-col justify-end p-4">
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
        {/* 技術のタグは1行だけ表示し、はみ出た分は隠して右端をぼかす（7-7b）。
            折り返すとタグの数だけ文字の部分が上へ伸び、サムネイルの要素と重なるため。
            全件は作品詳細に出る。隠れたタグもDOMには残るので、スクリーンリーダーは全件を読む。 */}
        <div className="flex flex-nowrap gap-2 overflow-hidden [mask-image:linear-gradient(to_right,black_calc(100%_-_2rem),transparent)]">
          {work.technologies.map((t) => (
            <Tag key={t} className="shrink-0 whitespace-nowrap">
              {t}
            </Tag>
          ))}
        </div>
      </div>
    </article>
  );
}
