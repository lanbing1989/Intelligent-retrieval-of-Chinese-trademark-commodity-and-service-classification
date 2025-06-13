<?php
require_once __DIR__.'/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 查询所有大类
$cats = $pdo->query("SELECT * FROM category ORDER BY code+0")->fetchAll(PDO::FETCH_ASSOC);
// 查询所有小类
$subs = $pdo->query("SELECT * FROM subclass ORDER BY code+0")->fetchAll(PDO::FETCH_ASSOC);
// 按category_id分组小类
$subsByCat = [];
foreach($subs as $s) {
    $subsByCat[$s['category_id']][] = $s;
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>所有分类目录 - 中国商标商品服务分类</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="static/style.css">
    <style>
    body { background: #f5f7fa; margin: 0; }
    .main-container { max-width: 950px; margin: 0 auto; padding: 24px 12px 40px 12px;}
    h1 { text-align:center; font-size:2em; font-weight:700; margin:32px 0 24px 0;}
    .class-nav { margin: 0 0 24px 0; text-align: center;}
    .class-nav a { display:inline-block; margin:0 6px 8px 6px; padding:3px 14px; background:#e9f1fc; border-radius:7px; color:#1976d2; font-weight:600; text-decoration:none; transition: background 0.16s;}
    .class-nav a:hover { background:#1976d2; color:#fff; }
    .cat-block { background: #fff; border-radius: 12px; margin-bottom: 32px; padding: 20px 26px; box-shadow:0 2px 12px #e3e8f8;}
    .cat-title { font-weight: 700; font-size: 1.16em; color: #1976d2; margin-bottom: 12px;}
    .cat-desc { color: #263753; font-size:1.09em; margin-bottom: 13px; font-weight:600;}
    .subclass-list { list-style: none; margin: 0; padding: 0;}
    .subclass-link { cursor: pointer; color: #1976d2; text-decoration:underline; font-weight:500; margin-right:18px;}
    .subclass-link:hover { color: #388be6; }
    .subclass-detail { display: none; background: #f8fafd; margin: 8px 0 14px 38px; border-radius: 8px; padding: 13px 18px; color:#333; }
    .back-to-top {
        position: fixed;
        right: 32px;
        bottom: 52px;
        z-index: 999;
        background: #1976d2;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        font-size: 2em;
        box-shadow: 0 2px 8px rgba(30,80,180,0.12);
        display: none;
        cursor: pointer;
        transition: background 0.18s, color 0.18s, opacity 0.2s;
        opacity: 0.88;
    }
    .back-to-top:hover {
        background: #388be6;
        color: #fff;
        opacity: 1;
    }
    .footer-wrap {
        width: 100%;
        background: none;
        display: flex;
        justify-content: center;
        align-items: center;
        border: none;
        margin: 0;
        padding: 0;
        flex-shrink: 0;
        position: relative;
        z-index: 2;
    }
    .footer {
        max-width: 820px;
        width: 100%;
        margin: 0 auto;
        padding: 18px 0 18px 0;
        color: #6c7a96;
        background: #f6f8fb;
        border-top: 1px solid #e0e3e8;
        text-align: center;
        font-size: 1.06em;
        box-shadow: 0 -2px 10px 0 rgba(30,80,180,0.03);
        border-radius: 0 0 12px 12px;
        letter-spacing: 0.01em;
        user-select: none;
    }
    .footer .footer-icon {
        font-size: 1.12em;
        margin-right: 7px;
        opacity: 0.82;
        vertical-align: text-bottom;
    }
    .footer .footer-link {
        color: #1976d2;
        text-decoration: underline dotted;
        margin-left: 5px;
        font-weight: 400;
        transition: color 0.15s;
    }
    .footer .footer-link:hover {
        color: #388be6;
        text-decoration: underline;
    }
    @media (max-width: 900px) {
        .footer, .main-container { max-width: 99vw; }
        .back-to-top { right: 12px; bottom: 12px; width: 40px; height: 40px; font-size: 1.4em;}
    }
    </style>
</head>
<body>
<div class="main-container">
    <h1>中国商标商品服务分类 - 所有分类目录</h1>
    <div class="class-nav">
        <?php foreach($cats as $cat): ?>
            <a href="#cat-<?php echo htmlspecialchars($cat['code']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a>
        <?php endforeach; ?>
    </div>
    <?php foreach($cats as $cat): ?>
        <div class="cat-block" id="cat-<?php echo htmlspecialchars($cat['code']); ?>">
            <div class="cat-title">【<?php echo htmlspecialchars($cat['name']); ?>】</div>
            <?php if (!empty($cat['description'])): ?>
                <div class="cat-desc"><?php echo nl2br(htmlspecialchars($cat['description'])); ?></div>
            <?php endif; ?>
            <ul class="subclass-list">
                <?php if(isset($subsByCat[$cat['id']])): ?>
                    <?php foreach($subsByCat[$cat['id']] as $sub): ?>
                        <li>
                            <span class="subclass-link" onclick="toggleDetail('<?php echo $cat['code'].'_'.$sub['code']; ?>')">
                                [<?php echo htmlspecialchars($sub['code']); ?>组] <?php echo htmlspecialchars($sub['title']); ?>
                            </span>
                            <div class="subclass-detail" id="detail-<?php echo $cat['code'].'_'.$sub['code']; ?>">
                                <?php echo nl2br(htmlspecialchars($sub['content'])); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="color:#999;font-size:0.98em;">暂无小类</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>
<!-- 返回顶部按钮 -->
<button class="back-to-top" id="backToTopBtn" title="返回顶部">↑</button>
<div class="footer-wrap">
  <footer class="footer">
    <span class="footer-icon">📚</span>
    基于 <span style="font-weight:600;">“类似商品和服务区分表——基于尼斯分类第十二版（2024文本）”</span> 构建
    <a class="footer-link" href="https://sbj.cnipa.gov.cn/sbj/sbsq/sphfwfl/" target="_blank" rel="noopener">尼斯国际分类</a>
  </footer>
</div>
<script>
function toggleDetail(id) {
    let detail = document.getElementById('detail-' + id);
    if (detail) {
        detail.style.display = (detail.style.display === 'block' ? 'none' : 'block');
    }
}
// 返回顶部按钮
const backToTopBtn = document.getElementById('backToTopBtn');
window.onscroll = function() {
    if (window.scrollY > 400) {
        backToTopBtn.style.display = "block";
    } else {
        backToTopBtn.style.display = "none";
    }
};
backToTopBtn.onclick = function() {
    window.scrollTo({top: 0, behavior: 'smooth'});
};
</script>
</body>
</html>