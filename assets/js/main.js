/**
 * 前台交互脚本
 */

// ==================== 功能菜单 ====================
const funcBtn = document.getElementById('funcBtn');
const funcMenu = document.getElementById('funcMenu');

if (funcBtn && funcMenu) {
    funcBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        funcMenu.classList.toggle('show');
    });

    document.addEventListener('click', function() {
        funcMenu.classList.remove('show');
    });

    funcMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
}

// ==================== 复制链接 ====================
function copyLink() {
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('链接已复制到剪贴板');
        }).catch(() => {
            fallbackCopy(url);
        });
    } else {
        fallbackCopy(url);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showToast('链接已复制到剪贴板');
}

// ==================== 分享页面 ====================
function sharePage() {
    const shareData = {
        title: document.title,
        text: '发现了一个不错的网站，推荐给你！',
        url: window.location.href
    };

    if (navigator.share) {
        navigator.share(shareData)
            .then(() => {
                showToast('分享成功');
            })
            .catch((err) => {
                if (err.name !== 'AbortError') {
                    // 不是用户取消，尝试降级
                    fallbackShare(shareData);
                }
            });
    } else {
        fallbackShare(shareData);
    }
}

function fallbackShare(shareData) {
    const url = shareData.url;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('当前浏览器不支持原生分享，链接已复制到剪贴板');
        }).catch(() => {
            fallbackCopy(url);
            showToast('当前浏览器不支持原生分享，链接已复制到剪贴板');
        });
    } else {
        fallbackCopy(url);
        showToast('当前浏览器不支持原生分享，链接已复制到剪贴板');
    }
}

// ==================== Toast提示 ====================
function showToast(message, duration = 2000) {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        `;
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.opacity = '1';

    setTimeout(() => {
        toast.style.opacity = '0';
    }, duration);
}

// ==================== 图片轮播 ====================
let slideIndex = 0;
let slideInterval;

function initSlideCarousel() {
    const slider = document.getElementById('slideCarousel');
    if (!slider) return;

    const slides = slider.querySelectorAll('.slide-item');
    const dots = slider.querySelectorAll('.slide-dot');

    if (slides.length <= 1) {
        slides.forEach(slide => {
            slide.classList.add('active');
        });
        return;
    }

    function showSlide(index) {
        slides.forEach((slide, i) => {
            if (i === index) {
                slide.classList.add('active');
            } else {
                slide.classList.remove('active');
            }
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        slideIndex = index;
    }

    // 初始化显示第一个
    showSlide(0);

    // 自动轮播
    slideInterval = setInterval(() => {
        showSlide((slideIndex + 1) % slides.length);
    }, 6000);

    // 点击切换
    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            clearInterval(slideInterval);
            showSlide(i);
            slideInterval = setInterval(() => {
                showSlide((slideIndex + 1) % slides.length);
            }, 4000);
        });
    });
}

// ==================== 分类切换 & 卡片加载 ====================
function switchCategory(categoryId) {
    // 更新Tab状态
    const tabs = document.querySelectorAll('.category-tab');
    tabs.forEach(tab => {
        tab.classList.toggle('active', parseInt(tab.dataset.id) === categoryId);
    });

    // SSR模式：显示/隐藏对应分类的卡片网格
    const grids = document.querySelectorAll('.card-grid[data-category]');
    if (grids.length > 0) {
        grids.forEach(grid => {
            grid.classList.toggle('hidden', parseInt(grid.dataset.category) !== categoryId);
        });
        return;
    }

    // 降级：如果没有SSR网格，走AJAX加载
    loadCards(categoryId);
}

function loadCards(categoryId) {
    const grid = document.getElementById('cardGrid');
    grid.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>加载中...</p>
        </div>
    `;

    fetch(`admin/api/cards.php?category_id=${categoryId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data.length > 0) {
                renderCards(result.data);
            } else {
                renderEmpty();
            }
        })
        .catch(error => {
            console.error('加载卡片失败:', error);
            renderEmpty();
        });
}

function renderCards(cards) {
    const grid = document.getElementById('cardGrid');
    grid.innerHTML = cards.map(card => {
        const cardType = card.card_type || 'link';

        // 角标文字：优先使用自定义角标，否则使用默认类型角标
        let badgeText = card.badge_text || '';
        let badgeClass = '';
        if (badgeText) {
            badgeClass = 'custom';
        } else {
            badgeClass = cardType === 'detail' ? 'detail' : 'link';
            badgeText = cardType === 'detail' ? '详情' : '外链';
        }

        const onclickAttr = cardType === 'detail'
            ? `onclick="goToDetail(${card.id})"`
            : `onclick="goToLink(${card.id}, '${encodeURIComponent(card.link || '#')}')"`;

        return `
        <div class="card-item" ${onclickAttr}>
            <span class="card-type-badge ${badgeClass}">${badgeText}</span>
            <div class="card-image">
                ${card.thumb_image
                    ? `<img src="${card.thumb_image}" alt="${escapeHtml(card.title)}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">`
                    : `<div class="card-placeholder">图片</div>`
                }
            </div>
            <div class="card-title">${escapeHtml(card.title)}</div>
        </div>
    `}).join('');
}

function renderEmpty() {
    const grid = document.getElementById('cardGrid');
    grid.innerHTML = `
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="9" cy="9" r="2"/>
                <path d="M21 15l-5-5L5 21"/>
            </svg>
            <p>暂无内容</p>
        </div>
    `;
}

function goToDetail(cardId) {
    // 记录点击
    fetch(`admin/api/click.php?id=${cardId}`).catch(() => {});
    // 跳转到详情页
    window.location.href = `detail/${cardId}.html`;
}

function goToLink(cardId, link) {
    // 记录点击
    fetch(`admin/api/click.php?id=${cardId}`).catch(() => {});
    // 跳转到外部链接
    const decodedLink = decodeURIComponent(link);
    if (decodedLink && decodedLink !== '#') {
        window.open(decodedLink, '_blank');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== 返回顶部按钮 ====================
function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;

    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // 滚动监听
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(function() {
                if (window.scrollY > 300) {
                    btn.classList.add('show');
                } else {
                    btn.classList.remove('show');
                }
                ticking = false;
            });
            ticking = true;
        }
    });
}

// ==================== 初始化 ====================
document.addEventListener('DOMContentLoaded', function() {
    initSlideCarousel();
    initBackToTop();
});
