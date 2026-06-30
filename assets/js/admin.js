/**
 * 后台管理交互脚本
 */

// ==================== 获取CSRF Token ====================
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.getElementById('csrfToken')?.value || '';
}

// ==================== Toast提示 ====================
function showToast(message, type = 'success') {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s ease;
        `;
        document.body.appendChild(toast);
    }

    const colors = {
        success: 'background: linear-gradient(135deg, #4ecca3, #6bcb77); color: #fff;',
        error: 'background: linear-gradient(135deg, #e94560, #ff6b6b); color: #fff;',
        warning: 'background: linear-gradient(135deg, #f9a825, #ff9800); color: #fff;'
    };

    toast.style.cssText += colors[type] || colors.success;
    toast.textContent = message;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(0)';

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
    }, 3000);
}

// ==================== 模态框 ====================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// ==================== 图片上传预览 ====================
function initImageUpload(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!input || !preview) return;

    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
        };
        reader.readAsDataURL(file);
    });
}

// ==================== 通用保存函数 ====================
async function saveData(action, data, successCallback) {
    // 自动附加CSRF token
    data.csrf_token = getCsrfToken();

    try {
        const response = await fetch(`api/save.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('保存成功', 'success');
            if (successCallback) successCallback(result);
        } else {
            showToast(result.message || '保存失败', 'error');
        }

        return result;
    } catch (error) {
        showToast('网络错误，请重试', 'error');
        console.error('Save error:', error);
        return { success: false };
    }
}

// ==================== 通用删除函数 ====================
async function deleteItem(action, id, successCallback) {
    if (!confirm('确定要删除吗？此操作不可恢复。')) {
        return;
    }

    try {
        const response = await fetch('api/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, id: id, csrf_token: getCsrfToken() })
        });

        const result = await response.json();

        if (result.success) {
            showToast('删除成功', 'success');
            if (successCallback) successCallback(result);
        } else {
            showToast(result.message || '删除失败', 'error');
        }

        return result;
    } catch (error) {
        showToast('网络错误，请重试', 'error');
        console.error('Delete error:', error);
        return { success: false };
    }
}

// ==================== 上传文件到服务器 ====================
async function uploadImage(file, type = 'cards') {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', getCsrfToken());

    try {
        const response = await fetch(`api/upload.php?type=${type}`, {
            method: 'POST',
            body: formData
        });

        // 检查HTTP状态
        if (!response.ok) {
            const text = await response.text();
            console.error('Upload HTTP error:', response.status, text);
            return { success: false, message: '服务器错误 ' + response.status + ': ' + text.substring(0, 100) };
        }

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Upload error:', error);
        return { success: false, message: '上传失败: ' + (error.message || '网络错误') };
    }
}

// ==================== 拖拽排序 ====================
function initSortable(listId, tableName) {
    const list = document.getElementById(listId);
    if (!list) return;

    let draggedItem = null;

    list.addEventListener('dragstart', function(e) {
        draggedItem = e.target.closest('tr');
        e.target.closest('tr').style.opacity = '0.5';
    });

    list.addEventListener('dragend', function(e) {
        e.target.closest('tr').style.opacity = '1';
        draggedItem = null;
    });

    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        const afterElement = getDragAfterElement(list, e.clientY);
        if (afterElement == null) {
            list.appendChild(draggedItem);
        } else {
            list.insertBefore(draggedItem, afterElement);
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('tr:not([style*="opacity: 0.5"])')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
}

// ==================== 初始化 ====================
document.addEventListener('DOMContentLoaded', function() {
    // 模态框关闭
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    });

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.classList.remove('show');
        });
    });
});
