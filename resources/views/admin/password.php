<?php
/**
 * 修改密码页内容模板
 */
?>
<div class="page-header">
    <div>
        <h1>修改密码</h1>
        <p>定期修改密码以提升账户安全</p>
    </div>
</div>
<div class="table-section" style="max-width: 480px;">
    <form id="passwordForm" class="form-stack">
        <div class="form-group">
            <label>旧密码</label>
            <input type="password" name="old_password" autocomplete="current-password" required>
        </div>
        <div class="form-group">
            <label>新密码</label>
            <input type="password" name="new_password" autocomplete="new-password" required>
            <span class="form-hint">6-128 位，建议混合大小写与数字</span>
        </div>
        <div class="form-group">
            <label>确认新密码</label>
            <input type="password" name="confirm_password" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary">修改密码</button>
    </form>
</div>
