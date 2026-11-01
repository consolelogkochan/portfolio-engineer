import '../css/app.css';
import BaseLayout from './Layouts/BaseLayout';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { type ReactNode } from 'react';

createInertiaApp({
  // titleオプションは指定しない：サイト名サフィックス付きの完成形タイトルは
  // サーバー側（PHP: app/Services/PageMetaBuilder.php）で組み立て済みのものを
  // pageTitle propとして受け取り、各ページがPageMeta（<Head title>）にそのまま渡す（7-2）。
  // 省略時、Inertia coreはtitleをそのまま使う（titleCallback未指定時のデフォルト＝恒等関数）。
  // ページはすべて最初にまとめて読む（eager）。7-9d でページごとに分けて読む形を試し、取り消した。
  // 人の理由：ページを移るときの待ちが増え、実際の端末で気になった。最初の表示の差
  // （手元の模擬で約0.3秒）より、移るときの使い心地を優先する。経緯は docs/development.md の9節。
  resolve: (name) => {
    const pages = import.meta.glob<{ default: ResolvedComponent }>('./Pages/**/*.tsx', {
      eager: true,
    });
    const page = pages[`./Pages/${name}.tsx`];

    // 個別ページが layout を指定していればそれを優先、なければ BaseLayout を適用する。
    // ページ単位でレイアウトを上書きしたい場合は PageComponent.layout に関数を設定すること。
    page.default.layout =
      page.default.layout ?? ((children: ReactNode) => <BaseLayout>{children}</BaseLayout>);

    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
