<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ContentRepository;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private readonly ContentRepository $repository) {}

    public function index(): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        $urls = [
            $baseUrl.'/',
            $baseUrl.'/works',
            $baseUrl.'/about',
            $baseUrl.'/contact',
        ];

        foreach ($this->repository->listPublishedWorks() as $work) {
            $urls[] = $baseUrl.'/works/'.$work['slug'];
        }

        // draft=trueは除外済み（ContentRepository::listPublishedLogs）
        foreach ($this->repository->listPublishedLogs() as $log) {
            $urls[] = $baseUrl.'/logs/'.$log['slug'];
        }

        return response($this->toXml($urls))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * XMLをここで直接組み立てる（.blade.phpにしない）。
     * <?xml ...?> をテンプレートファイルに書くと、IDEのBladeパーサがPHP開始タグと誤認して
     * 常に構文エラー扱いする既知の問題があるため、素のPHP文字列組み立てで確実に回避する。
     *
     * @param  array<int, string>  $urls
     */
    private function toXml(array $urls): string
    {
        $body = '';
        foreach ($urls as $url) {
            $body .= '  <url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8')."</loc></url>\n";
        }

        return '<'.'?xml version="1.0" encoding="UTF-8"?'.">\n"
            ."<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            .$body
            ."</urlset>\n";
    }
}
