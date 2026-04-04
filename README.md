
# 🚀 SBFL - 中国商标分类智能检索系统

![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&amp;logo=php)
![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=for-the-badge&amp;logo=mysql)
![Status](https://img.shields.io/badge/Status-Stable-green?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)

基于尼斯分类第十二版（2024文本）及NCL12-2025构建的专业商标分类查询系统

[功能特性](#-功能特性) • [快速开始](#-快速开始) • [技术架构](#-技术架构) • [API文档](#-api-文档)

---

## ✨ 功能特性

| 核心功能 | 用户体验 | 管理能力 |
|---------|---------|---------|
| 🎯 智能中文分词搜索 | 📱 完美响应式设计 | 📋 全部分类浏览 |
| 🔍 多维度检索 | ⌨️ 键盘快捷键支持 | 🔎 页面内快速筛选 |
| 📂 分类筛选功能 | 💾 本地存储偏好 | 📖 小类详情展开 |
| 📊 双视图模式 | 🎨 平滑过渡动画 | |
| 📄 详情弹窗 | ⬆️ 一键返回顶部 | |
| 📑 分页加载 | | |

## 🎯 系统亮点

- **智能分词引擎**：基于结巴分词，精准解析中文查询
- **全文索引优化**：MySQL全文索引，毫秒级搜索响应
- **零框架依赖**：原生JavaScript，极致加载速度
- **安全性优先**：内置速率限制、SQL注入防护
- **生产就绪**：完整的错误处理和日志系统

---

## 🚀 快速开始

### 环境要求

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx Web服务器
- Composer

### 一键安装

```bash
# 1. 克隆项目
git clone &lt;repository-url&gt;
cd sbfl

# 2. 安装依赖
composer install

# 3. 创建数据库
mysql -u root -p &lt;&lt; 'EOF'
CREATE DATABASE trademark_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF

# 4. 导入数据
mysql -u root -p trademark_db &lt; data.sql

# 5. 配置环境
cp config.php config.local.php
# 编辑 config.local.php 填入你的数据库信息

# 6. 启动服务
# Apache/Nginx 配置指向项目目录
```

### Docker 部署（可选）

```dockerfile
# 即将推出
```

---

## 🏗️ 技术架构

### 后端技术栈

| 技术 | 版本 | 用途 |
|-----|------|------|
| PHP | 7.4+ | 核心后端语言 |
| MySQL | 5.7+ | 关系型数据库 |
| jieba-php | ~0.40 | 中文分词引擎 |
| PDO | - | 数据库访问层 |

### 前端技术栈

- 原生 JavaScript（无框架依赖）
- CSS3 现代样式与动画
- 响应式设计（移动优先）

### 项目结构

```
sbfl/
├── api/                    # RESTful API 接口
│   ├── search.php            # 搜索接口
│   ├── detail.php            # 详情接口
│   ├── categories.php        # 分类列表接口
│   └── test.php              # 健康检查
├── static/                # 静态资源
│   └── style.css             # 样式表
├── index.php              # 首页入口
├── all_categories.php     # 分类浏览页
├── config.php             # 配置文件（模板）
├── data.sql               # 数据库结构与数据
├── txt_to_mysql_full.py   # 数据导入工具
├── .htaccess              # Apache 配置
├── nginx.conf             # Nginx 配置
└── composer.json          # 依赖管理
```

---

## ⚙️ 配置指南

### 数据库配置

编辑 `config.php` 或创建 `config.local.php`：

```php
&lt;?php
// 优先使用环境变量（推荐）
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'trademark_db');
define('DB_USER', getenv('DB_USER') ?: 'username');
define('DB_PASS', getenv('DB_PASS') ?: 'password');

// 分页配置
define('PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// 安全配置
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 60);

// 调试模式（生产环境设为 false）
define('DEBUG_MODE', false);
```

### 环境变量设置

**Apache (.htaccess)**：
```apache
SetEnv DB_HOST localhost
SetEnv DB_NAME trademark_db
SetEnv DB_USER your_username
SetEnv DB_PASS your_password
```

**Nginx**：
```nginx
fastcgi_param DB_HOST localhost;
fastcgi_param DB_NAME trademark_db;
fastcgi_param DB_USER your_username;
fastcgi_param DB_PASS your_password;
```

**Shell**：
```bash
export DB_HOST=localhost
export DB_NAME=trademark_db
export DB_USER=your_username
export DB_PASS=your_password
```

---

## 📡 API 文档

### 搜索接口

**端点**: `GET /api/search.php`

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| q | string | 是 | 搜索关键词 |
| page | int | 否 | 页码，默认 1 |
| pageSize | int | 否 | 每页数量，默认 20 |
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

**端点**: `GET /api/detail.php`

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 记录 ID |
| q | string | 否 | 搜索关键词（用于高亮） |

### 分类列表接口

**端点**: `GET /api/categories.php`

返回所有大类及小类列表。

---

## 📊 性能优化

### 数据库优化

- ✅ 常用字段已建立索引
- ✅ 全文索引优化搜索性能
- 💡 建议使用 Redis 缓存热门查询

### 前端优化

- ✅ 原生 JavaScript 零依赖
- ✅ CSS/JS 支持 Gzip 压缩
- ✅ 静态资源浏览器缓存

### 服务器优化

- 💡 启用 OPcache
- 💡 配置适当的 PHP 内存限制
- 💡 使用 CDN 加速静态资源

---

## 🔒 安全最佳实践

1. **永远不要提交配置文件** - 项目已包含 `.gitignore`
2. **使用环境变量** - 生产环境通过环境变量传递凭据
3. **启用 HTTPS** - 加密所有传输数据
4. **定期备份数据库** - 设置自动化备份策略
5. **使用强密码** - 包含大小写、数字、特殊字符
6. **限制数据库用户权限** - 遵循最小权限原则
7. **定期更新依赖** - 保持 Composer 依赖最新
8. **审查访问日志** - 监控异常访问行为
9. **禁用错误显示** - 生产环境 `DEBUG_MODE = false`

---

## 📝 更新日志

### v2.0.1 (2026)
- 🎨 全面提升 UI 设计
- ⚡ 更稳定的搜索性能
- 💾 优化资源占用
- 🚀 整体性能提升

### v2.0 (2024)
- 🎨 全新设计的用户界面
- 📄 新增分页功能
- 📂 支持分类筛选
- ⚡ 优化搜索算法
- 🛡️ 添加速率限制
- 🐛 完善错误处理

### v1.0
- 🎉 初始版本发布
- 🔍 基础搜索功能
- 📊 卡片/表格双视图

---

## 📄 许可证

本项目采用 [MIT License](LICENSE) 开源许可证。

---

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

---

## 📮 联系方式

如有问题或建议，欢迎通过以下方式联系：

- 提交 [Issue](../../issues)
- 发送 Pull Request

---

**数据来源**: 类似商品和服务区分表——基于尼斯分类第十二版（2024文本）及NCL12-2025

Made with ❤️ by the SBFL Team

