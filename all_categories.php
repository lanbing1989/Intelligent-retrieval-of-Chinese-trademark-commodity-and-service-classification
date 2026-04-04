<?php
/**
 * 所有分类目录页面 - 服务端渲染版本
 */

require_once __DIR__.'/config.php';

try {
    $pdo = getDB();
    
    // 获取所有大类
    $cats = $pdo->query("SELECT * FROM category ORDER BY CAST(code AS UNSIGNED) ASC")->fetchAll();
    
    // 获取所有小类
    $subs = $pdo->query("SELECT * FROM subclass ORDER BY CAST(code AS UNSIGNED) ASC")->fetchAll();
    
    // 按 category_id 分组小类
    $subsByCat = [];
    foreach ($subs as $s) {
        $subsByCat[$s['category_id']][] = $s;
    }
    
} catch (Exception $e) {
    die('数据库错误: ' . $e->getMessage());
}

// HTML 转义函数
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="中国商标商品服务分类完整目录，基于尼斯分类第十二版">
    <title>所有分类目录 - 中国商标商品服务分类</title>
    <link rel="stylesheet" href="static/style.css?v=2">
    <style>
        body { background: #f5f7fa; margin: 0; }
        .main-container { max-width: 950px; margin: 0 auto; padding: 24px 12px 40px 12px; }
        h1 { text-align: center; font-size: 2em; font-weight: 700; margin: 32px 0 24px 0; }
        h1 a { color: inherit; text-decoration: none; }
        
        .search-box { margin-bottom: 24px; }
        .search-box input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 28px;
            font-size: 1rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .search-box input:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
        }
        
        .category-nav {
            position: sticky;
            top: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 100;
        }
        .category-nav-title {
            font-size: 0.9rem;
            color: #757575;
            margin-bottom: 12px;
            font-weight: 500;
        }
        .category-nav-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 120px;
            overflow-y: auto;
        }
        .category-nav-item {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background: #f5f5f5;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
        }
        .category-nav-item:hover {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .category-block {
            background: #fff;
            border-radius: 12px;
            margin-bottom: 32px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            scroll-margin-top: 160px;
        }
        .category-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e8eaf6;
        }
        .category-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1976d2;
            opacity: 0.3;
            line-height: 1;
        }
        .category-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 8px;
        }
        .category-description {
            color: #616161;
            font-size: 0.95rem;
            line-height: 1.7;
            white-space: pre-line;
        }
        
        .subclass-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 12px;
        }
        .subclass-item {
            background: #f5f7fa;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid transparent;
        }
        .subclass-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .subclass-header:hover {
            background: #e3f2fd;
        }
        .subclass-code {
            font-family: monospace;
            font-size: 0.85rem;
            color: #1976d2;
            font-weight: 600;
            background: rgba(25, 118, 210, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }
        .subclass-title {
            font-weight: 600;
            color: #212121;
            font-size: 0.95rem;
            flex: 1;
        }
        .expand-icon {
            color: #9e9e9e;
            transition: transform 0.2s;
        }
        .subclass-item.expanded .expand-icon {
            transform: rotate(180deg);
        }
        .subclass-detail {
            display: none;
            padding: 0 16px 16px;
            color: #424242;
            font-size: 0.9rem;
            line-height: 1.8;
            white-space: pre-line;
        }
        .subclass-item.expanded .subclass-detail {
            display: block;
        }
        .subclass-detail-content {
            background: #fff;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #1976d2;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #757575;
        }
        
        .footer-wrap {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }
        .footer {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            padding: 24px;
            color: #757575;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 0.95rem;
            border-radius: 12px 12px 0 0;
        }
        .footer-link {
            color: #1976d2;
            text-decoration: none;
            margin-left: 8px;
        }
        
        .back-to-top {
            position: fixed;
            right: 24px;
            bottom: 24px;
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        @media (max-width: 768px) {
            .category-nav { position: relative; top: auto; }
            .subclass-grid { grid-template-columns: 1fr; }
            .category-header { flex-direction: column; gap: 8px; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <h1><a href="index.php">中国商标商品服务分类</a></h1>
        
        <!-- 搜索框 -->
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="在此页面内搜索小类..." autocomplete="off">
        </div>
        
        <!-- 分类导航 -->
        <div class="category-nav">
            <div class="category-nav-title">快速导航</div>
            <div class="category-nav-list">
                <?php foreach ($cats as $cat): ?>
                    <a href="#cat-<?php echo e($cat['code']); ?>" class="category-nav-item">
                        第<?php echo e($cat['code']); ?>类
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 分类内容 -->
        <div id="categoriesContainer">
            <?php foreach ($cats as $cat): ?>
                <div class="category-block" id="cat-<?php echo e($cat['code']); ?>" data-name="<?php echo e($cat['name']); ?>">
                    <div class="category-header">
                        <div class="category-number"><?php echo e($cat['code']); ?></div>
                        <div class="category-info">
                            <div class="category-title"><?php echo e($cat['name']); ?></div>
                            <?php if (!empty($cat['description'])): ?>
                                <div class="category-description"><?php echo nl2br(e($cat['description'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="subclass-grid">
                        <?php if (isset($subsByCat[$cat['id']])): ?>
                            <?php foreach ($subsByCat[$cat['id']] as $sub): ?>
                                <div class="subclass-item" data-title="<?php echo e($sub['title']); ?>" data-code="<?php echo e($sub['code']); ?>">
                                    <div class="subclass-header" onclick="toggleDetail(this)">
                                        <span class="subclass-code"><?php echo e($sub['code']); ?></span>
                                        <span class="subclass-title"><?php echo e($sub['title']); ?></span>
                                        <span class="expand-icon">▼</span>
                                    </div>
                                    <div class="subclass-detail">
                                        <div class="subclass-detail-content">
                                            <?php echo nl2br(e($sub['content'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:#999;font-size:0.98em;">暂无小类</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="no-results" id="noResults" style="display:none;">
            <div style="font-size:4rem;margin-bottom:16px;opacity:0.5;">🔍</div>
            <h3>未找到匹配的分类</h3>
            <p>请尝试其他关键词</p>
        </div>
    </div>
    
    <!-- 返回顶部 -->
    <button class="back-to-top" id="backToTop" onclick="scrollToTop()">↑</button>
    
    <!-- 页脚 -->
    <div class="footer-wrap">
        <footer class="footer">
            <span style="font-size:1.2rem;margin-right:8px;">📚</span>
            基于 <strong>"类似商品和服务区分表——基于尼斯分类第十二版（2024文本）"</strong> 构建<br>
            <a class="footer-link" href="index.php">返回搜索</a>
            <a class="footer-link" href="https://sbj.cnipa.gov.cn/sbj/sbsq/sphfwfl/" target="_blank" rel="noopener">商标局官网</a>
            <a class="footer-link" href="http://jinghong.me/yyzz.jpg" target="_blank" rel="noopener">营业执照</a>
            <a class="footer-link" href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=37083002370893" target="_blank" rel="noopener">鲁公网安备37083002370893号</a>
            <a class="footer-link" href="https://beian.miit.gov.cn/" target="_blank" rel="noopener">鲁ICP备2026012160号-2</a>
        </footer>
    </div>

<script>
// 切换小类详情显示
function toggleDetail(header) {
    const item = header.parentElement;
    const isExpanded = item.classList.contains('expanded');
    
    // 只切换当前项，不关闭其他项
    item.classList.toggle('expanded', !isExpanded);
}

// 页面内搜索
const searchInput = document.getElementById('searchInput');
const noResults = document.getElementById('noResults');

searchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase().trim();
    const blocks = document.querySelectorAll('.category-block');
    let hasResults = false;
    
    blocks.forEach(block => {
        const items = block.querySelectorAll('.subclass-item');
        let blockHasMatch = false;
        
        items.forEach(item => {
            const title = item.dataset.title.toLowerCase();
            const code = item.dataset.code.toLowerCase();
            const match = title.includes(filter) || code.includes(filter);
            item.style.display = match ? '' : 'none';
            if (match) blockHasMatch = true;
        });
        
        block.style.display = blockHasMatch || !filter ? '' : 'none';
        if (blockHasMatch || !filter) hasResults = true;
    });
    
    noResults.style.display = hasResults ? 'none' : 'block';
});

// 返回顶部
const backToTopBtn = document.getElementById('backToTop');
window.addEventListener('scroll', function() {
    if (window.scrollY > 400) {
        backToTopBtn.classList.add('visible');
    } else {
        backToTopBtn.classList.remove('visible');
    }
});

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// 平滑滚动
document.querySelectorAll('.category-nav-item').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>
