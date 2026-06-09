(function () {
  'use strict';

  var current = document.currentScript;
  if (!current) {
    var scripts = document.getElementsByTagName('script');
    current = scripts[scripts.length - 1];
  }
  if (!current) return;

  var botId = current.getAttribute('data-bot-id');
  if (!botId) {
    console.error('[ChatBot] data-bot-id is required');
    return;
  }

  if (document.querySelector('script[data-cb-widget-loaded]')) {
    return;
  }

  var base = current.src.replace(/\/chatbot\.js(\?.*)?$/, '');

  function loadWidget(version) {
    var el = document.createElement('script');
    el.src = base + '/chatbot-widget.js?v=' + encodeURIComponent(version);
    el.setAttribute('data-bot-id', botId);
    el.setAttribute('data-cb-widget-loaded', '1');
    el.defer = true;
    document.head.appendChild(el);
  }

  fetch(base + '/chatbot-widget.ver', { cache: 'no-store' })
    .then(function (res) {
      if (!res.ok) throw new Error('version fetch failed');
      return res.text();
    })
    .then(function (v) {
      loadWidget(v.trim());
    })
    .catch(function () {
      loadWidget(String(Date.now()));
    });
})();
