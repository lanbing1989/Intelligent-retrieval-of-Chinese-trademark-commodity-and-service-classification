<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="中国商标商品服务分类智能检索系统，基于尼斯分类第十二版，支持智能分词搜索">
    <meta name="keywords" content="商标查询,尼斯分类,商品服务分类,商标检索">
    <title>惊鸿科技-中国商标商品服务分类智能检索</title>
    <link rel="stylesheet" href="static/style.css?v=2">
</head>
<body>
    <div class="main-container">
        <!-- 搜索区域 -->
        <div class="center-box" id="centerBox">
            <h1>
                <a href="index.php" title="返回首页">中国商标商品服务分类智能检索</a>
            </h1>
            <form class="search-bar" id="searchForm" autocomplete="off">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="请输入商品/服务/大类/小类关键词…" 
                    autofocus
                    aria-label="搜索关键词"
                    maxlength="100"
                >
                <button type="submit" aria-label="搜索">
                    <span>搜索</span>
                </button>
                <div class="search-suggestions" id="suggestions"></div>
            </form>
        </div>

        <!-- 视图切换 -->
        <div class="view-toggle" id="viewToggle" style="display:none;">
            <span class="view-toggle-label">显示方式：</span>
            <button type="button" class="toggle-btn active" id="cardModeBtn" aria-label="卡片模式">
                卡片模式
            </button>
            <button type="button" class="toggle-btn" id="tableModeBtn" aria-label="表格模式">
                表格模式
            </button>
        </div>

        <!-- 统计面板 -->
        <div class="stats-panel" id="statsPanel" style="display:none;"></div>

        <!-- 分类筛选 -->
        <div class="category-filter" id="categoryFilter" style="display:none;"></div>

        <!-- 结果列表 -->
        <div id="resultList"></div>

        <!-- 分页 -->
        <div class="pagination" id="pagination" style="display:none;"></div>
    </div>

    <!-- 详情模态框 -->
    <div class="modal-backdrop" id="detailModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content" onclick="event.stopPropagation();">
            <button class="close-btn" onclick="closeModal()" aria-label="关闭">&times;</button>
            <h2 id="modalTitle"></h2>
            <div class="cat-badge" id="modalCat"></div>
            <div class="detail-body" id="modalContent"></div>
            <div class="related-section" id="relatedSection" style="display:none;">
                <h3>相关小类</h3>
                <div class="related-list" id="relatedList"></div>
            </div>
        </div>
    </div>

    <!-- 返回顶部 -->
    <button class="back-to-top" id="backToTop" onclick="scrollToTop()" aria-label="返回顶部">↑</button>

    <!-- 页脚 -->
    <div class="footer-wrap">
        <footer class="footer">
            <span class="footer-icon">📚</span>
            基于 <span style="font-weight:600;">"类似商品和服务区分表——基于尼斯分类第十二版（2024文本）及NCL12-2025"</span> 构建<br>
            <a class="footer-link" href="all_categories.php">查看全部分类</a>
            <a class="footer-link" href="https://sbj.cnipa.gov.cn/sbj/sbsq/sphfwfl/" target="_blank" rel="noopener">商标局官网</a>
            <a class="footer-link" href="http://jinghong.me/yyzz.jpg" target="_blank" rel="noopener">营业执照</a>
            <a class="footer-link" href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=37083002370893" target="_blank" rel="noopener">鲁公网安备37083002370893号</a>
            <a class="footer-link" href="https://beian.miit.gov.cn/" target="_blank" rel="noopener">鲁ICP备2026012160号-2</a>
        </footer>
    </div>

<script>
/**
 * 商标查询系统前端逻辑 - 优化版本
 */

// DOM选择器快捷方式
const $ = selector => document.querySelector(selector);
const $$ = selector => document.querySelectorAll(selector);

// 应用状态
const state = {
    currentResults: [],
    currentStats: {},
    currentKeywords: [],
    currentPage: 1,
    totalPages: 1,
    viewMode: localStorage.getItem('viewMode') || 'card',
    isFirstSearch: true,
    isLoading: false,
    categoryFilter: '',
    currentQuery: ''
};

// 防抖函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 初始化
function init() {
    setupEventListeners();
    setupScrollListener();
    restoreViewMode();
    $('#searchInput').focus();
}

// 设置事件监听
function setupEventListeners() {
    // 搜索表单提交
    $('#searchForm').addEventListener('submit', handleSearchSubmit);
    
    // 输入框输入（防抖搜索建议）
    $('#searchInput').addEventListener('input', debounce(handleInput, 300));
    
    // 视图切换
    $('#cardModeBtn').addEventListener('click', () => switchViewMode('card'));
    $('#tableModeBtn').addEventListener('click', () => switchViewMode('table'));
    
    // 模态框关闭
    $('#detailModal').addEventListener('click', closeModal);
    document.addEventListener('keydown', handleKeyDown);
    
    // 点击外部关闭搜索建议
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-bar')) {
            hideSuggestions();
        }
    });
}

// 设置滚动监听
function setupScrollListener() {
    const backToTopBtn = $('#backToTop');
    
    window.addEventListener('scroll', debounce(() => {
        if (window.scrollY > 400) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    }, 100));
}

// 键盘事件处理
function handleKeyDown(e) {
    if (e.key === 'Escape') {
        closeModal();
        hideSuggestions();
    }
}

// 处理搜索提交
function handleSearchSubmit(e) {
    e.preventDefault();
    const query = $('#searchInput').value.trim();
    if (query.length < 1) return;
    
    hideSuggestions();
    state.currentQuery = query;
    state.currentPage = 1;
    state.categoryFilter = '';
    performSearch(query, 1);
}

// 处理输入
function handleInput(e) {
    const value = e.target.value.trim();
    if (value.length > 0) {
        // 可以在这里添加搜索建议逻辑
        // showSuggestions(value);
    } else {
        hideSuggestions();
    }
}

// 执行搜索
async function performSearch(query, page = 1) {
    if (state.isLoading) return;
    
    state.isLoading = true;
    showLoading();
    
    try {
        const params = new URLSearchParams({
            q: query,
            page: page.toString(),
            pageSize: '20'
        });
        
        if (state.categoryFilter) {
            params.append('category', state.categoryFilter);
        }
        
        const response = await fetch(`api/search.php?${params}`);
        const data = await response.json();
        
        if (!data.success) {
            showError(data.error || '搜索失败');
            return;
        }
        
        state.currentResults = data.data.results || [];
        state.currentStats = data.data.stats || {};
        state.currentKeywords = data.data.keywords || [];
        state.currentPage = data.data.pagination?.current || 1;
        state.totalPages = data.data.pagination?.total || 1;
        
        renderResults();
        
        if (state.isFirstSearch) {
            adjustLayoutAfterFirstSearch();
        }
        
    } catch (error) {
        console.error('Search error:', error);
        showError('搜索失败，请稍后重试');
    } finally {
        state.isLoading = false;
    }
}

// 显示加载状态
function showLoading() {
    $('#resultList').innerHTML = `
        <div class="loading">
            <div class="loading-spinner"></div>
            <p>正在检索中...</p>
        </div>
    `;
}

// 显示错误
function showError(message) {
    $('#resultList').innerHTML = `
        <div class="error-state">
            <div class="empty-state-icon">⚠️</div>
            <h3>出错了</h3>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
}

// 渲染结果
function renderResults() {
    renderStats();
    renderCategoryFilter();
    
    if (state.viewMode === 'card') {
        renderCards();
    } else {
        renderTable();
    }
    
    renderPagination();
}

// 渲染统计信息
function renderStats() {
    const statsPanel = $('#statsPanel');
    const stats = state.currentStats;
    
    if (!stats || stats.total === 0) {
        statsPanel.style.display = 'none';
        return;
    }
    
    const categories = stats.categories || [];
    let html = `
        <div class="stats-row">
            <span class="stats-label">共找到</span>
            <span class="stats-value">${stats.total}</span>
            <span class="stats-label">条结果</span>
        </div>
    `;
    
    if (categories.length > 0) {
        html += `<div class="stats-categories">`;
        categories.forEach((cat, idx) => {
            const code = stats.categoryCodes?.[idx] || '';
            const isActive = state.categoryFilter === code;
            html += `
                <span class="cat-tag ${isActive ? 'active' : ''}" 
                      onclick="filterByCategory('${code}')"
                      title="点击筛选">
                    ${escapeHtml(cat)}
                </span>
            `;
        });
        html += `</div>`;
    }
    
    statsPanel.innerHTML = html;
    statsPanel.style.display = 'block';
}

// 渲染分类筛选
function renderCategoryFilter() {
    // 已在统计面板中实现
}

// 按分类筛选
function filterByCategory(categoryCode) {
    if (state.categoryFilter === categoryCode) {
        state.categoryFilter = ''; // 取消筛选
    } else {
        state.categoryFilter = categoryCode;
    }
    state.currentPage = 1;
    performSearch(state.currentQuery, 1);
}

// 渲染卡片视图
function renderCards() {
    const results = state.currentResults;
    
    if (!results || results.length === 0) {
        renderEmptyState();
        return;
    }
    
    let html = '<div class="card-list">';
    results.forEach((item, index) => {
        html += `
            <div class="trademark-card fade-in" style="animation-delay: ${index * 0.05}s">
                <div class="card-header">
                    <span class="cat-badge">${highlightText(item.category_highlight || item.cat_name)}</span>
                    <span class="code">${escapeHtml(item.code)}</span>
                </div>
                <div class="title">${highlightText(item.title_highlight || item.title)}</div>
                <div class="excerpt">${highlightText(item.content_excerpt || item.content)}</div>
                <button class="detail-btn" onclick="openDetail(${item.id})">
                    查看详情
                </button>
            </div>
        `;
    });
    html += '</div>';
    
    $('#resultList').innerHTML = html;
}

// 渲染表格视图
function renderTable() {
    const results = state.currentResults;
    
    if (!results || results.length === 0) {
        renderEmptyState();
        return;
    }
    
    let html = `
        <table class="tradetable">
            <thead>
                <tr>
                    <th style="width:80px">类别</th>
                    <th style="width:100px">小类代码</th>
                    <th style="width:200px">小类名称</th>
                    <th>商品／服务项</th>
                    <th style="width:100px">操作</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    results.forEach(item => {
        html += `
            <tr>
                <td>${highlightText(item.category_highlight || item.cat_name)}</td>
                <td>${escapeHtml(item.code)}</td>
                <td>${highlightText(item.title_highlight || item.title)}</td>
                <td>${highlightText(item.content_excerpt || item.content)}</td>
                <td>
                    <button class="detail-btn" onclick="openDetail(${item.id})" style="padding:6px 12px;font-size:0.85rem;">
                        详情
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    $('#resultList').innerHTML = html;
}

// 渲染空状态
function renderEmptyState() {
    $('#resultList').innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <h3>未找到结果</h3>
            <p>请尝试其他关键词，或检查输入是否正确</p>
        </div>
    `;
}

// 渲染分页
function renderPagination() {
    const pagination = $('#pagination');
    
    if (state.totalPages <= 1) {
        pagination.style.display = 'none';
        return;
    }
    
    let html = '';
    
    // 上一页
    html += `
        <button class="pagination-btn" onclick="goToPage(${state.currentPage - 1})" 
                ${state.currentPage <= 1 ? 'disabled' : ''}>
            ←
        </button>
    `;
    
    // 页码
    const maxVisible = 5;
    let startPage = Math.max(1, state.currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(state.totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            html += `<span class="pagination-info">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button class="pagination-btn ${i === state.currentPage ? 'active' : ''}" 
                    onclick="goToPage(${i})">
                ${i}
            </button>
        `;
    }
    
    if (endPage < state.totalPages) {
        if (endPage < state.totalPages - 1) {
            html += `<span class="pagination-info">...</span>`;
        }
        html += `<button class="pagination-btn" onclick="goToPage(${state.totalPages})">${state.totalPages}</button>`;
    }
    
    // 下一页
    html += `
        <button class="pagination-btn" onclick="goToPage(${state.currentPage + 1})" 
                ${state.currentPage >= state.totalPages ? 'disabled' : ''}>
            →
        </button>
    `;
    
    // 页码信息
    html += `<span class="pagination-info">${state.currentPage} / ${state.totalPages}</span>`;
    
    pagination.innerHTML = html;
    pagination.style.display = 'flex';
}

// 跳转到指定页
function goToPage(page) {
    if (page < 1 || page > state.totalPages || page === state.currentPage) return;
    state.currentPage = page;
    performSearch(state.currentQuery, page);
    scrollToTop();
}

// 切换视图模式
function switchViewMode(mode) {
    if (state.viewMode === mode) return;
    state.viewMode = mode;
    localStorage.setItem('viewMode', mode);
    
    $('#cardModeBtn').classList.toggle('active', mode === 'card');
    $('#tableModeBtn').classList.toggle('active', mode === 'table');
    
    renderResults();
}

// 恢复视图模式
function restoreViewMode() {
    $('#cardModeBtn').classList.toggle('active', state.viewMode === 'card');
    $('#tableModeBtn').classList.toggle('active', state.viewMode === 'table');
}

// 调整布局（首次搜索后）
function adjustLayoutAfterFirstSearch() {
    const centerBox = $('#centerBox');
    centerBox.style.minHeight = '0';
    centerBox.style.marginBottom = '16px';
    centerBox.style.display = 'block';
    
    $('#viewToggle').style.display = 'flex';
    
    state.isFirstSearch = false;
}

// 打开详情弹窗
async function openDetail(id) {
    const modal = $('#detailModal');
    const titleEl = $('#modalTitle');
    const catEl = $('#modalCat');
    const contentEl = $('#modalContent');
    const relatedSection = $('#relatedSection');
    const relatedList = $('#relatedList');
    
    modal.style.display = 'flex';
    modal.classList.add('active');
    
    titleEl.innerHTML = '<span style="color:#bbb">加载中...</span>';
    catEl.innerHTML = '';
    contentEl.innerHTML = '';
    relatedSection.style.display = 'none';
    
    try {
        const response = await fetch(`api/detail.php?id=${id}&q=${encodeURIComponent(state.currentQuery)}`);
        const data = await response.json();
        
        if (!data.success) {
            titleEl.innerHTML = '加载失败';
            contentEl.innerHTML = data.error || '无法加载详情';
            return;
        }
        
        const detail = data.data;
        
        titleEl.innerHTML = detail.title_highlight || escapeHtml(detail.title);
        catEl.innerHTML = detail.category_highlight || escapeHtml(detail.category?.name || '');
        
        // 构建详情内容
        let contentHtml = '';
        if (detail.subclass) {
            contentHtml += `
                <div class="subclass-card">
                    <b>小类代码：</b>${escapeHtml(detail.subclass.code)}<br>
                    <b>小类名称：</b>${escapeHtml(detail.subclass.title)}<br>
                    <b>详细说明：</b>
                    <div style="margin-top:8px;">${detail.content_highlight || escapeHtml(detail.content)}</div>
                </div>
            `;
        }
        contentEl.innerHTML = contentHtml;
        
        // 显示相关项目
        if (detail.related_items && detail.related_items.length > 0) {
            relatedList.innerHTML = detail.related_items.map(item => `
                <span class="related-item" onclick="openDetail(${item.id})">
                    [${escapeHtml(item.code)}] ${escapeHtml(item.title)}
                </span>
            `).join('');
            relatedSection.style.display = 'block';
        }
        
    } catch (error) {
        console.error('Detail error:', error);
        titleEl.innerHTML = '加载失败';
        contentEl.innerHTML = '无法加载详情，请稍后重试';
    }
}

// 关闭模态框
function closeModal() {
    const modal = $('#detailModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 200);
}

// 滚动到顶部
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// HTML转义
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 高亮文本
function highlightText(text) {
    if (!text) return '';
    return text;
}

// 隐藏搜索建议
function hideSuggestions() {
    $('#suggestions').classList.remove('active');
}

// 启动应用
document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
