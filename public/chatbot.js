(function () {
  'use strict';

  const script = document.currentScript || (function () {
    const scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();

  const BOT_ID = script.getAttribute('data-bot-id');
  const BASE_URL = script.src.replace('/chatbot.js', '');

  if (!BOT_ID) {
    console.error('[ChatBot] data-bot-id is required');
    return;
  }

  let config = {};
  let sessionId = sessionStorage.getItem('cb_session_' + BOT_ID) || null;
  let isOpen = false;
  let isTyping = false;

  const STYLES = `
    #cb-widget * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    #cb-widget { position: fixed; z-index: 999999; }
    #cb-widget.bottom-right { bottom: 20px; right: 20px; }
    #cb-widget.bottom-left { bottom: 20px; left: 20px; }
    #cb-bubble {
      width: 56px; height: 56px; border-radius: 50%;
      background: var(--cb-primary, #4F46E5);
      border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2); transition: transform .2s, box-shadow .2s;
      color: #fff; font-size: 24px;
    }
    #cb-bubble:hover { transform: scale(1.08); box-shadow: 0 6px 24px rgba(0,0,0,0.25); }
    #cb-window {
      position: absolute; bottom: 70px;
      width: 360px; max-height: 560px;
      background: #fff; border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      display: flex; flex-direction: column; overflow: hidden;
      transition: opacity .25s, transform .25s;
      color: #1e293b; font-family: system-ui, -apple-system, sans-serif;
    }
    #cb-widget.bottom-right #cb-window { right: 0; }
    #cb-widget.bottom-left #cb-window { left: 0; }
    #cb-window.cb-hidden { opacity: 0; transform: translateY(12px) scale(.97); pointer-events: none; }
    #cb-header {
      padding: 16px; background: var(--cb-primary, #4F46E5);
      color: #fff; display: flex; align-items: center; gap: 10px;
    }
    #cb-header-avatar {
      width: 38px; height: 38px; border-radius: 50%;
      background: rgba(255,255,255,0.25); object-fit: cover;
      display: flex; align-items: center; justify-content: center; font-size: 18px;
    }
    #cb-header-info { flex: 1; }
    #cb-header-name { font-weight: 700; font-size: 15px; }
    #cb-header-status { font-size: 12px; opacity: .8; }
    #cb-close-btn { background: none; border: none; cursor: pointer; color: #fff; font-size: 20px; opacity: .8; line-height: 1; }
    #cb-messages {
      flex: 1; overflow-y: auto; padding: 16px;
      display: flex; flex-direction: column; gap: 10px; min-height: 200px; max-height: 350px;
    }
    #cb-messages::-webkit-scrollbar { width: 4px; }
    #cb-messages::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
    .cb-msg { max-width: 80%; padding: 10px 14px; border-radius: 12px; font-size: 14px; line-height: 1.5; word-break: break-word; }
    .cb-msg-user { background: var(--cb-primary, #4F46E5); color: #fff; margin-left: auto; border-bottom-right-radius: 3px; }
    .cb-msg-assistant { background: #f1f5f9; color: #1e293b; margin-right: auto; border-bottom-left-radius: 3px; }
    .cb-msg-agent { background: #fef3c7; color: #92400e; margin-right: auto; border-bottom-left-radius: 3px; border-left: 3px solid #f59e0b; }
    .cb-msg-time { font-size: 11px; opacity: .6; margin-top: 3px; text-align: right; }
    .cb-typing { display: flex; gap: 4px; padding: 12px; align-items: center; }
    .cb-typing span { width: 7px; height: 7px; border-radius: 50%; background: #94a3b8; animation: cb-bounce .9s infinite; }
    .cb-typing span:nth-child(2) { animation-delay: .15s; }
    .cb-typing span:nth-child(3) { animation-delay: .3s; }
    @keyframes cb-bounce { 0%,80%,100%{transform:scale(.8)} 40%{transform:scale(1.2)} }
    .cb-rating { display: flex; gap: 6px; margin-top: 6px; }
    .cb-rating button { background: none; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; padding: 3px 8px; font-size: 14px; transition: background .2s; }
    .cb-rating button:hover { background: #f8fafc; }
    #cb-quick-replies { padding: 8px 12px; display: flex; flex-wrap: wrap; gap: 6px; border-top: 1px solid #f1f5f9; }
    .cb-quick-btn { background: none; border: 1.5px solid var(--cb-primary, #4F46E5); color: var(--cb-primary, #4F46E5); border-radius: 20px; padding: 5px 14px; font-size: 13px; cursor: pointer; transition: all .2s; white-space: nowrap; }
    .cb-quick-btn:hover { background: var(--cb-primary, #4F46E5); color: #fff; }
    #cb-input-area { padding: 12px; border-top: 1px solid #f1f5f9; display: flex; gap: 8px; align-items: center; }
    #cb-input {
      flex: 1; border: 1.5px solid #e2e8f0; border-radius: 22px; padding: 9px 16px;
      font-size: 14px; outline: none; resize: none; max-height: 80px;
      transition: border-color .2s; line-height: 1.4;
      color: #1e293b !important; background: #fff !important;
    }
    #cb-input::placeholder { color: #94a3b8 !important; }
    #cb-input:focus { border-color: var(--cb-primary, #4F46E5); }
    #cb-send-btn {
      background: var(--cb-primary, #4F46E5); color: #fff; border: none;
      width: 38px; height: 38px; border-radius: 50%; cursor: pointer; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: 16px;
      transition: background .2s;
    }
    #cb-send-btn:hover { opacity: 0.88; }
    #cb-footer { text-align: center; padding: 6px; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    .cb-msg b, .cb-msg strong { font-weight: 700; }
    .cb-msg em, .cb-msg i { font-style: italic; }
    .cb-msg ul { padding-left: 18px; margin: 4px 0; }
    .cb-msg a { color: var(--cb-primary, #4F46E5); text-decoration: underline; }
  `;

  function injectStyles() {
    const style = document.createElement('style');
    style.textContent = STYLES;
    document.head.appendChild(style);
  }

  function buildWidget() {
    const pos = config.position || 'bottom-right';
    const wrapper = document.createElement('div');
    wrapper.id = 'cb-widget';
    wrapper.className = pos;
    wrapper.style.setProperty('--cb-primary', config.primary_color || '#4F46E5');

    wrapper.innerHTML = `
      <div id="cb-window" class="cb-hidden">
        <div id="cb-header">
          <div id="cb-header-avatar">${config.avatar ? `<img src="${config.avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover">` : '🤖'}</div>
          <div id="cb-header-info">
            <div id="cb-header-name">${escapeHtml(config.name || 'Asisten')}</div>
            <div id="cb-header-status">● Online</div>
          </div>
          <button id="cb-close-btn" aria-label="Tutup">✕</button>
        </div>
        <div id="cb-messages"></div>
        <div id="cb-quick-replies"></div>
        <div id="cb-input-area">
          <textarea id="cb-input" placeholder="Ketik pesan..." rows="1"></textarea>
          <button id="cb-send-btn" aria-label="Kirim">➤</button>
        </div>
        <div id="cb-footer">Powered by AI CS Chatbot</div>
      </div>
      <button id="cb-bubble" aria-label="Buka chat">💬</button>
    `;

    document.body.appendChild(wrapper);
    bindEvents();
  }

  function bindEvents() {
    document.getElementById('cb-bubble').addEventListener('click', toggleWindow);
    document.getElementById('cb-close-btn').addEventListener('click', toggleWindow);
    document.getElementById('cb-send-btn').addEventListener('click', sendMessage);
    document.getElementById('cb-input').addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }

  function toggleWindow() {
    isOpen = !isOpen;
    const win = document.getElementById('cb-window');
    const bubble = document.getElementById('cb-bubble');
    if (isOpen) {
      win.classList.remove('cb-hidden');
      bubble.innerHTML = '✕';
      if (document.getElementById('cb-messages').children.length === 0) {
        showGreeting();
      }
      setTimeout(() => document.getElementById('cb-input').focus(), 100);
    } else {
      win.classList.add('cb-hidden');
      bubble.innerHTML = '💬';
    }
  }

  function showGreeting() {
    if (config.greeting) {
      appendMessage('assistant', config.greeting);
    }
    renderQuickReplies();
  }

  function renderQuickReplies() {
    const container = document.getElementById('cb-quick-replies');
    container.innerHTML = '';
    if (config.quick_replies && config.quick_replies.length > 0) {
      config.quick_replies.forEach(function (text) {
        const btn = document.createElement('button');
        btn.className = 'cb-quick-btn';
        btn.textContent = text;
        btn.addEventListener('click', function () {
          container.innerHTML = '';
          sendMessageText(text);
        });
        container.appendChild(btn);
      });
    }
  }

  function appendMessage(role, content, messageId) {
    const container = document.getElementById('cb-messages');
    const div = document.createElement('div');
    div.className = 'cb-msg cb-msg-' + role;
    div.innerHTML = parseMarkdown(content);

    if (role === 'assistant' && messageId) {
      const rating = document.createElement('div');
      rating.className = 'cb-rating';
      rating.innerHTML = `
        <button onclick="window._cbRate(${messageId}, 1, this)" title="Helpful">👍</button>
        <button onclick="window._cbRate(${messageId}, -1, this)" title="Not helpful">👎</button>
      `;
      div.appendChild(rating);
    }

    const time = document.createElement('div');
    time.className = 'cb-msg-time';
    time.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    div.appendChild(time);

    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
  }

  function showTyping() {
    const container = document.getElementById('cb-messages');
    const div = document.createElement('div');
    div.className = 'cb-msg cb-msg-assistant';
    div.id = 'cb-typing-indicator';
    div.innerHTML = '<div class="cb-typing"><span></span><span></span><span></span></div>';
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    isTyping = true;
  }

  function hideTyping() {
    const el = document.getElementById('cb-typing-indicator');
    if (el) el.remove();
    isTyping = false;
  }

  function sendMessage() {
    const input = document.getElementById('cb-input');
    const text = input.value.trim();
    if (!text || isTyping) return;
    input.value = '';
    input.style.height = 'auto';
    sendMessageText(text);
  }

  function sendMessageText(text) {
    appendMessage('user', text);
    showTyping();

    fetch(BASE_URL + '/api/chat/message', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ bot_id: BOT_ID, session_id: sessionId, message: text }),
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        hideTyping();
        if (data.session_id && !sessionId) {
          sessionId = data.session_id;
          sessionStorage.setItem('cb_session_' + BOT_ID, sessionId);
        }
        appendMessage('assistant', data.message || 'Maaf, terjadi kesalahan.', data.message_id);
        if (data.handoff) {
          appendMessage('assistant', '🔗 Menghubungkan ke agen...');
        }
      })
      .catch(function () {
        hideTyping();
        appendMessage('assistant', 'Maaf, terjadi kesalahan koneksi. Silakan coba lagi.');
      });
  }

  window._cbRate = function (messageId, rating, btn) {
    const parent = btn.closest('.cb-rating');
    fetch(BASE_URL + '/api/chat/rate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ message_id: messageId, rating: rating }),
    }).then(function () {
      parent.innerHTML = rating === 1 ? '✅ Terima kasih!' : '📝 Terima kasih atas masukan Anda!';
    });
  };

  function parseMarkdown(text) {
    return text
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/_(.*?)_/g, '<em>$1</em>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/`(.*?)`/g, '<code>$1</code>')
      .replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
      .replace(/^- (.+)$/gm, '<li>$1</li>')
      .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
      .replace(/\n/g, '<br>');
  }

  function escapeHtml(text) {
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function init() {
    fetch(BASE_URL + '/api/bot/config/' + BOT_ID)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        config = data;
        injectStyles();
        buildWidget();

        if (config.auto_open_delay && config.auto_open_delay > 0) {
          setTimeout(function () {
            if (!isOpen) toggleWindow();
          }, config.auto_open_delay * 1000);
        }
      })
      .catch(function (err) {
        console.error('[ChatBot] Failed to load config', err);
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
