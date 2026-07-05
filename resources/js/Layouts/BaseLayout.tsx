import { Link } from '@inertiajs/react';
import React from 'react';

type Props = {
  children: React.ReactNode;
};

export default function BaseLayout({ children }: Props) {
  return (
    <>
      <header>
        <Link href="/">portfolio-engineer</Link>
        <nav>
          <Link href="/works">Works</Link>
          <Link href="/logs">Logs</Link>
          <Link href="/about">About</Link>
          <Link href="/contact">Contact</Link>
        </nav>
      </header>

      {/* 色・フォント等の装飾は 5-2 のデザイントークン導入時に行う */}
      <main style={{ maxWidth: '960px', margin: '0 auto', padding: '0 1rem' }}>{children}</main>

      <footer>
        <p>© {new Date().getFullYear()} portfolio-engineer</p>
      </footer>
    </>
  );
}
