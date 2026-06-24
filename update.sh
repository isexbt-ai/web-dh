#!/bin/bash
# 美女导航项目 - 自动更新脚本
# 用法: ./update.sh

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  美女导航项目 - 自动更新脚本${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

echo -e "${YELLOW}📍 当前目录: $(pwd)${NC}"
echo ""

# 检查是否是git仓库
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ 错误: 当前目录不是git仓库${NC}"
    echo -e "${YELLOW}💡 提示: 请确保在正确的项目目录中运行此脚本${NC}"
    exit 1
fi

# 检查远程仓库
echo -e "${BLUE}🔍 检查远程仓库...${NC}"
if ! git remote -v > /dev/null 2>&1; then
    echo -e "${RED}❌ 错误: 未配置远程仓库${NC}"
    echo -e "${YELLOW}💡 提示: 请先运行: git remote add origin <仓库地址>${NC}"
    exit 1
fi

REMOTE_URL=$(git remote get-url origin 2>/dev/null || echo "")
echo -e "${GREEN}✅ 远程仓库: $REMOTE_URL${NC}"
echo ""

# 获取当前分支
BRANCH=$(git rev-parse --abbrev-ref HEAD)
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
            git stash push -m "update-$(date +%Y%m%d-%H%M%S)"
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

# 拉取最新代码
echo -e "${BLUE}⬇️  正在拉取最新代码...${NC}"
if ! git pull origin "$BRANCH"; then
    echo -e "${RED}❌ 拉取代码失败${NC}"
    echo -e "${YELLOW}💡 提示: 可能是网络问题或合并冲突${NC}"

    # 如果之前有暂存，尝试恢复
    if [ -n "$STASHED" ]; then
        echo -e "${BLUE}📦 恢复暂存的更改...${NC}"
        git stash pop
    fi
    exit 1
fi
echo -e "${GREEN}✅ 代码拉取成功${NC}"
echo ""

# 检查并应用数据库迁移
echo -e "${BLUE}🗄️  检查数据库迁移...${NC}"
php -r "
require_once 'includes/db.php';
echo \"数据库连接成功\n\";
// initDatabase 会在 db.php 加载时自动执行
echo \"数据库迁移完成\n\";
" 2>/dev/null || {
    echo -e "${YELLOW}⚠️  数据库迁移检查失败，请手动检查${NC}"
}
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

# 如果之前有暂存，提示用户
git stash list | grep -q "update-" && {
    echo -e "${YELLOW}⚠️  注意: 之前有本地更改被暂存${NC}"
    echo -e "${YELLOW}   运行 'git stash pop' 恢复更改${NC}"
    echo ""
}

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  🎉 更新完成！${NC}"
echo -e "${GREEN}========================================${NC}"
