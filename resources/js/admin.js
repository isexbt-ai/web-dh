/**
 * 后台管理交互脚本（IIFE，事件委托，统一 CRUD）
 */
(function () {
    'use strict';

    // ==================== CSRF ====================
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // ==================== Toast ====================
    var toastEl = null;
    function showToast(message, type) {
        type = type || 'success';
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.id = 'toast';
            toastEl.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:500;z-index:9999;opacity:0;transform:translateX(20px);transition:all .3s ease;';
            document.body.appendChild(toastEl);
        }
        var colors = {
            success: 'background:linear-gradient(135deg,#4ecca3,#6bcb77);color:#fff;',
            error: 'background:linear-gradient(135deg,#e94560,#ff6b6b);color:#fff;'
        };
        toastEl.style.cssText += (colors[type] || colors.success);
        toastEl.textContent = message;
        toastEl.style.opacity = '1';
        toastEl.style.transform = 'translateX(0)';
        setTimeout(function () { toastEl.style.opacity = '0'; toastEl.style.transform = 'translateX(20px)'; }, 3000);
    }

    // ==================== Modal ====================
    function openModal(id) {
        var modal = document.getElementById(id);
        if (modal) { modal.classList.add('show'); document.body.style.overflow = 'hidden'; }
    }
    function closeModal(id) {
        var modal = document.getElementById(id);
        if (modal) { modal.classList.remove('show'); document.body.style.overflow = ''; }
    }

    // ==================== 表单工具 ====================
    function collectForm(form) {
        var data = {};
        if (form.dataset.op) data.op = form.dataset.op;
        form.querySelectorAll('[name]').forEach(function (field) {
            if (field.type === 'checkbox') {
                data[field.name] = field.checked ? 1 : 0;
            } else if (field.type === 'radio') {
                // 同名 radio 只取 checked 的那个，否则全部 value 会被覆盖、最后一个生效
                if (field.checked) data[field.name] = field.value;
            } else {
                data[field.name] = field.value;
            }
        });
        return data;
    }
    function fillForm(form, data) {
        Object.keys(data).forEach(function (key) {
            if (key === 'action' || key === 'modal') return;
            var field = form.querySelector('[name="' + key + '"]');
            if (!field) return;
            if (field.type === 'checkbox') {
                field.checked = data[key] === '1' || data[key] === 1;
            } else {
                field.value = data[key] || '';
            }
        });
    }
    function previewImage(input, targetName) {
        var form = input.closest('form');
        var field = form.querySelector('[name="' + targetName + '"]');
        var preview = form.querySelector('.image-preview');
        if (field && preview) {
            preview.innerHTML = field.value ? '<img src="' + field.value + '" alt="">' : '';
        }
    }

    // ==================== 数据操作 ====================
    async function saveData(action, data, successCallback) {
        data.csrf_token = getCsrfToken();
        try {
            var res = await fetch('/admin/api/save?action=' + encodeURIComponent(action), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            var result = await res.json();
            if (result.success) {
                showToast(result.message || '保存成功');
                if (successCallback) successCallback(result);
            } else {
                showToast(result.message || '保存失败', 'error');
            }
            return result;
        } catch (err) {
            showToast('网络错误，请重试', 'error');
            return { success: false };
        }
    }

    async function deleteItem(action, id, successCallback) {
        if (!confirm('确定要删除吗？此操作不可恢复。')) return;
        try {
            var res = await fetch('/admin/api/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action, id: id, csrf_token: getCsrfToken() })
            });
            var result = await res.json();
            if (result.success) {
                showToast('删除成功');
                if (successCallback) successCallback(result);
            } else {
                showToast(result.message || '删除失败', 'error');
            }
            return result;
        } catch (err) {
            showToast('网络错误，请重试', 'error');
            return { success: false };
        }
    }

    async function uploadImage(file, type) {
        var fd = new FormData();
        fd.append('image', file);
        fd.append('csrf_token', getCsrfToken());
        try {
            var res = await fetch('/admin/api/upload?type=' + encodeURIComponent(type), { method: 'POST', body: fd });
            return await res.json();
        } catch (err) {
            return { success: false, message: '上传失败' };
        }
    }

    // ==================== 初始化 ====================
    document.addEventListener('DOMContentLoaded', function () {
        // 主题选择器：change 事件即时切换 .active 视觉（不依赖保存）
        document.querySelectorAll('.theme-picker input[type="radio"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var picker = radio.closest('.theme-picker');
                if (!picker) return;
                picker.querySelectorAll('.theme-option').forEach(function (opt) { opt.classList.remove('active'); });
                var opt = radio.closest('.theme-option');
                if (opt) opt.classList.add('active');
            });
        });

        // 登录
        var loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var data = Object.fromEntries(new FormData(loginForm));
                data.csrf_token = getCsrfToken();
                fetch('/admin/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).then(function (r) { return r.json(); }).then(function (res) {
                    if (res.success) {
                        showToast('登录成功');
                        setTimeout(function () { window.location.href = res.redirect || '/admin'; }, 500);
                    } else {
                        var err = document.getElementById('loginError');
                        if (err) { err.textContent = res.message || '登录失败'; err.hidden = false; }
                    }
                }).catch(function () {
                    var err = document.getElementById('loginError');
                    if (err) { err.textContent = '网络错误，请重试'; err.hidden = false; }
                });
            });
        }

        // 修改密码
        var passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var data = collectForm(passwordForm);
                data.csrf_token = getCsrfToken();
                fetch('/admin/password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).then(function (r) { return r.json(); }).then(function (res) {
                    if (res.success) {
                        showToast('修改成功，请重新登录');
                        setTimeout(function () { window.location.href = '/admin/login'; }, 1200);
                    } else {
                        showToast(res.message || '修改失败', 'error');
                    }
                }).catch(function () { showToast('网络错误，请重试', 'error'); });
            });
        }

        // 新增按钮
        var btnAdd = document.getElementById('btnAdd');
        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                var form = document.getElementById('editForm');
                if (form) {
                    form.reset();
                    form.querySelector('[name="id"]').value = '';
                    var title = document.getElementById('modalTitle');
                    if (title) title.textContent = '新增';
                    var preview = form.querySelector('.image-preview');
                    if (preview) preview.innerHTML = '';
                }
                openModal('editModal');
            });
        }
        var btnAddGallery = document.getElementById('btnAddGallery');
        if (btnAddGallery) {
            btnAddGallery.addEventListener('click', function () {
                var form = document.getElementById('galleryForm');
                if (form) { form.reset(); form.querySelector('[name="id"]').value = ''; }
                openModal('galleryModal');
            });
        }

        // 事件委托：编辑/回复/审核/删除
        document.addEventListener('click', function (e) {
            var editBtn = e.target.closest ? e.target.closest('.btn-edit') : null;
            if (editBtn) {
                var modalId = editBtn.getAttribute('data-modal') || 'editModal';
                var form = document.querySelector('#' + modalId + ' form');
                if (form) {
                    form.reset();
                    fillForm(form, editBtn.dataset);
                    var title = form.closest('.modal-content').querySelector('h3');
                    if (title) title.textContent = '编辑';
                    var imgInput = form.querySelector('input[name="image"], input[name="cover_image"]');
                    if (imgInput) previewImage(imgInput, imgInput.name);
                }
                openModal(modalId);
                return;
            }
            var replyBtn = e.target.closest ? e.target.closest('.btn-reply') : null;
            if (replyBtn) {
                document.getElementById('replyContent').textContent = replyBtn.getAttribute('data-content') || '';
                document.querySelector('#replyForm [name="id"]').value = replyBtn.getAttribute('data-id');
                document.querySelector('#replyForm [name="reply"]').value = replyBtn.getAttribute('data-reply') || '';
                openModal('replyModal');
                return;
            }
            var toggleBtn = e.target.closest ? e.target.closest('.btn-toggle') : null;
            if (toggleBtn) {
                saveData('message', {
                    id: toggleBtn.getAttribute('data-id'),
                    op: 'toggle',
                    is_active: toggleBtn.getAttribute('data-is_active') === '1' ? 0 : 1
                }, function () { window.location.reload(); });
                return;
            }
            var delBtn = e.target.closest ? e.target.closest('.btn-delete') : null;
            if (delBtn) {
                deleteItem(delBtn.getAttribute('data-action'), delBtn.getAttribute('data-id'), function () { window.location.reload(); });
            }
        });

        // 通用表单保存（editForm/galleryForm/replyForm/configForm）
        document.querySelectorAll('form[data-action]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var data = collectForm(form);
                var modal = form.closest('.modal-overlay');
                saveData(form.getAttribute('data-action'), data, function () {
                    if (modal) closeModal(modal.id);
                    setTimeout(function () { window.location.reload(); }, 300);
                });
            });
        });

        // 图片上传
        function initImageUpload(inputId, targetName, type) {
            var input = document.getElementById(inputId);
            if (!input) return;
            input.addEventListener('change', function () {
                var file = input.files[0];
                if (!file) return;
                uploadImage(file, type).then(function (result) {
                    if (result.success) {
                        var form = input.closest('form');
                        var field = form.querySelector('[name="' + targetName + '"]');
                        if (field) {
                            field.value = result.url || result.path || '';
                            previewImage(input, targetName);
                        }
                        showToast('上传成功');
                    } else {
                        showToast(result.message || '上传失败', 'error');
                    }
                });
            });
        }
        initImageUpload('editImageFile', 'image', 'cards');
        initImageUpload('editCoverFile', 'cover_image', 'articles');
        initImageUpload('editImageFile', 'image', 'showcase');

        // Modal 关闭
        document.querySelectorAll('.modal-overlay').forEach(function (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) modal.classList.remove('show');
            });
        });
        document.querySelectorAll('.modal-close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var modal = btn.closest('.modal-overlay');
                if (modal) modal.classList.remove('show');
            });
        });
    });
})();
