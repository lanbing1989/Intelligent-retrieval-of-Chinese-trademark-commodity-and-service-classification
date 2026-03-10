# 中国商标商品服务分类智能检索系统

基于尼斯分类第十二版（2024文本）及NCL12-2025构建的商标分类查询系统。

## 功能特性

### 核心功能
- **智能分词搜索**：基于结巴分词，支持中文智能分词和关键词高亮
- **多维度检索**：支持按商品/服务名称、大类、小类代码搜索
- **分类筛选**：搜索结果可按大类快速筛选
- **双视图模式**：卡片视图和表格视图自由切换
- **详情弹窗**：点击查看完整商品/服务说明
- **分页加载**：支持大量结果分页展示

### 用户体验
- **响应式设计**：完美适配桌面端和移动端
- **键盘快捷键**：ESC关闭弹窗、方向键导航
- **本地存储**：记住用户的视图偏好
- **平滑动画**：优雅的过渡效果
- **返回顶部**：长页面快速返回

### 管理功能
- **全部分类浏览**：45个尼斯分类完整展示
- **小类详情展开**：点击展开查看详细说明
- **页面内搜索**：支持在当前页快速筛选

## 技术栈

### 后端
- **PHP 7.4+**：核心后端语言
- **MySQL 5.7+**：数据存储
- **结巴分词 (jieba-php)**：中文分词处理
- **PDO**：数据库访问层

### 前端
- **原生 JavaScript**：无框架依赖
- **CSS3**：现代样式和动画
- **响应式设计**：移动优先

## 目录结构

```
商标查询系统/
├── api/                    # API接口
│   ├── search.php         # 搜索接口
│   ├── detail.php         # 详情接口
│   └── categories.php     # 分类列表接口
├── static/                # 静态资源
│   └── style.css          # 样式表
├── logs/                  # 日志目录
├── config.php             # 配置文件
├── index.php              # 首页
├── all_categories.php     # 全部分类页面
├── txt_to_mysql_full.py   # 数据导入工具
├── data.sql               # 数据库备份
├── 45.txt                 # 原始数据文件
├── .htaccess              # Apache配置
└── README.md              # 说明文档
```

## 安装部署

### 环境要求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Apache/Nginx Web服务器
- Composer（用于安装依赖）

### 安装步骤

1. **克隆/上传代码**
   ```bash
   git clone <repository-url>
   cd 商标查询系统
   ```

2. **安装依赖**
   ```bash
   composer install
   ```

3. **创建数据库**
   ```sql
   CREATE DATABASE trademark_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **导入数据**
   ```bash
   # 使用SQL文件
   mysql -u username -p trademark_db < data.sql
   
   # 或使用Python脚本从文本导入
   python txt_to_mysql_full.py --user username --password password --database trademark_db --clear
   ```

5. **配置数据库**
   编辑 `config.php`，设置正确的数据库连接信息：
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'trademark_db');
   define('DB_USER', 'username');
   define('DB_PASS', 'password');
   ```

6. **设置权限**
   ```bash
   chmod 755 logs
   chmod 644 config.php
   ```

7. **配置Web服务器**

   **Apache**：确保 `.htaccess` 文件生效，启用 `mod_rewrite`
   ```bash
   a2enmod rewrite
   a2enmod deflate
   a2enmod expires
   a2enmod headers
   ```

   **Nginx**：添加以下配置
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   
   location ~ \.php$ {
       fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
       fastcgi_index index.php;
       include fastcgi_params;
   }
   
   location ~ /\.(sql|log|ini|conf|md)$ {
       deny all;
   }
   ```

## 配置说明

### config.php 配置项

```php
// 数据库配置
define('DB_HOST', 'localhost');      // 数据库主机
define('DB_NAME', 'trademark_db');   // 数据库名称
define('DB_USER', 'username');       // 数据库用户
define('DB_PASS', 'password');       // 数据库密码

// 分页配置
define('PAGE_SIZE', 20);             // 每页结果数
define('MAX_PAGE_SIZE', 100);        // 最大每页结果数

// 搜索配置
define('SEARCH_MIN_LENGTH', 1);      // 最小搜索长度
define('SEARCH_MAX_LENGTH', 100);    // 最大搜索长度

// 安全配置
define('RATE_LIMIT_ENABLED', true);  // 启用速率限制
define('RATE_LIMIT_REQUESTS', 60);   // 每分钟最大请求数

// 调试模式
define('DEBUG_MODE', false);         // 生产环境设为false
```

## API 文档

### 搜索接口

**URL**: `/api/search.php`

**方法**: GET

**参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| q | string | 是 | 搜索关键词 |
| page | int | 否 | 页码，默认1 |
| pageSize | int | 否 | 每页数量，默认20 |
| category | string | 否 | 按大类筛选 |

**响应示例**:
```json
{
    "success": true,
    "data": {
        "results": [...],
        "stats": {
            "total": 100,
            "categories": ["第一类", "第二类"],
            "categoryCodes": ["01", "02"]
        },
        "keywords": ["关键词"],
        "pagination": {
            "current": 1,
            "total": 5,
            "pageSize": 20,
            "totalCount": 100,
            "hasNext": true,
            "hasPrev": false
        },
        "query": "搜索词"
    }
}
```

### 详情接口

**URL**: `/api/detail.php`

**方法**: GET

**参数**:
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 记录ID |
| q | string | 否 | 搜索关键词（用于高亮） |

### 分类列表接口

**URL**: `/api/categories.php`

**方法**: GET

**响应**: 返回所有大类及小类列表

## 数据导入

### 从文本文件导入

```bash
# 基本用法
python txt_to_mysql_full.py --user root --password 123456 --database trademark_db

# 清空现有数据后导入
python txt_to_mysql_full.py --user root --password 123456 --database trademark_db --clear

# 指定文件路径
python txt_to_mysql_full.py data.txt --user root --password 123456 --database trademark_db
```

### 文本文件格式

```
第一类
用于工业、科学、摄影、农业、园艺和林业的化学品...
【注释】
...

0101
工业气体,单质
(一)氨*010061,无水氨010066...

0102
用于工业、科学、农业、园艺、林业的工业化工原料
...

第二类
...
```

## 性能优化

### 数据库优化
- 已为常用字段创建索引
- 使用全文索引优化搜索性能
- 建议使用 Redis 缓存热门查询

### 前端优化
- CSS/JS 文件启用 Gzip 压缩
- 静态资源启用浏览器缓存
- 图片懒加载（如有图片）

### 服务器优化
- 启用 OPcache
- 配置适当的 PHP 内存限制
- 使用 CDN 加速静态资源

## 安全建议

1. **修改默认数据库密码**
2. **定期备份数据库**
3. **启用 HTTPS**
4. **限制 API 请求频率**
5. **定期更新依赖包**
6. **禁用 PHP 错误显示（生产环境）**

## 更新日志

### v2.0 (2024)
- 全新设计的用户界面
- 添加分页功能
- 支持分类筛选
- 优化搜索算法
- 添加速率限制
- 完善错误处理

### v1.0
- 初始版本发布
- 基础搜索功能
- 卡片/表格双视图

## 许可证

MIT License

## 联系方式

如有问题或建议，欢迎反馈。

---

**数据来源**: 类似商品和服务区分表——基于尼斯分类第十二版（2024文本）及NCL12-2025
