<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../vendor/autoload.php';

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
Jieba::init();
Finalseg::init();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$pageSize = defined('PAGE_SIZE') ? PAGE_SIZE : 20;
$offset = ($page-1)*$pageSize;

if(!$q) { 
    echo json_encode([
        'results'=>[], 
        'stats'=>['total'=>0,'categories'=>[]],
        'keywords'=>[]
    ]); 
    exit; 
}

$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 使用结巴进行分词，支持智能部分匹配
$jiebaWords = Jieba::cutForSearch($q);
// 去重+去空
$keywords = array_values(array_unique(array_filter($jiebaWords)));
$params = [];
$where_ors = [];
$score_sql = [];

// 构造 OR 关系和相关度加权
foreach ($keywords as $idx => $kw) {
    $param = ":kw$idx";
    $like = "%$kw%";
    // 三个主字段都要匹配
    $where_ors[] = "(subclass.title LIKE $param OR subclass.content LIKE $param OR category.name LIKE $param)";
    // 相关度分数
    $score_sql[] = " (CASE WHEN subclass.title LIKE $param THEN 10 ELSE 0 END) ";
    $score_sql[] = " (CASE WHEN subclass.content LIKE $param THEN 4 ELSE 0 END) ";
    $score_sql[] = " (CASE WHEN category.name LIKE $param THEN 3 ELSE 0 END) ";
    $params[$param] = $like;
}
$where = implode(' OR ', $where_ors);
$score_expr = implode(' + ', $score_sql);

$sql = "SELECT subclass.id, subclass.code, subclass.title, subclass.content, category.code as cat_code, category.name as cat_name,
        ($score_expr) AS rel_score
        FROM subclass 
        JOIN category ON subclass.category_id = category.id
        WHERE $where
        ORDER BY rel_score DESC, category.code, subclass.code
        LIMIT :offset, :pagesize";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':pagesize', $pageSize, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 构建高亮摘要
function make_excerpt($text, $keywords, $width=40) {
    if (!$keywords || !is_array($keywords) || !count($keywords)) {
        return htmlspecialchars(mb_substr($text, 0, $width*2).'...', ENT_QUOTES, 'UTF-8');
    }
    $pattern = '/('.implode('|', array_map('preg_quote', $keywords)).')/iu';
    if (preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
        $start = max(0, mb_strpos($text, $m[0][0]) - $width);
        $snippet = mb_substr($text, $start, $width*2);
        $snippet = preg_replace($pattern, '<mark>$1</mark>', htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'));
        if ($start > 0) $snippet = '...'.$snippet;
        if ($start + $width*2 < mb_strlen($text)) $snippet .= '...';
        return $snippet;
    }
    return htmlspecialchars(mb_substr($text, 0, $width*2).'...', ENT_QUOTES, 'UTF-8');
}

foreach($data as &$item) {
    $item['title_highlight'] = make_excerpt($item['title'], $keywords, 12);
    $item['content_excerpt'] = make_excerpt($item['content'], $keywords, 40);
    $item['category_highlight'] = make_excerpt($item['cat_name'], $keywords, 8);
}
unset($item);

// 统计
$categories = array_unique(array_column($data, 'cat_name'));
if (empty($categories)) $categories = [];
$stats = [
    'total' => count($data),
    'categories' => array_values($categories)
];

echo json_encode([
    'results'=>$data, 
    'stats'=>$stats, 
    'keywords'=>$keywords // 结巴分词关键词数组，供前端高亮用
], JSON_UNESCAPED_UNICODE);