<?php
/**
 * 商标查询系统配置文件
 * 优化版本 - 支持更多配置选项
 */

// 数据库配置
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'trademark_db');
define('DB_USER', getenv('DB_USER') ?: 'username');
define('DB_PASS', getenv('DB_PASS') ?: 'password');
define('DB_CHARSET', 'utf8mb4');

// 分页配置
define('PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// 搜索配置
define('SEARCH_MIN_LENGTH', 1);
define('SEARCH_MAX_LENGTH', 100);
define('EXCERPT_WIDTH', 40);

// 缓存配置
define('CACHE_ENABLED', true);
define('CACHE_TTL', 300); // 5分钟

// 安全配置
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 60); // 每分钟最大请求数
define('RATE_LIMIT_WINDOW', 60);

// 调试模式
define('DEBUG_MODE', false);

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误处理
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// CORS配置
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 数据库连接函数
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('数据库连接失败');
        }
    }
    return $pdo;
}

// 统一的JSON响应函数
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 错误响应函数
function errorResponse($message, $code = 400) {
    jsonResponse(['success' => false, 'error' => $message], $code);
}

// 成功响应函数
function successResponse($data, $message = '') {
    $response = ['success' => true, 'data' => $data];
    if ($message) {
        $response['message'] = $message;
    }
    jsonResponse($response);
}

// 输入清理函数
function sanitizeInput($input, $maxLength = 255) {
    if (!is_string($input)) {
        return '';
    }
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    if (mb_strlen($input) > $maxLength) {
        $input = mb_substr($input, 0, $maxLength);
    }
    return $input;
}

// 速率限制检查
function checkRateLimit($identifier = null) {
    if (!RATE_LIMIT_ENABLED) {
        return true;
    }
    
    $identifier = $identifier ?: $_SERVER['REMOTE_ADDR'];
    $key = 'rate_limit:' . md5($identifier);
    
    // 使用文件存储简单的速率限制
    $rateFile = sys_get_temp_dir() . '/' . $key . '.txt';
    $now = time();
    
    $requests = [];
    if (file_exists($rateFile)) {
        $data = file_get_contents($rateFile);
        $requests = json_decode($data, true) ?: [];
        // 清理过期的请求记录
        $requests = array_filter($requests, function($time) use ($now) {
            return $time > $now - RATE_LIMIT_WINDOW;
        });
    }
    
    if (count($requests) >= RATE_LIMIT_REQUESTS) {
        return false;
    }
    
    $requests[] = $now;
    file_put_contents($rateFile, json_encode(array_values($requests)));
    
    return true;
}

// 日志记录函数
function logMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/logs/app_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    error_log($logEntry, 3, $logFile);
}
