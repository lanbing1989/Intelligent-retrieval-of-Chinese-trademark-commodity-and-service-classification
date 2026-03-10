<?php
/**
 * 商标详情API - 优化版本
 * 获取单个商标分类的详细信息
 */

require_once __DIR__.'/../config.php';

// 获取并验证参数
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($id <= 0) {
    errorResponse('无效的ID参数', 400);
}

// 清理搜索关键词
$q = sanitizeInput($q, SEARCH_MAX_LENGTH);

try {
    $pdo = getDB();
    
    // 查询详情
    $sql = "SELECT 
                subclass.id, 
                subclass.code, 
                subclass.title, 
                subclass.content,
                subclass.img_paths,
                category.code as cat_code, 
                category.name as cat_name,
                category.description as cat_description
            FROM subclass 
            JOIN category ON subclass.category_id = category.id
            WHERE subclass.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    
    if (!$row) {
        errorResponse('未找到该记录', 404);
    }
    
    // 处理图片路径
    $images = [];
    if (!empty($row['img_paths'])) {
        $images = array_filter(explode(',', $row['img_paths']));
    }
    
    // 获取相关小类（同一大类下的其他小类）
    $relatedSql = "SELECT id, code, title 
                   FROM subclass 
                   WHERE category_id = (SELECT category_id FROM subclass WHERE id = :id) 
                   AND id != :id2
                   ORDER BY code ASC
                   LIMIT 10";
    $relatedStmt = $pdo->prepare($relatedSql);
    $relatedStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $relatedStmt->bindValue(':id2', $id, PDO::PARAM_INT);
    $relatedStmt->execute();
    $relatedItems = $relatedStmt->fetchAll();
    
    // 高亮处理
    $keywords = [];
    if ($q) {
        // 简单分词处理
        $keywords = array_filter(preg_split('/\s+/u', $q));
        if (empty($keywords)) {
            $keywords = [$q];
        }
    }
    
    $response = [
        'id' => $row['id'],
        'code' => $row['code'],
        'title' => $row['title'],
        'title_highlight' => highlightText($row['title'], $keywords),
        'content' => $row['content'],
        'content_highlight' => highlightText($row['content'], $keywords),
        'category' => [
            'code' => $row['cat_code'],
            'name' => $row['cat_name'],
            'description' => $row['cat_description']
        ],
        'category_highlight' => highlightText($row['cat_name'], $keywords),
        'images' => $images,
        'subclass' => [
            'id' => $row['id'],
            'code' => $row['code'],
            'title' => $row['title'],
            'content' => nl2br(htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'))
        ],
        'related_items' => $relatedItems
    ];
    
    successResponse($response);
    
} catch (Exception $e) {
    errorResponse('获取详情失败: ' . $e->getMessage(), 500);
}

/**
 * 文本高亮函数
 */
function highlightText($text, $keywords) {
    if (empty($keywords) || !is_array($keywords)) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    $escapedKeywords = array_map(function($kw) {
        return preg_quote($kw, '/');
    }, $keywords);
    
    $pattern = '/(' . implode('|', $escapedKeywords) . ')/iu';
    
    return preg_replace_callback($pattern, function($matches) {
        return '<mark>' . $matches[0] . '</mark>';
    }, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}
