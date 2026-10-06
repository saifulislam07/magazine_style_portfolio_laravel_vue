<?php

namespace App\Http\Controllers;

use App\Models\BookPage;
use App\Models\BookSetting;
use App\Models\PageVisit;
use App\Models\SiteSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        $book = BookSetting::query()->find(1)?->data ?? [];
        $seo = SiteSetting::valuesFor('seo');
        $analytics = SiteSetting::valuesFor('analytics');

        if ($analytics['track_visits']) {
            PageVisit::recordToday();
        }

        $brand = $book['brand'] ?? config('app.name');
        $author = $book['author'] ?? null;
        $title = $seo['meta_title'] ?: trim(Str::title($brand).($author ? ' — '.Str::title($author) : ''));
        $description = $seo['meta_description'] ?: ($book['site_description'] ?? '');
        $image = $seo['og_image'] ?: BookPage::query()->where('kind', 'cover')->first()?->content['image'] ?? null;
        $canonical = $seo['canonical_url'] ?: url('/');

        $structuredData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $author ? Str::title($author) : $brand,
            'jobTitle' => isset($book['photography_label']) ? Str::title($book['photography_label']) : null,
            'description' => $description ?: null,
            'url' => $canonical,
            'image' => $image,
            'sameAs' => array_column(SiteSetting::socialLinks(), 'url') ?: null,
        ]);

        return view('magazine', [
            'meta' => [
                'title' => $title,
                'description' => $description,
                'keywords' => $seo['meta_keywords'],
                'image' => $image,
                'canonical' => $canonical,
                'twitter' => $seo['twitter_handle'],
                'robots' => $seo['allow_indexing'] ? 'index, follow' : 'noindex, nofollow',
                'googleVerification' => $seo['google_site_verification'],
                'bingVerification' => $seo['bing_site_verification'],
                'siteName' => Str::title($brand),
            ],
            'structuredData' => $structuredData,
            'analytics' => $analytics,
        ]);
    }

    public function sitemap(): Response
    {
        $lastModified = collect([
            BookPage::query()->max('updated_at'),
            BookSetting::query()->max('updated_at'),
        ])->filter()->max();

        $seo = SiteSetting::valuesFor('seo');

        return response()
            ->view('sitemap', [
                'homeUrl' => $seo['canonical_url'] ?: url('/'),
                'lastModified' => $lastModified ? now()->parse($lastModified)->toAtomString() : null,
            ])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $allowIndexing = SiteSetting::valuesFor('seo')['allow_indexing'];

        $lines = $allowIndexing
            ? ['User-agent: *', 'Disallow: /admin', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain');
    }
}
