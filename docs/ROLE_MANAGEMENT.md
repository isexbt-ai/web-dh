# 角色权限分级说明

## 当前实现

项目支持两种角色：

| 角色 | 标识 | 权限 |
|------|------|------|
| **管理员** | `admin` | 常规管理权限（默认） |
| **超级管理员** | `superadmin` | 全部权限，包括敏感操作 |

## 如何切换角色

### 方法1：直接修改数据库

```sql
-- 将某个用户设为超级管理员
UPDATE admin_users SET role = 'superadmin' WHERE username = 'admin';

-- 将某个用户设为普通管理员
UPDATE admin_users SET role = 'admin' WHERE username = '某个用户';
```

### 方法2：通过 SQLite 命令行

```bash
sqlite3 data/nav.db
```

```sql
-- 查看当前用户角色
SELECT username, role FROM admin_users;

-- 修改角色
UPDATE admin_users SET role = 'superadmin' WHERE username = 'admin';

-- 退出
.quit
```

## 角色区别

### 普通管理员 (admin)
- 可以管理卡片、分类、公告、广告等常规内容
- 可以查看统计数据
- 可以管理留言板

### 超级管理员 (superadmin)
- 包含普通管理员的所有权限
- 可以管理其他管理员账号（未来扩展）
- 可以执行系统级操作（未来扩展）
- 可以查看安全日志（未来扩展）

## 代码中使用

```php
// 检查是否为超级管理员
if (isSuperAdmin()) {
    // 执行敏感操作
}

// 要求超级管理员权限
requireSuperAdmin(); // 不是超级管理员会返回403
```

## 注意事项

1. 默认创建的第一个管理员账号角色为 `admin`
2. 需要手动通过数据库修改角色为 `superadmin`
3. 角色信息存储在 `admin_users` 表的 `role` 字段
4. 登录时会自动从数据库加载角色到 Session
