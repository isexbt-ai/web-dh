# 美女导航站

## 项目简介

一个基于 PHP + SQLite 的轻量级导航站，包含前台展示页面和后台管理系统。支持广告位、公告、分类目录、导航卡片、效果展示、留言板等模块的完整管理。

## 技术栈

- **后端**: PHP 8.x + PDO + SQLite
- **前端**: 纯 HTML + CSS + JavaScript（无框架依赖）
- **样式**: 自定义 CSS（浅色主题）
- **缓存**: 文件缓存 + Service Worker 离线缓存
- **PWA**: 支持离线访问

## 目录结构

```
美女导航/
├── index.php              # 前台首页
├── detail.php             # 卡片详情页
├── guestbook.php          # 留言板
├── showcase.php           # 效果展示页
├── sitemap.php            # 站点地图
├── sw.js                  # Service Worker（离线缓存）
├── manifest.json          # PWA 配置清单
├── .htaccess              # Apache 配置（缓存、安全头、Gzip）
├── admin/                 # 后台管理
│   ├── index.php          # 登录页
│   ├── dashboard.php      # 仪表盘
│   ├── config.php         # 站点配置
│   ├── ads.php            # 广告管理
│   ├── notices.php        # 公告管理
│   ├── categories.php     # 分类管理
│   ├── cards.php          # 卡片管理
│   ├── showcase.php       # 效果展示管理
│   ├── links.php          # 链接管理
│   ├── messages.php       # 留言管理
│   ├── ip_stats.php       # IP统计
│   ├── password.php       # 修改密码
│   ├── logout.php         # 退出登录
│   └── api/               # API接口
│       ├── cards.php      # 获取卡片
│       ├── click.php      # 记录点击
│       ├── upload.php     # 文件上传
│       ├── save.php       # 通用保存
│       ├── delete.php     # 通用删除
│       ├── messages.php   # 留言API
│       └── ip_query.php   # IP归属地查询
├── includes/              # 公共文件
│   ├── db.php             # 数据库连接
│   ├── functions.php      # 公共函数库
│   └── auth.php           # 登录认证
├── assets/                # 静态资源
│   ├── css/
│   │   ├── style.css      # 前台样式（未压缩）
│   │   ├── style.min.css  # 前台样式（压缩版）
│   │   ├── admin.css      # 后台样式（未压缩）
│   │   └── admin.min.css  # 后台样式（压缩版）
│   └── js/
│       ├── main.js        # 前台交互（未压缩）
│       ├── main.min.js    # 前台交互（压缩版）
│       ├── admin.js       # 后台交互（未压缩）
│       └── admin.min.js   # 后台交互（压缩版）
├── uploads/               # 上传文件目录
│   ├── ads/
│   ├── cards/
│   ├── avatar/
│   └── showcase/
├── data/                  # 数据目录
│   ├── nav.db             # SQLite数据库
│   └── cache_*.json       # 文件缓存
├── logs/                  # 日志目录
│   ├── security_*.log     # 安全日志
│   └── access_*.log       # 访问日志
└── docs/                  # 文档
    └── ROLE_MANAGEMENT.md # 角色管理说明
```

## 功能特性

### 前台功能
- 头像和联系方式展示
- 广告位轮播（支持多张广告自动切换）
- 公告轮播展示
- 分类目录Tab切换（AJAX加载）
- 图片卡片网格展示（响应式布局）
- 卡片角标（外链/详情/自定义）
- 效果展示页面（支持WebP动图/视频）
- 留言板（支持无限滚动加载）
- 功能菜单（复制链接、分享页面）
- 返回顶部按钮
- 响应式设计（PC/平板/手机适配）
- PWA 支持（可添加到桌面，离线访问）

### 后台功能
- **仪表盘**: 今日访问、总访问量、卡片数量、分类数量统计 + 本周访问趋势图 + 热门卡片排行
- **站点配置**: 网站标题、描述、联系方式、头像上传、卡片布局配置
- **广告管理**: 添加/编辑/删除广告，支持图片上传、排序、启用/禁用
- **公告管理**: 添加/编辑/删除公告，支持排序、启用/禁用
- **分类管理**: 添加/编辑/删除分类，支持排序、启用/禁用
- **卡片管理**: 添加/编辑/删除卡片，支持图片上传、分类选择、排序、启用/禁用、角标设置
- **效果展示**: 管理展示图片/视频，支持相册分类、图床上传
- **链接管理**: 管理前台功能菜单链接
- **留言管理**: 管理留言板内容，支持回复、删除、恢复
- **IP统计**: 查看访问IP统计、归属地查询、地域分布
- **修改密码**: 安全修改管理员密码

## 部署说明

### 环境要求
- PHP 8.x
- SQLite 扩展（php-sqlite3）
- GD 扩展（用于图片处理）
- fileinfo 扩展（用于文件类型检测）

### 安装步骤

1. 将项目文件上传至Web服务器目录
2. 确保以下目录可写（chmod 755）：
   - `uploads/`（文件上传目录）
   - `data/`（数据库目录）
   - `logs/`（日志目录）
3. 访问网站首页，系统会自动初始化数据库
4. 访问 `admin/` 进入后台登录页

### 默认账号
- 用户名: `admin`
- 密码: 随机生成（查看服务器日志获取初始密码）

> ⚠️ **重要**: 首次登录后请立即修改密码！

## 安全特性

### 基础安全
- 密码使用 `password_hash()` 加密存储
- 所有用户输出使用 `htmlspecialchars()` 转义（XSS防护）
- SQL注入防护（全面使用PDO预处理语句）
- CSRF Token 验证（所有表单和API）
- Session 安全（httponly、SameSite=Strict、30分钟超时）
- Session Fixation 防护（登录后重新生成Session ID）
- 安全响应头（X-Frame-Options、X-Content-Type-Options等）

### 访问控制
- 登录锁定（5次失败后锁定15分钟）
- API 速率限制（基于IP的文件缓存实现）
- 文件上传安全（MIME检测、扩展名白名单、随机文件名）
- 路径验证（防止目录遍历攻击）
- 后台访问频率限制

### 日志审计
- 安全事件日志（登录成功/失败/锁定、权限变更）
- 访问日志（请求方法、URI、IP、UA）
- 日志按天分割，存储在 `logs/` 目录

## 性能优化

### 数据库优化
- 关键字段索引（cards.category_id、visit_stats.ip、messages.ip等）
- N+1查询修复（使用JOIN批量查询替代循环单条查询）
- 访问统计异步队列（文件队列 + 批量写入）
- IP归属地缓存（ip_location_cache表，避免重复查询API）

### 前端优化
- CSS/JS 压缩（减少约30%体积）
- 图片懒加载（`loading="lazy"`）
- WebP 格式优先（支持格式回退）
- 响应式图片（`<picture>`标签 + `srcset`）
- Service Worker 离线缓存
- 浏览器缓存策略（.htaccess配置）
- Gzip 压缩

### 缓存策略
- 文件缓存（公告、分类等数据缓存5分钟）
- 内存缓存（站点配置批量加载到内存）
- 静态资源长期缓存（CSS/JS 7天，图片30天）

## 角色权限

支持两种角色：

| 角色 | 权限 |
|------|------|
| **管理员** (`admin`) | 常规管理权限（默认） |
| **超级管理员** (`superadmin`) | 全部权限，包括敏感操作 |

### 切换角色
需要通过数据库直接修改：
```sql
-- 设为超级管理员
UPDATE admin_users SET role = 'superadmin' WHERE username = 'admin';
```

详细说明见 [docs/ROLE_MANAGEMENT.md](docs/ROLE_MANAGEMENT.md)

## 数据库表结构

### site_config（站点配置）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| key | TEXT | 配置键名 |
| value | TEXT | 配置值 |
| type | TEXT | 类型（text/image/color/toggle） |

### admin_users（管理员）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| username | TEXT | 用户名 |
| password | TEXT | 密码哈希 |
| role | TEXT | 角色（admin/superadmin） |

### ads（广告位）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| title | TEXT | 广告标题 |
| image | TEXT | 广告图片路径 |
| link | TEXT | 跳转链接 |
| sort_order | INTEGER | 排序 |
| is_active | INTEGER | 是否启用 |

### notices（公告）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| title | TEXT | 公告标题 |
| content | TEXT | 公告内容 |
| sort_order | INTEGER | 排序 |
| is_active | INTEGER | 是否启用 |

### categories（分类目录）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| name | TEXT | 分类名称 |
| sort_order | INTEGER | 排序 |
| is_active | INTEGER | 是否启用 |

### cards（导航卡片）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| category_id | INTEGER | 所属分类ID |
| title | TEXT | 卡片标题 |
| image | TEXT | 卡片图片路径 |
| link | TEXT | 跳转链接 |
| detail | TEXT | 详情内容（Markdown） |
| card_type | TEXT | 卡片类型（link/detail） |
| badge_text | TEXT | 自定义角标文字 |
| sort_order | INTEGER | 排序 |
| click_count | INTEGER | 点击次数 |
| is_active | INTEGER | 是否启用 |

### showcase（效果展示）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| title | TEXT | 标题 |
| image | TEXT | 本地图片路径 |
| media_type | TEXT | 媒体类型（image/video） |
| imgbed_url | TEXT | 图床URL |
| gallery_id | INTEGER | 所属相册ID |
| sort_order | INTEGER | 排序 |
| is_active | INTEGER | 是否启用 |

### galleries（相册合集）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| title | TEXT | 相册名称 |
| description | TEXT | 描述 |
| cover_image | TEXT | 封面图 |
| sort_order | INTEGER | 排序 |
| is_active | INTEGER | 是否启用 |

### messages（留言板）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| nickname | TEXT | 昵称 |
| content | TEXT | 内容 |
| ip | TEXT | IP地址 |
| reply | TEXT | 管理员回复 |
| is_active | INTEGER | 是否显示 |

### visit_stats（访问统计）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| page | TEXT | 访问页面 |
| ip | TEXT | 访问IP |
| user_agent | TEXT | 浏览器信息 |
| visit_date | DATE | 访问日期 |

### ip_location_cache（IP归属地缓存）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| ip | TEXT | IP地址（UNIQUE） |
| country | TEXT | 国家 |
| region | TEXT | 省份 |
| city | TEXT | 城市 |
| isp | TEXT | 运营商 |

## API 接口

### 公开接口
| 接口 | 方法 | 说明 | 速率限制 |
|------|------|------|----------|
| `/admin/api/cards.php` | GET | 获取卡片列表 | 60次/分钟 |
| `/admin/api/click.php` | GET | 记录卡片点击 | 5秒/次 |
| `/admin/api/messages.php` | GET | 获取留言 | 120次/分钟 |
| `/admin/api/messages.php` | POST | 提交留言 | 5次/分钟 |

### 需要登录
| 接口 | 方法 | 说明 | 速率限制 |
|------|------|------|----------|
| `/admin/api/upload.php` | POST | 文件上传 | 10次/分钟 |
| `/admin/api/ip_query.php` | GET | IP归属地查询 | 30次/分钟 |
| `/admin/api/save.php` | POST | 通用保存 | 需CSRF |
| `/admin/api/delete.php` | POST | 通用删除 | 需CSRF |

## 使用指南

### 前台使用
1. 打开首页即可看到导航站内容
2. 点击分类Tab切换不同分类的卡片
3. 点击卡片图片跳转到对应网站
4. 点击右上角「功能」按钮可使用复制链接、分享页面等功能
5. 点击效果展示按钮查看精选内容
6. 点击留言板按钮发表留言

### 后台使用
1. 访问 `admin/` 登录后台
2. 在仪表盘查看访问统计和趋势
3. 通过左侧导航菜单管理各个模块

### 图片上传
- 支持格式: jpg, png, gif, webp, mp4, webm, mov
- 最大大小: 50MB（代码限制），10MB（.htaccess限制）
- 上传后自动生成缩略图
- 支持上传到图床（img.scdn.io）

## 扩展建议

1. **主题换肤**: 支持多套配色方案切换
2. **SEO优化**: 生成静态HTML、自定义meta信息
3. **多语言支持**: 中英文切换
4. **数据备份**: 一键导出/导入数据库
5. **邮件通知**: 新留言邮件提醒
6. **社交登录**: 支持微信/QQ登录

## 更新日志

### 2024-07-03 第三阶段
- Service Worker 离线缓存
- 无限滚动 + 骨架屏
- 角色权限分级

### 2024-07-03 第二阶段
- N+1查询修复
- API速率限制
- 安全日志系统
- 图片响应式 + WebP回退

### 2024-07-03 第一阶段
- 数据库索引优化
- XSS漏洞修复
- CSS/JS压缩
- data目录保护

## 许可证

本项目仅供学习和参考使用。
