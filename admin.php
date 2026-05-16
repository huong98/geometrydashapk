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

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'new-page';
}

function safe_root_html_path($filename) {
    global $baseDir;
    $filename = basename((string) $filename);
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
  <link rel="stylesheet" href="../Blog/style.css">
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

    if ($action === 'create_page') {
        $slug = slugify($_POST['slug'] ?: $_POST['title']);
        $target = $baseDir . DIRECTORY_SEPARATOR . $slug . '.html';
        if (file_exists($target)) {
            $error = 'Trang da ton tai: ' . $slug . '.html';
        } else {
            $html = page_template($_POST['title'] ?? 'New Page', $_POST['description'] ?? '', $_POST['content'] ?? '');
            file_put_contents($target, $html);
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
}

$section = $_GET['section'] ?? 'dashboard';
$editPage = $_GET['edit_page'] ?? '';
$editBlog = $_GET['edit_blog'] ?? '';
$pageContent = '';
$blogContent = '';

if ($loggedIn && $editPage) {
    $path = safe_root_html_path($editPage);
    if ($path) {
        $section = 'edit-page';
        $pageContent = file_get_contents($path);
    }
}

if ($loggedIn && $editBlog) {
    $path = safe_blog_html_path($editBlog);
    if ($path) {
        $section = 'edit-blog';
        $blogContent = file_get_contents($path);
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
    .field { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
    label { font-size:13px; color:#334155; font-weight:700; }
    input,textarea,select { border:1px solid var(--line); border-radius:8px; background:#fff; color:var(--text); min-height:40px; padding:9px 11px; outline:none; width:100%; }
    textarea.code { min-height:620px; font-family:Consolas,Monaco,monospace; font-size:13px; line-height:1.45; white-space:pre; }
    textarea.body { min-height:240px; }
    input:focus,textarea:focus,select:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(245,180,0,.18); }
    .notice { padding:12px 14px; border-radius:8px; margin-bottom:16px; border:1px solid #bbf7d0; background:#dcfce7; color:#166534; font-weight:700; }
    .error { border-color:#fecaca; background:#fee2e2; color:#991b1b; }
    .hint { color:var(--muted); font-size:13px; margin-top:6px; }
    .mobile-menu { display:none; }
    @media (max-width:1100px) { .grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .grid-2 { grid-template-columns:1fr; } }
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
        <a class="<?= in_array($section, ['pages','edit-page'], true) ? 'active' : '' ?>" href="admin.php?section=pages"><span class="icon">PG</span>Sua trang</a>
        <a class="<?= $section === 'new-page' ? 'active' : '' ?>" href="admin.php?section=new-page"><span class="icon">NP</span>Them trang</a>
        <a class="<?= in_array($section, ['blogs','edit-blog'], true) ? 'active' : '' ?>" href="admin.php?section=blogs"><span class="icon">BL</span>Bai blog</a>
        <a class="<?= $section === 'new-blog' ? 'active' : '' ?>" href="admin.php?section=new-blog"><span class="icon">NB</span>Them blog</a>
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
                        <td><a class="btn" href="?edit_page=<?= urlencode($page['name']) ?>">Sua</a></td>
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
                      <td><a class="btn" href="?edit_page=<?= urlencode($page['name']) ?>">Sua HTML</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($section === 'edit-page'): ?>
          <div class="section-title">
            <div><h2>Sua trang: <?= h($editPage) ?></h2><p>Editor nay sua truc tiep toan bo file HTML.</p></div>
            <a class="btn" href="<?= h($editPage === 'index.html' ? './' : './' . preg_replace('/\.html$/', '', $editPage)) ?>" target="_blank">Xem trang</a>
          </div>
          <form class="panel" method="post">
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
            <div><h2>Them trang moi</h2><p>Tao file HTML moi o thu muc goc website.</p></div>
          </div>
          <form class="panel" method="post">
            <div class="panel-body">
              <input type="hidden" name="action" value="create_page">
              <div class="field"><label for="pageTitle">Tieu de</label><input id="pageTitle" name="title" required></div>
              <div class="field"><label for="pageSlug">Slug URL</label><input id="pageSlug" name="slug" placeholder="vi-du: download-guide"></div>
              <div class="field"><label for="pageDescription">Meta description</label><textarea id="pageDescription" name="description" required></textarea></div>
              <div class="field"><label for="pageContent">Noi dung HTML</label><textarea class="body" id="pageContent" name="content" placeholder="<h2>Section title</h2><p>Noi dung...</p>"></textarea></div>
              <button class="btn primary" type="submit">Tao trang</button>
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
                      <td><a class="btn" href="?edit_blog=<?= urlencode($post['name']) ?>">Sua HTML</a></td>
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
            <div><h2>Sua bai blog: <?= h($editBlog) ?></h2><p>Editor nay sua truc tiep toan bo file HTML.</p></div>
            <a class="btn" href="./blog/<?= h(preg_replace('/\.html$/', '', $editBlog)) ?>" target="_blank">Xem bai</a>
          </div>
          <form class="panel" method="post">
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
          <form class="panel" method="post">
            <div class="panel-body">
              <input type="hidden" name="action" value="create_blog">
              <div class="field"><label for="blogTitle">Tieu de</label><input id="blogTitle" name="title" required></div>
              <div class="field"><label for="blogSlug">Slug URL</label><input id="blogSlug" name="slug" placeholder="vi-du: geometry-dash-tips"></div>
              <div class="field"><label for="blogCategory">Chuyen muc</label><input id="blogCategory" name="category" value="Guide"></div>
              <div class="field"><label for="blogExcerpt">Mo ta ngan</label><textarea id="blogExcerpt" name="excerpt" required></textarea></div>
              <div class="field"><label for="blogContent">Noi dung HTML</label><textarea class="body" id="blogContent" name="content" placeholder="<h2>Heading</h2><p>Noi dung bai viet...</p>"></textarea></div>
              <button class="btn primary" type="submit">Dang bai blog</button>
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
  </script>
<?php endif; ?>
</body>
</html>
