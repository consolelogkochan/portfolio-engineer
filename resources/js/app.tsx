import '../css/app.css';
import BaseLayout from './Layouts/BaseLayout';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { type ReactNode } from 'react';

createInertiaApp({
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
