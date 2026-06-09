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
  var el = document.createElement('script');
  el.src = base + '/chatbot-widget.js?v={{ (int) $version }}';
  el.setAttribute('data-bot-id', botId);
  el.setAttribute('data-cb-widget-loaded', '1');
  el.defer = true;
  document.head.appendChild(el);
})();
