<?php
/**
 * 商标搜索API - 生产版本
 * 支持智能搜索、分页、排序、统计
 */

require_once __DIR__.'/../config.php';

// 获取并验证参数
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'relevance';

// 验证搜索关键词
if (empty($q)) {
    successResponse([
        'results' => [],
        'stats' => ['total' => 0, 'categories' => []],
        'keywords' => [],
        'pagination' => null
    ]);
}

if (mb_strlen($q) < SEARCH_MIN_LENGTH) {
    errorResponse('搜索关键词太短', 400);
}

if (mb_strlen($q) > SEARCH_MAX_LENGTH) {
    errorResponse('搜索关键词太长', 400);
}

// 获取分页大小
$pageSize = defined('PAGE_SIZE') ? PAGE_SIZE : 20;
if (isset($_GET['pageSize'])) {
    $pageSize = min(MAX_PAGE_SIZE, max(1, intval($_GET['pageSize'])));
}
$offset = ($page - 1) * $pageSize;

try {
    $pdo = getDB();
    
    // 简单分词
    $keywords = preg_split('/\s+/u', $q);
    $keywords = array_filter($keywords);
    if (empty($keywords)) {
        $keywords = [$q];
    }
    
    // 转义关键词用于 SQL
    $escapedKeywords = array_map(function($kw) use ($pdo) {
        return $pdo->quote('%' . $kw . '%');
    }, $keywords);
    
    // 构建 WHERE 条件
    $whereConditions = [];
    foreach ($escapedKeywords as $escaped) {
        $whereConditions[] = "(subclass.title LIKE {$escaped} OR subclass.content LIKE {$escaped} OR category.name LIKE {$escaped})";
    }
    $whereClause = implode(' OR ', $whereConditions);
    
    // 添加大类筛选
    if ($categoryFilter && preg_match('/^\d{1,2}$/', $categoryFilter)) {
        $catCode = $pdo->quote(str_pad($categoryFilter, 2, '0', STR_PAD_LEFT));
        $whereClause = "({$whereClause}) AND category.code = {$catCode}";
    }
    
    // 首先获取总数
    $countSql = "SELECT COUNT(DISTINCT subclass.id) as total 
                 FROM subclass 
                 JOIN category ON subclass.category_id = category.id 
                 WHERE {$whereClause}";
    $totalCount = intval($pdo->query($countSql)->fetchColumn());
    
    // 获取涉及的大类统计
    $catSql = "SELECT DISTINCT category.code, category.name 
               FROM subclass 
               JOIN category ON subclass.category_id = category.id 
               WHERE {$whereClause}
               ORDER BY category.code ASC";
    $categories = $pdo->query($catSql)->fetchAll();
    
    // 构建评分表达式
    $scoreParts = [];
    foreach ($escapedKeywords as $escaped) {
        $scoreParts[] = "(CASE WHEN subclass.title LIKE {$escaped} THEN 10 ELSE 0 END)";
        $scoreParts[] = "(CASE WHEN subclass.content LIKE {$escaped} THEN 4 ELSE 0 END)";
        $scoreParts[] = "(CASE WHEN category.name LIKE {$escaped} THEN 3 ELSE 0 END)";
    }
    $scoreExpr = implode(' + ', $scoreParts);
    
    // 排序方式
    $orderBy = "ORDER BY rel_score DESC, category.code ASC, subclass.code ASC";
    if ($sortBy === 'code') {
        $orderBy = "ORDER BY category.code ASC, subclass.code ASC";
    } elseif ($sortBy === 'title') {
        $orderBy = "ORDER BY subclass.title ASC";
    }
    
    // 主查询
    $sql = "SELECT 
                subclass.id, 
                subclass.code, 
                subclass.title, 
                subclass.content, 
                category.code as cat_code, 
                category.name as cat_name,
                ({$scoreExpr}) AS rel_score
            FROM subclass 
            JOIN category ON subclass.category_id = category.id
            WHERE {$whereClause}
            {$orderBy}
            LIMIT {$offset}, {$pageSize}";
    
    $data = $pdo->query($sql)->fetchAll();
    
    // 处理结果
    foreach ($data as &$item) {
        $item['title_highlight'] = makeExcerpt($item['title'], $keywords, 12);
        $item['content_excerpt'] = makeExcerpt($item['content'], $keywords, EXCERPT_WIDTH);
        $item['category_highlight'] = makeExcerpt($item['cat_name'], $keywords, 8);
        unset($item['rel_score']);
    }
    unset($item);
    
    // 构建分页信息
    $totalPages = ceil($totalCount / $pageSize);
    $pagination = [
        'current' => $page,
        'total' => $totalPages,
        'pageSize' => $pageSize,
        'totalCount' => $totalCount,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1
    ];
    
    // 构建统计信息
    $stats = [
        'total' => $totalCount,
        'categories' => array_map(function($cat) {
            return $cat['name'];
        }, $categories),
        'categoryCodes' => array_map(function($cat) {
            return $cat['code'];
        }, $categories)
    ];
    
    successResponse([
        'results' => $data,
        'stats' => $stats,
        'keywords' => array_values($keywords),
        'pagination' => $pagination,
        'query' => $q
    ]);
    
} catch (Exception $e) {
    errorResponse('搜索失败: ' . $e->getMessage(), 500);
}

/**
 * 生成高亮摘要
 */
function makeExcerpt($text, $keywords, $width = 40) {
    if (empty($keywords) || !is_array($keywords)) {
        return htmlspecialchars(mb_substr($text, 0, $width * 2), ENT_QUOTES, 'UTF-8');
    }
    
    $escapedKeywords = array_map(function($kw) {
        return preg_quote($kw, '/');
    }, $keywords);
    
    $pattern = '/(' . implode('|', $escapedKeywords) . ')/iu';
    
    if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
        $matchPos = mb_strpos($text, $matches[0][0]);
        $start = max(0, $matchPos - $width);
        $snippet = mb_substr($text, $start, $width * 2);
        $snippet = preg_replace($pattern, '<mark>$1</mark>', htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'));
        if ($start > 0) $snippet = '...' . $snippet;
        if ($start + $width * 2 < mb_strlen($text)) $snippet .= '...';
        return $snippet;
    }
    
    $snippet = mb_substr($text, 0, $width * 2);
    return htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8') . (mb_strlen($text) > $width * 2 ? '...' : '');
}
