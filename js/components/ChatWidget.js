/**
 * ChatWidget - Floating web chat for the shared AI chat core.
 */
const ChatWidget = {
  STORAGE_KEY: 'gcms_chat_widget_state',
  handoffPollTimer: null,

  state: {
    initialized: false,
    loading: false,
    open: false,
    conversationId: '',
    handoffStatus: '',
    capabilities: null,
    messages: []
  },

  elements: {
    root: null,
    panel: null,
    toggle: null,
    messages: null,
    form: null,
    input: null,
    submit: null
  },

  async init() {
    if (this.state.initialized) {
      return;
    }
    this.state.initialized = true;

    this._restoreState();
    this._injectStyles();
    this._render();
    this._bindEvents();
    this._syncPanelVisibility();
    this._updateComposerPlaceholder();
    this._resizeInput();
    this._renderMessages();

    await this._loadCapabilities();
    this._applyChatUiFromCapabilities();

    if (this.state.messages.length === 0) {
      this._addMessage({
        role: 'assistant',
        text: this._starterMessage(),
        actions: []
      });
    } else {
      this._renderMessages();
    }

    this._syncHandoffPolling();
  },

  async _loadCapabilities() {
    try {
      const response = await fetch(this._url('api/index/chat/capabilities'), {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json'
        }
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Failed to load chat capabilities');
      }

      this.state.capabilities = result.data || null;
    } catch (error) {
      // Capabilities are optional; chat still works with defaults.
    }
  },

  _render() {
    if (this.elements.root) {
      return;
    }

    const rootEl = document.createElement('div');
    rootEl.className = 'chat-widget';
    rootEl.innerHTML = `
      <button type="button" class="chat-widget__toggle" aria-expanded="false" aria-controls="chat-widget-panel">
        <span class="chat-widget__toggle-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>
          </svg>
        </span>
        <span class="chat-widget__toggle-label">Assistant</span>
      </button>
      <section id="chat-widget-panel" class="chat-widget__panel" role="dialog" aria-labelledby="chat-widget-title" hidden>
        <header class="chat-widget__header">
          <div class="chat-widget__header-text">
            <h2 id="chat-widget-title" class="chat-widget__title">AI assistant</h2>
          </div>
          <button type="button" class="chat-widget__close" aria-label="Close chat">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <path d="M18 6 6 18M6 6l12 12"></path>
            </svg>
          </button>
        </header>
        <div class="chat-widget__messages" aria-live="polite"></div>
        <form class="chat-widget__composer" autocomplete="off">
          <label class="visually-hidden" for="chat-widget-input">Message</label>
          <div class="chat-widget__composer-row">
            <textarea id="chat-widget-input" class="chat-widget__input" rows="1" placeholder="Type /help for commands"></textarea>
            <button type="submit" class="chat-widget__submit" aria-label="Send">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m22 2-7 20-4-9-9-4z"></path>
                <path d="M22 2 11 13"></path>
              </svg>
            </button>
          </div>
        </form>
      </section>
    `;

    document.body.appendChild(rootEl);

    this.elements.root = rootEl;
    this.elements.panel = rootEl.querySelector('.chat-widget__panel');
    this.elements.toggle = rootEl.querySelector('.chat-widget__toggle');
    this.elements.messages = rootEl.querySelector('.chat-widget__messages');
    this.elements.form = rootEl.querySelector('.chat-widget__composer');
    this.elements.input = rootEl.querySelector('.chat-widget__input');
    this.elements.submit = rootEl.querySelector('.chat-widget__submit');
  },

  _bindEvents() {
    this.elements.toggle.addEventListener('click', () => {
      this._openPanel();
    });

    this.elements.root.querySelector('.chat-widget__close').addEventListener('click', () => {
      this._closePanel();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && this.state.open) {
        this._closePanel();
      }
    });

    this.elements.form.addEventListener('submit', (event) => {
      event.preventDefault();
      const text = this.elements.input.value.trim();
      if (text === '') {
        this.elements.input.focus();
        return;
      }
      this.sendMessage(text);
    });

    this.elements.input.addEventListener('input', () => {
      this._resizeInput();
    });

    this.elements.input.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        this.elements.form.requestSubmit();
      }
    });
  },

  _openPanel(focusInput = true) {
    this.state.open = true;
    this._syncPanelVisibility();
    this._saveState();
    this._scrollToBottom();
    if (focusInput) {
      window.requestAnimationFrame(() => {
        this.elements.input.focus();
      });
    }
  },

  _closePanel() {
    this.state.open = false;
    this._syncPanelVisibility();
    this._saveState();
  },

  _syncPanelVisibility() {
    if (!this.elements.root) {
      return;
    }

    const isOpen = !!this.state.open;
    this.elements.root.classList.toggle('chat-widget--open', isOpen);
    this.elements.panel.hidden = !isOpen;
    this.elements.toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    this.elements.toggle.hidden = isOpen;
  },

  async sendMessage(text) {
    if (this.state.loading) {
      return;
    }

    const content = String(text || '').trim();
    if (content === '') {
      return;
    }

    this._openPanel(false);
    this._addMessage({role: 'user', text: content});
    this.elements.input.value = '';
    this._resizeInput();
    this._setLoading(true);

    try {
      const response = await fetch(this._url('api/index/chat/message'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          channel: 'web',
          message: content,
          conversation_id: this.state.conversationId,
          history: this._historyPayload()
        })
      });

      const result = await response.json();
      const payload = result.data || {};
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Chat request failed');
      }

      this.state.conversationId = payload.conversation_id || this.state.conversationId;
      if (payload.meta && payload.meta.handoff && typeof payload.meta.handoff === 'object') {
        this.state.handoffStatus = String(payload.meta.handoff.status || 'open').trim().toLowerCase();
        this._syncHandoffPolling();
        this._updateComposerPlaceholder();
      }
      const ui = this.state.capabilities?.chat_ui || {};
      this._addMessage({
        role: 'assistant',
        text: payload.message || ui.error_empty_reply || 'No reply was returned.',
        cards: Array.isArray(payload.cards) ? payload.cards : [],
        actions: Array.isArray(payload.actions) ? payload.actions : []
      });
    } catch (error) {
      const ui = this.state.capabilities?.chat_ui || {};
      this._addMessage({
        role: 'assistant',
        text: error.message || ui.error_send || 'Could not send your message right now.',
        error: true,
        actions: []
      });
    } finally {
      this._setLoading(false);
    }
  },

  _addMessage(message) {
    const item = {
      role: message.role === 'user' ? 'user' : 'assistant',
      text: String(message.text || '').trim(),
      error: !!message.error,
      notice: !!message.notice,
      cards: Array.isArray(message.cards) ? message.cards : [],
      actions: Array.isArray(message.actions) ? message.actions : []
    };

    this.state.messages.push(item);
    this.state.messages = this.state.messages.slice(-20);
    this._saveState();
    this._renderMessages();
  },

  _renderMessages() {
    if (!this.elements.messages) {
      return;
    }

    this.elements.messages.innerHTML = '';
    this.state.messages.forEach((message) => {
      this.elements.messages.appendChild(this._renderMessage(message));
    });

    if (this.state.loading) {
      this.elements.messages.appendChild(this._renderTypingIndicator());
    }

    this._scrollToBottom();
  },

  _renderTypingIndicator() {
    const ui = this.state.capabilities?.chat_ui || {};
    const typingLabel = String(ui.typing || 'Typing');

    const article = document.createElement('article');
    article.className = 'chat-widget__message chat-widget__message--assistant chat-widget__message--typing';
    const bubble = document.createElement('div');
    bubble.className = 'chat-widget__bubble';
    const wrap = document.createElement('span');
    wrap.className = 'chat-widget__typing';
    wrap.setAttribute('aria-label', typingLabel);
    for (let i = 0; i < 3; i += 1) {
      wrap.appendChild(document.createElement('span'));
    }
    bubble.appendChild(wrap);
    article.appendChild(bubble);
    return article;
  },

  _renderMessage(message) {
    const article = document.createElement('article');
    article.className = `chat-widget__message chat-widget__message--${message.role}${message.error ? ' chat-widget__message--error' : ''}${message.notice ? ' chat-widget__message--notice' : ''}`;

    const bubbleNode = document.createElement('div');
    bubbleNode.className = 'chat-widget__bubble';

    if (message.text) {
      const text = document.createElement('p');
      text.className = 'chat-widget__text';
      text.innerHTML = this._formatMessageText(message.text);
      bubbleNode.appendChild(text);
    }

    if (message.cards.length > 0) {
      const cards = document.createElement('div');
      cards.className = `chat-widget__cards${message.text ? '' : ' chat-widget__cards--only'}`;
      message.cards.forEach((card) => {
        cards.appendChild(this._renderCard(card));
      });
      bubbleNode.appendChild(cards);
    }

    const bubbleActions = this._renderBubbleActions(message);
    if (bubbleActions) {
      bubbleNode.appendChild(bubbleActions);
    }

    article.appendChild(bubbleNode);
    return article;
  },

  _applyChatUiFromCapabilities() {
    const ui = this.state.capabilities?.chat_ui;
    if (!ui || typeof ui !== 'object') {
      return;
    }

    const toggleLabel = this.elements.toggle && this.elements.toggle.querySelector('.chat-widget__toggle-label');
    if (toggleLabel && ui.toggle) {
      toggleLabel.textContent = ui.toggle;
    }

    const titleEl = this.elements.root && this.elements.root.querySelector('#chat-widget-title');
    if (titleEl && ui.title) {
      titleEl.textContent = ui.title;
    }

    const closeBtn = this.elements.root && this.elements.root.querySelector('.chat-widget__close');
    if (closeBtn && ui.close) {
      closeBtn.setAttribute('aria-label', ui.close);
    }

    const inputLabel = this.elements.root && this.elements.root.querySelector('label[for="chat-widget-input"]');
    if (inputLabel && ui.composer_label) {
      inputLabel.textContent = ui.composer_label;
    }

    if (this.elements.submit && ui.send) {
      this.elements.submit.setAttribute('aria-label', ui.send);
    }

    this._updateComposerPlaceholder();
  },

  _renderBubbleActions(message) {
    if (message.role !== 'assistant' || !message.actions || message.actions.length === 0) {
      return null;
    }

    const wrap = document.createElement('div');
    wrap.className = 'chat-widget__bubble-actions';

    message.actions.forEach((action) => {
      if (!action || !action.type) {
        return;
      }

      if (action.type === 'handoff_note') {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'chat-widget__bubble-action';
        button.textContent = String(action.label || action.value || '');
        button.addEventListener('click', () => {
          this._openPanel(false);
          this._updateComposerPlaceholder(true);
          this.elements.input.focus();
        });
        wrap.appendChild(button);
        return;
      }

      if (action.type !== 'prompt' || !action.value) {
        return;
      }

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'chat-widget__bubble-action';
      button.textContent = String(action.label || action.value || '');
      button.addEventListener('click', () => {
        const behavior = String(action.behavior || 'send').toLowerCase();
        if (behavior === 'compose') {
          this._openPanel(false);
          this.elements.input.value = String(action.value || '');
          this._resizeInput();
          this.elements.input.focus();
          return;
        }
        this.sendMessage(String(action.value));
      });
      wrap.appendChild(button);
    });

    return wrap.childNodes.length > 0 ? wrap : null;
  },

  _formatMessageText(text) {
    const source = String(text || '');
    const pattern = /https?:\/\/[^\s<>"]+/g;
    let html = '';
    let lastIndex = 0;
    let match = pattern.exec(source);

    while (match) {
      const url = match[0];
      const index = match.index;
      html += Utils.string.escape(source.slice(lastIndex, index));
      html += this._inlineLinkHtml(url);
      lastIndex = index + url.length;
      match = pattern.exec(source);
    }

    html += Utils.string.escape(source.slice(lastIndex));

    return html.replace(/\n/g, '<br>');
  },

  _inlineLinkHtml(url) {
    const label = this._shortLinkLabel(url);

    return `<a class="chat-widget__inline-link" href="${Utils.string.escape(url)}" target="_blank" rel="noopener noreferrer" title="${Utils.string.escape(url)}">${Utils.string.escape(label)}</a>`;
  },

  _shortLinkLabel(url) {
    try {
      const parsed = new URL(String(url), window.location.origin);
      const path = decodeURIComponent(parsed.pathname || '/');
      const visible = `${parsed.host}${path}${parsed.search ? '?...' : ''}`;

      return visible.length > 44 ? `${visible.slice(0, 41)}...` : visible;
    } catch (error) {
      const fallback = String(url || '');
      return fallback.length > 44 ? `${fallback.slice(0, 41)}...` : fallback;
    }
  },

  _renderCard(card) {
    const ui = this.state.capabilities?.chat_ui || {};

    const element = document.createElement('section');
    element.className = 'chat-widget__card';

    const title = document.createElement('h3');
    title.className = 'chat-widget__card-title';
    title.textContent = String(card.title || ui.card_default_title || 'Result');
    element.appendChild(title);

    if (card.description) {
      const description = document.createElement('p');
      description.className = 'chat-widget__card-description';
      description.textContent = String(card.description);
      element.appendChild(description);
    }

    const meta = [];
    if (card.module) {
      meta.push(String(card.module));
    }
    if (card.date) {
      meta.push(String(card.date));
    }
    if (meta.length > 0) {
      const metaEl = document.createElement('p');
      metaEl.className = 'chat-widget__card-meta';
      metaEl.textContent = meta.join(' • ');
      element.appendChild(metaEl);
    }

    if (card.url) {
      const link = document.createElement('a');
      link.className = 'chat-widget__card-link';
      link.href = String(card.url);
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.textContent = String(ui.open_link || 'Open link');
      element.appendChild(link);
    }

    return element;
  },

  _starterMessage() {
    const configured = String(this.state.capabilities?.messages?.starter_message || '').trim();
    if (configured !== '') {
      return configured;
    }

    const fallback = String(this.state.capabilities?.chat_ui?.starter_default || '').trim();
    if (fallback !== '') {
      return fallback;
    }

    return 'Hello! Type /help to see all commands — for example /search topic or /contact your message to staff.';
  },

  _historyPayload() {
    return this.state.messages.slice(-12).map((message) => ({
      role: message.role,
      content: message.text
    }));
  },

  _setLoading(loading) {
    this.state.loading = loading;
    this.elements.submit.disabled = loading;
    this.elements.input.disabled = loading;
    this.elements.root.classList.toggle('chat-widget--loading', loading);
    this._renderMessages();
  },

  _handoffNoticeText(status) {
    const ui = this.state.capabilities?.chat_ui || {};
    const normalized = String(status || '').trim().toLowerCase();
    if (normalized === 'accepted') {
      return ui.handoff_accepted || 'A staff member has accepted your request. Please wait for a reply.';
    }
    if (normalized === 'closed') {
      return ui.handoff_closed || 'This request was closed. You can send a new message if you need more help.';
    }

    return '';
  },

  _addHandoffNotice(status, preferredText = '') {
    const text = String(preferredText || '').trim() || this._handoffNoticeText(status);
    if (text === '') {
      return;
    }

    const last = this.state.messages[this.state.messages.length - 1];
    if (last && last.text === text) {
      return;
    }

    this._addMessage({
      role: 'assistant',
      text,
      notice: true,
      actions: []
    });
  },

  _syncHandoffPolling() {
    if (!this.state.conversationId || !this.state.handoffStatus || this.state.handoffStatus === 'closed') {
      this._stopHandoffPolling();
      this._updateComposerPlaceholder();
      return;
    }

    this._updateComposerPlaceholder();
    this._startHandoffPolling();
  },

  _updateComposerPlaceholder(forceHandoff = false) {
    if (!this.elements.input) {
      return;
    }

    const ui = this.state.capabilities?.chat_ui || {};
    const active = forceHandoff
      || (this.state.handoffStatus && this.state.handoffStatus !== 'closed');
    this.elements.input.placeholder = active
      ? (ui.composer_placeholder_handoff || 'Type your message to staff…')
      : (ui.composer_placeholder || 'Type /help for commands — e.g. /search topic, /contact your note');
  },

  _resizeInput() {
    if (!this.elements.input) {
      return;
    }

    this.elements.input.style.height = 'auto';
    const nextHeight = Math.min(this.elements.input.scrollHeight, 120);
    this.elements.input.style.height = `${Math.max(44, nextHeight)}px`;
  },

  _startHandoffPolling() {
    if (this.handoffPollTimer !== null) {
      return;
    }

    this.handoffPollTimer = window.setInterval(() => {
      this._pollHandoffProgress();
    }, 15000);
    this._pollHandoffProgress();
  },

  _stopHandoffPolling() {
    if (this.handoffPollTimer !== null) {
      window.clearInterval(this.handoffPollTimer);
      this.handoffPollTimer = null;
    }
  },

  async _pollHandoffProgress() {
    if (!this.state.conversationId || !this.state.handoffStatus || this.state.handoffStatus === 'closed') {
      this._stopHandoffPolling();
      return;
    }

    try {
      const params = new URLSearchParams({
        conversation_id: this.state.conversationId,
        current_status: this.state.handoffStatus
      });
      const response = await fetch(this._url(`api/index/chat/handoffProgress?${params.toString()}`), {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json'
        }
      });
      const result = await response.json();
      const payload = result.data || {};
      if (!response.ok || !result.success || !payload.item) {
        return;
      }

      const nextStatus = String(payload.item.status || '').trim().toLowerCase();
      if (nextStatus === '' || nextStatus === this.state.handoffStatus) {
        if (nextStatus === 'closed') {
          this._stopHandoffPolling();
          this._updateComposerPlaceholder();
        }
        return;
      }

      this.state.handoffStatus = nextStatus;
      this._saveState();
      this._updateComposerPlaceholder();

      const statusMessage = String(payload.status_message || '').trim();
      this._addHandoffNotice(nextStatus, statusMessage);

      if (nextStatus === 'closed') {
        this._stopHandoffPolling();
      }
    } catch (error) {
      // Ignore transient polling errors.
    }
  },

  _scrollToBottom() {
    if (this.elements.messages) {
      this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
    }
  },

  _restoreState() {
    try {
      const raw = sessionStorage.getItem(this.STORAGE_KEY);
      if (!raw) {
        this.state.open = false;
        return;
      }
      const data = JSON.parse(raw);
      this.state.open = !!data.open;
      this.state.conversationId = typeof data.conversationId === 'string' ? data.conversationId : '';
      this.state.handoffStatus = typeof data.handoffStatus === 'string' ? data.handoffStatus : '';
      this.state.messages = Array.isArray(data.messages) ? data.messages.slice(-20) : [];
    } catch (error) {
      this.state.open = false;
      this.state.conversationId = '';
      this.state.handoffStatus = '';
      this.state.messages = [];
    }
  },

  _saveState() {
    try {
      sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify({
        open: this.state.open,
        conversationId: this.state.conversationId,
        handoffStatus: this.state.handoffStatus,
        messages: this.state.messages
      }));
    } catch (error) {
      // Ignore storage failures.
    }
  },

  _url(path) {
    const baseUrl = typeof WEB_URL === 'string' ? WEB_URL : '/';
    return `${baseUrl}${path}`;
  },

  _injectStyles() {
    if (document.getElementById('chat-widget-styles')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'chat-widget-styles';
    style.textContent = `
      .chat-widget {
        position: fixed;
        right: 1.25rem;
        bottom: 1.25rem;
        z-index: calc(var(--z-index-toast, 1090) + 20);
        font-family: var(--font-family-base, system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif);
        --cw-panel-bg: var(--color-background, #fff);
        --cw-panel-border: rgba(15, 23, 42, 0.1);
        --cw-panel-shadow: 0 24px 64px rgba(15, 23, 42, 0.22);
        --cw-header-bg: var(--color-background, #fff);
        --cw-header-border: rgba(15, 23, 42, 0.08);
        --cw-title: var(--color-text, #0f172a);
        --cw-close-bg: rgba(15, 23, 42, 0.06);
        --cw-close-bg-hover: rgba(15, 23, 42, 0.1);
        --cw-close-fg: var(--color-text-muted, #64748b);
        --cw-close-fg-hover: var(--color-text, #0f172a);
        --cw-messages-bg: #f8fafc;
        --cw-bubble-bg: #fff;
        --cw-bubble-border: rgba(148, 163, 184, 0.22);
        --cw-bubble-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
        --cw-text: var(--color-text, #0f172a);
        --cw-notice-bg: rgba(37, 99, 235, 0.08);
        --cw-notice-border: rgba(37, 99, 235, 0.18);
        --cw-notice-text: #1e40af;
        --cw-error-bg: #fef2f2;
        --cw-error-border: rgba(220, 38, 38, 0.28);
        --cw-error-text: #991b1b;
        --cw-card-bg: #f8fafc;
        --cw-card-border: rgba(148, 163, 184, 0.22);
        --cw-card-desc: #64748b;
        --cw-quick-bg: var(--color-surface, #fff);
        --cw-quick-border: rgba(15, 23, 42, 0.06);
        --cw-chip-bg: #fff;
        --cw-chip-border: rgba(37, 99, 235, 0.22);
        --cw-chip-text: var(--color-text, #0f172a);
        --cw-chip-hover: rgba(37, 99, 235, 0.07);
        --cw-composer-bg: var(--color-surface, #fff);
        --cw-row-bg: #f8fafc;
        --cw-row-border: rgba(15, 23, 42, 0.12);
        --cw-row-focus-bg: #fff;
        --cw-placeholder: #94a3b8;
        --cw-typing-dot: #94a3b8;
        --cw-link-bg: rgba(37, 99, 235, 0.12);
        --cw-link-text: var(--color-primary, #2563eb);
      }
      [data-theme="dark"] .chat-widget,
      html.dark .chat-widget {
        --cw-panel-bg: #1e293b;
        --cw-panel-border: rgba(148, 163, 184, 0.22);
        --cw-panel-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
        --cw-header-bg: #1e293b;
        --cw-header-border: rgba(148, 163, 184, 0.15);
        --cw-title: #f1f5f9;
        --cw-close-bg: rgba(255, 255, 255, 0.08);
        --cw-close-bg-hover: rgba(255, 255, 255, 0.14);
        --cw-close-fg: #94a3b8;
        --cw-close-fg-hover: #f1f5f9;
        --cw-messages-bg: #0f172a;
        --cw-bubble-bg: #1e293b;
        --cw-bubble-border: rgba(148, 163, 184, 0.25);
        --cw-bubble-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
        --cw-text: #e2e8f0;
        --cw-notice-bg: rgba(59, 130, 246, 0.18);
        --cw-notice-border: rgba(96, 165, 250, 0.35);
        --cw-notice-text: #93c5fd;
        --cw-error-bg: rgba(127, 29, 29, 0.45);
        --cw-error-border: rgba(248, 113, 113, 0.35);
        --cw-error-text: #fecaca;
        --cw-card-bg: #1e293b;
        --cw-card-border: rgba(148, 163, 184, 0.22);
        --cw-card-desc: #94a3b8;
        --cw-quick-bg: #1e293b;
        --cw-quick-border: rgba(148, 163, 184, 0.12);
        --cw-chip-bg: #0f172a;
        --cw-chip-border: rgba(96, 165, 250, 0.35);
        --cw-chip-text: #e2e8f0;
        --cw-chip-hover: rgba(59, 130, 246, 0.15);
        --cw-composer-bg: #1e293b;
        --cw-row-bg: #0f172a;
        --cw-row-border: rgba(148, 163, 184, 0.25);
        --cw-row-focus-bg: #1e293b;
        --cw-placeholder: #64748b;
        --cw-typing-dot: #94a3b8;
        --cw-link-bg: rgba(59, 130, 246, 0.22);
        --cw-link-text: #93c5fd;
      }
      @media (prefers-color-scheme: dark) {
        html:not([data-theme="light"]) .chat-widget {
          --cw-panel-bg: #1e293b;
          --cw-panel-border: rgba(148, 163, 184, 0.22);
          --cw-panel-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
          --cw-header-bg: #1e293b;
          --cw-header-border: rgba(148, 163, 184, 0.15);
          --cw-title: #f1f5f9;
          --cw-close-bg: rgba(255, 255, 255, 0.08);
          --cw-close-bg-hover: rgba(255, 255, 255, 0.14);
          --cw-close-fg: #94a3b8;
          --cw-close-fg-hover: #f1f5f9;
          --cw-messages-bg: #0f172a;
          --cw-bubble-bg: #1e293b;
          --cw-bubble-border: rgba(148, 163, 184, 0.25);
          --cw-bubble-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
          --cw-text: #e2e8f0;
          --cw-notice-bg: rgba(59, 130, 246, 0.18);
          --cw-notice-border: rgba(96, 165, 250, 0.35);
          --cw-notice-text: #93c5fd;
          --cw-error-bg: rgba(127, 29, 29, 0.45);
          --cw-error-border: rgba(248, 113, 113, 0.35);
          --cw-error-text: #fecaca;
          --cw-card-bg: #1e293b;
          --cw-card-border: rgba(148, 163, 184, 0.22);
          --cw-card-desc: #94a3b8;
          --cw-quick-bg: #1e293b;
          --cw-quick-border: rgba(148, 163, 184, 0.12);
          --cw-chip-bg: #0f172a;
          --cw-chip-border: rgba(96, 165, 250, 0.35);
          --cw-chip-text: #e2e8f0;
          --cw-chip-hover: rgba(59, 130, 246, 0.15);
          --cw-composer-bg: #1e293b;
          --cw-row-bg: #0f172a;
          --cw-row-border: rgba(148, 163, 184, 0.25);
          --cw-row-focus-bg: #1e293b;
          --cw-placeholder: #64748b;
          --cw-typing-dot: #94a3b8;
          --cw-link-bg: rgba(59, 130, 246, 0.22);
          --cw-link-text: #93c5fd;
        }
      }
      .chat-widget__toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--color-primary, #2563eb), var(--color-primary-hover, #1d4ed8));
        color: #fff;
        padding: 0.75rem 1rem;
        box-shadow: 0 12px 32px rgba(37, 99, 235, 0.35);
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
      }
      .chat-widget__toggle:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 36px rgba(37, 99, 235, 0.4);
      }
      .chat-widget__toggle[hidden] {
        display: none !important;
      }
      .chat-widget__toggle-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
      }
      .chat-widget__toggle-label {
        font-size: 0.92rem;
        font-weight: 600;
      }
      .chat-widget__panel {
        position: absolute;
        right: 0;
        bottom: 0;
        z-index: calc(var(--z-index-toast, 1090) + 20);
        width: min(380px, calc(100vw - 1.5rem));
        height: min(560px, calc(100vh - 5.5rem));
        display: none;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--cw-panel-border);
        border-radius: 20px;
        background: var(--cw-panel-bg);
        box-shadow: var(--cw-panel-shadow);
        color: var(--cw-text);
      }
      .chat-widget__panel[hidden] {
        display: none !important;
      }
      .chat-widget--open .chat-widget__panel {
        display: flex;
      }
      .chat-widget__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1rem 0.85rem;
        border-bottom: 1px solid var(--cw-header-border);
        background: var(--cw-header-bg);
      }
      .chat-widget__header-text {
        min-width: 0;
        flex: 1;
      }
      .chat-widget__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--cw-title);
      }
      .chat-widget__close {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 0;
        padding: 0;
        border-radius: 999px;
        background: var(--cw-close-bg);
        color: var(--cw-close-fg);
        cursor: pointer;
      }
      .chat-widget__close:hover {
        background: var(--cw-close-bg-hover);
        color: var(--cw-close-fg-hover);
      }
      .chat-widget__messages {
        flex: 1;
        overflow: auto;
        padding: 0.85rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        background: var(--cw-messages-bg);
      }
      .chat-widget__message {
        display: flex;
      }
      .chat-widget__message--user {
        justify-content: flex-end;
      }
      .chat-widget__message--notice {
        justify-content: center;
      }
      .chat-widget__message--notice .chat-widget__bubble {
        max-width: 100%;
        background: var(--cw-notice-bg);
        border-color: var(--cw-notice-border);
        color: var(--cw-notice-text);
        font-size: 0.85rem;
        text-align: center;
        box-shadow: none;
      }
      .chat-widget__bubble {
        max-width: 88%;
        border-radius: 16px;
        padding: 0.7rem 0.9rem;
        background: var(--cw-bubble-bg);
        border: 1px solid var(--cw-bubble-border);
        box-shadow: var(--cw-bubble-shadow);
        color: var(--cw-text);
      }
      .chat-widget__message--user .chat-widget__bubble {
        background: linear-gradient(135deg, var(--color-primary, #2563eb), var(--color-primary-hover, #1d4ed8));
        border-color: transparent;
        color: #fff;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
      }
      .chat-widget__message--error .chat-widget__bubble {
        border-color: var(--cw-error-border);
        background: var(--cw-error-bg);
        color: var(--cw-error-text);
      }
      .chat-widget__text {
        margin: 0;
        white-space: pre-wrap;
        line-height: 1.55;
        font-size: 0.92rem;
        color: inherit;
      }
      .chat-widget__typing {
        display: inline-flex;
        gap: 0.3rem;
        align-items: center;
        min-height: 1rem;
      }
      .chat-widget__typing span {
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 999px;
        background: var(--cw-typing-dot);
        animation: chat-widget-typing 1s infinite ease-in-out;
      }
      .chat-widget__typing span:nth-child(2) { animation-delay: 0.15s; }
      .chat-widget__typing span:nth-child(3) { animation-delay: 0.3s; }
      @keyframes chat-widget-typing {
        0%, 80%, 100% { opacity: 0.35; transform: translateY(0); }
        40% { opacity: 1; transform: translateY(-3px); }
      }
      .chat-widget__inline-link {
        display: inline-flex;
        max-width: min(100%, 16rem);
        margin: 0.1rem 0;
        padding: 0.1rem 0.5rem;
        border-radius: 999px;
        background: var(--cw-link-bg);
        color: var(--cw-link-text);
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .chat-widget__message--user .chat-widget__inline-link {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
      }
      .chat-widget__cards {
        display: grid;
        gap: 0.6rem;
        margin-top: 0.65rem;
      }
      .chat-widget__cards--only { margin-top: 0; }
      .chat-widget__card {
        padding: 0.7rem 0.8rem;
        border-radius: 12px;
        background: var(--cw-card-bg);
        border: 1px solid var(--cw-card-border);
        color: var(--cw-text);
      }
      .chat-widget__card-title,
      .chat-widget__card-description,
      .chat-widget__card-meta {
        margin: 0;
        font-size: 0.88rem;
      }
      .chat-widget__card-description {
        margin-top: 0.25rem;
        color: var(--cw-card-desc);
      }
      .chat-widget__card-link {
        display: inline-flex;
        margin-top: 0.5rem;
        color: var(--cw-link-text);
        font-weight: 600;
        font-size: 0.85rem;
      }
      .chat-widget__bubble-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.6rem;
      }
      .chat-widget__bubble-action {
        border: 1px solid var(--cw-chip-border);
        background: var(--cw-chip-bg);
        color: var(--cw-chip-text);
        border-radius: 999px;
        padding: 0.35rem 0.7rem;
        font-size: 0.78rem;
        cursor: pointer;
      }
      .chat-widget__bubble-action:hover {
        background: var(--cw-chip-hover);
      }
      .chat-widget__composer {
        padding: 0.65rem 1rem 1rem;
        background: var(--cw-composer-bg);
        border-top: 1px solid var(--cw-row-border);
      }
      .chat-widget__composer-row {
        display: flex;
        align-items: flex-end;
        gap: 0.5rem;
        padding: 0.35rem;
        border-radius: 16px;
        border: 1px solid var(--cw-row-border);
        background: var(--cw-row-bg);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
      }
      .chat-widget__composer-row:focus-within {
        border-color: rgba(37, 99, 235, 0.45);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        background: var(--cw-row-focus-bg);
      }
      [data-theme="dark"] .chat-widget .chat-widget__composer-row:focus-within,
      html.dark .chat-widget .chat-widget__composer-row:focus-within {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.22);
      }
      @media (prefers-color-scheme: dark) {
        html:not([data-theme="light"]) .chat-widget .chat-widget__composer-row:focus-within {
          box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.22);
        }
      }
      .chat-widget__input {
        flex: 1;
        min-width: 0;
        resize: none;
        border: 0;
        background: transparent;
        padding: 0.55rem 0.35rem 0.55rem 0.65rem;
        line-height: 1.5;
        font-size: 0.92rem;
        color: var(--cw-text);
        outline: none;
        max-height: 120px;
      }
      .chat-widget__input::placeholder {
        color: var(--cw-placeholder);
      }
      .chat-widget__submit {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border: none;
        padding: 0;
        border-radius: 12px;
        background: var(--color-primary, #2563eb);
        color: #fff;
        cursor: pointer;
      }
      .chat-widget__submit:hover:not(:disabled) {
        background: var(--color-primary-hover, #1d4ed8);
      }
      .chat-widget__submit:disabled {
        opacity: 0.55;
        cursor: wait;
      }
      .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
      }
      @media (max-width: 640px) {
        .chat-widget {
          right: 0;
          bottom: 0;
          left: 0;
        }
        .chat-widget__panel {
          position: fixed;
          width: 100%;
          height: min(85vh, 640px);
          border-radius: 20px 20px 0 0;
          bottom: 0;
        }
        .chat-widget__toggle {
          position: fixed;
          right: 1rem;
          bottom: 1rem;
        }
      }
    `;

    document.head.appendChild(style);
  }
};

ChatWidget.init();
window.ChatWidget = ChatWidget;
