<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if(!$id) { echo json_encode(['error'=>'参数错误']); exit; }

$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql = "SELECT subclass.id, subclass.code, subclass.title, subclass.content, category.code as cat_code, category.name as cat_name
        FROM subclass 
        JOIN category ON subclass.category_id = category.id
        WHERE subclass.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row) { echo json_encode(['error'=>'未找到']); exit; }

function highlight($text, $q) {
    if (!$q) return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $words = array_filter(preg_split('/\s+/', preg_quote($q, '/')));
    if(!$words) return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    return preg_replace_callback('/('.implode('|', $words).')/iu', function($m){
        return '<mark>'.$m[0].'</mark>';
    }, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

$row['title_highlight'] = highlight($row['title'], $q);
$row['content_highlight'] = highlight($row['content'], $q);
$row['category_highlight'] = highlight($row['cat_name'], $q);

// 增加 subclass 字段，显示当前卡片的小类信息
$row['subclass'] = [
    'id'      => $row['id'],
    'code'    => $row['code'],
    'title'   => $row['title'],
    'content' => $row['content']
];

echo json_encode($row, JSON_UNESCAPED_UNICODE);