<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>中国商标商品服务分类智能检索</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="static/style.css">
    <style>
    body {
        min-height: 100vh;
        background: #f5f7fa;
        margin: 0;
        display: flex;
        flex-direction: column;
    }
    .main-container {
        max-width: 820px;
        margin: 0 auto;
        padding: 0 12px 40px 12px;
        flex: 1 0 auto;
        position: relative;
        z-index: 1;
    }
    .center-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 65vh;
        margin-bottom: 32px;
    }
    .search-bar {
        display: flex;
        justify-content: center;
        width: 100%;
        margin: 0 auto 24px auto;
    }
    .search-bar input {
        width: 510px;
        max-width: 95vw;
        font-size: 1.16em;
        padding: 1.2em 1.2em;
        border-radius: 24px 0 0 24px;
        border: 1px solid #1976d2;
        outline: none;
        box-sizing: border-box;
        background: #fff;
        transition: border-color 0.2s;
    }
    .search-bar button {
        border-radius: 0 24px 24px 0;
        border: 1px solid #1976d2;
        border-left: none;
        padding: 1.2em 2.4em;
        background: #1976d2;
        color: #fff;
        font-size: 1.16em;
        cursor: pointer;
        transition: background 0.2s;
    }
    .search-bar button:hover {
        background: #1257a6;
    }
    h1 {
        font-size: 2.2em;
        font-weight: 700;
        text-align: center;
        margin: 36px 0 28px 0;
        letter-spacing: 0.03em;
    }
    h1 a {
        color: #222;
        text-decoration: none;
        transition: color 0.18s;
        border-radius: 6px;
        padding: 2px 10px;
    }
    h1 a:hover {
        background: #1976d2;
        color: #fff;
    }
    .view-toggle { display: flex; gap: 12px; justify-content: flex-end; margin-bottom: 10px;}
    .toggle-btn { border: 1px solid #1976d2; background: #fff; color: #1976d2; border-radius: 8px; padding: 6px 18px; cursor:pointer;}
    .toggle-btn.active, .toggle-btn:hover { background: #1976d2; color: #fff; }
    .tradetable { border-collapse:collapse; width:100%; background:#fff; border-radius: 12px; overflow: hidden;}
    .tradetable th,.tradetable td{ border:1px solid #e3e6ef;padding:10px 12px; font-size:1.03em;}
    .tradetable th{ background:#f3f6fa;}
    .tradetable tr:nth-child(even){ background:#f8fafd;}
    .tradetable td.marked{ background:#fffbe8;}
    .highlight{background:#ffe066; color:#222; border-radius:2px;}
    .subclass-card {
        background: #f3f6fa;
        border-radius: 7px;
        margin-bottom: 14px;
        padding: 10px 15px;
        font-size: 1.07em;
        color: #235;
        line-height: 1.7;
    }
    .modal-backdrop {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.18);
        z-index: 999;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background: #fff;
        border-radius: 9px;
        padding: 28px 26px 18px 26px;
        max-width: 540px;
        width: 95vw;
        box-shadow: 0 6px 32px #b7bfda44;
        position: relative;
        text-align: left;
    }
    .modal-content h2 {
        font-size: 1.23em;
        font-weight: bold;
        margin: 0 0 9px 0;
    }
    .cat-badge {
        display: inline-block;
        padding: 2px 10px;
        background: #e9f1fc;
        color: #1976d2;
        border-radius: 6px;
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 1.06em;
    }
    .detail-body {
        margin-top: 6px;
        margin-bottom: 14px;
        color: #234;
        font-size: 1.06em;
        line-height: 1.8;
        word-break: break-all;
    }
    .close-btn {
        position: absolute;
        top: 10px;
        right: 14px;
        border: none;
        background: #f5f7fa;
        border-radius: 6px;
        padding: 5px 15px;
        color: #888;
        cursor: pointer;
        font-size: 1.1em;
        transition: background 0.2s;
    }
    .close-btn:hover { background: #1976d2; color: #fff; }
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
        .search-bar input { width: 90vw; }
        .modal-content { max-width:96vw;}
    }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="center-box" id="centerBox">
            <h1>
                <a href="index.php" title="返回首页">中国商标商品服务分类智能检索</a>
            </h1>
            <form class="search-bar" id="searchForm" autocomplete="off">
                <input id="searchInput" placeholder="请输入商品/服务/大类/小类关键词…" autofocus>
                <button type="submit">搜索</button>
            </form>
        </div>
        <div class="view-toggle" style="display:none;">
            <button type="button" class="toggle-btn active" id="cardModeBtn">卡片模式</button>
            <button type="button" class="toggle-btn" id="tableModeBtn">表格模式</button>
        </div>
        <div class="stats-panel" id="statsPanel" style="display:none;"></div>
        <div id="resultList"></div>
    </div>
    <div class="modal-backdrop" id="detailModal" style="display:none;">
        <div class="modal-content" onclick="event.stopPropagation();">
            <h2 id="modalTitle"></h2>
            <div class="cat-badge" id="modalCat"></div>
            <div class="detail-body" id="modalContent"></div>
            <button class="close-btn" onclick="closeModal()">关闭</button>
        </div>
    </div>
    <div class="footer-wrap">
      <footer class="footer">
        <span class="footer-icon">📚</span>
        基于 <span style="font-weight:600;">“类似商品和服务区分表——基于尼斯分类第十二版（2024文本）”</span> 构建
        <a class="footer-link" href="https://sbj.cnipa.gov.cn/sbj/sbsq/sphfwfl/" target="_blank" rel="noopener">尼斯国际分类</a>
      </footer>
    </div>
<script>
const qs = s => document.querySelector(s);
let currentResults = [], currentStats = {}, currentMode = localStorage.getItem('viewMode') || 'card';
let currentKeywords = []; // 分词高亮数组
let isFirstSearch = true;

// 渲染统计信息
function renderStats(stats) {
    if (!stats || !stats.total) {
        qs("#statsPanel").style.display = "none";
        return;
    }
    let cats = Array.isArray(stats.categories) ? stats.categories : [];
    let html = `共找到 <b>${stats.total}</b> 条结果，涉及大类：`;
    cats.forEach(cat => html += `<span class="cat-tag">${cat}</span>`);
    qs("#statsPanel").innerHTML = html;
    qs("#statsPanel").style.display = "";
}

// 高亮函数：keywords为数组
function highlightAll(text, keywords) {
    if(!keywords || !keywords.length) return text;
    let pattern = new RegExp('(' + keywords.map(w => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|') + ')', 'gi');
    return text.replace(pattern, '<span class="highlight">$1</span>');
}

// 卡片模式渲染
function renderCards(results) {
    let html = '<div class="card-list">';
    if(Array.isArray(results) && results.length) {
        results.forEach(item => {
            html += `
            <div class="trademark-card">
                <div class="card-header">
                    <span class="cat-badge">${highlightAll(item.category_highlight, currentKeywords)}</span>
                    <span class="code">${item.code}</span>
                    <span class="title">${highlightAll(item.title_highlight, currentKeywords)}</span>
                </div>
                <div class="excerpt">${highlightAll(item.content_excerpt, currentKeywords)}</div>
                <button class="detail-btn" onclick="openDetail('${item.id}', '${encodeURIComponent(qs('#searchInput').value)}')">查看详情</button>
            </div>
            `;
        });
        html += '</div>';
        qs("#resultList").innerHTML = html;
    } else {
        qs("#resultList").innerHTML = "<div style='margin:2em auto;color:#888;text-align:center;'>未找到结果，请尝试其他关键词。</div>";
    }
}

// 表格模式渲染
function renderTable(results) {
    let html = `<table class="tradetable">
    <thead><tr><th>类别</th><th>小类代码</th><th>小类名称</th><th>商品／服务项</th></tr></thead>
    <tbody>`;
    if(Array.isArray(results) && results.length) {
        results.forEach(item => {
            html += `<tr>
                <td>${highlightAll(item.category_highlight, currentKeywords)}</td>
                <td>${item.code}</td>
                <td>${highlightAll(item.title_highlight, currentKeywords)}</td>
                <td>${highlightAll(item.content, currentKeywords)}</td>
            </tr>`;
        });
    } else {
        html += `<tr><td colspan="4" style="text-align:center;color:#888;">未找到结果，请尝试其他关键词。</td></tr>`;
    }
    html += '</tbody></table>';
    qs("#resultList").innerHTML = html;
}

// 搜索功能
async function doSearch(q) {
    qs("#resultList").innerHTML = '<div style="margin:2em auto;color:#888;">正在检索…</div>';
    let resp = await fetch("api/search.php?q=" + encodeURIComponent(q));
    let data = await resp.json();
    currentResults = data.results || [];
    currentStats = data.stats || {};
    currentKeywords = data.keywords || [];
    renderStats(currentStats);
    renderView();
    if (isFirstSearch) {
        qs('#centerBox').style.minHeight = '0';
        qs('#centerBox').style.marginBottom = '0';
        qs('#centerBox').style.display = 'block';
        qs('.view-toggle').style.display = 'flex';
        isFirstSearch = false;
    }
}

// 模式切换渲染
function renderView() {
    if(currentMode === 'card') {
        renderCards(currentResults);
    } else {
        renderTable(currentResults);
    }
    qs("#cardModeBtn").classList.toggle('active', currentMode === 'card');
    qs("#tableModeBtn").classList.toggle('active', currentMode === 'table');
    localStorage.setItem('viewMode', currentMode);
}

// 绑定表单提交
qs("#searchForm").onsubmit = function(e){
    e.preventDefault();
    let q = qs("#searchInput").value.trim();
    if(q.length<1) return;
    doSearch(q);
};

// 切换按钮
qs("#cardModeBtn").onclick = function(){
    if(currentMode !== 'card') {
        currentMode = 'card';
        renderView();
    }
};
qs("#tableModeBtn").onclick = function(){
    if(currentMode !== 'table') {
        currentMode = 'table';
        renderView();
    }
};

// 详情弹窗
window.openDetail = async function(id, q){
    qs("#detailModal").style.display = "flex";
    qs("#modalTitle").innerHTML = "<span style='color:#bbb'>加载中…</span>";
    qs("#modalCat").innerHTML = "";
    qs("#modalContent").innerHTML = "";
    let resp = await fetch(`api/detail.php?id=${id}&q=${q}`);
    let detail = await resp.json();
    qs("#modalTitle").innerHTML = detail.title_highlight;
    qs("#modalCat").innerHTML = detail.category_highlight;
    // 显示小类卡片
    let subclassHtml = "";
    if(detail.subclass){
        subclassHtml = `
            <div class="subclass-card">
                <b>小类代码：</b>${detail.subclass.code}<br>
                <b>小类名称：</b>${detail.subclass.title}<br>
                <b>小类说明：</b>
                <div style="margin-top:4px;">${detail.subclass.content}</div>
            </div>
        `;
    }
    qs("#modalContent").innerHTML = subclassHtml;
};
window.closeModal = function(){
    qs("#detailModal").style.display = "none";
};
qs("#detailModal").onclick = closeModal;

// 自动聚焦输入框
qs("#searchInput").focus();

// 搜索后调整页面布局：隐藏居中大盒子，仅保留标题和搜索条在顶部
function adjustLayoutOnSearch() {
    if (qs('#centerBox')) {
        qs('#centerBox').style.minHeight = '0';
        qs('#centerBox').style.marginBottom = '0';
        qs('#centerBox').style.display = 'block';
    }
    qs('.view-toggle').style.display = 'flex';
}
</script>
</body>
</html>