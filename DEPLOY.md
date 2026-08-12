# 美女导航（dhz 重构版）部署文档

> 重构版基于 Slim 4 + SQLite，部署时与生产零数据迁移。构建产物随 git 入库，服务器零构建。

---

## 一、环境要求

| 组件 | 要求 |
|---|---|
| PHP | ≥ 8.0（生产容器 8.0.30 已兼容） |
| 扩展 | pdo_sqlite、gd、curl、fileinfo、mbstring |
| Web 服务器 | Nginx（1Panel OpenResty 亦可） |
| Composer | 本地构建用（服务器可不用） |

## 二、目录结构要点

```
public/            # ★ Web 根（唯一对外入口，nginx 指向这里）
  index.php        # Slim 前端控制器
  assets/          # 构建产物 .min + manifest.json（随 git 入库）+ logo/favicon
data/              # SQLite 数据库（生产保留 data/nav.db，零迁移）
uploads/           # 上传目录（生产保留，图片路径不变）
app/               # 控制器/模型/服务/中间件
resources/         # 视图 + CSS/JS 源码
routes/web.php     # 全部路由
storage/cache/     # 文件缓存（可写）
tools/             # CLI：auto_article / polish_article / migrate_r2 / create_admin
build.php          # 前端构建（composer build）
```

## 三、部署步骤

### 1. 上传代码

```bash
# 首次：初始化 git 并关联远端（meinv-nav 尚未初始化）
cd meinv-nav
git init
git remote add origin git@github.com:isexbt-ai/web-dh.git   # 关联生产远端
git add -A && git commit -m "重构完成"

# 替换站点根为 public/（保留原站为 site_backup/ 以便回滚）
rsync -av --exclude='.env' --exclude='data' --exclude='uploads' ./ /opt/.../dhz/
```

> 后续更新走 `git pull`；`composer build` 产物随 git 入库，服务器零构建。

### 2. 数据与上传保留（零迁移）

```bash
# 保留原站的数据库、上传目录、logo
cp 原站/data/nav.db       新站/data/nav.db
cp -r 原站/uploads/*       新站/uploads/
cp 原站/assets/images/logo.png 新站/public/assets/images/logo.png 2>/dev/null || true
```

> `data/nav.db` 表结构与新代码完全兼容（迁移脚本幂等：CREATE IF NOT EXISTS + ALTER 补列）。

### 3. 配置 .env

```bash
cp .env.example .env
vim .env   # 填写 SITE_DOMAIN / R2_* 等
```

### 4. 依赖与构建（本地完成，随包上传）

```bash
composer install --no-dev --optimize-autoloader   # 出 vendor/
composer build                                    # 出 public/assets/*.min.* + manifest.json
```

> 服务器不跑构建；改源码后本地重新 `composer build` 再提交，hash 自动变化。

### 5. Nginx 配置（1Panel 伪静态 / OpenResty）

```nginx
server {
    listen 80;
    server_name dh.xlbk.blog;

    root /opt/.../dhz/public;   # ★ 指向 public/
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;   # 静态文件直接返回，其余走 Slim
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;   # 或 unix socket / 容器端口
    }

    # 禁止访问敏感目录
    location ~ ^/(data|storage|uploads)/.*\.php$ { deny all; }
    location ~ /\.(git|env) { deny all; }
}
```

> `try_files $uri $uri/ /index.php` 保证旧 URL 由 Slim 的 301 路由接管（detail.php 等）。

### 6. 目录权限

```bash
chown -R www-data:www-data storage/ data/ uploads/
chmod -R 755 storage/ data/
```

### 7. 创建管理员并登录验证

```bash
docker exec <php容器> php /www/sites/dhz/index/tools/create_admin.php admin 你的强密码 superadmin
# 访问 /admin/login 登录 → 立即改默认设置
```

## 四、本地开发

```bash
php -S 127.0.0.1:8080 -t public public/dev-server.php
# dev-server 脚本：静态文件直接返回，其余走 Slim（.html 友好 URL 也正常）
```

## 五、CLI 工具

| 脚本 | 用途 |
|---|---|
| `tools/auto_article.php` | 扫描 `sitehtml/` 自动发布文章（R2 可选） |
| `tools/polish_article.php` | 一键润色指定文章 |
| `tools/migrate_r2.php` | 迁移本地图片到 R2 |
| `tools/create_admin.php` | 创建/更新管理员 |

## 六、全站回归清单（部署后逐项验证）

- [ ] `/` 首页 200，分类/卡片/公告/轮播显示
- [ ] `/card/{id}.html`、`/category/{id}.html` 正常
- [ ] `/articles`、`/article/{id}-{slug}.html` 正常（分页/详情）
- [ ] `/guestbook` 留言列表 + 提交成功
- [ ] `/showcase` 效果展示 + 相册切换 + 全屏查看
- [ ] `/sitemap.xml`、`/robots.txt`、`/feed.xml`、`/sw.js` 输出正常
- [ ] 旧 URL 301：`/detail.php?id=1` → `/card/1.html` 等
- [ ] 后台 `/admin/login` 登录 → 各模块 CRUD 可用
- [ ] 后台写操作后前台首页缓存失效（刷新可见新数据）
- [ ] CSRF：无 token 的后台写请求被拒
- [ ] 资源引用为 `*.min.*?v=hash`，无手工 `?v=` 混用
- [ ] 数据零丢失：卡片/文章/留言/统计/配置切换后完整可见

## 七、回滚

保留原站目录为 `site_backup/`；切换 nginx root 指回旧目录即可。构建产物随 git 入库，回滚 = 回滚 commit。
