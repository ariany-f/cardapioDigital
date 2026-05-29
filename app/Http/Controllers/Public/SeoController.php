<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(SeoService $seo): Response
    {
        $s = $seo->settings();
        $sitemapUrl = url('/sitemap.xml');

        $lines = [
            'User-agent: *',
            $s->robots_index ? 'Allow: /' : 'Disallow: /',
            'Disallow: /platform',
            'Disallow: /*/admin',
            'Disallow: /*/entregador',
            'Disallow: /login',
            'Disallow: /register',
            '',
            'Sitemap: '.$sitemapUrl,
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(SeoService $seo): Response
    {
        $urls = $seo->sitemapUrls();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $entry) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8')."</loc>\n";
            if (! empty($entry['changefreq'])) {
                $xml .= '    <changefreq>'.$entry['changefreq']."</changefreq>\n";
            }
            if (! empty($entry['priority'])) {
                $xml .= '    <priority>'.$entry['priority']."</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
