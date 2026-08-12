# 美女导航（dhz 重构版）开发规范

> 本规范为 dhz 重构项目（Slim 4）的**强制约定**，编写任何代码前先读此文档，编码时严格遵守。

---

## 1. 编码风格（PSR-12）

- 缩进 4 空格；类/方法大括号换行，控制结构大括号同行
- 行宽 ≤ 120 字符（长链式调用换行缩进）
- 文件末尾保留一个换行；PHP 标签统一 `<?php`（不闭合 `?>`）
- 命名：类 `PascalCase`、方法/变量 `camelCase`、常量 `UPPER_SNAKE_CASE`、受保护属性 `$_` 前缀（如 `$_pdo`）
- 严格类型：所有文件开头 `declare(strict_types=1);`
- 不用 `global` 关键字取依赖，一律通过容器注入

## 2. 分层架构

```
Controller（薄：收参/校验/调 Service/渲染）
  → Service（业务逻辑，可复用）
    → Model（数据访问，只操作 SQLite，不含业务判断）
      → PDO
```

- **Controller 只做 3 件事**：接收请求参数 → 调用 Service → 返回响应
- **Model 不做业务判断**：`getActive()` 这种筛选交给查询条件，`is_active = ?` 参数化
- **Service 不做 SQL**：数据读写全部走 Model
- 跨层禁止：Controller 直接 new Model 拼 SQL、模板里写查询

## 3. 类型与输入

- PHP 8.0 联合类型 `?string`、`int|string`；参数和返回值必须声明类型
- 所有外部输入（`$_GET/$_POST/$_FILES/$_SERVER`）必须经参数校验，禁止裸用
- 整型输入用 `(int)` + 范围校验；字符串用 `trim()` + 长度限制（常量 `MAX_*`）
- 上传文件：校验扩展名（白名单）+ MIME（finfo）+ 大小上限，全部拒绝时**不泄露内部检测细节**

## 4. 安全铁律（禁止违反）

1. **SQL**：一律 PDO 预处理（`prepare + execute` 绑定参数），禁止字符串拼接 SQL
2. **XSS**：输出一律 `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`，模板统一走 `e()` 辅助函数
3. **CSRF**：所有写操作（POST/PUT/DELETE）必须校验 CSRF token；token 存 session，比对用 `hash_equals`
4. **认证**：登录后 `session_regenerate_id(true)`；session cookie `httponly + samesite=Strict + secure(生产)`
5. **暴力破解**：登录失败按 IP 记录，5 次锁定 15 分钟（存 `login_attempts` 表）
6. **敏感信息**：R2 密钥/管理员密码/umami 全部读 `.env`，**禁止硬编码**；`.env` 不入 git
7. **错误隐藏**：用户侧统一"系统维护中"提示，详情只进 `error_log()`
8. **文件删除**：`realpath()` + `str_starts_with()` 验证路径在 uploads 目录内
9. **javascript: 协议**：link/ad/card 的 URL 一律过滤 `javascript:` 前缀

## 5. 数据库（SQLite）

- 连接：PDO `ERRMODE_EXCEPTION` + `FETCH_ASSOC`，开启 `WAL` + `busy_timeout=5000`
- 迁移：`migrations.php` 只 `CREATE TABLE IF NOT EXISTS` + `ALTER ADD COLUMN`，**禁止 DROP/ALTER 现有字段**
- 索引：常用查询列建索引（`idx_*` 命名）
- 时间：统一存 `CURRENT_TIMESTAMP` / `date('Y-m-d H:i:s')`，输出格式化放 View 层
- 批查：禁止循环内查库（N+1），一次 `JOIN`/`IN` 取出

## 6. 前端规范

- **风格**：沿用现有浅色主题 + 品牌红 `#e94560`；**禁止**蓝紫渐变、霓虹光晕、玻璃拟态、花哨动画
- JS：IIFE 模块，`<script defer>` 引入；**禁止** inline onclick（用事件委托）；交互状态必须覆盖 loading/empty/error
- CSS：收敛到文件（`style.css`/`pages.css`/`admin.css`），**禁止**每页大段内联 style（<10 行微调可留）
- 资源引用：**一律走 `asset()` 辅助函数**读 manifest.json 生成 `.min?v=hash` URL，禁止手写版本号
- 图片：`<img>` 补 `width/height` 或 `aspect-ratio`、`alt`、懒加载 `loading="lazy"`
- 语义：首页用可见 H1（或 `.sr-only` + aria-label），禁止 `left:-9999px` 黑帽手法

## 7. 错误处理与日志

- 业务异常用自定义 `AppException`（含用户消息），框架异常用 `ErrorMiddleware` 兜底
- `error_log()` 记录上下文：`[模块] 操作, user=xxx, ip=xxx, msg=...`
- 禁止吞异常、禁止裸 `catch (Exception)` 不记录

## 8. 构建与版本号

- 源码在 `resources/`，构建产物（`*.min.*` + `manifest.json`）在 `public/assets/`，**产物随 git 入库**
- 改源码后必须本地跑 `php build.php` 重建，版本号（内容 hash）自动变化
- 服务器**不跑构建**，`git pull` 即生效

## 9. 功能与变更纪律

- 只实现 REFACTOR_PLAN.md 已列功能，**不新增未请求功能**
- 不注释掉旧代码（用 git 历史）；不留调试残留
- 新增函数/方法必须带一句用途注释（docblock 保持简短）
- 每阶段完成：`php -l` 全绿 + `php -S` 本地跑通对应页面后再进下一阶段

---

## 本规范验收方式

提交前自查：`php -l` 全文件通过、无硬编码密钥、无裸 SQL 拼接、无 inline onclick、资源引用走 `asset()`、页面可访问。
