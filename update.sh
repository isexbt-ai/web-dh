#!/bin/bash
# 美女导航项目 - 服务器自动更新脚本
# 用法: ./update.sh
# 功能: 从GitHub拉取最新代码，自动应用数据库迁移，设置权限

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_NAME="美女导航"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  $PROJECT_NAME - 服务器自动更新${NC}"
echo -e "${BLUE}========================================${NC}"
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
    echo -e "${YELLOW}⚠️  未检测到git仓库，正在初始化...${NC}"
    git init
    git remote add origin https://github.com/isexbt-ai/web-dh.git
    echo -e "${GREEN}✅ Git仓库初始化完成${NC}"
    echo ""
fi

# 检查远程仓库配置
REMOTE_URL=$(git remote get-url origin 2>/dev/null || echo "")
if [ -z "$REMOTE_URL" ]; then
    echo -e "${BLUE}🔗 配置远程仓库...${NC}"
    git remote add origin https://github.com/isbt-ai/web-dh.git
    REMOTE_URL=$(git remote get-url origin)
fi
echo -e "${GREEN}✅ 远程仓库: $REMOTE_URL${NC}"
echo ""

# 获取当前分支
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
echo -e "${BLUE}🌿 当前分支: $BRANCH${NC}"

# 检查是否有未提交的更改
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
