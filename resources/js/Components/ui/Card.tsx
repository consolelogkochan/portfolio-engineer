import React from 'react';

// 中身を知らず囲むだけ＝あらゆる中身のカードに使える器
// 色は semanticトークンのみ参照（base層の --color-neutral-* 等は直接使わない）
//
// Button/Tag/Card は独立記述。3個揃ったが、共通部分はクラス結合の1行のみで
// 要素・透過・バリアントはそれぞれ異なるため、抽象化コストに見合わず保留。
type CardProps = { children: React.ReactNode; className?: string };

const base = 'bg-surface border border-border rounded-lg p-6';

export default function Card({ children, className }: CardProps) {
  return <div className={[base, className].filter(Boolean).join(' ')}>{children}</div>;
}
