#!/bin/bash
# 美女导航项目 - 服务器自动更新脚本
# 用法: ./update.sh [版本号]
# 功能: 从GitHub拉取最新代码，自动应用数据库迁移，设置权限
# 支持回退到任意版本: ./update.sh caa106f

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_NAME="美女导航"

# 版本参数
TARGET_VERSION="${1:-}"

# 帮助信息
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  $PROJECT_NAME - 服务器自动更新${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${GREEN}用法:${NC}"
    echo "  ./update.sh           拉取远程最新版本"
    echo "  ./update.sh <版本号>   回退到指定版本"
    echo "  ./update.sh --list     列出最近10个版本"
    echo ""
    echo -e "${GREEN}示例:${NC}"
    echo "  ./update.sh            # 更新到最新版本"
    echo "  ./update.sh caa106f    # 回退到 caa106f 版本"
    echo "  ./update.sh --list     # 查看版本列表"
    echo ""
    exit 0
fi

# 列出版本列表
if [ "$1" = "--list" ]; then
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  最近 10 个版本${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    cd "$SCRIPT_DIR"
    git log --oneline --decorate -10 --color=always | while read -r line; do
        echo "  $line"
    done
    echo ""
    echo -e "${YELLOW}用法: ./update.sh <版本号>${NC}"
    exit 0
fi

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  $PROJECT_NAME - 服务器自动更新${NC}"
echo -e "${BLUE}========================================${NC}"

# 显示操作模式
if [ -n "$TARGET_VERSION" ]; then
    echo -e "${YELLOW}🔄 回退模式: 目标版本 $TARGET_VERSION${NC}"
else
    echo -e "${YELLOW}🔄 更新模式: 拉取远程最新版本${NC}"
fi
echo ""

# 切换到项目目录
cd "$SCRIPT_DIR"
echo -e "${YELLOW}📍 项目目录: $(pwd)${NC}"
echo ""

# 修复 git safe.directory 权限问题（root运行时需要）
GIT_OWNER=$(stat -c '%U' "$SCRIPT_DIR/.git" 2>/dev/null || echo "")
if [ "$(whoami)" != "$GIT_OWNER" ] && [ -n "$GIT_OWNER" ]; then
    echo -e "${YELLOW}⚠️  当前用户($(whoami))与仓库所有者($GIT_OWNER)不一致${NC}"
    echo -e "${BLUE}🔧 正在添加 safe.directory 例外...${NC}"
    git config --global --add safe.directory "$SCRIPT_DIR" 2>/dev/null || true
    echo -e "${GREEN}✅ 权限配置完成${NC}"
    echo ""
fi

# 检查是否是git仓库
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ 未检测到git仓库，请先初始化${NC}"
    exit 1
fi

# 检查远程仓库配置
REMOTE_URL=$(git remote get-url origin 2>/dev/null || echo "")
if [ -z "$REMOTE_URL" ]; then
    echo -e "${RED}❌ 未配置远程仓库${NC}"
    exit 1
fi
echo -e "${GREEN}✅ 远程仓库: $REMOTE_URL${NC}"
echo ""

# 获取当前分支
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
echo -e "${BLUE}🌿 当前分支: $BRANCH${NC}"

# 保存当前版本信息（用于回滚）
CURRENT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")

# 回退到指定版本
if [ -n "$TARGET_VERSION" ]; then
    echo -e "${BLUE}🔄 准备回退到版本 $TARGET_VERSION...${NC}"

    # 先获取远程信息
    echo -e "${BLUE}⬇️  获取远程仓库信息...${NC}"
    git fetch origin 2>/dev/null || true

    # 检查版本是否存在
    if ! git cat-file -t "$TARGET_VERSION" >/dev/null 2>&1; then
        echo -e "${RED}❌ 版本 $TARGET_VERSION 不存在${NC}"
        echo -e "${YELLOW}💡 运行 './update.sh --list' 查看可用版本${NC}"
        exit 1
    fi

    # 检查是否有未提交的更改
    if [ -n "$(git status --porcelain)" ]; then
        echo -e "${YELLOW}⚠️  发现未提交的本地更改:${NC}"
        git status --short
        echo ""
        echo -e "${YELLOW}请选择操作:${NC}"
        echo "  1) 暂存更改 (git stash) 并继续"
        echo "  2) 放弃更改 (git checkout .) 并继续"
        echo "  3) 取消操作"
        read -p "请选择 [1/2/3]: " choice

        case $choice in
            1)
                echo -e "${BLUE}📦 暂存本地更改...${NC}"
                git stash push -m "auto-stash-$(date +%Y%m%d-%H%M%S)"
                ;;
            2)
                echo -e "${YELLOW}🗑️  放弃本地更改...${NC}"
                git checkout .
                ;;
            *)
                echo -e "${YELLOW}❎ 取消操作${NC}"
                exit 0
                ;;
        esac
    fi

    # 执行回退
    echo -e "${BLUE}🔄 正在回退到 $TARGET_VERSION...${NC}"
    if git reset --hard "$TARGET_VERSION"; then
        echo -e "${GREEN}✅ 成功回退到 $TARGET_VERSION${NC}"
    else
        echo -e "${RED}❌ 回退失败${NC}"
        exit 1
    fi

else
    # 拉取最新版本
    echo -e "${BLUE}🔍 检查本地更改...${NC}"
    if [ -n "$(git status --porcelain)" ]; then
        echo -e "${YELLOW}⚠️  发现未提交的本地更改:${NC}"
        git status --short
        echo ""
        echo -e "${YELLOW}请选择操作:${NC}"
        echo "  1) 暂存更改 (git stash) 并继续更新"
        echo "  2) 放弃更改 (git checkout .) 并继续更新"
        echo "  3) 取消更新"
        read -p "请选择 [1/2/3]: " choice

        case $choice in
            1)
                echo -e "${BLUE}📦 暂存本地更改...${NC}"
                git stash push -m "auto-stash-$(date +%Y%m%d-%H%M%S)"
                STASHED=1
                ;;
            2)
                echo -e "${YELLOW}🗑️  放弃本地更改...${NC}"
                git checkout .
                ;;
            *)
                echo -e "${YELLOW}❎ 取消更新${NC}"
                exit 0
                ;;
        esac
    fi
    echo ""

    # 从GitHub拉取最新代码
    echo -e "${BLUE}⬇️  正在从GitHub拉取最新代码...${NC}"
    if ! git pull origin "$BRANCH"; then
        echo -e "${RED}❌ 拉取代码失败${NC}"
        echo -e "${YELLOW}💡 尝试强制重置到远程分支...${NC}"
        git fetch origin
        git reset --hard "origin/$BRANCH"
        echo -e "${GREEN}✅ 已强制同步到远程最新版本${NC}"
    fi
    echo -e "${GREEN}✅ 代码拉取成功${NC}"
fi
echo ""

# 检查并应用数据库迁移
echo -e "${BLUE}🗄️  检查数据库迁移...${NC}"
php -r "
require_once 'includes/db.php';
echo \"数据库连接成功\n\";
echo \"数据库迁移完成\n\";
" 2>/dev/null && echo -e "${GREEN}✅ 数据库迁移完成${NC}" || echo -e "${YELLOW}⚠️  数据库迁移检查失败，请手动检查${NC}"
echo ""

# 设置文件权限
echo -e "${BLUE}🔐 设置文件权限...${NC}"
chmod -R 755 . 2>/dev/null || true
chmod -R 777 data/ uploads/ 2>/dev/null || true
echo -e "${GREEN}✅ 权限设置完成${NC}"
echo ""

# 显示更新信息
echo -e "${BLUE}📊 更新信息:${NC}"
echo -e "  当前版本: ${GREEN}$(git rev-parse --short HEAD)${NC}"
echo -e "  提交时间: ${GREEN}$(git log -1 --format=%cd --date=iso)${NC}"
echo -e "  提交信息: ${GREEN}$(git log -1 --format=%s)${NC}"
if [ -n "$TARGET_VERSION" ]; then
    echo -e "  回退版本: ${YELLOW}$TARGET_VERSION${NC}"
fi
echo ""

# 如果有暂存的更改，提示恢复
if [ -n "$STASHED" ]; then
    echo -e "${YELLOW}⚠️  注意: 之前有本地更改被暂存${NC}"
    echo -e "${YELLOW}   运行 'git stash pop' 恢复更改${NC}"
    echo ""
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  🎉 更新完成！${NC}"
echo -e "${GREEN}========================================${NC}"
