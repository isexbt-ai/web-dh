// stats/tracker.js - 前端统计SDK
(function() {
    'use strict';

    var StatsTracker = {
        endpoint: '/stats/api.php?action=collect',
        sessionId: null,

        init: function(options) {
            this.endpoint = options.endpoint || this.endpoint;
            this.sessionId = this.generateSessionId();
        },

        generateSessionId: function() {
            return 'st_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        // 解析设备信息
        parseDevice: function() {
            var ua = navigator.userAgent;
            var device = {
                type: 'desktop',
                browser: 'unknown',
                os: 'unknown'
            };

            // 设备类型
            if (/Mobile|Android|iPhone|iPod/.test(ua)) {
                device.type = 'mobile';
            } else if (/iPad|Tablet/.test(ua)) {
                device.type = 'tablet';
            }

            // 浏览器
            if (/Chrome\/\d+/.test(ua) && !/Edg\/\d+/.test(ua)) {
                device.browser = 'Chrome';
            } else if (/Edg\/\d+/.test(ua)) {
                device.browser = 'Edge';
            } else if (/Firefox\/\d+/.test(ua)) {
                device.browser = 'Firefox';
            } else if (/Safari\/\d+/.test(ua) && /Apple Computer/.test(ua)) {
                device.browser = 'Safari';
            } else if (/MicroMessenger/.test(ua)) {
                device.browser = 'WeChat';
            } else if (/QQ\//.test(ua)) {
                device.browser = 'QQ';
            }

            // 操作系统
            if (/Windows NT/.test(ua)) {
                device.os = 'Windows';
            } else if (/Mac OS X/.test(ua)) {
                device.os = 'macOS';
            } else if (/Android/.test(ua)) {
                device.os = 'Android';
            } else if (/iPhone|iPad|iPod/.test(ua)) {
                device.os = 'iOS';
            } else if (/Linux/.test(ua)) {
                device.os = 'Linux';
            }

            return device;
        },

        // 收集数据
        collect: function() {
            var device = this.parseDevice();
            return {
                session_id: this.sessionId,
                page_url: window.location.href,
                referer: document.referrer || '',
                user_agent: navigator.userAgent,
                device_type: device.type,
                browser: device.browser,
                os: device.os,
                screen_width: window.screen.width,
                screen_height: window.screen.height,
                language: navigator.language,
                timestamp: Date.now()
            };
        },

        // 上报数据（优先使用 sendBeacon，不阻塞页面）
        send: function() {
            var data = this.collect();
            var payload = JSON.stringify(data);

            // 优先使用 sendBeacon（页面跳转时也能发送）
            if (navigator.sendBeacon) {
                var blob = new Blob([payload], { type: 'application/json' });
                var sent = navigator.sendBeacon(this.endpoint, blob);
                if (sent) return;
            }

            // 兜底：fetch（keepalive确保跳转不丢失）
            fetch(this.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            }).catch(function(err) {
                console.warn('Stats send failed:', err);
            });
        }
    };

    // 导出
    window.StatsTracker = StatsTracker;
})();
