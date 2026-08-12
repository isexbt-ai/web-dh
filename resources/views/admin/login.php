<?php
/**
 * 后台登录页内容模板（数据：siteTitle）
 */
?>
<div class="login-container">
    <form class="login-form" id="loginForm">
        <h1>后台管理</h1>
        <p class="login-subtitle">登录以继续</p>
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" autocomplete="username" required>
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="login-btn">登录</button>
        <p class="login-error" id="loginError" hidden></p>
    </form>
</div>
