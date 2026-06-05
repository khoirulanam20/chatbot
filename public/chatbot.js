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
  let lastMessageId = null;
  let pollingInterval = null;
  let agentSessionActive = false;

  const ICONS = {
    bot: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>',
    message: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>',
    close: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
    send: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
    attach: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>',
    thumbsUp: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>',
    thumbsDown: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></svg>',
    check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
  };

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
      color: #fff;
    }
    #cb-bubble svg { width: 26px; height: 26px; }
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
      background: rgba(255,255,255,0.2); object-fit: cover;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; overflow: hidden;
    }
    #cb-header-avatar svg { width: 22px; height: 22px; color: #fff; }
    #cb-header-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    #cb-header-info { flex: 1; }
    #cb-header-name { font-weight: 700; font-size: 15px; }
    #cb-header-status { font-size: 12px; opacity: .9; display: flex; align-items: center; gap: 5px; }
    .cb-status-dot { width: 7px; height: 7px; border-radius: 50%; background: #4ade80; flex-shrink: 0; box-shadow: 0 0 0 2px rgba(74,222,128,0.35); }
    #cb-close-btn {
      background: rgba(255,255,255,0.12); border: none; cursor: pointer; color: #fff;
      width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
      transition: background .2s; flex-shrink: 0;
    }
    #cb-close-btn:hover { background: rgba(255,255,255,0.22); }
    #cb-close-btn svg { width: 18px; height: 18px; }
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
    .cb-rating { display: flex; gap: 8px; margin-top: 8px; align-items: center; }
    .cb-rating button {
      background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer;
      width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center;
      transition: border-color .2s, background .2s, color .2s; color: #64748b;
    }
    .cb-rating button:hover { background: #f8fafc; border-color: var(--cb-primary, #4F46E5); color: var(--cb-primary, #4F46E5); }
    .cb-rating button svg { width: 16px; height: 16px; }
    .cb-rating-thanks { font-size: 12px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }
    .cb-rating-thanks svg { width: 14px; height: 14px; color: #10b981; }
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
      width: 40px; height: 40px; border-radius: 50%; cursor: pointer; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      transition: opacity .2s, transform .15s;
    }
    #cb-send-btn:hover { opacity: 0.9; transform: scale(1.04); }
    #cb-send-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
    #cb-send-btn svg { width: 18px; height: 18px; margin-left: 1px; }
    #cb-attach-btn {
      background: #fff; border: 1.5px solid #e2e8f0; color: #64748b;
      width: 40px; height: 40px; border-radius: 50%; cursor: pointer; flex-shrink: 0;
      display: none; align-items: center; justify-content: center;
    }
    #cb-attach-btn:hover { border-color: var(--cb-primary, #4F46E5); color: var(--cb-primary, #4F46E5); }
    #cb-attach-btn svg { width: 18px; height: 18px; }
    .cb-msg-image { display: block; margin-bottom: 4px; }
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
          <div id="cb-header-avatar">${config.avatar ? `<img src="${escapeHtml(config.avatar)}" alt="">` : ICONS.bot}</div>
          <div id="cb-header-info">
            <div id="cb-header-name">${escapeHtml(config.name || 'Asisten')}</div>
            <div id="cb-header-status"><span class="cb-status-dot"></span>Online</div>
          </div>
          <button id="cb-close-btn" aria-label="Tutup" type="button">${ICONS.close}</button>
        </div>
        <div id="cb-messages"></div>
        <div id="cb-quick-replies"></div>
        <div id="cb-input-area">
          <input type="file" id="cb-file-input" accept="image/jpeg,image/png,image/gif,image/webp" hidden />
          <button id="cb-attach-btn" aria-label="Kirim gambar" type="button">${ICONS.attach}</button>
          <textarea id="cb-input" placeholder="Ketik pesan..." rows="1"></textarea>
          <button id="cb-send-btn" aria-label="Kirim" type="button">${ICONS.send}</button>
        </div>
        <div id="cb-footer">Powered by AI CS Chatbot</div>
      </div>
      <button id="cb-bubble" aria-label="Buka chat" type="button">${ICONS.message}</button>
    `;

    document.body.appendChild(wrapper);
    if (config.allow_file_upload) {
      document.getElementById('cb-attach-btn').style.display = 'flex';
    }
    bindEvents();
  }

  function bindEvents() {
    document.getElementById('cb-bubble').addEventListener('click', toggleWindow);
    document.getElementById('cb-close-btn').addEventListener('click', toggleWindow);
    document.getElementById('cb-send-btn').addEventListener('click', sendMessage);
    document.getElementById('cb-attach-btn').addEventListener('click', function () {
      document.getElementById('cb-file-input').click();
    });
    document.getElementById('cb-file-input').addEventListener('change', function (e) {
      var file = e.target.files && e.target.files[0];
      e.target.value = '';
      if (file) uploadImage(file);
    });
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
      bubble.innerHTML = ICONS.close;
      if (sessionId) {
        loadHistory();
      } else if (document.getElementById('cb-messages').children.length === 0) {
        showGreeting();
      }
      startPolling();
      setTimeout(() => document.getElementById('cb-input').focus(), 100);
    } else {
      win.classList.add('cb-hidden');
      bubble.innerHTML = ICONS.message;
      stopPolling();
    }
  }

  function loadHistory() {
    fetch(BASE_URL + '/api/chat/history/' + sessionId, {
      headers: { 'Accept': 'application/json' },
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        const container = document.getElementById('cb-messages');
        container.innerHTML = '';
        lastMessageId = null;
        if (data.messages && data.messages.length > 0) {
          data.messages.forEach(function (msg) {
            appendMessage(msg.role, msg.content, msg.id, null, msg.metadata);
            lastMessageId = msg.id;
          });
        } else {
          showGreeting();
        }
      })
      .catch(function () {
        if (document.getElementById('cb-messages').children.length === 0) {
          showGreeting();
        }
      });
  }

  function startPolling() {
    stopPolling();
    pollingInterval = setInterval(function () {
      if (sessionId && isOpen) {
        pollNewMessages();
      }
    }, 3000);
  }

  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }
  }

  function pollNewMessages() {
    var url = BASE_URL + '/api/chat/history/' + sessionId;
    if (lastMessageId) url += '?after=' + lastMessageId;
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.messages && data.messages.length > 0) {
          data.messages.forEach(function (msg) {
            // Skip pesan user — sudah ditampilkan secara optimistic saat kirim
            if (msg.role === 'user') {
              if (msg.id > (lastMessageId || 0)) lastMessageId = msg.id;
              return;
            }
            if (!document.querySelector('[data-msg-id="' + msg.id + '"]')) {
              appendMessage(msg.role, msg.content, msg.id, null, msg.metadata);
              if (msg.id > (lastMessageId || 0)) lastMessageId = msg.id;
            }
            if (msg.role === 'agent') setAgentSessionStatus(true);
          });
        }
      })
      .catch(function () {});
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

  function setAgentSessionStatus(active) {
    agentSessionActive = active;
    const statusEl = document.getElementById('cb-header-status');
    if (!statusEl) return;
    if (active) {
      statusEl.innerHTML = '<span class="cb-status-dot" style="background:#f59e0b"></span>Agen menangani';
    } else {
      statusEl.innerHTML = '<span class="cb-status-dot"></span>Online';
    }
  }

  function appendMessage(role, content, messageId, tempId, metadata) {
    const container = document.getElementById('cb-messages');
    const div = document.createElement('div');
    div.className = 'cb-msg cb-msg-' + role;
    if (messageId) div.setAttribute('data-msg-id', messageId);
    if (tempId) div.setAttribute('data-temp-id', tempId);
    if (metadata && metadata.type === 'image' && metadata.url) {
      const img = document.createElement('img');
      img.src = metadata.url.startsWith('http') ? metadata.url : BASE_URL + metadata.url;
      img.alt = 'Gambar';
      img.className = 'cb-msg-image';
      img.style.maxWidth = '100%';
      img.style.borderRadius = '8px';
      div.appendChild(img);
      if (content && content !== '[Gambar]') {
        const cap = document.createElement('div');
        cap.innerHTML = parseMarkdown(content);
        div.appendChild(cap);
      }
    } else {
      div.innerHTML = parseMarkdown(content);
    }

    if (role === 'assistant' && messageId) {
      const rating = document.createElement('div');
      rating.className = 'cb-rating';
      rating.innerHTML = `
        <button type="button" onclick="window._cbRate(${messageId}, 1, this)" title="Membantu" aria-label="Membantu">${ICONS.thumbsUp}</button>
        <button type="button" onclick="window._cbRate(${messageId}, -1, this)" title="Tidak membantu" aria-label="Tidak membantu">${ICONS.thumbsDown}</button>
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

  function uploadImage(file) {
    if (!file || isTyping) return;
    if (file.size > 10 * 1024 * 1024) {
      appendMessage('assistant', 'Ukuran gambar maksimal 10MB.');
      return;
    }
    var tempId = 'temp_img_' + Date.now();
    var previewUrl = URL.createObjectURL(file);
    appendMessage('user', '[Gambar]', null, tempId, { type: 'image', url: previewUrl });
    showTyping();

    var form = new FormData();
    form.append('bot_id', BOT_ID);
    form.append('image', file);
    if (sessionId) form.append('session_id', sessionId);

    fetch(BASE_URL + '/api/chat/image', {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: form,
    })
      .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, data: d }; }); })
      .then(function (result) {
        hideTyping();
        URL.revokeObjectURL(previewUrl);
        if (!result.ok) {
          appendMessage('assistant', result.data.error || 'Gagal mengunggah gambar.');
          return;
        }
        var data = result.data;
        if (data.session_id && !sessionId) {
          sessionId = data.session_id;
          sessionStorage.setItem('cb_session_' + BOT_ID, sessionId);
        }
        if (data.message_id) {
          var tempEl = document.querySelector('[data-temp-id="' + tempId + '"]');
          if (tempEl) {
            tempEl.setAttribute('data-msg-id', data.message_id);
            if (data.metadata && data.metadata.url) {
              var img = tempEl.querySelector('img');
              if (img) img.src = data.metadata.url.startsWith('http') ? data.metadata.url : BASE_URL + data.metadata.url;
            }
          }
          lastMessageId = data.message_id;
        }
      })
      .catch(function () {
        hideTyping();
        URL.revokeObjectURL(previewUrl);
        appendMessage('assistant', 'Gagal mengunggah gambar. Silakan coba lagi.');
      });
  }

  function appendChunksAnimated(chunks, messageId, pacingMs) {
    var index = 0;
    function next() {
      if (index >= chunks.length) return;
      var id = index === chunks.length - 1 ? messageId : null;
      appendMessage('assistant', chunks[index], id);
      index++;
      if (index < chunks.length) {
        showTyping();
        setTimeout(function () {
          hideTyping();
          next();
        }, pacingMs);
      }
    }
    next();
  }

  function sendMessageText(text) {
    const tempId = 'temp_' + Date.now();
    appendMessage('user', text, null, tempId);
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
        // Update temp user message dengan ID asli agar polling tidak duplikat
        if (data.user_message_id) {
          var tempEl = document.querySelector('[data-temp-id="' + tempId + '"]');
          if (tempEl) tempEl.setAttribute('data-msg-id', data.user_message_id);
        }
        if (data.message_id) lastMessageId = data.message_id;
        var chunks = data.message_chunks;
        if (chunks && chunks.length > 1 && data.pacing_ms > 0) {
          appendChunksAnimated(chunks, data.message_id, data.pacing_ms);
        } else {
          appendMessage('assistant', data.message || 'Maaf, terjadi kesalahan.', data.message_id);
        }
        if (data.handoff || data.agent_session) {
          setAgentSessionStatus(true);
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
      parent.innerHTML = '<span class="cb-rating-thanks">' + ICONS.check + ' Terima kasih atas masukan Anda</span>';
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
