# 美女导航站

## 项目简介

一个基于 PHP + SQLite 的导航站，包含前台展示页面和后台管理系统。支持广告位、公告、分类目录、导航卡片等模块的完整管理。

## 技术栈

- **后端**: PHP 8.x + PDO + SQLite
- **前端**: 纯 HTML + CSS + JavaScript（无框架依赖）
- **样式**: 自定义 CSS（暗色主题）

## 目录结构

```
美女导航/
├── index.php              # 前台首页
├── admin/                 # 后台管理
│   ├── index.php          # 登录页
│   ├── dashboard.php      # 仪表盘（访问统计）
│   ├── config.php         # 站点配置
│   ├── ads.php            # 广告管理
│   ├── notices.php        # 公告管理
│   ├── categories.php     # 分类管理
│   ├── cards.php          # 卡片管理
│   ├── logout.php         # 退出登录
│   └── api/               # API接口
│       ├── cards.php      # 获取卡片
│       ├── click.php      # 记录点击
│       ├── upload.php     # 图片上传
│       ├── save.php       # 通用保存
│       └── delete.php     # 通用删除
├── includes/              # 公共文件
│   ├── db.php             # 数据库连接
│   ├── functions.php      # 公共函数
│   └── auth.php           # 登录认证
├── assets/                # 静态资源
│   ├── css/
│   │   ├── style.css      # 前台样式
│   │   └── admin.css      # 后台样式
│   └── js/
│       ├── main.js        # 前台交互
│       └── admin.js       # 后台交互
├── uploads/               # 上传图片
│   ├── ads/
│   ├── cards/
│   └── avatar/
└── data/
    └── nav.db             # SQLite数据库
```

## 功能特性

### 前台功能
- 头像和联系方式展示
- 广告位轮播（支持多张广告自动切换）
- 公告轮播展示
- 分类目录Tab切换
- 图片卡片网格展示（4列响应式布局）
- 功能菜单（复制链接、分享页面、后台入口）
- 响应式设计（PC/平板/手机适配）

### 后台功能
- **仪表盘**: 今日访问、总访问量、卡片数量、分类数量统计 + 本周访问趋势图 + 热门卡片排行
- **站点配置**: 网站标题、描述、联系方式、头像上传
- **广告管理**: 添加/编辑/删除广告，支持图片上传、排序、启用/禁用
- **公告管理**: 添加/编辑/删除公告，支持排序、启用/禁用
- **分类管理**: 添加/编辑/删除分类，支持排序、启用/禁用
- **卡片管理**: 添加/编辑/删除卡片，支持图片上传、分类选择、排序、启用/禁用
- **访问统计**: 自动记录每次访问，支持按天统计

## 部署说明

### 环境要求
- PHP 8.x
- SQLite 扩展（php-sqlite3）
- GD 扩展（用于图片处理，可选）

### 安装步骤

1. 将项目文件上传至Web服务器目录
2. 确保以下目录可写（chmod 755）：
   - `uploads/`（图片上传目录）
   - `data/`（数据库目录）
3. 访问网站首页，系统会自动初始化数据库
4. 访问 `admin/` 进入后台登录页

### 默认账号
- 用户名: `admin`
- 密码: `admin`

> ⚠️ **重要**: 首次登录后请立即修改密码！

### 修改密码
目前需要在数据库中手动修改，或使用PHP代码：
```php
$hash = password_hash('新密码', PASSWORD_DEFAULT);
// 更新 admin_users 表的 password 字段
```

## 使用指南

### 前台使用
1. 打开首页即可看到导航站内容
2. 点击分类Tab切换不同分类的卡片
3. 点击卡片图片跳转到对应网站
4. 点击右上角「功能」按钮可使用复制链接、分享页面等功能

### 后台使用
1. 访问 `admin/` 登录后台
2. 在仪表盘查看访问统计
3. 通过左侧导航菜单管理各个模块：
   - **站点配置**: 设置网站标题、头像、联系方式
   - **广告管理**: 添加轮播广告图片和链接
   - **公告管理**: 添加网站公告
   - **分类管理**: 添加/编辑导航分类
   - **卡片管理**: 添加导航卡片（选择分类、上传图片、设置链接）

### 图片上传
- 支持格式: jpg, png, gif, webp
- 最大大小: 2MB
- 上传后会自动保存到对应目录

## 安全特性
- 密码使用 password_hash 加密存储
- 所有用户输入使用 htmlspecialchars 转义
- SQL注入防护（PDO预处理语句）
- 图片上传类型白名单校验
- 后台接口需要登录验证

## 数据库表结构

### site_config（站点配置）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| key | TEXT | 配置键名 |
| value | TEXT | 配置值 |
| type | TEXT | 类型（text/image/color） |

### ads（广告位）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键，自增 |
| title | TEXT | 广告标题 |
| image | TEXT | 广告图片路径 |
| link | TEXT | 跳转链接 |
| sort_order | INTEGER | 排序 |
| is_active | INTEGER | 是否启用 |

### notices（公告）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键，自增 |
| title | TEXT | 公告标题 |
| content | TEXT | 公告内容 |
| sort_order | INTEGER | 排序 |
| is_active | INTEGER | 是否启用 |

### categories（分类目录）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键，自增 |
| name | TEXT | 分类名称 |
| sort_order | INTEGER | 排序 |
| is_active | INTEGER | 是否启用 |

### cards（导航卡片）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键，自增 |
| category_id | INTEGER | 所属分类ID |
| title | TEXT | 卡片标题 |
| image | TEXT | 卡片图片路径 |
| link | TEXT | 跳转链接 |
| sort_order | INTEGER | 排序 |
| click_count | INTEGER | 点击次数 |
| is_active | INTEGER | 是否启用 |

### visit_stats（访问统计）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键，自增 |
| page | TEXT | 访问页面 |
| ip | TEXT | 访问IP |
| user_agent | TEXT | 浏览器信息 |
| visit_date | DATE | 访问日期 |

## 扩展建议

1. **主题换肤**: 支持多套配色方案切换
2. **缓存优化**: 对前台页面进行文件缓存
3. **SEO优化**: 生成静态HTML、自定义meta信息
4. **多语言支持**: 中英文切换
5. **数据备份**: 一键导出/导入数据库
6. **密码修改**: 在后台添加修改密码功能

## 许可证

本项目仅供学习和参考使用。
