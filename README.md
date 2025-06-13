# 中国商标商品服务分类智能检索

本项目是一个基于尼斯分类（第十二版，2024文本）实现的“中国商标商品服务分类智能检索”系统。支持商品/服务/类别/小类等多关键词智能搜索，结果高亮显示，可切换卡片/表格模式，便于商标注册、查询与参考。

## 功能简介

- 🔍 智能检索：支持商品、服务、大类、小类等多种关键词搜索
- 🎨 高亮展示：命中关键词自动高亮
- 🗂 视图切换：卡片模式 & 表格模式自由切换
- 📄 数据来源：基于尼斯分类第十二版（2024文本）
- 📦 支持 API 查询和前端交互
- 🐍 附带 Python 脚本辅助文本数据导入 MySQL

## 目录结构（部分）

- `index.php`         —— 前端主页面，集成搜索与展示交互
- `api/`              —— API 目录，包含数据检索后端接口（如 search.php, detail.php 等）
- `static/`           —— 静态资源（如 CSS、JS）
- `install.sql`       —— MySQL 初始化数据脚本
- `45.txt`            —— 原始商品/服务数据文本
- `txt_to_mysql.py`   —— 文本数据导入 MySQL 的 Python 脚本
- `all_categories.php`—— 类别数据处理脚本
- `composer.json`     —— PHP 依赖管理
- `config.php`        —— 数据库连接等配置信息

## 部署与运行

### 1. 环境准备

- PHP 7.2+，推荐 8.x
- MySQL 5.7+ 或兼容数据库
- Composer（PHP 依赖管理器）
- 可选：Python 3.x（用于数据导入脚本）

### 2. 安装依赖

```bash
composer install
```

### 3. 数据库初始化

1. 创建数据库并导入 `install.sql` 文件：

```bash
mysql -u youruser -p yourdb < install.sql
```

2. 配置数据库连接，在 `config.php` 填写相关信息。

### 4. 数据导入（如需从 txt 文件重新导入）

```bash
python3 txt_to_mysql.py
```

### 5. 启动服务

将项目部署至支持 PHP 的 Web 服务器（如 Apache/Nginx），访问 `index.php` 即可使用。

## 主要界面说明

- 首页即为智能检索入口，输入关键词可实时查询商品/服务类别。
- 支持卡片和表格两种展示模式，可切换。
- 结果点击“查看详情”可弹窗展示详细信息和小类说明。

## 数据来源

- 类似商品和服务区分表——基于尼斯分类第十二版（2024文本）
- [中国商标网 - 尼斯国际分类](https://sbj.cnipa.gov.cn/sbj/sbsq/sphfwfl/)

## 许可证

MIT License

---

如有建议或问题，欢迎提 Issue 或 PR！
