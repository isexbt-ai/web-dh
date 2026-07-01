<?php
/**
 * redirect/browser-tip-inline.php
 * Apache 环境下 QQ/微信 UA 拦截的内联提示页
 * 功能与 Nginx 的 $anti_html 完全一致
 */

// 检测 User-Agent
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$isQQ = strpos($ua, 'qq/') !== false;
$isWeChat = strpos($ua, 'micromessenger') !== false;

// 如果不是 QQ/微信，重定向到正常的 browser-tip.html
if (!$isQQ && !$isWeChat) {
    header('Location: browser-tip.html');
    exit;
}

// 获取当前 URL
$currentUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');

// 输出内联提示页面
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>请在浏览器中打开</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#667eea">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body,html{width:100%;height:100%;margin:0;padding:0}
        body{background:#fff;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"PingFang SC","Microsoft YaHei",sans-serif}
        .container{max-width:480px;margin:0 auto;min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}
        .top-bar{text-align:center;padding:60px 20px 40px;color:#fff}
        .top-bar .icon-arrow{display:inline-block;animation:bounce 1.5s infinite}
        .top-bar .icon-arrow svg{width:40px;height:40px;fill:#fff}
        @keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
        .top-bar h2{font-size:22px;margin:15px 0;font-weight:700}
        .top-bar p{font-size:14px;opacity:.9;line-height:2}
        .content-wrapper{background:#fff;border-radius:20px 20px 0 0;padding:30px 20px;margin-top:-20px;min-height:60vh}
        .tips{text-align:center;color:#333}
        .tips h3{font-size:20px;color:#2466f4;margin-bottom:20px}
        .tips p{font-size:15px;line-height:2;color:#666;margin:10px 0}
        .divider{width:80%;height:1px;background:linear-gradient(to right,transparent,#2466f4,transparent);margin:20px auto}
        .browser-icons{display:flex;justify-content:center;gap:30px;margin:20px 0}
        .browser-icon{text-align:center}
        .browser-icon svg{width:50px;height:50px}
        .browser-icon span{display:block;font-size:12px;color:#999;margin-top:5px}
        .hint-box{background:#f8f9ff;border-radius:12px;padding:15px;margin:20px 0;border-left:4px solid #2466f4}
        .hint-box p{font-size:14px;color:#555;line-height:1.8}
        .url-box{background:#f5f5f5;border-radius:8px;padding:12px;margin:15px 0;word-break:break-all;font-size:13px;color:#2466f4;text-align:center}
        .copy-btn{display:block;width:100%;height:46px;line-height:46px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;text-align:center;border-radius:23px;font-size:16px;text-decoration:none;cursor:pointer;border:none;letter-spacing:1px;-webkit-tap-highlight-color:transparent}
    </style>
</head>
<body>
    <div class="container">
        <div class="top-bar">
            <div class="icon-arrow">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
            </div>
            <h2>请在浏览器中打开</h2>
            <p>点击右上角 ··· 选择<span style="font-weight:700">在浏览器中打开</span></p>
        </div>
        <div class="content-wrapper">
            <div class="tips">
                <h3>访问提示</h3>
                <p>1. 本站部分功能不支持微信或QQ</p>
                <p>2. 请按提示在手机浏览器打开</p>
            </div>
            <div class="divider"></div>
            <div class="browser-icons">
                <div class="browser-icon">
                    <svg viewBox="0 0 24 24" fill="#147EFB">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                    </svg>
                    <span>Safari</span>
                </div>
                <div class="browser-icon">
                    <svg viewBox="0 0 24 24" fill="#4285F4">
                        <circle cx="12" cy="12" r="10" fill="none" stroke="#4285F4" stroke-width="2"/>
                        <circle cx="12" cy="12" r="4" fill="#4285F4"/>
                    </svg>
                    <span>Chrome</span>
                </div>
            </div>
            <div class="hint-box">
                <p><strong>温馨提示</strong><br>点击右上角菜单，选择<strong>在浏览器中打开</strong>，或复制下方网址自行打开</p>
            </div>
            <div class="url-box" id="url"><?php echo htmlspecialchars($currentUrl); ?></div>
            <button class="copy-btn" id="copyBtn">点此复制本站网址</button>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded",function(){
            var url="<?php echo htmlspecialchars($currentUrl); ?>";
            document.getElementById("url").textContent=url;
            var btn=document.getElementById("copyBtn");
            btn.addEventListener("click",function(){
                if(navigator.clipboard&&navigator.clipboard.writeText){
                    navigator.clipboard.writeText(url).then(function(){alert("复制成功！请粘贴到浏览器中打开")}).catch(function(){fallbackCopy(url)})
                }else{fallbackCopy(url)}
            });
            function fallbackCopy(text){
                var ta=document.createElement("textarea");
                ta.value=text;
                ta.style.position="fixed";
                ta.style.top="0";
                ta.style.left="0";
                ta.style.opacity="0";
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                try{
                    var success=document.execCommand("copy");
                    alert(success?"复制成功！请粘贴到浏览器中打开":"复制失败，请手动复制上方网址")
                }catch(e){alert("复制失败，请手动复制上方网址")}
                document.body.removeChild(ta)
            }
        });
    </script>
</body>
</html>
