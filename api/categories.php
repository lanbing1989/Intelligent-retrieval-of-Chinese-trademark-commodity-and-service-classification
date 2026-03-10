<?php
/**
 * 分类列表API
 * 获取所有大类及其小类列表
 */

require_once __DIR__.'/../config.php';

try {
    $pdo = getDB();
    
    // 获取所有大类
    $catSql = "SELECT id, code, name, description FROM category ORDER BY CAST(code AS UNSIGNED) ASC";
    $catStmt = $pdo->query($catSql);
    $categories = $catStmt->fetchAll();
    
    // 获取所有小类
    $subSql = "SELECT id, category_id, code, title FROM subclass ORDER BY CAST(code AS UNSIGNED) ASC";
    $subStmt = $pdo->query($subSql);
    $subclasses = $subStmt->fetchAll();
    
    // 按大类分组小类
    $subclassMap = [];
    foreach ($subclasses as $sub) {
        $catId = $sub['category_id'];
        if (!isset($subclassMap[$catId])) {
            $subclassMap[$catId] = [];
        }
        $subclassMap[$catId][] = [
            'id' => $sub['id'],
            'code' => $sub['code'],
            'title' => $sub['title']
        ];
    }
    
    // 构建响应
    $result = [];
    foreach ($categories as $cat) {
        $result[] = [
            'id' => $cat['id'],
            'code' => $cat['code'],
            'name' => $cat['name'],
            'description' => $cat['description'],
            'subclass_count' => isset($subclassMap[$cat['id']]) ? count($subclassMap[$cat['id']]) : 0,
            'subclasses' => isset($subclassMap[$cat['id']]) ? $subclassMap[$cat['id']] : []
        ];
    }
    
    successResponse([
        'total_categories' => count($categories),
        'total_subclasses' => count($subclasses),
        'categories' => $result
    ]);
    
} catch (Exception $e) {
    errorResponse('获取分类列表失败: ' . $e->getMessage(), 500);
}
