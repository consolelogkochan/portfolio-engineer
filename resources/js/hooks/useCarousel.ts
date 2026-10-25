// embla-carousel-react の配線（Gallery・FeaturedShowcase で共通。7-8c で1か所にした）。
// loop:true のため末尾→先頭、先頭→末尾へ回り込む（常に両方向へ送れる＝前後ボタンの無効化は不要）。
import useEmblaCarousel from 'embla-carousel-react';
import { KeyboardEvent, useCallback, useSyncExternalStore } from 'react';

export function useCarousel() {
  const [emblaRef, emblaApi] = useEmblaCarousel({ loop: true });

  // embla の内部状態（選択中インデックス等）は embla 自身が持つ外部ストアなので、
  // useState+useEffectで複製せず useSyncExternalStore で直接同期する。
  // subscribe は useCallback で同じ関数を保つ。描くたびに新しい関数を渡すと、
  // React が購読を外して付け直す（React の文書の useSyncExternalStore の Caveats）。
  const subscribe = useCallback(
    (callback: () => void) => {
      if (!emblaApi) return () => {};
      emblaApi.on('select', callback).on('reInit', callback);
      return () => {
        emblaApi.off('select', callback).off('reInit', callback);
      };
    },
    [emblaApi]
  );

  const selectedIndex = useSyncExternalStore(subscribe, () => emblaApi?.selectedScrollSnap() ?? 0);

  // キーボード操作（← →）：emblaコアにはキーボード制御が無いため手動で配線。スワイプ/ドラッグはembla標準機能。
  const handleKeyDown = useCallback(
    (e: KeyboardEvent) => {
      if (e.key === 'ArrowLeft') emblaApi?.scrollPrev();
      if (e.key === 'ArrowRight') emblaApi?.scrollNext();
    },
    [emblaApi]
  );

  return { emblaRef, emblaApi, selectedIndex, handleKeyDown };
}
