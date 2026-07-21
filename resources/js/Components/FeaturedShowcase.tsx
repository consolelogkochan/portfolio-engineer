// トップページ専用：featured作品を大きく見せるショーケース。
// Works一覧のWorkCard（小・グリッド）とは目的が違う（トップ唯一のリッチな展示）ため独立実装。
// featuredの絞り込みはサーバー（ContentRepository::listFeaturedWorks）が担う。
// このコンポーネントは渡された配列をそのまま表示するだけで、featuredかどうかは判定しない。
//
// embla-carousel-react のセットアップ（useSyncExternalStoreでの状態同期等）はGallery(5-10)と
// 同じ考え方を流用している。中身（1枚の画像 vs 1件の作品カード）が別物なので今は別々に実装したが、
// emblaセットアップ部分の重複は共通化の芽としてここに記録しておく（5-14時点では着手しない）。
//
// 0件：null（Home側でセクションごと非表示）。1件：カルーセルUI無しで単体を大きく表示。
// 複数件：前後ボタン・ドット・カウンター付きのemblaカルーセル（Galleryと同様の操作UI）。
import { WorkSummary } from '@/types/work';
import { Link } from '@inertiajs/react';
import useEmblaCarousel, { type UseEmblaCarouselType } from 'embla-carousel-react';
import { KeyboardEvent, useCallback, useSyncExternalStore } from 'react';

type Props = { works: WorkSummary[] };

function subscribe(emblaApi: UseEmblaCarouselType[1]) {
  return (callback: () => void) => {
    if (!emblaApi) return () => {};
    emblaApi.on('select', callback).on('reInit', callback);
    return () => {
      emblaApi.off('select', callback).off('reInit', callback);
    };
  };
}

export default function FeaturedShowcase({ works }: Props) {
  // loop:true のため末尾→先頭、先頭→末尾へ回り込む（前後ボタンの無効化は不要）
  const [emblaRef, emblaApi] = useEmblaCarousel({ loop: true });

  const selectedIndex = useSyncExternalStore(
    subscribe(emblaApi),
    () => emblaApi?.selectedScrollSnap() ?? 0
  );

  const handleKeyDown = useCallback(
    (e: KeyboardEvent) => {
      if (e.key === 'ArrowLeft') emblaApi?.scrollPrev();
      if (e.key === 'ArrowRight') emblaApi?.scrollNext();
    },
    [emblaApi]
  );

  if (works.length === 0) return null;

  const multiple = works.length > 1;

  return (
    <div className="relative">
      <div
        className="overflow-hidden rounded-lg"
        ref={emblaRef}
        tabIndex={multiple ? 0 : undefined}
        onKeyDown={multiple ? handleKeyDown : undefined}
      >
        <div className="flex">
          {works.map((work) => (
            <div key={work.slug} className="relative flex-[0_0_100%] min-w-0">
              <FeaturedCard work={work} />
            </div>
          ))}
        </div>
      </div>

      {multiple && (
        <>
          {/* カウンター：画像上に直接乗るため、Galleryのcontain余白配置とは違い背景を敷いて読みやすくする */}
          <span className="absolute top-3 right-3 md:top-4 md:right-4 bg-background/70 text-text-muted text-xs font-mono px-2 py-0.5 rounded-sm">
            {selectedIndex + 1} / {works.length}
          </span>

          <button
            type="button"
            onClick={() => emblaApi?.scrollPrev()}
            aria-label="前の作品へ"
            className="absolute left-2 md:left-4 top-1/2 -translate-y-1/2 w-9 h-9 md:w-10 md:h-10 rounded-full bg-primary text-background hover:bg-primary-hover transition-colors flex items-center justify-center"
          >
            <span className="text-xl md:text-2xl leading-none">‹</span>
          </button>
          <button
            type="button"
            onClick={() => emblaApi?.scrollNext()}
            aria-label="次の作品へ"
            className="absolute right-2 md:right-4 top-1/2 -translate-y-1/2 w-9 h-9 md:w-10 md:h-10 rounded-full bg-primary text-background hover:bg-primary-hover transition-colors flex items-center justify-center"
          >
            <span className="text-xl md:text-2xl leading-none">›</span>
          </button>
        </>
      )}

      {multiple && (
        <div className="flex justify-center gap-2 mt-4">
          {works.map((work, i) => (
            <button
              key={work.slug}
              type="button"
              aria-label={`${i + 1}件目（${work.title}）へ`}
              onClick={() => emblaApi?.scrollTo(i)}
              className={`w-2 h-2 rounded-full ${i === selectedIndex ? 'bg-primary' : 'bg-border'}`}
            />
          ))}
        </div>
      )}
    </div>
  );
}

function FeaturedCard({ work }: { work: WorkSummary }) {
  return (
    // トップ唯一のリッチな展示：primary-lightの淡いグロー＋枠でWorkCard（一覧）より一段格上に見せる
    <article className="relative overflow-hidden rounded-lg border border-primary-light/40 bg-surface shadow-[0_0_48px_-16px_var(--color-primary-light)] h-104 md:h-128">
      {work.thumbnail ? (
        <img
          src={work.thumbnail}
          alt={work.title}
          className="absolute inset-0 w-full h-full object-cover"
        />
      ) : (
        <div className="absolute inset-0 bg-surface" />
      )}

      {/* グラデーションオーバーレイ：画像下部を暗くしてテキストを読みやすくする（WorkCardと同じ手法） */}
      <div className="absolute inset-x-0 bottom-0 top-1/3 bg-linear-to-t from-background/95 via-background/85 to-transparent flex flex-col justify-end p-6 md:p-10">
        <p className="text-primary-light text-xs md:text-sm font-mono mb-2 tracking-wide">
          ★ 注目の作品
        </p>
        <h3 className="text-xl md:text-3xl font-bold leading-snug mb-3">
          {/* カード全体クリックは擬似要素パターン（WorkCardと同じ）：リンクはタイトル1つに保ち、
              after:content-[''] after:absolute after:inset-0 相当でクリック領域をカード全体に広げる */}
          <Link
            href={`/works/${work.slug}`}
            className="text-text after:content-[''] after:absolute after:inset-x-0 after:bottom-0 after:-top-1/2"
          >
            {work.title}
          </Link>
        </h3>
        <p className="text-text-muted text-sm md:text-base line-clamp-2 md:line-clamp-3 max-w-2xl">
          {work.summary}
        </p>
      </div>
    </article>
  );
}
