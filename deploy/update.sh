#!/usr/bin/env bash
# 导航站一键更新脚本（Slim 4 + SQLite，零迁移，零构建）
# --------------------------------------------------------------
# 用途：在生产站点根目录下执行，拉取远程仓库最新代码并完成
#       composer 依赖更新、PHP 语法检查、运行时缓存清理。
# 用法：
#   chmod +x deploy/update.sh
#   ./deploy/update.sh                       # 默认拉 origin/main
#   BRANCH=main ./deploy/update.sh           # 自定义分支
#   REMOTE=origin ./deploy/update.sh         # 自定义远程名
#
# 前置：
#   1. 站点目录已是 git 仓库（remote = origin）
#   2. 宿主机有 php 命令行（用于 php -l 语法检查；可选）
#   3. 依赖变更时宿主机有 composer
#
# 与图站 update.sh 的差异：
#   - 不跑迁移：Slim 启动时自动调 Migrations::run（IF NOT EXISTS + ALTER ADD，幂等）
#   - 不跑构建：前端构建产物已随 git 入库
#   - 多了 PHP 语法检查：拉完立刻 php -l，错误自动回滚
#   - 缓存只清 storage/cache/*.cache，不动 data/ uploads/
#
# 安全：
#   更新前自动打 tag backup-<时间>-<旧 commit> 用于回滚
#   仅在 composer.json/lock 变更时跑 composer install
# --------------------------------------------------------------

set -euo pipefail

BRANCH="${BRANCH:-main}"

# 自动检测唯一 remote 作为默认 REMOTE
if [ "$(git remote 2>/dev/null | wc -l)" = "1" ]; then
    REMOTE="${REMOTE:-$(git remote)}"
else
    REMOTE="${REMOTE:-origin}"
fi

# PHP 二进制：宿主机 PATH 优先
PHP_BIN="${PHP_BIN:-$(command -v php 2>/dev/null || echo '')}"

red()    { printf '\033[31m%s\033[0m\n' "$*" >&2; }
green()  { printf '\033[32m%s\033[0m\n' "$*"; }
yellow() { printf '\033[33m%s\033[0m\n' "$*" >&2; }
blue()   { printf '\033[34m%s\033[0m\n' "$*"; }

# === 前置检查 ===
[ -d .git ] || { red "当前目录不是 git 仓库，请在站点根目录执行"; exit 1; }
command -v git >/dev/null || { red "未找到 git"; exit 1; }

# === 1. 拉取代码 ===
blue "[1/4] 拉取远程代码 ${REMOTE}/${BRANCH} ..."
git fetch --prune "$REMOTE" "$BRANCH"

LOCAL=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse "$REMOTE/$BRANCH")

if [ "$LOCAL" = "$REMOTE_COMMIT" ]; then
    green "当前已是最新提交（$(git log -1 --format='%h %s' HEAD)），无需更新"
    exit 0
fi

green "  本地: $LOCAL"
green "  远程: $REMOTE_COMMIT"
echo "  提交日志:"
git log --oneline "$LOCAL..$REMOTE_COMMIT" | sed 's/^/    /'

# 回滚锚点：更新前的 commit 打 tag
BACKUP_TAG="backup-$(date +%Y%m%d-%H%M%S)-${LOCAL:0:7}"
git tag "$BACKUP_TAG" "$LOCAL" >/dev/null 2>&1 || yellow "  ↳ 备份 tag 已存在，跳过"
yellow "  回滚锚点: $BACKUP_TAG"

# 强制更新到远程 commit（丢弃本地未推送修改）
git reset --hard "$REMOTE_COMMIT"
green "  ✓ 代码已更新"

# === 2. composer 依赖（按需） ===
blue "[2/4] 检查 composer 依赖变更 ..."
if git diff --name-only "$LOCAL" "$REMOTE_COMMIT" | grep -qE '^composer\.(json|lock)$'; then
    if command -v composer >/dev/null 2>&1; then
        composer install --no-dev --optimize-autoloader --no-interaction
        green "  ✓ composer 安装完成"
    else
        red "  ✗ composer.json 有变更但未安装 composer，请先安装或手动跑 composer install"
        exit 2
    fi
else
    yellow "  ↳ composer 文件无变化，跳过"
fi

# === 3. PHP 语法快速检查（按需） ===
blue "[3/4] PHP 语法检查 ..."
if [ -z "$PHP_BIN" ]; then
    yellow "  ↳ 未找到 php，跳过语法检查"
elif [ -d vendor ]; then
    # 仅检查本次提交变更的 PHP 文件（含 vendor 时也涵盖）
    changed_php=$(git diff --name-only "$LOCAL" "$REMOTE_COMMIT" -- '*.php' ':!vendor/*' || true)
    if [ -z "$changed_php" ]; then
        yellow "  ↳ 无 PHP 文件变更，跳过"
    else
        syntax_err=0
        for f in $changed_php; do
            if [ -f "$f" ] && ! $PHP_BIN -l "$f" >/dev/null 2>&1; then
                $PHP_BIN -l "$f"
                syntax_err=1
            fi
        done
        if [ "$syntax_err" = "1" ]; then
            red "  ✗ 语法错误，自动回滚到 $BACKUP_TAG"
            git reset --hard "$BACKUP_TAG"
            exit 3
        fi
        green "  ✓ 语法全部通过"
    fi
else
    yellow "  ↳ 未找到 vendor/，跳过 PHP 语法检查"
fi

# === 4. 清理运行时缓存（关键：避免旧 page_home 缓存冻住新代码） ===
blue "[4/4] 清理运行时缓存 ..."
rm -f storage/cache/*.cache 2>/dev/null || true
green "  ✓ 缓存清理完成"

# === 完成 ===
green "=========================================================="
green "  更新成功: ${LOCAL:0:7} → ${REMOTE_COMMIT:0:7}"
green "  回滚命令: git reset --hard $BACKUP_TAG"
green "=========================================================="
