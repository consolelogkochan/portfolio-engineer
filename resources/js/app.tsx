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
  // ページはページごとに分けて読む（eager にしない）。問い合わせのページだけが使う zod などを、
  // ほかのページの最初の読み込みに含めないため（7-9d）。
  resolve: async (name) => {
    const pages = import.meta.glob<{ default: ResolvedComponent }>('./Pages/**/*.tsx');
    const page = await pages[`./Pages/${name}.tsx`]();

    // 個別ページが layout を指定していればそれを優先、なければ BaseLayout を適用する。
    // ページ単位でレイアウトを上書きしたい場合は PageComponent.layout に関数を設定すること。
    page.default.layout =
      page.default.layout ?? ((children: ReactNode) => <BaseLayout>{children}</BaseLayout>);

    return page.default;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
