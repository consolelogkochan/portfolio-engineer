import React from 'react';

type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: 'primary' | 'secondary';
  size?: 'sm' | 'md';
};

const base =
  'inline-flex items-center justify-center rounded-md font-mono transition-colors focus-visible:outline-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed';

const variantClasses = {
  primary: 'bg-primary text-background hover:bg-primary-hover',
  secondary: 'border border-border text-text hover:border-primary hover:text-primary',
};

const sizeClasses = {
  sm: 'px-3 py-1 text-sm',
  md: 'px-4 py-2',
};

// <button>と同じ見た目を<a>（Inertia Link等）にも適用したい箇所向けに公開。
// <button>を<a>の中にネストするのはHTML上不正（インタラクティブ要素の入れ子）なため、
// リンクをボタン風に見せたい場合はこの関数でクラスだけ取り出して使う。
export function buttonClasses({
  variant = 'primary',
  size = 'md',
  className,
}: Pick<ButtonProps, 'variant' | 'size' | 'className'> = {}) {
  return [base, variantClasses[variant], sizeClasses[size], className].filter(Boolean).join(' ');
}

export default function Button({
  variant = 'primary',
  size = 'md',
  className,
  children,
  ...rest
}: ButtonProps) {
  return (
    <button className={buttonClasses({ variant, size, className })} {...rest}>
      {children}
    </button>
  );
}
