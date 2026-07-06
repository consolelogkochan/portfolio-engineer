import { Link } from '@inertiajs/react';
import React from 'react';

type Props = {
  children: React.ReactNode;
};

export default function BaseLayout({ children }: Props) {
  return (
    // body は app.css @layer base で bg-background / text-text / font-sans 適用済み
    <div className="flex min-h-screen flex-col">
      <header className="flex items-center justify-between border-b border-border px-6 py-3">
        {/* サイト名: モノスペース＋グリーンアクセントでエンジニア感を出す */}
        <Link href="/" className="font-mono font-bold text-primary">
          portfolio-engineer
        </Link>
        <nav className="flex gap-6 text-sm">
          <Link href="/works" className="text-text-muted hover:text-primary">
            Works
          </Link>
          <Link href="/logs" className="text-text-muted hover:text-primary">
            Logs
          </Link>
          <Link href="/about" className="text-text-muted hover:text-primary">
            About
          </Link>
          <Link href="/contact" className="text-text-muted hover:text-primary">
            Contact
          </Link>
        </nav>
      </header>

      <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-8">{children}</main>

      <footer className="border-t border-border px-6 py-4 text-center text-sm text-text-muted">
        <p>© {new Date().getFullYear()} portfolio-engineer</p>
      </footer>
    </div>
  );
}
