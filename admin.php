<?php
session_start();

$baseDir = __DIR__;
$siteName = 'Geometry Dash APK Games';
$adminPassword = getenv('GEOMETRY_ADMIN_PASSWORD') ?: 'admin123';
$message = '';
$error = '';

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function editor_toolbar() {
    return '
                <div class="wp-editor-top">
                  <button class="btn add-media" type="button" data-action="image">Add Media</button>
                  <div class="wp-editor-tabs">
                    <button class="wp-tab active" type="button">Visual</button>
                    <button class="wp-tab" type="button" data-action="source">HTML</button>
                  </div>
                </div>
                <div class="editor-toolbar" data-toolbar>
                  <button class="tool wide" type="button" data-action="source">Source</button>
                  <button class="tool" type="button" data-cmd="undo">Undo</button>
                  <button class="tool" type="button" data-cmd="redo">Redo</button>
                  <button class="tool wide" type="button" data-format="p">Normal</button>
                  <button class="tool" type="button" data-format="h2">H2</button>
                  <button class="tool" type="button" data-format="h3">H3</button>
                  <button class="tool" type="button" data-cmd="bold">B</button>
                  <button class="tool" type="button" data-cmd="italic">I</button>
                  <button class="tool" type="button" data-cmd="underline">U</button>
                  <button class="tool" type="button" data-cmd="strikeThrough">S</button>
                  <button class="tool" type="button" data-cmd="subscript">x2</button>
                  <button class="tool" type="button" data-cmd="superscript">x^2</button>
                  <button class="tool" type="button" data-cmd="insertUnorderedList">UL</button>
                  <button class="tool" type="button" data-cmd="insertOrderedList">OL</button>
                  <button class="tool" type="button" data-cmd="outdent">Out</button>
                  <button class="tool" type="button" data-cmd="indent">In</button>
                  <button class="tool" type="button" data-format="blockquote">Quote</button>
                  <button class="tool" type="button" data-cmd="justifyLeft">Left</button>
                  <button class="tool" type="button" data-cmd="justifyCenter">Center</button>
                  <button class="tool" type="button" data-cmd="justifyRight">Right</button>
                  <button class="tool" type="button" data-action="link">Link</button>
                  <button class="tool" type="button" data-cmd="unlink">Unlink</button>
                  <button class="tool" type="button" data-action="image">Img</button>
                  <button class="tool" type="button" data-action="foreColor">Text</button>
                  <button class="tool" type="button" data-action="backColor">Bg</button>
                  <button class="tool" type="button" data-cmd="insertHorizontalRule">HR</button>
                  <button class="tool" type="button" data-cmd="removeFormat">Clear</button>
                </div>';
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'new-page';
}

function safe_root_html_path($filename) {
    global $baseDir;
    $filename = basename((string) $filename);
    if ($filename !== '' && substr(strtolower($filename), -5) !== '.html') {
        $filename .= '.html';
    }
    if (!preg_match('/^[a-zA-Z0-9._-]+\.html$/', $filename)) {
        return null;
    }
    $path = realpath($baseDir . DIRECTORY_SEPARATOR . $filename);
    if (!$path || dirname($path) !== realpath($baseDir)) {
        return null;
    }
    return $path;
}

function safe_blog_html_path($filename) {
    global $baseDir;
    $filename = basename((string) $filename);
    if ($filename !== '' && substr(strtolower($filename), -5) !== '.html') {
        $filename .= '.html';
    }
    if (!preg_match('/^[a-zA-Z0-9._-]+\.html$/', $filename)) {
        return null;
    }
    $blogDir = realpath($baseDir . DIRECTORY_SEPARATOR . 'Blog');
    $path = realpath($blogDir . DIRECTORY_SEPARATOR . $filename);
    if (!$path || dirname($path) !== $blogDir) {
        return null;
    }
    return $path;
}

function get_tag_content($html, $tag) {
    if (preg_match('/<' . preg_quote($tag, '/') . '\b[^>]*>(.*?)<\/' . preg_quote($tag, '/') . '>/is', $html, $match)) {
        return html_entity_decode(trim(strip_tags($match[1])), ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function get_meta_description($html) {
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\']/i', $html, $match)) {
        return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('/<meta\s+content=["\']([^"\']*)["\']\s+name=["\']description["\']/i', $html, $match)) {
        return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function get_main_content($html) {
    if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $html, $match)) {
        return trim($match[1]);
    }
    if (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $match)) {
        return trim($match[1]);
    }
    return $html;
}

function replace_first_tag_content($html, $tag, $content) {
    return preg_replace('/(<' . preg_quote($tag, '/') . '\b[^>]*>)(.*?)(<\/' . preg_quote($tag, '/') . '>)/is', '$1' . $content . '$3', $html, 1);
}

function replace_meta_description($html, $description) {
    $descriptionEsc = h($description);
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'][^"\']*["\']/i', $html)) {
        return preg_replace('/<meta\s+name=["\']description["\']\s+content=["\'][^"\']*["\']/i', '<meta name="description" content="' . $descriptionEsc . '"', $html, 1);
    }
    return preg_replace('/<\/head>/i', '  <meta name="description" content="' . $descriptionEsc . '">' . PHP_EOL . '</head>', $html, 1);
}

function replace_main_content($html, $content) {
    if (preg_match('/<main\b[^>]*>.*?<\/main>/is', $html)) {
        return preg_replace('/(<main\b[^>]*>)(.*?)(<\/main>)/is', '$1' . PHP_EOL . $content . PHP_EOL . '$3', $html, 1);
    }
    return preg_replace('/<\/body>/i', '<main>' . PHP_EOL . $content . PHP_EOL . '</main>' . PHP_EOL . '</body>', $html, 1);
}

function page_template($title, $description, $bodyHtml) {
    $titleEsc = h($title);
    $descriptionEsc = h($description);
    return '<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
  <meta name="description" content="' . $descriptionEsc . '">
  <title>' . $titleEsc . '</title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" href="./Home/style.css">
</head>
<body>
  <header>
    <a href="./" class="logo">
      <img src="./Home/logo.webp" alt="Geometry Dash Logo" class="logo-icon">
      <span class="logo-text">Geometry Dash APK</span>
    </a>
    <nav>
      <ul class="nav-menu">
        <li><a href="./" class="nav-link">Home</a></li>
        <li><a href="./Android" class="nav-link">ON Android</a></li>
        <li><a href="./IOS" class="nav-link">ON iOS</a></li>
        <li><a href="./Window" class="nav-link">ON PC</a></li>
        <li><a href="./blog" class="nav-link">Blog</a></li>
      </ul>
    </nav>
  </header>
  <main>
    <section class="hero">
      <div class="hero-content">
        <div class="hero-text">
          <h1>' . $titleEsc . '</h1>
          <p class="hero-subtitle">' . $descriptionEsc . '</p>
        </div>
      </div>
    </section>
    <section class="features-section">
      ' . $bodyHtml . '
    </section>
  </main>
  <footer>
    <div class="footer-bottom">
      <p class="footer-copyright">© 2026 <a href="/">Geometry Dash APK Games</a>. All rights reserved.</p>
    </div>
  </footer>
  <script src="./Home/script.js.download"></script>
</body>
</html>
';
}

function blog_template($title, $description, $category, $contentHtml) {
    $titleEsc = h($title);
    $descriptionEsc = h($description);
    $categoryEsc = h($category);
    $date = date('F j, Y');
    return '<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
  <meta name="description" content="' . $descriptionEsc . '">
  <title>' . $titleEsc . '</title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" href="../Blog/style.css?v=20260516-blog-colors">
</head>
<body>
  <header>
    <a href="../" class="logo">
      <img src="../Blog/logo.webp" alt="Geometry Dash Logo" class="logo-icon">
      <span class="logo-text">Geometry Dash APK</span>
    </a>
    <nav>
      <ul class="nav-menu">
        <li><a href="../" class="nav-link">Home</a></li>
        <li><a href="../Android" class="nav-link">ON Android</a></li>
        <li><a href="../IOS" class="nav-link">ON iOS</a></li>
        <li><a href="../Window" class="nav-link">ON PC</a></li>
        <li><a href="../blog" class="nav-link active">Blog</a></li>
      </ul>
    </nav>
  </header>
  <main>
    <section class="blog-hero">
      <div class="container">
        <span class="blog-category">' . $categoryEsc . '</span>
        <h1 class="blog-hero-title">' . $titleEsc . '</h1>
        <p class="blog-hero-description">' . $descriptionEsc . '</p>
        <div class="blog-meta"><span>' . h($date) . '</span><span>5 min read</span></div>
      </div>
    </section>
    <section class="blog-section">
      <div class="container">
        <article class="blog-card-content">
          ' . $contentHtml . '
        </article>
      </div>
    </section>
  </main>
  <footer>
    <div class="footer-bottom">
      <p class="footer-copyright">© 2026 <a href="/">Geometry Dash APK</a>. All rights reserved.</p>
    </div>
  </footer>
  <script src="../Blog/script.js.download"></script>
</body>
</html>
';
}

function insert_blog_card($slug, $title, $category, $excerpt) {
    global $baseDir;
    $blogIndex = $baseDir . DIRECTORY_SEPARATOR . 'blog.html';
    if (!is_file($blogIndex) || !is_writable($blogIndex)) {
        return false;
    }
    $html = file_get_contents($blogIndex);
    $card = '
            <article class="blog-card">
              <a href="./blog/' . h($slug) . '" class="blog-card-link">
                <div class="blog-card-content">
                  <span class="blog-category">' . h($category) . '</span>
                  <h2 class="blog-card-title">' . h($title) . '</h2>
                  <p class="blog-card-excerpt">' . h($excerpt) . '</p>
                  <div class="blog-meta">
                    <span class="blog-date">' . h(date('F j, Y')) . '</span>
                    <span class="blog-read-time">5 min read</span>
                  </div>
                </div>
              </a>
            </article>
';
    $needle = '<div class="blog-grid">';
    if (strpos($html, $needle) === false) {
        return false;
    }
    $html = str_replace($needle, $needle . $card, $html);
    return file_put_contents($blogIndex, $html) !== false;
}

function remove_blog_card($slug) {
    global $baseDir;
    $blogIndex = $baseDir . DIRECTORY_SEPARATOR . 'blog.html';
    if (!is_file($blogIndex) || !is_writable($blogIndex)) {
        return false;
    }
    $html = file_get_contents($blogIndex);
    $pattern = '/\s*<article\s+class="blog-card">\s*<a\s+href=["\']\.\/blog\/' . preg_quote($slug, '/') . '["\']\s+class="blog-card-link">.*?<\/a>\s*<\/article>/is';
    $updated = preg_replace($pattern, '', $html, 1, $count);
    if ($count > 0) {
        backup_file($blogIndex);
        return file_put_contents($blogIndex, $updated) !== false;
    }
    return true;
}

function backup_file($path) {
    global $baseDir;
    if (!is_file($path)) {
        return;
    }
    $backupDir = $baseDir . DIRECTORY_SEPARATOR . '.admin-backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }
    $name = str_replace(['\\', '/', ':'], '-', str_replace($baseDir, '', $path));
    copy($path, $backupDir . DIRECTORY_SEPARATOR . date('Ymd-His') . '-' . ltrim($name, '-'));
}

function add_menu_item_to_public_pages($label, $slug) {
    global $baseDir;
    $files = glob($baseDir . DIRECTORY_SEPARATOR . '*.html') ?: [];
    $link = './' . $slug;
    $item = '<li><a href="' . h($link) . '" class="nav-link">' . h($label) . '</a></li>';
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, ['admin.html', '404.html'], true) || !is_writable($file)) {
            continue;
        }
        $html = file_get_contents($file);
        if (strpos($html, 'href="' . $link . '"') !== false || strpos($html, "href='" . $link . "'") !== false) {
            continue;
        }
        if (strpos($html, '<li><a href="./blog"') !== false) {
            backup_file($file);
            $html = str_replace('<li><a href="./blog"', $item . PHP_EOL . '          <li><a href="./blog"', $html);
            file_put_contents($file, $html);
        }
    }
}

function remove_menu_item_from_public_pages($slug) {
    global $baseDir;
    $files = glob($baseDir . DIRECTORY_SEPARATOR . '*.html') ?: [];
    $slug = trim((string) $slug, '/');
    if ($slug === '') {
        return 0;
    }
    $changed = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, ['admin.html'], true) || !is_writable($file)) {
            continue;
        }
        $html = file_get_contents($file);
        $pattern = '/\s*<li>\s*<a\s+href=["\'](?:\.\/|\/)?' . preg_quote($slug, '/') . '["\']\s+class=["\']nav-link(?:\s+active)?["\'][^>]*>.*?<\/a>\s*<\/li>/is';
        $updated = preg_replace($pattern, '', $html, -1, $count);
        if ($count > 0) {
            backup_file($file);
            file_put_contents($file, $updated);
            $changed++;
        }
    }
    return $changed;
}

function get_home_head_code() {
    global $baseDir;
    $path = $baseDir . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($path)) {
        return '';
    }
    $html = file_get_contents($path);
    if (preg_match('/<!-- ADMIN HEAD CODE START -->(.*?)<!-- ADMIN HEAD CODE END -->/is', $html, $match)) {
        return trim($match[1]);
    }
    return '';
}

function save_home_head_code($code) {
    global $baseDir;
    $path = $baseDir . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($path) || !is_writable($path)) {
        return false;
    }
    $html = file_get_contents($path);
    $block = '<!-- ADMIN HEAD CODE START -->' . PHP_EOL . rtrim((string) $code) . PHP_EOL . '<!-- ADMIN HEAD CODE END -->';
    if (preg_match('/<!-- ADMIN HEAD CODE START -->.*?<!-- ADMIN HEAD CODE END -->/is', $html)) {
        $html = preg_replace('/<!-- ADMIN HEAD CODE START -->.*?<!-- ADMIN HEAD CODE END -->/is', $block, $html, 1);
    } else {
        $html = preg_replace('/<\/head>/i', $block . PHP_EOL . '</head>', $html, 1);
    }
    backup_file($path);
    return file_put_contents($path, $html) !== false;
}

function list_root_pages() {
    global $baseDir;
    $skip = ['admin.html'];
    $files = glob($baseDir . DIRECTORY_SEPARATOR . '*.html') ?: [];
    $pages = [];
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $skip, true)) {
            continue;
        }
        $pages[] = [
            'name' => $name,
            'url' => $name === 'index.html' ? './' : './' . preg_replace('/\.html$/', '', $name),
            'size' => filesize($file),
            'updated' => date('Y-m-d H:i', filemtime($file)),
        ];
    }
    return $pages;
}

function list_blog_posts() {
    global $baseDir;
    $files = glob($baseDir . DIRECTORY_SEPARATOR . 'Blog' . DIRECTORY_SEPARATOR . '*.html') ?: [];
    $posts = [];
    foreach ($files as $file) {
        $name = basename($file);
        $posts[] = [
            'name' => $name,
            'url' => './blog/' . preg_replace('/\.html$/', '', $name),
            'size' => filesize($file),
            'updated' => date('Y-m-d H:i', filemtime($file)),
        ];
    }
    return $posts;
}

function admin_json_path($filename) {
    global $baseDir;
    return $baseDir . DIRECTORY_SEPARATOR . $filename;
}

function read_admin_json($filename, $default) {
    $path = admin_json_path($filename);
    if (!is_file($path)) {
        return $default;
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : $default;
}

function write_admin_json($filename, $data) {
    $path = admin_json_path($filename);
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function ai_default_config() {
    return [
        'api_key' => '',
        'model' => 'gpt-5.2',
        'daily_limit' => 2,
        'category' => 'Guide',
        'language' => 'English',
        'site_context' => 'Geometry Dash APK blog for Android, iOS, and PC players. Write helpful, SEO-friendly posts about Geometry Dash APK guides, tips, updates, installation, gameplay, levels, and safety.',
        'cron_token' => bin2hex(random_bytes(16)),
        'updated_at' => date('c'),
    ];
}

function get_ai_config() {
    $config = array_merge(ai_default_config(), read_admin_json('.admin-ai-config.json', []));
    $config['daily_limit'] = max(1, (int) ($config['daily_limit'] ?? 2));
    $config['model'] = trim((string) ($config['model'] ?? 'gpt-5.2')) ?: 'gpt-5.2';
    return $config;
}

function save_ai_config($input) {
    $current = get_ai_config();
    $apiKey = trim((string) ($input['api_key'] ?? ''));
    $config = [
        'api_key' => $apiKey !== '' ? $apiKey : ($current['api_key'] ?? ''),
        'model' => trim((string) ($input['model'] ?? 'gpt-5.2')) ?: 'gpt-5.2',
        'daily_limit' => max(1, (int) ($input['daily_limit'] ?? 2)),
        'category' => trim((string) ($input['category'] ?? 'Guide')) ?: 'Guide',
        'language' => trim((string) ($input['language'] ?? 'English')) ?: 'English',
        'site_context' => trim((string) ($input['site_context'] ?? '')),
        'cron_token' => $current['cron_token'] ?? bin2hex(random_bytes(16)),
        'updated_at' => date('c'),
    ];
    if ($config['site_context'] === '') {
        $config['site_context'] = ai_default_config()['site_context'];
    }
    return write_admin_json('.admin-ai-config.json', $config);
}

function get_ai_queue() {
    $queue = read_admin_json('.admin-ai-queue.json', []);
    return is_array($queue) ? $queue : [];
}

function save_ai_queue($queue) {
    return write_admin_json('.admin-ai-queue.json', array_values($queue));
}

function mask_secret($value) {
    $value = (string) $value;
    if ($value === '') {
        return 'Chua cau hinh';
    }
    return substr($value, 0, 7) . '...' . substr($value, -4);
}

function openai_api_key($config) {
    $envKey = getenv('OPENAI_API_KEY');
    if ($envKey) {
        return $envKey;
    }
    return trim((string) ($config['api_key'] ?? ''));
}

function unique_blog_slug($title, $preferredSlug = '') {
    global $baseDir;
    $base = slugify($preferredSlug ?: $title);
    $slug = $base;
    $i = 2;
    while (file_exists($baseDir . DIRECTORY_SEPARATOR . 'Blog' . DIRECTORY_SEPARATOR . $slug . '.html')) {
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

function create_blog_post_file($title, $excerpt, $category, $contentHtml, $preferredSlug = '') {
    global $baseDir;
    $slug = unique_blog_slug($title, $preferredSlug);
    $target = $baseDir . DIRECTORY_SEPARATOR . 'Blog' . DIRECTORY_SEPARATOR . $slug . '.html';
    $html = blog_template($title, $excerpt, $category, $contentHtml);
    if (file_put_contents($target, $html) === false) {
        return [false, 'Khong ghi duoc file Blog/' . $slug . '.html'];
    }
    backup_file($baseDir . DIRECTORY_SEPARATOR . 'blog.html');
    if (!insert_blog_card($slug, $title, $category, $excerpt)) {
        return [false, 'Da tao file nhung khong chen duoc card vao blog.html'];
    }
    return [true, $slug];
}

function extract_response_text($response) {
    if (isset($response['output_text']) && is_string($response['output_text'])) {
        return $response['output_text'];
    }
    $text = '';
    foreach (($response['output'] ?? []) as $item) {
        foreach (($item['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                $text .= $content['text'];
            }
        }
    }
    return $text;
}

function openai_generate_blog_article($title, $config) {
    $apiKey = openai_api_key($config);
    if ($apiKey === '') {
        return [false, 'Thieu OPENAI_API_KEY hoac API key trong cau hinh AI.'];
    }

    $schema = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['title', 'slug', 'category', 'excerpt', 'content_html'],
        'properties' => [
            'title' => ['type' => 'string'],
            'slug' => ['type' => 'string'],
            'category' => ['type' => 'string'],
            'excerpt' => ['type' => 'string'],
            'content_html' => ['type' => 'string'],
        ],
    ];
    $input = "Write a complete blog post for this title: {$title}\n\n"
        . "Language: " . ($config['language'] ?? 'English') . "\n"
        . "Default category: " . ($config['category'] ?? 'Guide') . "\n"
        . "Site context: " . ($config['site_context'] ?? '') . "\n\n"
        . "Return useful SEO content for readers. Use clean HTML only inside content_html: h2, h3, p, ul, ol, li, strong, em, a. Do not include html, head, body, script, style, or markdown fences. Include an introduction, several practical sections, and a short conclusion.";

    $payload = [
        'model' => $config['model'] ?? 'gpt-5.2',
        'input' => $input,
        'text' => [
            'format' => [
                'type' => 'json_schema',
                'name' => 'blog_article',
                'strict' => true,
                'schema' => $schema,
            ],
        ],
    ];

    if (!function_exists('curl_init')) {
        return [false, 'PHP chua bat extension cURL, khong the goi OpenAI API.'];
    }

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 180,
    ]);
    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $curlError) {
        return [false, 'Loi ket noi OpenAI: ' . $curlError];
    }
    $response = json_decode($raw, true);
    if ($status < 200 || $status >= 300) {
        $detail = $response['error']['message'] ?? $raw;
        return [false, 'OpenAI tra loi loi HTTP ' . $status . ': ' . $detail];
    }
    $text = extract_response_text($response);
    $article = json_decode($text, true);
    if (!is_array($article)) {
        return [false, 'Khong doc duoc JSON bai viet tu OpenAI.'];
    }
    return [true, $article];
}

function add_ai_titles_to_queue($titlesText) {
    $titles = preg_split('/\R+/', (string) $titlesText);
    $queue = get_ai_queue();
    $added = 0;
    foreach ($titles as $title) {
        $title = trim($title);
        if ($title === '') {
            continue;
        }
        $queue[] = [
            'id' => bin2hex(random_bytes(8)),
            'title' => $title,
            'status' => 'pending',
            'created_at' => date('c'),
            'posted_at' => '',
            'slug' => '',
            'error' => '',
        ];
        $added++;
    }
    save_ai_queue($queue);
    return $added;
}

function ai_posts_count_today($queue) {
    $today = date('Y-m-d');
    $count = 0;
    foreach ($queue as $item) {
        if (($item['status'] ?? '') === 'posted' && substr((string) ($item['posted_at'] ?? ''), 0, 10) === $today) {
            $count++;
        }
    }
    return $count;
}

function run_ai_queue($limitOverride = null) {
    $config = get_ai_config();
    $queue = get_ai_queue();
    $remaining = max(0, (int) ($config['daily_limit'] ?? 2) - ai_posts_count_today($queue));
    if ($limitOverride !== null) {
        $remaining = min($remaining, max(0, (int) $limitOverride));
    }
    if ($remaining < 1) {
        return [0, 'Da dat gioi han dang bai hom nay.'];
    }

    $posted = 0;
    foreach ($queue as $index => $item) {
        if ($remaining < 1) {
            break;
        }
        if (($item['status'] ?? '') !== 'pending') {
            continue;
        }
        $remaining--;
        $queue[$index]['status'] = 'running';
        $queue[$index]['error'] = '';
        save_ai_queue($queue);

        [$ok, $result] = openai_generate_blog_article($item['title'] ?? '', $config);
        if (!$ok) {
            $queue[$index]['status'] = 'error';
            $queue[$index]['error'] = $result;
            continue;
        }

        $title = trim((string) ($result['title'] ?? $item['title']));
        $excerpt = trim((string) ($result['excerpt'] ?? ''));
        $category = trim((string) ($result['category'] ?? ($config['category'] ?? 'Guide'))) ?: 'Guide';
        $content = trim((string) ($result['content_html'] ?? ''));
        if ($excerpt === '' || $content === '') {
            $queue[$index]['status'] = 'error';
            $queue[$index]['error'] = 'Bai viet AI thieu excerpt hoac content_html.';
            continue;
        }

        [$created, $slugOrError] = create_blog_post_file($title, $excerpt, $category, $content, $result['slug'] ?? '');
        if (!$created) {
            $queue[$index]['status'] = 'error';
            $queue[$index]['error'] = $slugOrError;
            continue;
        }
        $queue[$index]['status'] = 'posted';
        $queue[$index]['posted_at'] = date('c');
        $queue[$index]['slug'] = $slugOrError;
        $queue[$index]['error'] = '';
        $posted++;
    }
    save_ai_queue($queue);
    return [$posted, 'Da xu ly queue AI.'];
}

$cronConfig = get_ai_config();
if (isset($_GET['run_ai_queue'])) {
    $token = (string) ($_GET['token'] ?? '');
    if ($token === '' || !hash_equals((string) ($cronConfig['cron_token'] ?? ''), $token)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    [$posted, $cronMessage] = run_ai_queue();
    header('Content-Type: text/plain; charset=UTF-8');
    echo $cronMessage . ' Posted: ' . $posted;
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (hash_equals($adminPassword, (string) ($_POST['password'] ?? ''))) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    }
    $error = 'Mat khau khong dung.';
}

$loggedIn = !empty($_SESSION['admin_logged_in']);

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_page') {
        $path = safe_root_html_path($_POST['filename'] ?? '');
        if (!$path || !is_writable($path)) {
            $error = 'Khong the ghi file trang nay.';
        } else {
            backup_file($path);
            file_put_contents($path, (string) ($_POST['html'] ?? ''));
            $message = 'Da luu trang ' . basename($path) . '.';
        }
    }

    if ($action === 'save_page_content') {
        $path = safe_root_html_path($_POST['filename'] ?? '');
        if (!$path || !is_writable($path)) {
            $error = 'Khong the ghi file trang nay.';
        } else {
            $html = file_get_contents($path);
            $html = replace_first_tag_content($html, 'title', h($_POST['title'] ?? ''));
            $html = replace_meta_description($html, $_POST['description'] ?? '');
            $html = replace_main_content($html, (string) ($_POST['content'] ?? ''));
            backup_file($path);
            file_put_contents($path, $html);
            $message = 'Da luu noi dung trang ' . basename($path) . '.';
        }
    }

    if ($action === 'save_blog') {
        $path = safe_blog_html_path($_POST['filename'] ?? '');
        if (!$path || !is_writable($path)) {
            $error = 'Khong the ghi file bai viet nay.';
        } else {
            backup_file($path);
            file_put_contents($path, (string) ($_POST['html'] ?? ''));
            $message = 'Da luu bai viet ' . basename($path) . '.';
        }
    }

    if ($action === 'save_blog_content') {
        $path = safe_blog_html_path($_POST['filename'] ?? '');
        if (!$path || !is_writable($path)) {
            $error = 'Khong the ghi file bai viet nay.';
        } else {
            $html = file_get_contents($path);
            $html = replace_first_tag_content($html, 'title', h($_POST['title'] ?? ''));
            $html = replace_meta_description($html, $_POST['description'] ?? '');
            $html = replace_main_content($html, (string) ($_POST['content'] ?? ''));
            backup_file($path);
            file_put_contents($path, $html);
            $message = 'Da luu noi dung bai blog ' . basename($path) . '.';
        }
    }

    if ($action === 'create_page') {
        $slug = slugify($_POST['slug'] ?: $_POST['title']);
        $target = $baseDir . DIRECTORY_SEPARATOR . $slug . '.html';
        if (file_exists($target)) {
            $error = 'Trang da ton tai: ' . $slug . '.html';
        } else {
            $html = page_template($_POST['title'] ?? 'New Page', $_POST['description'] ?? '', $_POST['content'] ?? '');
            file_put_contents($target, $html);
            if (!empty($_POST['add_to_menu'])) {
                add_menu_item_to_public_pages($_POST['menu_label'] ?: $_POST['title'], $slug);
            }
            $message = 'Da tao trang moi: ' . $slug . '.html';
        }
    }

    if ($action === 'create_blog') {
        $slug = slugify($_POST['slug'] ?: $_POST['title']);
        $target = $baseDir . DIRECTORY_SEPARATOR . 'Blog' . DIRECTORY_SEPARATOR . $slug . '.html';
        if (file_exists($target)) {
            $error = 'Bai viet da ton tai: Blog/' . $slug . '.html';
        } else {
            $title = $_POST['title'] ?? 'New Blog Post';
            $excerpt = $_POST['excerpt'] ?? '';
            $category = $_POST['category'] ?? 'Guide';
            $html = blog_template($title, $excerpt, $category, $_POST['content'] ?? '');
            file_put_contents($target, $html);
            backup_file($baseDir . DIRECTORY_SEPARATOR . 'blog.html');
            insert_blog_card($slug, $title, $category, $excerpt);
            $message = 'Da tao bai blog moi va them vao blog.html: ' . $slug . '.html';
        }
    }

    if ($action === 'save_ai_config') {
        if (save_ai_config($_POST)) {
            $message = 'Da luu cau hinh AI.';
        } else {
            $error = 'Khong luu duoc cau hinh AI.';
        }
        $section = 'ai-blog';
    }

    if ($action === 'add_ai_titles') {
        $added = add_ai_titles_to_queue($_POST['titles'] ?? '');
        $message = 'Da them ' . $added . ' tieu de vao queue AI.';
        if (!empty($_POST['run_now'])) {
            [$posted, $runMessage] = run_ai_queue((int) ($_POST['run_limit'] ?? 1));
            $message .= ' ' . $runMessage . ' Dang thanh cong: ' . $posted . '.';
        }
        $section = 'ai-blog';
    }

    if ($action === 'run_ai_queue_now') {
        [$posted, $runMessage] = run_ai_queue((int) ($_POST['run_limit'] ?? 1));
        $message = $runMessage . ' Dang thanh cong: ' . $posted . '.';
        $section = 'ai-blog';
    }

    if ($action === 'retry_ai_item') {
        $id = (string) ($_POST['id'] ?? '');
        $queue = get_ai_queue();
        foreach ($queue as $index => $item) {
            if (($item['id'] ?? '') === $id && in_array(($item['status'] ?? ''), ['error', 'running'], true)) {
                $queue[$index]['status'] = 'pending';
                $queue[$index]['error'] = '';
            }
        }
        save_ai_queue($queue);
        $message = 'Da dua muc AI ve trang thai pending.';
        $section = 'ai-blog';
    }

    if ($action === 'delete_page') {
        $filename = basename((string) ($_POST['filename'] ?? ''));
        $protected = ['index.html', 'blog.html', 'admin.html', '404.html'];
        $path = safe_root_html_path($filename);
        if (in_array($filename, $protected, true)) {
            $error = 'Khong the xoa trang he thong: ' . $filename;
        } elseif (!$path || !is_writable($path)) {
            $error = 'Khong the xoa trang nay.';
        } else {
            backup_file($path);
            unlink($path);
            $slug = preg_replace('/\.html$/i', '', $filename);
            $removedMenus = remove_menu_item_from_public_pages($slug);
            $message = 'Da xoa trang ' . $filename . '.';
            if ($removedMenus > 0) {
                $message .= ' Da go link menu khoi ' . $removedMenus . ' trang.';
            }
        }
    }

    if ($action === 'delete_blog') {
        $filename = basename((string) ($_POST['filename'] ?? ''));
        $path = safe_blog_html_path($filename);
        if (!$path || !is_writable($path)) {
            $error = 'Khong the xoa bai blog nay.';
        } else {
            $slug = preg_replace('/\.html$/i', '', $filename);
            backup_file($path);
            unlink($path);
            remove_blog_card($slug);
            $message = 'Da xoa bai blog ' . $filename . ' va go card khoi blog.html.';
        }
    }

    if ($action === 'remove_menu_slug') {
        $slug = slugify($_POST['slug'] ?? '');
        $removedMenus = remove_menu_item_from_public_pages($slug);
        $message = 'Da go link menu slug "' . $slug . '" khoi ' . $removedMenus . ' trang.';
    }

    if ($action === 'save_home_head_code') {
        if (save_home_head_code($_POST['head_code'] ?? '')) {
            $message = 'Da luu code trong the head cua trang chu index.html.';
        } else {
            $error = 'Khong the luu code head vao index.html.';
        }
    }
}

$section = $_GET['section'] ?? 'dashboard';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['save_ai_config', 'add_ai_titles', 'run_ai_queue_now', 'retry_ai_item'], true)) {
    $section = 'ai-blog';
}
$editPage = $_GET['edit_page'] ?? '';
$editBlog = $_GET['edit_blog'] ?? '';
$pageContent = '';
$blogContent = '';
$pageTitle = '';
$pageDescription = '';
$pageMain = '';
$blogTitle = '';
$blogDescription = '';
$blogMain = '';

if ($loggedIn && $editPage) {
    $path = safe_root_html_path($editPage);
    if ($path) {
        $section = 'edit-page';
        $pageContent = file_get_contents($path);
        $pageTitle = get_tag_content($pageContent, 'title');
        $pageDescription = get_meta_description($pageContent);
        $pageMain = get_main_content($pageContent);
    }
}

if ($loggedIn && $editBlog) {
    $path = safe_blog_html_path($editBlog);
    if ($path) {
        $section = 'edit-blog';
        $blogContent = file_get_contents($path);
        $blogTitle = get_tag_content($blogContent, 'title');
        $blogDescription = get_meta_description($blogContent);
        $blogMain = get_main_content($blogContent);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin - <?= h($siteName) ?></title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <style>
    :root { --bg:#f4f6f8; --panel:#fff; --text:#1f2933; --muted:#64748b; --line:#d9e2ec; --brand:#f5b400; --dark:#171b22; --green:#0f9f6e; --red:#d64545; --shadow:0 8px 24px rgba(15,23,42,.08); }
    * { box-sizing:border-box; }
    body { margin:0; font-family:Arial,Helvetica,sans-serif; background:var(--bg); color:var(--text); line-height:1.5; }
    a { color:inherit; text-decoration:none; }
    button,input,textarea,select { font:inherit; }
    .login { min-height:100vh; display:grid; place-items:center; padding:20px; }
    .login-card { width:min(420px,100%); background:#fff; border:1px solid var(--line); border-radius:10px; box-shadow:var(--shadow); padding:24px; }
    .shell { min-height:100vh; display:grid; grid-template-columns:260px minmax(0,1fr); }
    .sidebar { background:var(--dark); color:#e5e7eb; padding:22px 18px; position:sticky; top:0; height:100vh; overflow:auto; }
    .brand { display:flex; align-items:center; gap:10px; padding-bottom:20px; border-bottom:1px solid rgba(255,255,255,.1); margin-bottom:18px; }
    .brand img { width:38px; height:38px; border-radius:8px; object-fit:cover; }
    .brand strong { display:block; font-size:15px; line-height:1.2; }
    .brand span span { color:#aeb7c4; font-size:12px; }
    .nav-label { color:#8f9bad; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; margin:20px 10px 8px; }
    .nav a { display:flex; align-items:center; gap:10px; padding:10px; border-radius:8px; color:#d1d5db; }
    .nav a.active,.nav a:hover { background:#242b36; color:#fff; }
    .icon { width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; border-radius:7px; background:rgba(245,180,0,.14); color:var(--brand); font-size:12px; font-weight:700; flex:0 0 auto; }
    .topbar { min-height:70px; background:#fff; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 28px; position:sticky; top:0; z-index:5; }
    .topbar h1 { margin:0; font-size:20px; }
    .topbar p { margin:2px 0 0; color:var(--muted); font-size:13px; }
    .actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
    .content { padding:26px 28px 42px; }
    .grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
    .grid-2 { display:grid; grid-template-columns:minmax(0,1fr) minmax(360px,.55fr); gap:16px; align-items:start; }
    .card,.panel { background:#fff; border:1px solid var(--line); border-radius:8px; box-shadow:var(--shadow); overflow:hidden; }
    .card { padding:16px; }
    .card span { display:block; color:var(--muted); font-size:13px; margin-bottom:8px; }
    .card strong { display:block; font-size:26px; line-height:1; margin-bottom:8px; }
    .panel-head { padding:15px 16px; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .panel-head h2,.panel-head h3 { margin:0; font-size:17px; }
    .panel-body { padding:16px; }
    .section-title { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:18px; }
    .section-title h2 { margin:0; font-size:22px; }
    .section-title p { margin:4px 0 0; color:var(--muted); }
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; min-width:760px; }
    th,td { padding:12px 14px; border-bottom:1px solid var(--line); text-align:left; vertical-align:middle; font-size:14px; }
    th { color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.05em; background:#f8fafc; }
    tr:last-child td { border-bottom:0; }
    .btn { border:1px solid var(--line); background:#fff; color:var(--text); min-height:38px; padding:8px 13px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; font-weight:700; white-space:nowrap; }
    .btn.primary { border-color:var(--brand); background:var(--brand); color:#161616; }
    .btn.red { color:var(--red); border-color:rgba(214,69,69,.35); }
    .status { display:inline-flex; align-items:center; min-height:24px; padding:3px 8px; border-radius:999px; font-size:12px; font-weight:700; color:#047857; background:#d1fae5; }
    .status.pending { color:#92400e; background:#fef3c7; }
    .status.running { color:#1d4ed8; background:#dbeafe; }
    .status.error { color:#991b1b; background:#fee2e2; }
    .field { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
    label { font-size:13px; color:#334155; font-weight:700; }
    input,textarea,select { border:1px solid var(--line); border-radius:8px; background:#fff; color:var(--text); min-height:40px; padding:9px 11px; outline:none; width:100%; }
    textarea.code { min-height:620px; font-family:Consolas,Monaco,monospace; font-size:13px; line-height:1.45; white-space:pre; }
    textarea.body { min-height:240px; }
    .wp-post-shell { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:18px; align-items:start; }
    .wp-title-input { min-height:46px; border-radius:2px; font-size:24px; padding:8px 12px; background:#fff; }
    .wp-editor-top { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; margin-top:8px; }
    .add-media { width:auto; min-height:34px; border-radius:3px; background:#f6f7f7; }
    .wp-editor-tabs { display:flex; align-items:flex-end; gap:4px; }
    .wp-tab { border:1px solid var(--line); border-bottom:0; background:#f6f7f7; min-height:32px; padding:6px 12px; cursor:pointer; border-radius:3px 3px 0 0; color:#334155; }
    .wp-tab.active { background:#fff; color:#111827; }
    .editor-toolbar { display:flex; flex-wrap:wrap; gap:4px; padding:8px; border:1px solid var(--line); border-bottom:0; border-radius:3px 0 0 0; background:#eef0f2; }
    .tool { min-width:32px; height:30px; border:1px solid #b8c0cc; background:#fff; border-radius:3px; cursor:pointer; font-weight:700; color:#1f2933; padding:0 8px; }
    .tool.wide { min-width:64px; }
    .tool select { min-height:34px; border:0; padding:0 8px; background:#fff; }
    .wysiwyg { min-height:520px; border:1px solid var(--line); border-radius:0; background:#fff; padding:18px; outline:none; overflow:auto; font-size:16px; }
    .wysiwyg:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(245,180,0,.18); }
    .wysiwyg h1,.wysiwyg h2,.wysiwyg h3 { margin-top:0.85em; }
    .wysiwyg img { max-width:100%; height:auto; }
    .wysiwyg a { color:#d97706; text-decoration:underline; text-underline-offset:3px; font-weight:700; }
    .source-editor { display:none; min-height:520px; border-radius:0; font-family:Consolas,Monaco,monospace; font-size:13px; line-height:1.45; white-space:pre; }
    .editor-source-mode .wysiwyg { display:none; }
    .editor-source-mode .source-editor { display:block; }
    .publish-box .panel-body { display:grid; gap:12px; }
    .publish-row { color:var(--muted); font-size:13px; display:flex; justify-content:space-between; gap:10px; }
    .publish-row strong { color:var(--text); }
    .tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    input:focus,textarea:focus,select:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(245,180,0,.18); }
    .notice { padding:12px 14px; border-radius:8px; margin-bottom:16px; border:1px solid #bbf7d0; background:#dcfce7; color:#166534; font-weight:700; }
    .error { border-color:#fecaca; background:#fee2e2; color:#991b1b; }
    .hint { color:var(--muted); font-size:13px; margin-top:6px; }
    .mobile-menu { display:none; }
    @media (max-width:1100px) { .grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .grid-2,.wp-post-shell { grid-template-columns:1fr; } }
    @media (max-width:820px) { .shell { grid-template-columns:1fr; } .sidebar { position:fixed; inset:0 auto 0 0; width:280px; transform:translateX(-102%); transition:.2s; z-index:20; } .sidebar.open { transform:translateX(0); } .mobile-menu { display:inline-flex; } .topbar,.section-title { align-items:flex-start; flex-direction:column; } .actions,.btn { width:100%; } .content { padding:20px 16px 34px; } }
    @media (max-width:580px) { .grid { grid-template-columns:1fr; } }
  </style>
</head>
<body>
<?php if (!$loggedIn): ?>
  <main class="login">
    <form class="login-card" method="post">
      <input type="hidden" name="action" value="login">
      <h1>Dang nhap admin</h1>
      <p class="hint">Mat khau mac dinh: <strong>admin123</strong>. Nen doi bang bien moi truong <code>GEOMETRY_ADMIN_PASSWORD</code> khi dua len hosting.</p>
      <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
      <div class="field">
        <label for="password">Mat khau</label>
        <input id="password" type="password" name="password" required autofocus>
      </div>
      <button class="btn primary" type="submit">Dang nhap</button>
    </form>
  </main>
<?php else: ?>
  <div class="shell">
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="admin.php">
        <img src="./Home/logo.webp" alt="Geometry Dash APK Logo">
        <span><strong><?= h($siteName) ?></strong><span>Admin Panel</span></span>
      </a>
      <nav class="nav">
        <div class="nav-label">Tong quan</div>
        <a class="<?= $section === 'dashboard' ? 'active' : '' ?>" href="admin.php"><span class="icon">DB</span>Dashboard</a>
        <div class="nav-label">Noi dung</div>
        <a class="<?= in_array($section, ['pages','edit-page'], true) ? 'active' : '' ?>" href="admin.php?section=pages"><span class="icon">CM</span>Chuyen muc / Trang</a>
        <a class="<?= $section === 'new-page' ? 'active' : '' ?>" href="admin.php?section=new-page"><span class="icon">NP</span>Them chuyen muc</a>
        <a class="<?= in_array($section, ['blogs','edit-blog'], true) ? 'active' : '' ?>" href="admin.php?section=blogs"><span class="icon">BL</span>Bai blog</a>
        <a class="<?= $section === 'new-blog' ? 'active' : '' ?>" href="admin.php?section=new-blog"><span class="icon">NB</span>Them blog</a>
        <a class="<?= $section === 'ai-blog' ? 'active' : '' ?>" href="admin.php?section=ai-blog"><span class="icon">AI</span>AI dang blog</a>
        <div class="nav-label">Cau hinh</div>
        <a class="<?= $section === 'head-code' ? 'active' : '' ?>" href="admin.php?section=head-code"><span class="icon">HD</span>Code head trang chu</a>
        <a class="<?= $section === 'menu-tools' ? 'active' : '' ?>" href="admin.php?section=menu-tools"><span class="icon">MN</span>Cong cu menu</a>
        <div class="nav-label">Lien ket</div>
        <a href="./" target="_blank"><span class="icon">WE</span>Xem website</a>
        <a href="?logout=1"><span class="icon">LO</span>Dang xuat</a>
      </nav>
    </aside>

    <main>
      <header class="topbar">
        <div>
          <button class="btn mobile-menu" id="menuToggle" type="button">Menu</button>
          <h1>Quan tri noi dung</h1>
          <p>Sua file HTML, tao trang moi va dang bai blog truc tiep trong project.</p>
        </div>
        <div class="actions">
          <a class="btn" href="./" target="_blank">Xem website</a>
          <a class="btn red" href="?logout=1">Dang xuat</a>
        </div>
      </header>

      <div class="content">
        <?php if ($message): ?><div class="notice"><?= h($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>

        <?php if ($section === 'dashboard'): ?>
          <div class="section-title">
            <div>
              <h2>Dashboard</h2>
              <p>Cac chuc nang nay ghi truc tiep vao file trong thu muc website.</p>
            </div>
          </div>
          <div class="grid">
            <div class="card"><span>Trang HTML</span><strong><?= count(list_root_pages()) ?></strong><div class="hint">Root website</div></div>
            <div class="card"><span>Bai blog</span><strong><?= count(list_blog_posts()) ?></strong><div class="hint">Thu muc Blog</div></div>
            <div class="card"><span>Trang index blog</span><strong>1</strong><div class="hint">blog.html</div></div>
            <div class="card"><span>Trang thai</span><strong>OK</strong><div class="hint">Admin co the ghi file</div></div>
          </div>
          <div class="grid-2">
            <div class="panel">
              <div class="panel-head"><h3>Trang gan day</h3><a class="btn primary" href="?section=new-page">Them trang</a></div>
              <div class="table-wrap">
                <table>
                  <thead><tr><th>File</th><th>URL</th><th>Dung luong</th><th>Cap nhat</th><th>Thao tac</th></tr></thead>
                  <tbody>
                    <?php foreach (array_slice(list_root_pages(), 0, 6) as $page): ?>
                      <tr>
                        <td><?= h($page['name']) ?></td>
                        <td><a href="<?= h($page['url']) ?>" target="_blank"><?= h($page['url']) ?></a></td>
                        <td><?= number_format($page['size']) ?> bytes</td>
                        <td><?= h($page['updated']) ?></td>
                      <td>
                        <div class="actions">
                          <a class="btn" href="?edit_page=<?= urlencode($page['name']) ?>">Sua</a>
                          <?php if (!in_array($page['name'], ['index.html', 'blog.html', '404.html'], true)): ?>
                            <form method="post" onsubmit="return confirm('Xoa trang <?= h($page['name']) ?>? File se duoc backup truoc khi xoa.');">
                              <input type="hidden" name="action" value="delete_page">
                              <input type="hidden" name="filename" value="<?= h($page['name']) ?>">
                              <button class="btn red" type="submit">Xoa</button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="panel">
              <div class="panel-head"><h3>Huong dan nhanh</h3></div>
              <div class="panel-body">
                <p><strong>Sua trang:</strong> mo file HTML, sua noi dung trong editor va bam luu.</p>
                <p><strong>Them trang:</strong> tao file moi o root, URL se la <code>/slug</code> nho rule trong <code>.htaccess</code>.</p>
                <p><strong>Them blog:</strong> tao file trong <code>Blog/slug.html</code> va tu them card vao <code>blog.html</code>.</p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($section === 'pages'): ?>
          <div class="section-title">
            <div><h2>Sua trang</h2><p>Danh sach cac file HTML o root website.</p></div>
            <a class="btn primary" href="?section=new-page">Them trang</a>
          </div>
          <div class="panel">
            <div class="table-wrap">
              <table>
                <thead><tr><th>File</th><th>URL</th><th>Dung luong</th><th>Cap nhat</th><th>Thao tac</th></tr></thead>
                <tbody>
                  <?php foreach (list_root_pages() as $page): ?>
                    <tr>
                      <td><?= h($page['name']) ?></td>
                      <td><a href="<?= h($page['url']) ?>" target="_blank"><?= h($page['url']) ?></a></td>
                      <td><?= number_format($page['size']) ?> bytes</td>
                      <td><?= h($page['updated']) ?></td>
                      <td>
                        <div class="actions">
                          <a class="btn" href="?edit_page=<?= urlencode($page['name']) ?>">Sua</a>
                          <?php if (!in_array($page['name'], ['index.html', 'blog.html', '404.html'], true)): ?>
                            <form method="post" onsubmit="return confirm('Xoa trang <?= h($page['name']) ?>? File se duoc backup truoc khi xoa.');">
                              <input type="hidden" name="action" value="delete_page">
                              <input type="hidden" name="filename" value="<?= h($page['name']) ?>">
                              <button class="btn red" type="submit">Xoa</button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($section === 'edit-page'): ?>
          <div class="section-title">
            <div><h2>Sua chuyen muc / trang: <?= h($editPage) ?></h2><p>Soan thao noi dung bang editor truc quan, khong can sua code HTML.</p></div>
            <a class="btn" href="<?= h($editPage === 'index.html' ? './' : './' . preg_replace('/\.html$/', '', $editPage)) ?>" target="_blank">Xem trang</a>
          </div>
          <div class="tabs">
            <button class="btn primary" type="button" data-tab="visualPage">Soan thao</button>
            <button class="btn" type="button" data-tab="rawPage">Sua HTML goc</button>
          </div>
          <form class="tab-panel active" id="visualPage" method="post" data-editor-form>
            <div class="wp-post-shell">
            <div class="panel">
            <div class="panel-body">
              <input type="hidden" name="action" value="save_page_content">
              <input type="hidden" name="filename" value="<?= h($editPage) ?>">
              <input type="hidden" name="content" class="editor-output">
              <div class="field"><input class="wp-title-input" id="pageTitleEdit" name="title" value="<?= h($pageTitle) ?>" placeholder="Enter title here"></div>
              <div class="field"><label for="pageDescriptionEdit">Meta description</label><textarea id="pageDescriptionEdit" name="description"><?= h($pageDescription) ?></textarea></div>
              <div class="field wp-editor-wrap">
                <?= editor_toolbar() ?>
                <div class="wysiwyg" contenteditable="true"><?= $pageMain ?></div>
                <textarea class="source-editor" spellcheck="false"><?= h($pageMain) ?></textarea>
              </div>
            </div>
            </div>
            <aside class="panel publish-box">
              <div class="panel-head"><h3>Publish</h3></div>
              <div class="panel-body">
                <div class="publish-row"><span>Status</span><strong>Published</strong></div>
                <div class="publish-row"><span>Visibility</span><strong>Public</strong></div>
                <div class="publish-row"><span>File</span><strong><?= h($editPage) ?></strong></div>
                <button class="btn primary" type="submit">Update</button>
                <a class="btn" href="?section=pages">Back</a>
              </div>
            </aside>
            </div>
          </form>
          <form class="panel tab-panel" id="rawPage" method="post">
            <div class="panel-body">
              <input type="hidden" name="action" value="save_page">
              <input type="hidden" name="filename" value="<?= h($editPage) ?>">
              <div class="field">
                <label for="pageHtml">HTML</label>
                <textarea class="code" id="pageHtml" name="html" spellcheck="false"><?= h($pageContent) ?></textarea>
              </div>
              <button class="btn primary" type="submit">Luu trang</button>
              <a class="btn" href="?section=pages">Quay lai</a>
            </div>
          </form>
        <?php endif; ?>

        <?php if ($section === 'new-page'): ?>
          <div class="section-title">
            <div><h2>Them chuyen muc / trang moi</h2><p>Tao file HTML moi o thu muc goc website va co the them vao menu.</p></div>
          </div>
          <form method="post" data-editor-form>
            <div class="wp-post-shell">
            <div class="panel">
            <div class="panel-body">
              <input type="hidden" name="action" value="create_page">
              <input type="hidden" name="content" class="editor-output">
              <div class="field"><input class="wp-title-input" id="pageTitle" name="title" required placeholder="Enter title here"></div>
              <div class="field"><label for="pageSlug">Slug URL</label><input id="pageSlug" name="slug" placeholder="vi-du: download-guide"></div>
              <div class="field"><label for="pageDescription">Meta description</label><textarea id="pageDescription" name="description" required></textarea></div>
              <div class="field"><label for="menuLabel">Ten hien thi tren menu</label><input id="menuLabel" name="menu_label" placeholder="Mac dinh lay theo tieu de"></div>
              <div class="field"><label><input type="checkbox" name="add_to_menu" value="1" checked> Them vao menu cac trang public</label></div>
              <div class="field wp-editor-wrap">
                <?= editor_toolbar() ?>
                <div class="wysiwyg" contenteditable="true"><h2>Tieu de section</h2><p>Nhap noi dung cua ban tai day.</p></div>
                <textarea class="source-editor" spellcheck="false"><h2>Tieu de section</h2><p>Nhap noi dung cua ban tai day.</p></textarea>
              </div>
            </div>
            </div>
            <aside class="panel publish-box">
              <div class="panel-head"><h3>Publish</h3></div>
              <div class="panel-body">
                <div class="publish-row"><span>Status</span><strong>Draft</strong></div>
                <div class="publish-row"><span>Visibility</span><strong>Public</strong></div>
                <button class="btn primary" type="submit">Publish</button>
              </div>
            </aside>
            </div>
          </form>
        <?php endif; ?>

        <?php if ($section === 'blogs'): ?>
          <div class="section-title">
            <div><h2>Bai blog</h2><p>Danh sach file bai viet trong thu muc Blog.</p></div>
            <a class="btn primary" href="?section=new-blog">Them bai blog</a>
          </div>
          <div class="panel">
            <div class="table-wrap">
              <table>
                <thead><tr><th>File</th><th>URL</th><th>Dung luong</th><th>Cap nhat</th><th>Thao tac</th></tr></thead>
                <tbody>
                  <?php foreach (list_blog_posts() as $post): ?>
                    <tr>
                      <td><?= h($post['name']) ?></td>
                      <td><a href="<?= h($post['url']) ?>" target="_blank"><?= h($post['url']) ?></a></td>
                      <td><?= number_format($post['size']) ?> bytes</td>
                      <td><?= h($post['updated']) ?></td>
                      <td>
                        <div class="actions">
                          <a class="btn" href="?edit_blog=<?= urlencode($post['name']) ?>">Sua</a>
                          <form method="post" onsubmit="return confirm('Xoa bai blog <?= h($post['name']) ?>? File se duoc backup truoc khi xoa.');">
                            <input type="hidden" name="action" value="delete_blog">
                            <input type="hidden" name="filename" value="<?= h($post['name']) ?>">
                            <button class="btn red" type="submit">Xoa</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!list_blog_posts()): ?>
                    <tr><td colspan="5">Chua co bai blog rieng trong thu muc Blog.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($section === 'edit-blog'): ?>
          <div class="section-title">
            <div><h2>Sua bai blog: <?= h($editBlog) ?></h2><p>Soan thao bai viet bang editor truc quan.</p></div>
            <a class="btn" href="./blog/<?= h(preg_replace('/\.html$/', '', $editBlog)) ?>" target="_blank">Xem bai</a>
          </div>
          <div class="tabs">
            <button class="btn primary" type="button" data-tab="visualBlog">Soan thao</button>
            <button class="btn" type="button" data-tab="rawBlog">Sua HTML goc</button>
          </div>
          <form class="tab-panel active" id="visualBlog" method="post" data-editor-form>
            <div class="wp-post-shell">
            <div class="panel">
            <div class="panel-body">
              <input type="hidden" name="action" value="save_blog_content">
              <input type="hidden" name="filename" value="<?= h($editBlog) ?>">
              <input type="hidden" name="content" class="editor-output">
              <div class="field"><input class="wp-title-input" id="blogTitleEdit" name="title" value="<?= h($blogTitle) ?>" placeholder="Enter title here"></div>
              <div class="field"><label for="blogDescriptionEdit">Meta description</label><textarea id="blogDescriptionEdit" name="description"><?= h($blogDescription) ?></textarea></div>
              <div class="field wp-editor-wrap">
                <?= editor_toolbar() ?>
                <div class="wysiwyg" contenteditable="true"><?= $blogMain ?></div>
                <textarea class="source-editor" spellcheck="false"><?= h($blogMain) ?></textarea>
              </div>
            </div>
            </div>
            <aside class="panel publish-box">
              <div class="panel-head"><h3>Publish</h3></div>
              <div class="panel-body">
                <div class="publish-row"><span>Status</span><strong>Published</strong></div>
                <div class="publish-row"><span>Visibility</span><strong>Public</strong></div>
                <div class="publish-row"><span>File</span><strong><?= h($editBlog) ?></strong></div>
                <button class="btn primary" type="submit">Update</button>
                <a class="btn" href="?section=blogs">Back</a>
              </div>
            </aside>
            </div>
          </form>
          <form class="panel tab-panel" id="rawBlog" method="post">
            <div class="panel-body">
              <input type="hidden" name="action" value="save_blog">
              <input type="hidden" name="filename" value="<?= h($editBlog) ?>">
              <div class="field">
                <label for="blogHtml">HTML</label>
                <textarea class="code" id="blogHtml" name="html" spellcheck="false"><?= h($blogContent) ?></textarea>
              </div>
              <button class="btn primary" type="submit">Luu bai blog</button>
              <a class="btn" href="?section=blogs">Quay lai</a>
            </div>
          </form>
        <?php endif; ?>

        <?php if ($section === 'new-blog'): ?>
          <div class="section-title">
            <div><h2>Them bai blog</h2><p>Tao file bai viet va chen card vao trang blog.</p></div>
          </div>
          <form method="post" data-editor-form>
            <div class="wp-post-shell">
            <div class="panel">
            <div class="panel-body">
              <input type="hidden" name="action" value="create_blog">
              <input type="hidden" name="content" class="editor-output">
              <div class="field"><input class="wp-title-input" id="blogTitle" name="title" required placeholder="Enter title here"></div>
              <div class="field"><label for="blogSlug">Slug URL</label><input id="blogSlug" name="slug" placeholder="vi-du: geometry-dash-tips"></div>
              <div class="field"><label for="blogCategory">Chuyen muc</label><input id="blogCategory" name="category" value="Guide"></div>
              <div class="field"><label for="blogExcerpt">Mo ta ngan</label><textarea id="blogExcerpt" name="excerpt" required></textarea></div>
              <div class="field wp-editor-wrap">
                <?= editor_toolbar() ?>
                <div class="wysiwyg" contenteditable="true"><h2>Heading</h2><p>Noi dung bai viet...</p></div>
                <textarea class="source-editor" spellcheck="false"><h2>Heading</h2><p>Noi dung bai viet...</p></textarea>
              </div>
            </div>
            </div>
            <aside class="panel publish-box">
              <div class="panel-head"><h3>Publish</h3></div>
              <div class="panel-body">
                <div class="publish-row"><span>Status</span><strong>Draft</strong></div>
                <div class="publish-row"><span>Visibility</span><strong>Public</strong></div>
                <button class="btn primary" type="submit">Publish</button>
              </div>
            </aside>
            </div>
          </form>
        <?php endif; ?>

        <?php if ($section === 'ai-blog'): ?>
          <?php
            $aiConfig = get_ai_config();
            $aiQueue = get_ai_queue();
            $apiKeyStatus = getenv('OPENAI_API_KEY') ? 'Dang dung OPENAI_API_KEY tu server' : mask_secret($aiConfig['api_key'] ?? '');
            $cronUrl = 'admin.php?run_ai_queue=1&token=' . urlencode($aiConfig['cron_token'] ?? '');
          ?>
          <div class="section-title">
            <div>
              <h2>AI dang blog</h2>
              <p>Nhap list tieu de, moi dong mot bai. He thong goi OpenAI, tao file trong Blog va chen card vao blog.html.</p>
            </div>
            <form method="post">
              <input type="hidden" name="action" value="run_ai_queue_now">
              <input type="hidden" name="run_limit" value="<?= h($aiConfig['daily_limit'] ?? 2) ?>">
              <button class="btn primary" type="submit">Chay queue ngay</button>
            </form>
          </div>

          <div class="grid-2">
            <form class="panel" method="post">
              <div class="panel-head"><h3>Them tieu de vao queue</h3></div>
              <div class="panel-body">
                <input type="hidden" name="action" value="add_ai_titles">
                <div class="field">
                  <label for="aiTitles">Danh sach tieu de</label>
                  <textarea class="body" id="aiTitles" name="titles" placeholder="How to install Geometry Dash APK safely&#10;Best Geometry Dash beginner tips in 2026" required></textarea>
                  <div class="hint">Moi dong la mot bai viet rieng. Bai se duoc dang theo gioi han moi ngay.</div>
                </div>
                <div class="field">
                  <label for="runLimit">So bai xu ly ngay sau khi them</label>
                  <input id="runLimit" type="number" name="run_limit" min="1" value="1">
                </div>
                <div class="field">
                  <label><input type="checkbox" name="run_now" value="1"> Tao va dang ngay sau khi them</label>
                </div>
                <button class="btn primary" type="submit">Them vao queue</button>
              </div>
            </form>

            <form class="panel" method="post">
              <div class="panel-head"><h3>Cau hinh OpenAI</h3></div>
              <div class="panel-body">
                <input type="hidden" name="action" value="save_ai_config">
                <div class="field">
                  <label for="apiKey">OpenAI API key</label>
                  <input id="apiKey" type="password" name="api_key" placeholder="<?= h($apiKeyStatus) ?>">
                  <div class="hint">Nen dat bien moi truong <code>OPENAI_API_KEY</code>. Neu nhap o day, key duoc luu trong file <code>.admin-ai-config.json</code>.</div>
                </div>
                <div class="field">
                  <label for="aiModel">Model</label>
                  <input id="aiModel" name="model" value="<?= h($aiConfig['model'] ?? 'gpt-5.2') ?>">
                </div>
                <div class="field">
                  <label for="dailyLimit">Gioi han dang moi ngay</label>
                  <input id="dailyLimit" type="number" name="daily_limit" min="1" value="<?= h($aiConfig['daily_limit'] ?? 2) ?>">
                </div>
                <div class="field">
                  <label for="aiCategory">Chuyen muc mac dinh</label>
                  <input id="aiCategory" name="category" value="<?= h($aiConfig['category'] ?? 'Guide') ?>">
                </div>
                <div class="field">
                  <label for="aiLanguage">Ngon ngu bai viet</label>
                  <input id="aiLanguage" name="language" value="<?= h($aiConfig['language'] ?? 'English') ?>">
                </div>
                <div class="field">
                  <label for="siteContext">Huong dan noi dung</label>
                  <textarea id="siteContext" name="site_context"><?= h($aiConfig['site_context'] ?? '') ?></textarea>
                </div>
                <div class="field">
                  <label>Cron URL</label>
                  <input readonly value="<?= h($cronUrl) ?>">
                  <div class="hint">Dat cron tren hosting goi URL nay moi ngay. Moi lan goi se dang toi da so bai con lai trong gioi han ngay.</div>
                </div>
                <button class="btn primary" type="submit">Luu cau hinh</button>
              </div>
            </form>
          </div>

          <div class="panel" style="margin-top:16px;">
            <div class="panel-head"><h3>Queue AI</h3><span class="hint"><?= count($aiQueue) ?> muc</span></div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Tieu de</th><th>Trang thai</th><th>Slug</th><th>Ngay tao</th><th>Loi</th><th>Thao tac</th></tr></thead>
                <tbody>
                  <?php foreach (array_reverse($aiQueue) as $item): ?>
                    <tr>
                      <td><?= h($item['title'] ?? '') ?></td>
                      <td><span class="status <?= h($item['status'] ?? 'pending') ?>"><?= h($item['status'] ?? 'pending') ?></span></td>
                      <td>
                        <?php if (!empty($item['slug'])): ?>
                          <a href="./blog/<?= h($item['slug']) ?>" target="_blank"><?= h($item['slug']) ?></a>
                        <?php endif; ?>
                      </td>
                      <td><?= h(substr((string) ($item['created_at'] ?? ''), 0, 16)) ?></td>
                      <td><?= h($item['error'] ?? '') ?></td>
                      <td>
                        <?php if (in_array(($item['status'] ?? ''), ['error', 'running'], true)): ?>
                          <form method="post">
                            <input type="hidden" name="action" value="retry_ai_item">
                            <input type="hidden" name="id" value="<?= h($item['id'] ?? '') ?>">
                            <button class="btn" type="submit">Thu lai</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$aiQueue): ?>
                    <tr><td colspan="6">Chua co tieu de nao trong queue AI.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($section === 'head-code'): ?>
          <div class="section-title">
            <div>
              <h2>Code trong the head trang chu</h2>
              <p>Dan ma Google Tag Manager, Adsense, Search Console, pixel hoac script khac vao day.</p>
            </div>
            <a class="btn" href="./" target="_blank">Xem trang chu</a>
          </div>
          <form class="panel" method="post">
            <div class="panel-body">
              <input type="hidden" name="action" value="save_home_head_code">
              <div class="field">
                <label for="headCode">Code chen truoc the &lt;/head&gt; cua index.html</label>
                <textarea class="code" id="headCode" name="head_code" spellcheck="false" placeholder="<!-- Google tag / Adsense / verification code -->"><?= h(get_home_head_code()) ?></textarea>
                <div class="hint">Admin se luu code trong block <code>ADMIN HEAD CODE START/END</code> de lan sau sua lai khong bi trung lap.</div>
              </div>
              <button class="btn primary" type="submit">Luu code head</button>
            </div>
          </form>
        <?php endif; ?>

        <?php if ($section === 'menu-tools'): ?>
          <div class="section-title">
            <div>
              <h2>Cong cu menu</h2>
              <p>Su dung khi da xoa file trang nhung link chuyen muc van con tren menu do HTML cu hoac cache.</p>
            </div>
          </div>
          <form class="panel" method="post">
            <div class="panel-body">
              <input type="hidden" name="action" value="remove_menu_slug">
              <div class="field">
                <label for="menuSlug">Slug can go khoi menu</label>
                <input id="menuSlug" name="slug" placeholder="vi-du: abc" required>
                <div class="hint">Nhap slug khong co dau slash. Vi du link la <code>/abc</code> thi nhap <code>abc</code>.</div>
              </div>
              <button class="btn red" type="submit" onclick="return confirm('Go slug nay khoi menu cac trang public?')">Go link menu</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </main>
  </div>
  <script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
      menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    document.querySelectorAll('[data-tab]').forEach((button) => {
      button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.tab);
        if (!target) return;
        document.querySelectorAll('[data-tab]').forEach((item) => item.classList.remove('primary'));
        document.querySelectorAll('.tab-panel').forEach((panel) => panel.classList.remove('active'));
        button.classList.add('primary');
        target.classList.add('active');
      });
    });

    document.querySelectorAll('[data-toolbar]').forEach((toolbar) => {
      toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) return;
        const wrap = toolbar.closest('.wp-editor-wrap') || toolbar.parentElement;
        const editor = wrap.querySelector('.wysiwyg');
        const source = wrap.querySelector('.source-editor');
        if (!editor) return;
        editor.focus();

        if (button.dataset.cmd) {
          document.execCommand(button.dataset.cmd, false, null);
        }

        if (button.dataset.format) {
          document.execCommand('formatBlock', false, button.dataset.format);
        }

        if (button.dataset.action === 'link') {
          const url = prompt('Nhap URL lien ket:');
          if (url) {
            const selection = window.getSelection();
            if (!selection || selection.toString().trim() === '') {
              document.execCommand('insertHTML', false, '<a href="' + url + '">' + url + '</a>');
            } else {
              document.execCommand('createLink', false, url);
            }
          }
        }

        if (button.dataset.action === 'image') {
          const url = prompt('Nhap URL anh:');
          if (url) document.execCommand('insertImage', false, url);
        }

        if (button.dataset.action === 'foreColor') {
          const color = prompt('Nhap ma mau chu, vi du #ffcc3f:');
          if (color) document.execCommand('foreColor', false, color);
        }

        if (button.dataset.action === 'backColor') {
          const color = prompt('Nhap ma mau nen, vi du #fff3cd:');
          if (color) document.execCommand('backColor', false, color);
        }

        if (button.dataset.action === 'source' && source) {
          source.value = editor.innerHTML.trim();
          wrap.classList.toggle('editor-source-mode');
          const isSource = wrap.classList.contains('editor-source-mode');
          wrap.querySelectorAll('.wp-tab').forEach((tab) => tab.classList.toggle('active', !isSource));
          const htmlTab = wrap.querySelector('.wp-tab[data-action="source"]');
          if (htmlTab) htmlTab.classList.toggle('active', isSource);
        }
      });
    });

    document.querySelectorAll('.add-media').forEach((button) => {
      button.addEventListener('click', () => {
        const wrap = button.closest('.wp-editor-wrap');
        const editor = wrap ? wrap.querySelector('.wysiwyg') : null;
        const url = prompt('Nhap URL anh/media:');
        if (editor && url) {
          editor.focus();
          document.execCommand('insertImage', false, url);
        }
      });
    });

    document.querySelectorAll('.wp-tab').forEach((tab) => {
      tab.addEventListener('click', () => {
        const wrap = tab.closest('.wp-editor-wrap');
        if (!wrap) return;
        const editor = wrap.querySelector('.wysiwyg');
        const source = wrap.querySelector('.source-editor');
        if (!editor || !source) return;
        const sourceMode = tab.dataset.action === 'source';
        if (sourceMode) {
          source.value = editor.innerHTML.trim();
        } else {
          editor.innerHTML = source.value;
        }
        wrap.classList.toggle('editor-source-mode', sourceMode);
        wrap.querySelectorAll('.wp-tab').forEach((item) => item.classList.remove('active'));
        tab.classList.add('active');
      });
    });

    document.querySelectorAll('.source-editor').forEach((source) => {
      source.addEventListener('input', () => {
        const wrap = source.closest('.wp-editor-wrap');
        const editor = wrap ? wrap.querySelector('.wysiwyg') : null;
        if (editor) editor.innerHTML = source.value;
      });
    });

    document.querySelectorAll('[data-editor-form]').forEach((form) => {
      form.addEventListener('submit', () => {
        const editor = form.querySelector('.wysiwyg');
        const wrap = form.querySelector('.wp-editor-wrap');
        const source = form.querySelector('.source-editor');
        const output = form.querySelector('.editor-output');
        if (editor && output) {
          output.value = wrap && wrap.classList.contains('editor-source-mode') && source
            ? source.value.trim()
            : editor.innerHTML.trim();
        }
      });
    });
  </script>
<?php endif; ?>
</body>
</html>
