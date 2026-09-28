/**
 * 前台交互脚本（IIFE 模块，事件委托，无 inline 事件）
 */
(function () {
    'use strict';

    // ==================== Toast提示 ====================
    let toastEl = null;
    function showToast(message, duration) {
        duration = duration || 2000;
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.id = 'toast';
            toastEl.style.cssText = [
                'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%)',
                'background:rgba(0,0,0,.8);color:#fff;padding:12px 24px;border-radius:8px',
                'font-size:14px;z-index:9999;opacity:0;transition:opacity .3s ease;pointer-events:none'
            ].join(';');
            document.body.appendChild(toastEl);
        }
        toastEl.textContent = message;
        toastEl.style.opacity = '1';
        setTimeout(function () { toastEl.style.opacity = '0'; }, duration);
    }

    // ==================== 图片轮播 ====================
    function initSlideCarousel() {
        var slider = document.getElementById('slideCarousel');
        if (!slider) return;

        var slides = slider.querySelectorAll('.slide-item');
        var dots = slider.querySelectorAll('.slide-dot');
        var index = 0;
        var timer;

        function show(i) {
            slides.forEach(function (s, n) { s.classList.toggle('active', n === i); });
            dots.forEach(function (d, n) { d.classList.toggle('active', n === i); });
            index = i;
        }
        function autoplay(ms) {
            clearInterval(timer);
            timer = setInterval(function () { show((index + 1) % slides.length); }, ms);
        }

        if (slides.length <= 1) {
            slides.forEach(function (s) { s.classList.add('active'); });
            return;
        }
        show(0);
        autoplay(6000);
        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { show(i); autoplay(4000); });
        });
    }

    // ==================== 卡片点击（事件委托） ====================
    function initCardDelegate() {
        document.addEventListener('click', function (e) {
            var item = e.target.closest ? e.target.closest('.card-item') : null;
            if (!item) return;
            var id = item.getAttribute('data-card-id');
            var type = item.getAttribute('data-card-type');
            var link = item.getAttribute('data-link');

            fetch('/api/click', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ card_id: id })
            }).catch(function () {});

            if (type === 'detail') {
                window.location.href = '/card/' + id + '.html';
                return;
            }
            if (link && link !== '#') {
                var decoded = link;
                try { decoded = decodeURIComponent(link); } catch (err) { /* 保留原值 */ }
                window.open(decoded, '_blank');
            }
        });
    }

    // ==================== 返回顶部 ====================
    function initBackToTop() {
        var btn = document.getElementById('backToTop');
        if (!btn) return;
        btn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
        var ticking = false;
        window.addEventListener('scroll', function () {
            if (ticking) return;
            requestAnimationFrame(function () {
                btn.classList.toggle('show', window.scrollY > 300);
                ticking = false;
            });
            ticking = true;
        });
    }

    // ==================== 公告弹窗 ====================
    function initNoticeModal() {
        var modal = document.getElementById('noticeModal');
        if (!modal) return;
        var today = new Date().toDateString();
        if (localStorage.getItem('notice_closed') === today) return;

        setTimeout(function () { modal.classList.add('show'); }, 1000);
        function close() {
            modal.classList.remove('show');
            var dont = document.getElementById('noticeDontShow');
            if (dont && dont.checked) localStorage.setItem('notice_closed', today);
        }
        var closeBtn = document.getElementById('noticeClose');
        var confirmBtn = document.getElementById('noticeConfirm');
        if (closeBtn) closeBtn.addEventListener('click', close);
        if (confirmBtn) confirmBtn.addEventListener('click', close);
        var overlay = modal.querySelector('.notice-modal-overlay');
        if (overlay) overlay.addEventListener('click', function () { modal.classList.remove('show'); });
    }

    // ==================== 留言板 ====================
    function initGuestbook() {
        var form = document.getElementById('guestbookForm');
        if (!form) return;
        var content = document.getElementById('gbContent');
        var counter = document.getElementById('gbCharCount');
        var success = document.getElementById('guestbookSuccess');
        var btn = document.getElementById('gbSubmit');

        if (content && counter) {
            content.addEventListener('input', function () {
                counter.textContent = content.value.length + '/500';
            });
        }
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var nickname = (document.getElementById('gbNickname').value || '').trim();
            var text = content.value.trim();
            if (!text) { showToast('留言内容不能为空'); return; }
            btn.disabled = true;
            fetch('/api/messages', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nickname: nickname, content: text })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        if (success) success.hidden = false;
                        form.reset();
                        if (counter) counter.textContent = '0/500';
                        setTimeout(function () { window.location.reload(); }, 1200);
                    } else {
                        showToast((data && data.message) || '提交失败');
                    }
                })
                .catch(function () { showToast('网络错误，请重试'); })
                .finally(function () { btn.disabled = false; });
        });
    }

    // ==================== 效果展示灯箱（缩放/拖拽/全屏/键盘/手势） ====================
    function initShowcaseLightbox() {
        var grid = document.getElementById('showcaseGrid');
        var lbx = document.getElementById('showcaseLightbox');
        if (!grid || !lbx) return;

        var viewport = document.getElementById('lbxViewport');
        var mediaBox = document.getElementById('lbxMedia');
        var titleEl = document.getElementById('lbxTitle');
        var counterEl = document.getElementById('lbxCounter');
        var zoomLabel = document.getElementById('lbxZoomLabel');
        var btnClose = document.getElementById('lbxClose');
        var btnPrev = document.getElementById('lbxPrev');
        var btnNext = document.getElementById('lbxNext');
        var btnIn = document.getElementById('lbxZoomIn');
        var btnOut = document.getElementById('lbxZoomOut');
        var btnActual = document.getElementById('lbxActual');
        var btnFit = document.getElementById('lbxFit');
        var btnFs = document.getElementById('lbxFullscreen');

        // 建立索引（过滤掉没有可用 src 的项）
        var items = Array.prototype.slice.call(grid.querySelectorAll('.showcase-item'))
            .map(function (el) {
                return {
                    el: el,
                    type: el.getAttribute('data-media-type') || 'image',
                    src: el.getAttribute('data-src') || '',
                    poster: el.getAttribute('data-poster') || '',
                    title: el.getAttribute('data-title') || ''
                };
            })
            .filter(function (it) { return it.src !== ''; });
        if (items.length === 0) return;

        var idx = 0;
        var scale = 1;      // 当前缩放
        var tx = 0, ty = 0; // 平移偏移
        var MIN_SCALE = 0.5, MAX_SCALE = 5;
        var FIT_SCALE = 1;  // 适应窗口时的基准缩放

        function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

        function applyTransform() {
            mediaBox.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')';
            if (zoomLabel) zoomLabel.textContent = Math.round(scale * 100) + '%';
        }

        function resetView() {
            scale = 1; tx = 0; ty = 0;
            applyTransform();
        }

        function setScale(next, originX, originY) {
            var old = scale;
            var s = clamp(next, MIN_SCALE, MAX_SCALE);
            if (s === old) return;
            // 以 origin 为中心保持位置（origin 相对 viewport 中心）
            var cx = originX || 0, cy = originY || 0;
            tx = cx - (cx - tx) * (s / old);
            ty = cy - (cy - ty) * (s / old);
            scale = s;
            applyTransform();
        }

        function fitScale() {
            // 适应窗口：图片按 viewport 尺寸计算，video 固定 1
            var box = mediaBox.querySelector('img');
            if (!box) return 1;
            var vpW = viewport.clientWidth, vpH = viewport.clientHeight;
            var natW = box.naturalWidth || box.width;
            var natH = box.naturalHeight || box.height;
            if (!natW || !natH || !vpW || !vpH) return 1;
            return clamp(Math.min(vpW / natW, vpH / natH), MIN_SCALE, 1);
        }

        function render() {
            var it = items[idx];
            if (!it) return;
            mediaBox.innerHTML = '';

            if (it.type === 'video') {
                var v = document.createElement('video');
                v.src = it.src;
                if (it.poster) v.poster = it.poster;
                v.controls = true;
                v.autoplay = true;
                v.playsInline = true;
                v.preload = 'metadata';
                mediaBox.appendChild(v);
                lbx.classList.add('is-video');
            } else {
                var img = document.createElement('img');
                img.src = it.src;
                img.alt = it.title;
                img.draggable = false;
                mediaBox.appendChild(img);
                lbx.classList.remove('is-video');
            }

            if (titleEl) titleEl.textContent = it.title;
            if (counterEl) counterEl.textContent = (idx + 1) + ' / ' + items.length;
            if (btnPrev) btnPrev.disabled = idx === 0;
            if (btnNext) btnNext.disabled = idx === items.length - 1;

            resetView();
            // 图片加载后按适应窗口重算
            if (it.type !== 'video') {
                var box = mediaBox.querySelector('img');
                if (box && !box.complete) {
                    box.addEventListener('load', function () {
                        if (!lbx.hidden && idx === items.indexOf(it)) {
                            scale = fitScale(); tx = 0; ty = 0; applyTransform();
                        }
                    });
                }
            }
        }

        function open(i) {
            idx = clamp(i, 0, items.length - 1);
            lbx.hidden = false;
            document.body.style.overflow = 'hidden';
            render();
        }

        function close() {
            // 关闭前停止播放，避免后台继续播
            var v = mediaBox.querySelector('video');
            if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
            lbx.hidden = true;
            document.body.style.overflow = '';
            mediaBox.innerHTML = '';
            resetView();
        }

        function step(d) {
            var n = clamp(idx + d, 0, items.length - 1);
            if (n === idx) return;
            var v = mediaBox.querySelector('video');
            if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
            idx = n;
            render();
        }

        // ===== 事件绑定 =====
        grid.addEventListener('click', function (e) {
            var item = e.target.closest ? e.target.closest('.showcase-item') : null;
            if (!item) return;
            var i = items.findIndex(function (it) { return it.el === item; });
            if (i >= 0) open(i);
        });

        if (btnClose) btnClose.addEventListener('click', close);
        if (btnPrev) btnPrev.addEventListener('click', function () { step(-1); });
        if (btnNext) btnNext.addEventListener('click', function () { step(1); });
        if (btnIn) btnIn.addEventListener('click', function () { setScale(scale * 1.25); });
        if (btnOut) btnOut.addEventListener('click', function () { setScale(scale / 1.25); });
        if (btnActual) btnActual.addEventListener('click', function () { scale = 1; tx = 0; ty = 0; applyTransform(); });
        if (btnFit) btnFit.addEventListener('click', function () { scale = fitScale(); tx = 0; ty = 0; applyTransform(); });

        if (btnFs) {
            btnFs.addEventListener('click', function () {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else if (lbx.requestFullscreen) {
                    lbx.requestFullscreen();
                }
            });
        }

        // 点击背景关闭（点在媒体/工具栏上不关）
        lbx.addEventListener('click', function (e) {
            if (e.target === lbx || e.target === viewport) {
                if (e.target === lbx) close();
            }
        });

        // ===== 滚轮缩放（仅图片） =====
        viewport.addEventListener('wheel', function (e) {
            if (lbx.classList.contains('is-video')) return;
            e.preventDefault();
            var rect = viewport.getBoundingClientRect();
            var ox = e.clientX - rect.left - rect.width / 2;
            var oy = e.clientY - rect.top - rect.height / 2;
            setScale(scale * (e.deltaY < 0 ? 1.15 : 1 / 1.15), ox, oy);
        }, { passive: false });

        // ===== 拖拽平移（仅图片且放大时） =====
        var dragging = false, startX = 0, startY = 0, originTx = 0, originTy = 0;
        viewport.addEventListener('mousedown', function (e) {
            if (lbx.classList.contains('is-video')) return;
            if (scale <= 1) return;
            dragging = true;
            startX = e.clientX; startY = e.clientY;
            originTx = tx; originTy = ty;
            viewport.classList.add('dragging');
        });
        window.addEventListener('mousemove', function (e) {
            if (!dragging) return;
            tx = originTx + (e.clientX - startX);
            ty = originTy + (e.clientY - startY);
            applyTransform();
        });
        window.addEventListener('mouseup', function () {
            dragging = false;
            viewport.classList.remove('dragging');
        });

        // ===== 双击切换 1x / 适应窗口 =====
        viewport.addEventListener('dblclick', function (e) {
            if (lbx.classList.contains('is-video')) return;
            e.preventDefault();
            if (scale > 1.01) { scale = 1; tx = 0; ty = 0; }
            else { scale = fitScale(); tx = 0; ty = 0; }
            applyTransform();
        });

        // ===== 触屏：单指拖动 + 双指缩放 =====
        var touchDist = 0, pinchStartScale = 1, pinchStartDist = 0;
        viewport.addEventListener('touchstart', function (e) {
            if (lbx.classList.contains('is-video')) return;
            if (e.touches.length === 1) {
                dragging = true;
                startX = e.touches[0].clientX; startY = e.touches[0].clientY;
                originTx = tx; originTy = ty;
            } else if (e.touches.length === 2) {
                dragging = false;
                touchDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                pinchStartScale = scale;
                pinchStartDist = touchDist;
            }
        }, { passive: true });
        viewport.addEventListener('touchmove', function (e) {
            if (lbx.classList.contains('is-video')) return;
            if (e.touches.length === 1 && dragging) {
                tx = originTx + (e.touches[0].clientX - startX);
                ty = originTy + (e.touches[0].clientY - startY);
                applyTransform();
            } else if (e.touches.length === 2 && pinchStartDist > 0) {
                e.preventDefault();
                var d = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                setScale(pinchStartScale * (d / pinchStartDist));
            }
        }, { passive: false });
        viewport.addEventListener('touchend', function (e) {
            dragging = false;
            if (e.touches.length < 2) pinchStartDist = 0;
        }, { passive: true });

        // ===== 键盘 =====
        document.addEventListener('keydown', function (e) {
            if (lbx.hidden) return;
            switch (e.key) {
                case 'Escape': e.preventDefault(); close(); break;
                case 'ArrowLeft': e.preventDefault(); step(-1); break;
                case 'ArrowRight': e.preventDefault(); step(1); break;
                case 'Home': e.preventDefault(); open(0); break;
                case 'End': e.preventDefault(); open(items.length - 1); break;
                case '+': case '=': e.preventDefault(); setScale(scale * 1.25); break;
                case '-': case '_': e.preventDefault(); setScale(scale / 1.25); break;
                case '0': e.preventDefault(); scale = 1; tx = 0; ty = 0; applyTransform(); break;
                default: break;
            }
        });
    }

    // ==================== 侧边栏分类切换 ====================
    function initSideNav() {
        var sideNav = document.getElementById('sideNav');
        var sideMask = document.getElementById('sideMask');
        var burger = document.getElementById('mainBurger');
        if (!sideNav) return;

        var panels = Array.prototype.slice.call(document.querySelectorAll('.category-block'));
        var slideSection = document.getElementById('slideSection');
        var titleEl = document.getElementById('mainCatTitle');
        var metaEl = document.getElementById('mainCatMeta');
        var catButtons = Array.prototype.slice.call(sideNav.querySelectorAll('.side-cat'));

        function closeDrawer() {
            sideNav.classList.remove('open');
            if (sideMask) sideMask.classList.remove('open');
        }

        function selectCategory(catId, label, count) {
            catButtons.forEach(function (b) {
                b.classList.toggle('active', b.getAttribute('data-cat') === catId);
            });
            panels.forEach(function (p) {
                var match = catId === 'all' || p.getAttribute('data-cat') === catId;
                p.hidden = !match;
            });
            if (slideSection) slideSection.hidden = catId !== 'all';
            if (titleEl) titleEl.textContent = label;
            if (metaEl) metaEl.textContent = '共 ' + count + ' 个链接';
            closeDrawer();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        sideNav.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('.side-cat') : null;
            if (!btn) return;
            var catId = btn.getAttribute('data-cat');
            var nameEl = btn.querySelector('.side-cat-name');
            var countEl = btn.querySelector('.side-cat-count');
            selectCategory(
                catId,
                nameEl ? nameEl.textContent : '',
                countEl ? parseInt(countEl.textContent, 10) || 0 : 0
            );
        });

        if (burger) {
            burger.addEventListener('click', function () {
                var open = sideNav.classList.toggle('open');
                if (sideMask) sideMask.classList.toggle('open', open);
            });
        }
        if (sideMask) {
            sideMask.addEventListener('click', closeDrawer);
        }
        // 桌面端误点遮罩或窗口放大时复位
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) closeDrawer();
        });
    }

    // ==================== 搜索覆盖层 ====================
    function initSearchOverlay() {
        var overlay = document.getElementById('searchOverlay');
        var input = document.getElementById('searchInput');
        var closeBtn = document.getElementById('searchClose');
        var metaEl = document.getElementById('searchMeta');
        var resultsEl = document.getElementById('searchResults');
        var hintEl = document.getElementById('searchHint');
        var sideSearchBtn = document.getElementById('sideSearchBtn');
        var mainSearchBtn = document.getElementById('mainSearchBtn');
        if (!overlay || !input || !resultsEl) return;

        // 建立索引：所有卡片 + 所属分类名，供跨分类搜索
        var index = [];
        Array.prototype.forEach.call(document.querySelectorAll('.category-block'), function (panel) {
            var catNameEl = panel.querySelector('.category-title');
            var catName = catNameEl ? catNameEl.textContent.trim() : '';
            Array.prototype.forEach.call(panel.querySelectorAll('.card-item'), function (item) {
                var titleEl = item.querySelector('.card-title');
                var imgEl = item.querySelector('.card-image img');
                var type = item.getAttribute('data-card-type') || 'link';
                index.push({
                    id: item.getAttribute('data-card-id') || '',
                    type: type,
                    link: item.getAttribute('data-link') || '#',
                    title: titleEl ? titleEl.textContent.trim() : '',
                    category: catName,
                    thumb: imgEl ? imgEl.getAttribute('src') || '' : ''
                });
            });
        });

        var current = [];   // 当前匹配结果
        var activeIdx = -1; // 键盘高亮下标

        function open() {
            overlay.hidden = false;
            input.value = '';
            current = [];
            activeIdx = -1;
            if (metaEl) metaEl.textContent = '';
            resultsEl.innerHTML = '';
            if (hintEl) hintEl.textContent = '输入关键词开始搜索 · ESC 关闭';
            input.focus();
        }

        function close() {
            overlay.hidden = true;
            current = [];
            activeIdx = -1;
            if (metaEl) metaEl.textContent = '';
            resultsEl.innerHTML = '';
        }

        function doSearch(keyword) {
            var kw = keyword.trim().toLowerCase();
            activeIdx = -1;
            if (kw === '') {
                current = [];
                resultsEl.innerHTML = '';
                if (metaEl) metaEl.textContent = '';
                if (hintEl) hintEl.textContent = '输入关键词开始搜索 · ESC 关闭';
                return;
            }
            current = index.filter(function (it) {
                return it.title.toLowerCase().indexOf(kw) !== -1
                    || it.category.toLowerCase().indexOf(kw) !== -1;
            });

            if (metaEl) {
                metaEl.textContent = current.length > 0
                    ? '找到 ' + current.length + ' 个匹配「' + keyword.trim() + '」的链接'
                    : '没有找到匹配「' + keyword.trim() + '」的链接';
            }
            if (hintEl) {
                hintEl.textContent = current.length > 0
                    ? '↑↓ 选择 · Enter 打开 · ESC 关闭'
                    : '换个关键词试试';
            }
            renderResults(keyword.trim());
        }

        function renderResults(keyword) {
            if (current.length === 0) {
                resultsEl.innerHTML = '<p class="search-empty">没有匹配的链接</p>';
                return;
            }
            var html = current.map(function (it, i) {
                var thumb = it.thumb
                    ? '<img src="' + it.thumb + '" alt="" loading="lazy" width="36" height="36">'
                    : '<span class="sr-thumb">' + (it.title || '?').charAt(0) + '</span>';
                return '<a href="#" class="sr-item" data-idx="' + i + '">'
                    + '<span class="sr-thumb-wrap">' + thumb + '</span>'
                    + '<span class="sr-body">'
                    + '<span class="sr-title">' + highlight(it.title, keyword) + '</span>'
                    + '<span class="sr-cat">' + highlight(it.category, keyword) + '</span>'
                    + '</span></a>';
            }).join('');
            resultsEl.innerHTML = html;
        }

        /** 转义 HTML 后高亮关键词（先转义再插标签，避免 XSS）。 */
        function highlight(text, keyword) {
            var safe = text.replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
            if (!keyword) return safe;
            var kwSafe = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return safe.replace(new RegExp(kwSafe, 'gi'), function (m) {
                return '<mark>' + m + '</mark>';
            });
        }

        /** 打开某个结果：detail 跳详情，link 开新窗口；同时上报点击。 */
        function openResult(item) {
            if (!item) return;
            if (item.id) {
                fetch('/api/click', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ card_id: item.id })
                }).catch(function () {});
            }
            if (item.type === 'detail') {
                window.location.href = '/card/' + item.id + '.html';
                return;
            }
            if (item.link && item.link !== '#') {
                var decoded = item.link;
                try { decoded = decodeURIComponent(item.link); } catch (err) { /* 保留原值 */ }
                window.open(decoded, '_blank', 'noopener');
            }
            close();
        }

        function setActive(i) {
            var items = resultsEl.querySelectorAll('.sr-item');
            if (items.length === 0) return;
            activeIdx = (i + items.length) % items.length;
            Array.prototype.forEach.call(items, function (el, n) {
                el.classList.toggle('active', n === activeIdx);
            });
            var el = items[activeIdx];
            if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
        }

        if (sideSearchBtn) sideSearchBtn.addEventListener('click', open);
        if (mainSearchBtn) mainSearchBtn.addEventListener('click', open);
        if (closeBtn) closeBtn.addEventListener('click', close);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) { close(); return; }
            var item = e.target.closest ? e.target.closest('.sr-item') : null;
            if (item) {
                e.preventDefault();
                openResult(current[parseInt(item.getAttribute('data-idx'), 10)]);
            }
        });
        input.addEventListener('input', function () { doSearch(input.value); });
        document.addEventListener('keydown', function (e) {
            if (overlay.hidden) {
                if (e.key === '/' || (e.key === 'k' && (e.metaKey || e.ctrlKey))) {
                    e.preventDefault();
                    open();
                }
                return;
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(activeIdx + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(activeIdx - 1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                openResult(activeIdx >= 0 ? current[activeIdx] : current[0]);
            }
        });
    }

    // ==================== 初始化 ====================
    document.addEventListener('DOMContentLoaded', function () {
        initSideNav();
        initSearchOverlay();
        initSlideCarousel();
        initCardDelegate();
        initBackToTop();
        initNoticeModal();
        initGuestbook();
        initShowcaseLightbox();
    });
})();
