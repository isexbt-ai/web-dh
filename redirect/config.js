// config.js - 配置加载模块
(function() {
    'use strict';

    // 配置文件URL，从主项目API读取
    var CONFIG_URL = 'api/config.php';

    // 本地兜底配置（安全：不暴露真实目标URL）
    var FALLBACK_CONFIG = {
        version: '1.0.0',
        main: { url: 'about:blank', name: '错误', priority: 1 },
        backups: [],
        settings: { checkTimeout: 3000, countdownSeconds: 5, fallbackToFirst: false }
    };

    window.RedirectConfig = {
        data: null,

        load: function() {
            var self = this;
            return fetch(CONFIG_URL + '?t=' + Date.now(), {
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            }).then(function(res) {
                if (!res.ok) throw new Error('Config fetch failed: ' + res.status);
                return res.json();
            }).then(function(config) {
                self.data = config;
                return config;
            }).catch(function(err) {
                console.warn('Failed to load remote config, using fallback:', err);
                self.data = FALLBACK_CONFIG;
                return FALLBACK_CONFIG;
            });
        },

        getTargetUrl: function() {
            return this.data ? this.data.main.url : FALLBACK_CONFIG.main.url;
        },

        getBackupUrls: function() {
            return this.data ? this.data.backups : FALLBACK_CONFIG.backups;
        },

        getSetting: function(key) {
            var settings = this.data ? this.data.settings : FALLBACK_CONFIG.settings;
            return settings[key];
        },

        // 获取所有地址（主地址 + 备用地址），按优先级排序
        getAllUrls: function() {
            var urls = [];
            if (this.data && this.data.main) {
                urls.push(this.data.main);
            } else {
                urls.push(FALLBACK_CONFIG.main);
            }
            var backups = this.data ? this.data.backups : FALLBACK_CONFIG.backups;
            for (var i = 0; i < backups.length; i++) {
                urls.push(backups[i]);
            }
            return urls.sort(function(a, b) { return a.priority - b.priority; });
        }
    };
})();
