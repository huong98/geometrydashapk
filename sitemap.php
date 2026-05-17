<?php
declare(strict_types=1);

$baseUrl = 'https://geometrydashapk.games';
$root = __DIR__;

$languagePrefixes = [
    'vn',
    'jp',
    'kr',
    'cn',
    'tw',
    'es',
    'fr',
    'de',
    'it',
    'pt',
    'id',
    'th',
    'tr',
    'sa',
];

$pages = [
    ['path' => '/', 'file' => 'index.html', 'priority' => '1.00'],
    ['path' => '/Android', 'file' => 'Android.html', 'priority' => '0.80'],
    ['path' => '/IOS', 'file' => 'IOS.html', 'priority' => '0.80'],
    ['path' => '/Window', 'file' => 'Window.html', 'priority' => '0.80'],
    ['path' => '/blog', 'file' => 'blog.html', 'priority' => '0.80'],
    ['path' => '/about', 'file' => 'about.html', 'priority' => '0.70'],
    ['path' => '/contact', 'file' => 'contact.html', 'priority' => '0.70'],
    ['path' => '/privacy-policy', 'file' => 'privacy-policy.html', 'priority' => '0.60'],
    ['path' => '/disclaimer', 'file' => 'disclaimer.html', 'priority' => '0.60'],
    ['path' => '/terms-and-conditions', 'file' => 'terms-and-conditions.html', 'priority' => '0.60'],
];

$blogDir = $root . DIRECTORY_SEPARATOR . 'Blog';
if (is_dir($blogDir)) {
    foreach (glob($blogDir . DIRECTORY_SEPARATOR . '*.html') ?: [] as $filePath) {
        $slug = basename($filePath, '.html');
        $pages[] = [
            'path' => '/blog/' . rawurlencode($slug),
            'file' => 'Blog/' . basename($filePath),
            'priority' => '0.70',
        ];
    }
}

function fileLastModified(string $root, string $relativeFile): string
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFile);
    $timestamp = is_file($path) ? filemtime($path) : time();

    return gmdate('c', $timestamp ?: time());
}

function xmlEscape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function localizedPath(string $prefix, string $path): string
{
    if ($path === '/') {
        return '/' . $prefix;
    }

    return '/' . $prefix . $path;
}

$urls = [];
foreach ($pages as $page) {
    $lastmod = fileLastModified($root, $page['file']);
    $urls[] = [
        'loc' => $baseUrl . $page['path'],
        'lastmod' => $lastmod,
        'priority' => $page['priority'],
    ];

    foreach ($languagePrefixes as $prefix) {
        $urls[] = [
            'loc' => $baseUrl . localizedPath($prefix, $page['path']),
            'lastmod' => $lastmod,
            'priority' => $page['priority'],
        ];
    }
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

foreach ($urls as $url) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . xmlEscape($url['loc']) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . xmlEscape($url['lastmod']) . '</lastmod>' . PHP_EOL;
    echo '    <priority>' . xmlEscape($url['priority']) . '</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

echo '</urlset>' . PHP_EOL;
