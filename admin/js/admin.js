function initGeneralSettings(element, data) {
  const timezone = element.querySelector('#timezone');
  const server_time = element.querySelector('#server_time');
  const local_time = element.querySelector('#local_time');
  let intervalId = 0;

  const updateTimes = () => {
    // Update local time with selected timezone
    if (local_time && timezone?.value) {
      const options = {
        timeZone: timezone.value,
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      };
      local_time.textContent = new Date().toLocaleString('en-GB', options).replace(',', '');
    }

    // Update server time (add elapsed time to initial server time)
    if (server_time) {
      // Parse d/m/Y H:i:s format
      const parts = server_time.textContent.match(/(\d+)\/(\d+)\/(\d+)\s+(\d+):(\d+):(\d+)/);
      if (parts) {
        const seconds = parseInt(parts[6]) + 1;
        const serverStartTime = new Date(parts[3], parts[2] - 1, parts[1], parts[4], parts[5], seconds);
        const currentServerTime = new Date(serverStartTime.getTime());
        server_time.textContent = Utils.date.format(currentServerTime, 'DD/MM/YYYY HH:mm:ss', 'th-CE');
      }

    }
  };

  if (timezone && server_time && local_time) {
    updateTimes();
    intervalId = window.setInterval(updateTimes, 1000);
  }

  // Clear cache button handler
  const clearCacheBtn = element.querySelector('#clearCacheBtn');
  if (clearCacheBtn) {
    clearCacheBtn.addEventListener('click', async () => {
      if (!confirm(Now.translate('Are you sure you want to clear all cache?'))) {
        return;
      }

      clearCacheBtn.disabled = true;
      clearCacheBtn.textContent = Now.translate('Clearing Cache');

      try {
        const result = await http.post('api/index/cache/clear');

        if (result.success) {
          NotificationManager.show({
            type: 'success',
            title: Now.translate('Success'),
            message: result.message || Now.translate('Cache cleared successfully')
          });
        } else {
          throw new Error(result.message || 'Failed to clear cache');
        }
      } catch (error) {
        NotificationManager.show({
          type: 'error',
          title: Now.translate('Error'),
          message: error.message || Now.translate('Failed to clear cache')
        });
      } finally {
        clearCacheBtn.disabled = false;
        clearCacheBtn.textContent = Now.translate('Clear All Cache');
      }
    });
  }

  // Return cleanup function (optional)
  return () => {
    window.clearInterval(intervalId);
  };
}

function initEmailSettings(element, data) {
  const email_SMTPAuth = element.querySelector('#email_SMTPAuth');
  const test_email = element.querySelector('#test_email');

  const smtpAuthChange = () => {
    element.querySelector('#email_SMTPSecure').disabled = !email_SMTPAuth.checked;
    element.querySelector('#email_Username').disabled = !email_SMTPAuth.checked;
    element.querySelector('#email_Password').disabled = !email_SMTPAuth.checked;
  };
  email_SMTPAuth.addEventListener('change', smtpAuthChange);
  smtpAuthChange();

  // Test email button handler - sends to logged-in user's email
  const testEmailClick = async () => {
    // Disable button during request
    test_email.disabled = true;
    const originalText = test_email.innerHTML;
    test_email.innerHTML = '<span class="spinner"></span> ' + Now.translate('Sending...');

    try {
      const response = await ApiService.post('../api/index/settings/testEmail');

      if (response.success) {
        NotificationManager.success(response.message || Now.translate('Test sent successfully'));
      } else {
        NotificationManager.error(response.message || Now.translate('Failed to send test'));
      }
    } catch (error) {
      NotificationManager.error(Now.translate('Failed to send test'));
    } finally {
      test_email.disabled = false;
      test_email.innerHTML = originalText;
    }
  };

  if (test_email) {
    test_email.addEventListener('click', testEmailClick);
  }

  // Return cleanup function
  return () => {
    email_SMTPAuth.removeEventListener('change', smtpAuthChange);
    if (test_email) {
      test_email.removeEventListener('click', testEmailClick);
    }
  };
}

function initTelegramSettings(element, data) {
  const telegram_bot_token = element.querySelector('#telegram_bot_token');
  const telegram_chat_id = element.querySelector('#telegram_chat_id');
  const telegram_webhook_url = element.querySelector('#telegram_webhook_url');
  const telegram_webhook_secret = element.querySelector('#telegram_webhook_secret');
  const generate_telegram_webhook_secret = element.querySelector('#generate_telegram_webhook_secret');
  const test_telegram = element.querySelector('#test_telegram');
  const set_telegram_webhook = element.querySelector('#set_telegram_webhook');
  const delete_telegram_webhook = element.querySelector('#delete_telegram_webhook');

  const setBusy = (button, text) => {
    if (!button) {
      return () => {};
    }
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner"></span> ' + text;

    return () => {
      button.disabled = false;
      button.innerHTML = originalText;
    };
  };

  const generateSecretToken = (length = 40) => {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
    const size = Math.max(16, length);
    const buffer = new Uint8Array(size);

    if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
      window.crypto.getRandomValues(buffer);
    } else {
      for (let i = 0; i < size; i += 1) {
        buffer[i] = Math.floor(Math.random() * 256);
      }
    }

    let token = '';
    for (let i = 0; i < size; i += 1) {
      token += alphabet.charAt(buffer[i] % alphabet.length);
    }

    return token;
  };

  const testTelegramClick = async () => {
    if (!telegram_bot_token.value || !telegram_chat_id.value) {
      NotificationManager.error(Now.translate('Please fill in') + ' Bot token ' + Now.translate('and') + ' Chat ID');
      return;
    }

    const restoreButton = setBusy(test_telegram, Now.translate('Sending...'));

    try {
      const response = await ApiService.post('api/index/settings/testTelegram', {
        bot_token: telegram_bot_token.value,
        chat_id: telegram_chat_id.value
      });

      if (response.success) {
        NotificationManager.success(response.message || Now.translate('Test sent successfully'));
      } else {
        NotificationManager.error(response.message || Now.translate('Failed to send test'));
      }
    } catch (error) {
      NotificationManager.error(Now.translate('Failed to send test'));
    } finally {
      restoreButton();
    }
  };

  const setTelegramWebhookClick = async () => {
    if (!telegram_bot_token.value || !telegram_webhook_url.value) {
      NotificationManager.error(Now.translate('Please fill in') + ' Bot token ' + Now.translate('and') + ' Webhook URL');
      return;
    }

    const restoreButton = setBusy(set_telegram_webhook, 'Setting...');

    try {
      const response = await ApiService.post('api/index/settings/setTelegramWebhook', {
        bot_token: telegram_bot_token.value,
        webhook_url: telegram_webhook_url.value,
        secret_token: telegram_webhook_secret ? telegram_webhook_secret.value : ''
      });

      if (response.success) {
        NotificationManager.success(response.message || 'Telegram webhook configured');
      } else {
        NotificationManager.error(response.message || 'Failed to configure Telegram webhook');
      }
    } catch (error) {
      NotificationManager.error('Failed to configure Telegram webhook');
    } finally {
      restoreButton();
    }
  };

  const deleteTelegramWebhookClick = async () => {
    if (!telegram_bot_token.value) {
      NotificationManager.error(Now.translate('Please fill in') + ' Bot token');
      return;
    }

    const restoreButton = setBusy(delete_telegram_webhook, 'Removing...');

    try {
      const response = await ApiService.post('api/index/settings/deleteTelegramWebhook', {
        bot_token: telegram_bot_token.value
      });

      if (response.success) {
        NotificationManager.success(response.message || 'Telegram webhook removed');
      } else {
        NotificationManager.error(response.message || 'Failed to remove Telegram webhook');
      }
    } catch (error) {
      NotificationManager.error('Failed to remove Telegram webhook');
    } finally {
      restoreButton();
    }
  };

  const generateTelegramWebhookSecretClick = () => {
    if (!telegram_webhook_secret) {
      return;
    }

    telegram_webhook_secret.value = generateSecretToken();
    NotificationManager.success('Webhook secret generated');
  };

  if (test_telegram) {
    test_telegram.addEventListener('click', testTelegramClick);
  }
  if (generate_telegram_webhook_secret) {
    generate_telegram_webhook_secret.addEventListener('click', generateTelegramWebhookSecretClick);
  }
  if (set_telegram_webhook) {
    set_telegram_webhook.addEventListener('click', setTelegramWebhookClick);
  }
  if (delete_telegram_webhook) {
    delete_telegram_webhook.addEventListener('click', deleteTelegramWebhookClick);
  }

  // Return cleanup function
  return () => {
    if (test_telegram) {
      test_telegram.removeEventListener('click', testTelegramClick);
    }
    if (generate_telegram_webhook_secret) {
      generate_telegram_webhook_secret.removeEventListener('click', generateTelegramWebhookSecretClick);
    }
    if (set_telegram_webhook) {
      set_telegram_webhook.removeEventListener('click', setTelegramWebhookClick);
    }
    if (delete_telegram_webhook) {
      delete_telegram_webhook.removeEventListener('click', deleteTelegramWebhookClick);
    }
  };
}

function initAiChatConsole(element) {
  const ai_chat_messages = element.querySelector('#ai_chat_messages');
  const ai_chat_input = element.querySelector('#ai_chat_input');
  const ai_chat_send = element.querySelector('#ai_chat_send');
  const ai_chat_clear = element.querySelector('#ai_chat_clear');

  if (!ai_chat_messages) {
    return () => {};
  }

  const chatState = {
    loading: false,
    conversationId: '',
    handoffStatus: '',
    handoffPollTimer: null,
    capabilities: null,
    messages: [],
    messagesActionBound: false
  };

  const starterMessage = () => {
    const configured = String(chatState.capabilities?.messages?.starter_message || '').trim();
    if (configured !== '') {
      return configured;
    }
    const fallback = String(chatState.capabilities?.chat_ui?.starter_default || '').trim();
    if (fallback !== '') {
      return fallback;
    }
    return 'Hello! Type /help to see all commands — for example /search topic or /contact your message to staff.';
  };

  const applyChatUiFromCapabilities = () => {
    const ui = chatState.capabilities?.chat_ui;
    if (!ui || typeof ui !== 'object') {
      return;
    }
    const labelEl = element.querySelector('label[for="ai_chat_input"]');
    if (labelEl && ui.composer_label) {
      labelEl.textContent = ui.composer_label;
    }
    if (ai_chat_send && ui.send) {
      ai_chat_send.setAttribute('aria-label', ui.send);
      ai_chat_send.setAttribute('title', ui.send);
    }
    updateComposerPlaceholder();
  };

  const updateComposerPlaceholder = (forceHandoff = false) => {
    if (!ai_chat_input) {
      return;
    }
    const ui = chatState.capabilities?.chat_ui || {};
    const active = forceHandoff
      || (chatState.handoffStatus && chatState.handoffStatus !== 'closed');
    ai_chat_input.placeholder = active
      ? (ui.composer_placeholder_handoff || 'Type your message to staff…')
      : (ui.composer_placeholder || 'Type /help for commands — e.g. /search topic, /contact your note');
  };

  const stopHandoffPolling = () => {
    if (chatState.handoffPollTimer !== null) {
      window.clearInterval(chatState.handoffPollTimer);
      chatState.handoffPollTimer = null;
    }
  };

  const handoffNoticeText = (status) => {
    const ui = chatState.capabilities?.chat_ui || {};
    const normalized = String(status || '').trim().toLowerCase();
    if (normalized === 'accepted') {
      return ui.handoff_accepted || 'A staff member has accepted your request. Please wait for a reply.';
    }
    if (normalized === 'closed') {
      return ui.handoff_closed || 'This request was closed. You can send a new message if you need more help.';
    }
    return '';
  };

  const addHandoffNotice = (status, preferredText = '') => {
    const noticeText = String(preferredText || '').trim() || handoffNoticeText(status);
    if (noticeText === '') {
      return;
    }
    const last = chatState.messages[chatState.messages.length - 1];
    if (last && last.text === noticeText) {
      return;
    }
    addChatMessage({
      role: 'assistant',
      text: noticeText,
      notice: true,
      actions: []
    });
  };

  const pollHandoffProgress = async () => {
    if (!chatState.conversationId || !chatState.handoffStatus || chatState.handoffStatus === 'closed') {
      stopHandoffPolling();
      return;
    }
    try {
      const params = new URLSearchParams({
        conversation_id: chatState.conversationId,
        current_status: chatState.handoffStatus
      });
      const response = await ApiService.get(`../api/index/chat/handoffProgress?${params.toString()}`);
      const parsed = unwrapApiResponse(response);
      const payload = parsed.data && typeof parsed.data === 'object' ? parsed.data : {};
      if (!parsed.success || !payload.item) {
        return;
      }
      const nextStatus = String(payload.item.status || '').trim().toLowerCase();
      if (nextStatus === '' || nextStatus === chatState.handoffStatus) {
        if (nextStatus === 'closed') {
          stopHandoffPolling();
          updateComposerPlaceholder();
        }
        return;
      }
      chatState.handoffStatus = nextStatus;
      updateComposerPlaceholder();
      const statusMessage = String(payload.status_message || '').trim();
      addHandoffNotice(nextStatus, statusMessage);
      if (nextStatus === 'closed') {
        stopHandoffPolling();
      }
    } catch (err) {
      // Ignore transient polling errors.
    }
  };

  const syncHandoffPolling = () => {
    if (!chatState.conversationId || !chatState.handoffStatus || chatState.handoffStatus === 'closed') {
      stopHandoffPolling();
      updateComposerPlaceholder();
      return;
    }
    updateComposerPlaceholder();
    if (chatState.handoffPollTimer !== null) {
      return;
    }
    chatState.handoffPollTimer = window.setInterval(() => {
      pollHandoffProgress();
    }, 15000);
    pollHandoffProgress();
  };

  const renderBubbleActionsHtml = (message) => {
    if (message.role !== 'assistant' || !Array.isArray(message.actions) || message.actions.length === 0) {
      return '';
    }
    let html = '<div class="ai-chat-console__bubble-actions">';
    message.actions.forEach((action) => {
      if (!action || !action.type) {
        return;
      }
      if (action.type === 'handoff_note') {
        html += `<button type="button" class="ai-chat-console__bubble-action" data-chat-act="handoff">${Utils.string.escape(action.label || action.value || '')}</button>`;
        return;
      }
      if (action.type !== 'prompt' || !action.value) {
        return;
      }
      const behavior = String(action.behavior || 'send').toLowerCase();
      const act = behavior === 'compose' ? 'compose' : 'send';
      const enc = encodeURIComponent(String(action.value));
      html += `<button type="button" class="ai-chat-console__bubble-action" data-chat-act="${act}" data-chat-val="${enc}">${Utils.string.escape(action.label || action.value)}</button>`;
    });
    html += '</div>';
    return html;
  };

  const bindChatMessageActionClicks = () => {
    if (chatState.messagesActionBound) {
      return;
    }
    chatState.messagesActionBound = true;
    ai_chat_messages.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-chat-act]');
      if (!btn || !ai_chat_messages.contains(btn)) {
        return;
      }
      const act = btn.getAttribute('data-chat-act');
      if (act === 'handoff') {
        updateComposerPlaceholder(true);
        if (ai_chat_input) {
          ai_chat_input.focus();
        }
        return;
      }
      if (act === 'compose') {
        const raw = btn.getAttribute('data-chat-val') || '';
        let value = '';
        try {
          value = decodeURIComponent(raw);
        } catch (e) {
          value = raw;
        }
        if (ai_chat_input) {
          ai_chat_input.value = value;
          ai_chat_input.focus();
        }
        return;
      }
      if (act === 'send') {
        const raw = btn.getAttribute('data-chat-val') || '';
        let value = '';
        try {
          value = decodeURIComponent(raw);
        } catch (e) {
          value = raw;
        }
        sendChatMessage(value);
      }
    });
  };

  const formatShortLinkLabel = (url) => {
    try {
      const parsed = new URL(String(url), window.location.origin);
      const path = decodeURIComponent(parsed.pathname || '/');
      const visible = `${parsed.host}${path}${parsed.search ? '?...' : ''}`;

      return visible.length > 44 ? `${visible.slice(0, 41)}...` : visible;
    } catch (error) {
      const fallback = String(url || '');
      return fallback.length > 44 ? `${fallback.slice(0, 41)}...` : fallback;
    }
  };

  const renderChatText = (text) => {
    const source = String(text || '');
    const pattern = /https?:\/\/[^\s<>"]+/g;
    let html = '';
    let lastIndex = 0;
    let match = pattern.exec(source);

    while (match) {
      const url = match[0];
      const index = match.index;
      html += Utils.string.escape(source.slice(lastIndex, index));
      html += `<a class="ai-chat-console__inline-link" href="${Utils.string.escape(url)}" target="_blank" rel="noopener noreferrer" title="${Utils.string.escape(url)}">${Utils.string.escape(formatShortLinkLabel(url))}</a>`;
      lastIndex = index + url.length;
      match = pattern.exec(source);
    }

    html += Utils.string.escape(source.slice(lastIndex));

    return html.replace(/\n/g, '<br>');
  };

  const shouldRenderChatText = (message) => {
    if (!message || !message.text) {
      return false;
    }

    return true;
  };

  const renderChatCards = (cards, hasLeadingText = true) => {
    if (!Array.isArray(cards) || cards.length === 0) {
      return '';
    }

    const ui = chatState.capabilities?.chat_ui || {};
    const defaultTitle = ui.card_default_title || 'Result';
    const openLabel = ui.open_link || 'Open';

    return `<div class="ai-chat-console__cards${hasLeadingText ? '' : ' ai-chat-console__cards--only'}">${cards.map((card) => {
      const link = card.url
        ? `<a class="ai-chat-console__card-link" href="${Utils.string.escape(card.url)}" target="_blank" rel="noopener noreferrer">${Utils.string.escape(openLabel)}</a>`
        : '';
      const meta = [card.module, card.date].filter(Boolean).map((v) => Utils.string.escape(v)).join(' • ');
      return `
        <article class="ai-chat-console__card">
          <h4>${Utils.string.escape(card.title || defaultTitle)}</h4>
          ${card.description ? `<p>${Utils.string.escape(card.description)}</p>` : ''}
          ${meta ? `<p class="ai-chat-console__card-meta">${meta}</p>` : ''}
          ${link}
        </article>
      `;
    }).join('')}</div>`;
  };

  const renderChatMessages = () => {
    ai_chat_messages.innerHTML = chatState.messages.map((message) => {
      const showText = shouldRenderChatText(message);
      const noticeClass = message.notice ? ' ai-chat-console__message--notice' : '';

      return `
      <article class="ai-chat-console__message ai-chat-console__message--${message.role}${message.error ? ' ai-chat-console__message--error' : ''}${noticeClass}">
        <div class="ai-chat-console__bubble">
          ${showText ? `<p class="ai-chat-console__text">${renderChatText(message.text)}</p>` : ''}
          ${renderChatCards(message.cards, showText)}
          ${renderBubbleActionsHtml(message)}
        </div>
      </article>
    `;
    }).join('');

    ai_chat_messages.scrollTop = ai_chat_messages.scrollHeight;
  };

  const addChatMessage = (message) => {
    chatState.messages.push({
      role: message.role === 'user' ? 'user' : 'assistant',
      text: String(message.text || '').trim(),
      error: !!message.error,
      notice: !!message.notice,
      cards: Array.isArray(message.cards) ? message.cards : [],
      actions: Array.isArray(message.actions) ? message.actions : []
    });
    chatState.messages = chatState.messages.slice(-20);
    renderChatMessages();
  };

  const chatHistoryPayload = () => chatState.messages.slice(-12).map((message) => ({
    role: message.role,
    content: message.text
  }));

  const setChatBusy = (busy) => {
    chatState.loading = busy;
    if (ai_chat_send) {
      setBusyButton(ai_chat_send, Now.translate('Sending...'), busy);
    }
    if (ai_chat_input) {
      ai_chat_input.disabled = busy;
    }
  };

  async function sendChatMessage(rawText) {
    const text = typeof rawText === 'string' ? rawText.trim() : '';
    if (!text || chatState.loading) {
      return;
    }

    addChatMessage({role: 'user', text});
    if (ai_chat_input) {
      ai_chat_input.value = '';
    }
    setChatBusy(true);

    try {
      const response = await ApiService.post('../api/index/chat/message', {
        channel: 'web',
        message: text,
        conversation_id: chatState.conversationId,
        history: chatHistoryPayload()
      });
      const parsed = unwrapApiResponse(response);
      const payload = parsed.data && typeof parsed.data === 'object' ? parsed.data : {};

      if (!parsed.success) {
        throw new Error(parsed.message || 'Chat request failed');
      }

      chatState.conversationId = payload.conversation_id || chatState.conversationId;
      if (payload.meta && payload.meta.handoff && typeof payload.meta.handoff === 'object') {
        chatState.handoffStatus = String(payload.meta.handoff.status || 'open').trim().toLowerCase();
        syncHandoffPolling();
        updateComposerPlaceholder();
      }
      const ui = chatState.capabilities?.chat_ui || {};
      addChatMessage({
        role: 'assistant',
        text: payload.message || parsed.message || ui.error_empty_reply || 'No response',
        cards: Array.isArray(payload.cards) ? payload.cards : [],
        actions: Array.isArray(payload.actions) ? payload.actions : []
      });
    } catch (error) {
      const ui = chatState.capabilities?.chat_ui || {};
      addChatMessage({
        role: 'assistant',
        text: extractApiErrorMessage(error, ui.error_send || 'Chat request failed'),
        error: true,
        actions: []
      });
    } finally {
      setChatBusy(false);
    }
  }

  const clearChatConsole = () => {
    stopHandoffPolling();
    chatState.conversationId = '';
    chatState.handoffStatus = '';
    chatState.messages = [];
    renderChatMessages();
    addChatMessage({
      role: 'assistant',
      text: starterMessage(),
      actions: []
    });
    updateComposerPlaceholder();
  };

  const initChatSession = async () => {
    bindChatMessageActionClicks();

    try {
      const response = await ApiService.get('../api/index/chat/capabilities');
      const parsed = unwrapApiResponse(response);
      if (!parsed.success) {
        throw new Error(parsed.message || 'Unable to load chat capabilities');
      }

      chatState.capabilities = parsed.data;
      applyChatUiFromCapabilities();
    } catch (err) {
      NotificationManager.error(extractApiErrorMessage(err, Now.translate('Chat capabilities unavailable')));
    }

    clearChatConsole();
  };

  const sendChatClick = () => {
    sendChatMessage(ai_chat_input?.value || '');
  };

  const chatInputKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      sendChatMessage(ai_chat_input?.value || '');
    }
  };

  if (ai_chat_send) {
    ai_chat_send.addEventListener('click', sendChatClick);
  }
  if (ai_chat_clear) {
    ai_chat_clear.addEventListener('click', clearChatConsole);
  }
  if (ai_chat_input) {
    ai_chat_input.addEventListener('keydown', chatInputKeydown);
  }

  initChatSession();

  return () => {
    if (ai_chat_send) {
      ai_chat_send.removeEventListener('click', sendChatClick);
    }
    if (ai_chat_clear) {
      ai_chat_clear.removeEventListener('click', clearChatConsole);
    }
    if (ai_chat_input) {
      ai_chat_input.removeEventListener('keydown', chatInputKeydown);
    }
    stopHandoffPolling();
  };
}

function initAiSettings(element, data) {
  const payload = data && typeof data === 'object' ? data : {};
  const state = payload.data && typeof payload.data === 'object' && !Array.isArray(payload.data)
    ? payload.data
    : payload.state && typeof payload.state === 'object'
      ? payload.state
      : payload;
  const ai_provider = element.querySelector('#ai_provider');
  const ai_edit_provider = element.querySelector('#ai_edit_provider');
  const ai_model = element.querySelector('#ai_model');
  const ai_custom_model_field = element.querySelector('#ai_custom_model_field');
  const ai_custom_model = element.querySelector('#ai_custom_model');
  const ai_api_key = element.querySelector('#ai_api_key');
  const ai_api_url = element.querySelector('#ai_api_url');
  const ai_max_tokens = element.querySelector('#ai_max_tokens');
  const ai_temperature = element.querySelector('#ai_temperature');
  const ai_deepseek_fields = element.querySelector('#ai_deepseek_fields');
  const ai_thinking_enabled = element.querySelector('#ai_thinking_enabled');
  const ai_reasoning_effort = element.querySelector('#ai_reasoning_effort');
  const test_ai = element.querySelector('#test_ai');
  const providerDefaults = state && typeof state.ai_provider_defaults === 'object' ? state.ai_provider_defaults : {};
  const providerState = state && typeof state.ai_connections === 'object'
    ? JSON.parse(JSON.stringify(state.ai_connections))
    : {};
  const initialProvider = ai_provider?.value || state?.ai_provider || 'openai';
  if (ai_edit_provider && ai_edit_provider.value !== initialProvider) {
    ai_edit_provider.value = initialProvider;
  }
  let currentProvider = initialProvider;

  const getProviderMeta = (provider) => provider && providerDefaults[provider] ? providerDefaults[provider] : {};

  const getProviderModels = (provider) => {
    const meta = getProviderMeta(provider);
    return Array.isArray(meta.models) ? meta.models : [];
  };

  const defaultValue = (value, fallback) => (value !== undefined && value !== null && value !== '' ? String(value) : String(fallback));

  const rememberProviderState = (provider) => {
    if (!provider) {
      return;
    }
    const meta = getProviderMeta(provider);
    let apiUrl = ai_api_url ? ai_api_url.value.trim() : '';
    if (apiUrl === (meta.default_api_url || '')) {
      apiUrl = '';
    }
    providerState[provider] = {
      model: ai_model && ai_model.value !== '__custom__' ? ai_model.value : '',
      model_option: ai_model ? ai_model.value : '',
      custom_model: ai_custom_model ? ai_custom_model.value.trim() : '',
      use_custom_model: ai_model && ai_model.value === '__custom__' ? 1 : 0,
      api_key: ai_api_key ? ai_api_key.value : '',
      api_url: apiUrl,
      max_tokens: ai_max_tokens ? ai_max_tokens.value : '',
      temperature: ai_temperature ? ai_temperature.value : '',
      thinking_enabled: ai_thinking_enabled && ai_thinking_enabled.checked ? 1 : 0,
      reasoning_effort: ai_reasoning_effort ? ai_reasoning_effort.value : 'high'
    };
  };

  const normalizeProviderState = (provider) => {
    const meta = getProviderMeta(provider);
    const models = getProviderModels(provider);
    const draft = providerState[provider] || {};
    let modelOption = draft.model_option || '';
    let customModel = draft.custom_model || '';

    if (!modelOption) {
      if (draft.use_custom_model && customModel) {
        modelOption = '__custom__';
      } else if (draft.model && models.includes(draft.model)) {
        modelOption = draft.model;
      } else if (draft.model) {
        modelOption = '__custom__';
        customModel = draft.model;
      } else {
        modelOption = meta.default_model || models[0] || '__custom__';
      }
    }

    if (modelOption !== '__custom__' && modelOption && !models.includes(modelOption)) {
      customModel = modelOption;
      modelOption = '__custom__';
    }

    if (modelOption === '__custom__' && !customModel && draft.model && !models.includes(draft.model)) {
      customModel = draft.model;
    }

    return {
      api_key: draft.api_key || '',
      api_url: draft.api_url || meta.default_api_url || '',
      model_option: modelOption,
      custom_model: customModel,
      max_tokens: defaultValue(draft.max_tokens, state?.ai_max_tokens ?? 1024),
      temperature: defaultValue(draft.temperature, state?.ai_temperature ?? 0.7),
      thinking_enabled: draft.thinking_enabled !== undefined && draft.thinking_enabled !== ''
        ? Number(draft.thinking_enabled) === 1
        : Number(state?.ai_thinking_enabled ?? 0) === 1,
      reasoning_effort: draft.reasoning_effort || state?.ai_reasoning_effort || meta.default_reasoning_effort || 'high'
    };
  };

  const renderModelOptions = (provider, selectedValue) => {
    if (!ai_model) {
      return;
    }

    const meta = getProviderMeta(provider);
    const models = getProviderModels(provider);
    const options = models.map((model) => ({
      value: model,
      text: model === meta.default_model ? `${model} (${Now.translate('Default')})` : model
    }));
    options.push({value: '__custom__', text: Now.translate('Custom')});

    SelectElementFactory.updateOptions(ai_model, options, false);

    const fallback = meta.default_model || models[0] || '__custom__';
    ai_model.value = selectedValue && (selectedValue === '__custom__' || models.includes(selectedValue)) ? selectedValue : fallback;
  };

  const renderModelGuidance = (provider) => {
    const meta = getProviderMeta(provider);
    if (ai_api_key) {
      ai_api_key.placeholder = meta.local ? Now.translate('Not required for local models') : '';
    }
    if (ai_api_url) {
      ai_api_url.placeholder = meta.default_api_url || '';
    }
    if (ai_custom_model) {
      ai_custom_model.placeholder = meta.default_model || 'Enter exact model ID';
    }
  };

  const toggleCustomModel = () => {
    const showCustom = ai_model && ai_model.value === '__custom__';
    if (ai_custom_model_field) {
      ai_custom_model_field.classList.toggle('hidden', !showCustom);
    }
    if (ai_custom_model) {
      ai_custom_model.disabled = !showCustom;
    }
  };

  const toggleDeepSeekFields = (provider) => {
    const showDeepSeek = provider === 'deepseek';
    if (ai_deepseek_fields) {
      ai_deepseek_fields.classList.toggle('hidden', !showDeepSeek);
    }
    if (ai_thinking_enabled) {
      ai_thinking_enabled.disabled = !showDeepSeek;
    }
    if (ai_reasoning_effort) {
      ai_reasoning_effort.disabled = !showDeepSeek;
    }
  };

  const applyProviderState = (provider) => {
    const current = normalizeProviderState(provider);

    renderModelOptions(provider, current.model_option);
    if (ai_api_key) {
      ai_api_key.value = current.api_key;
    }
    if (ai_api_url) {
      ai_api_url.value = current.api_url;
    }
    if (ai_custom_model) {
      ai_custom_model.value = current.custom_model;
    }
    if (ai_max_tokens) {
      ai_max_tokens.value = current.max_tokens;
    }
    if (ai_temperature) {
      ai_temperature.value = current.temperature;
    }
    if (ai_thinking_enabled) {
      ai_thinking_enabled.checked = !!current.thinking_enabled;
    }
    if (ai_reasoning_effort) {
      ai_reasoning_effort.value = current.reasoning_effort === 'max' ? 'max' : 'high';
    }

    renderModelGuidance(provider);
    toggleCustomModel();
    toggleDeepSeekFields(provider);
  };

  const providerChange = () => {
    rememberProviderState(currentProvider);
    currentProvider = ai_edit_provider?.value || 'openai';
    applyProviderState(currentProvider);
  };

  applyProviderState(currentProvider);

  const modelChange = () => {
    toggleCustomModel();
  };

  if (ai_edit_provider) {
    ai_edit_provider.addEventListener('change', providerChange);
  }
  if (ai_model) {
    ai_model.addEventListener('change', modelChange);
  }

  const testAiClick = async () => {
    if (!test_ai) {
      return;
    }

    const provider = ai_edit_provider?.value || currentProvider || '';
    const model = ai_model?.value || '';
    const customModel = ai_custom_model?.value.trim() || '';

    if (model === '__custom__' && !customModel) {
      NotificationManager.error('{LNG_Please fill in} {LNG_Custom Model}');
      return;
    }

    rememberProviderState(currentProvider);

    const api_key = ai_api_key?.value || '';
    const api_url = ai_api_url?.value || '';
    const max_tokens = ai_max_tokens?.value || '';
    const temperature = ai_temperature?.value || '';

    test_ai.disabled = true;
    const originalText = test_ai.innerHTML;
    test_ai.innerHTML = '<span class="spinner"></span> ' + Now.translate('{LNG_Testing}...');

    try {
      const response = await ApiService.post('../api/index/settings/testAi', {
        ai_provider: ai_provider?.value || provider,
        ai_edit_provider: provider,
        ai_api_key: api_key,
        ai_api_url: api_url,
        ai_model: model,
        ai_custom_model: customModel,
        ai_max_tokens: max_tokens,
        ai_temperature: temperature,
        ai_thinking_enabled: ai_thinking_enabled && ai_thinking_enabled.checked ? 1 : 0,
        ai_reasoning_effort: ai_reasoning_effort?.value || 'high'
      });

      if (response.success) {
        NotificationManager.success(response.message || Now.translate('AI connection test successful'));
      } else {
        NotificationManager.error(response.message || Now.translate('AI connection test failed'));
      }
    } catch (error) {
      NotificationManager.error(Now.translate('AI connection test failed'));
    } finally {
      test_ai.disabled = false;
      test_ai.innerHTML = originalText;
    }
  };

  if (test_ai) {
    test_ai.addEventListener('click', testAiClick);
  }

  return () => {
    if (ai_edit_provider) {
      ai_edit_provider.removeEventListener('change', providerChange);
    }
    if (ai_model) {
      ai_model.removeEventListener('change', modelChange);
    }
    if (test_ai) {
      test_ai.removeEventListener('click', testAiClick);
    }
  };
}

function initAiChatPage(element, data) {
  return initAiChatConsole(element);
}
function initAiOcrPage(element, data) {
  const fileInput = element.querySelector('#ai_ocr_file');
  const documentType = element.querySelector('#ai_ocr_document_type');
  const visionModel = element.querySelector('#ai_ocr_vision_model');
  const submitButton = element.querySelector('#ai_ocr_submit');
  const rawTextArea = element.querySelector('#ai_ocr_raw_text');
  const structuredArea = element.querySelector('#ai_ocr_structured');
  const metaArea = element.querySelector('#ai_ocr_meta');

  if (!fileInput || !submitButton || !rawTextArea || !structuredArea || !metaArea) {
    return () => {};
  }

  const parseClick = async () => {
    const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
    if (!file) {
      NotificationManager.error(Now.translate('Please choose a file'));
      return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('document_type', (documentType?.value || 'auto').trim());
    const modelText = (visionModel?.value || '').trim();
    if (modelText !== '') {
      formData.append('vision_model', modelText);
    }

    setBusyButton(submitButton, Now.translate('Processing...'), true);
    try {
      const response = await fetch('../api/index/ocr/parse', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json'
        }
      });

      let payload = null;
      try {
        payload = await response.json();
      } catch (jsonError) {
        payload = null;
      }

      if (!response.ok || !payload || payload.success !== true) {
        const fallback = Now.translate('OCR request failed');
        const message = firstMessage(payload?.message, payload?.error, fallback);
        throw new Error(message || fallback);
      }

      const result = payload.data && typeof payload.data === 'object' ? payload.data : {};
      rawTextArea.value = String(result.raw_text || '');
      structuredArea.value = JSON.stringify(result.structured || {}, null, 2);
      metaArea.value = JSON.stringify({
        engine: result.engine || '',
        provider: result.provider || '',
        structured_engine: result.structured_engine || '',
        ...(result.meta || {})
      }, null, 2);

      NotificationManager.success(payload.message || Now.translate('OCR completed'));
    } catch (error) {
      NotificationManager.error(extractApiErrorMessage(error, Now.translate('OCR failed')));
    } finally {
      setBusyButton(submitButton, '', false);
    }
  };

  submitButton.addEventListener('click', parseClick);

  return () => {
    submitButton.removeEventListener('click', parseClick);
  };
}

function firstMessage(...candidates) {
  for (const candidate of candidates) {
    if (typeof candidate === 'string' && candidate.trim() !== '') {
      return candidate.trim();
    }
  }
  return '';
}

function setBusyButton(button, busyText, busy) {
  if (!button) {
    return;
  }
  if (busy) {
    button.dataset.originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner"></span> ' + busyText;
  } else {
    button.disabled = false;
    button.innerHTML = button.dataset.originalText || button.innerHTML;
  }
}

function unwrapApiResponse(response) {
  const raw = response && typeof response === 'object' ? response.data : null;
  const payload = raw && typeof raw === 'object' && !Array.isArray(raw) ? raw : null;

  let dataObject = raw;
  if (payload && payload.data !== undefined) {
    dataObject = payload.data;
  }

  const payloadSuccess = payload && typeof payload.success === 'boolean' ? payload.success : null;
  const responseSuccess = Boolean(response?.success);

  return {
    success: payloadSuccess === null ? responseSuccess : (responseSuccess && payloadSuccess),
    message: firstMessage(
      payload?.message,
      payload?.error,
      typeof raw === 'string' ? raw : '',
      response?.statusText,
      response?.message
    ),
    data: dataObject,
    raw
  };
}

function extractApiErrorMessage(error, fallback) {
  const responseData = error?.response?.data;
  const localData = error?.data;

  const message = firstMessage(
    responseData?.message,
    responseData?.error,
    typeof responseData === 'string' ? responseData : '',
    localData?.message,
    localData?.error,
    typeof localData === 'string' ? localData : '',
    error?.response?.message,
    error?.response?.statusText,
    error?.message,
    fallback
  );

  return message || fallback;
}

function initThemeSettingsAssistant(element, data) {
  const promptInput = element.querySelector('#theme_prompt');
  const suggestButton = element.querySelector('#ai_suggest_theme');
  const colorFields = [
    'ColorBackground',
    'ColorText',
    'ColorPrimary',
    'ColorInfo',
    'HeaderColorBackground',
    'HeaderColorText',
    'SidebarColorBackground',
    'SidebarColorText',
    'MenuHighlightBg',
    'MenuHighlightText',
    'FooterColorBackground',
    'FooterColorText'
  ];

  if (!suggestButton) {
    return () => {};
  }

  const applyColorsToForm = (colors) => {
    colorFields.forEach((field) => {
      const input = element.querySelector(`#${field}`);
      const value = colors && typeof colors === 'object' ? colors[field] : '';

      if (input && value != null) {
        const nextValue = typeof value === 'string' ? value.trim() : value;
        input.value = nextValue;

        if (nextValue === '') {
          input.removeAttribute('value');
        } else {
          input.setAttribute('value', nextValue);
        }

        input.dispatchEvent(new Event('input', {bubbles: true}));
        input.dispatchEvent(new Event('change', {bubbles: true}));
      }
    });
  };

  const suggestThemeClick = async () => {
    const prompt = promptInput?.value?.trim() || '';
    if (prompt === '') {
      NotificationManager.error(Now.translate('Please fill in') + ' ' + Now.translate('Design Brief'));
      return;
    }

    setBusyButton(suggestButton, Now.translate('{LNG_Generating}…'), true);
    try {
      const response = await ApiService.post('../api/index/settings/suggestTheme', {
        theme_prompt: prompt
      });
      const result = unwrapApiResponse(response);

      if (!result.success) {
        const errMsg = result.message || Now.translate('Failed to suggest theme');
        NotificationManager.error(errMsg);
        return;
      }

      const suggestion = result?.data?.data?.suggestion || result?.data?.suggestion || null;
      if (!suggestion || typeof suggestion !== 'object') {
        const errMsg = Now.translate('Theme suggestion response is missing suggestion data');
        NotificationManager.error(errMsg);
        return;
      }

      applyColorsToForm(suggestion.colors || {});

      const successMessage = result.message || Now.translate('Theme suggestion generated. Review the colors and click Save.');
      const suggestionSummary = [suggestion.name, suggestion.description].filter((value) => typeof value === 'string' && value.trim() !== '').join(' - ');

      NotificationManager.success(suggestionSummary ? `${successMessage} ${suggestionSummary}` : successMessage);
    } catch (error) {
      NotificationManager.error(extractApiErrorMessage(error, Now.translate('Failed to suggest theme')));
    } finally {
      setBusyButton(suggestButton, '', false);
    }
  };

  suggestButton.addEventListener('click', suggestThemeClick);

  return () => {
    suggestButton.removeEventListener('click', suggestThemeClick);
  };
}

/**
 * Format with options status
 */
function formatTableOptionStatus(cell, rawValue, rowData, attributes) {
  const opts = attributes.lookupOptions || attributes.tableDataOptions || attributes.tableFilterOptions;

  // Normalizer: build a map value->text
  const makeMap = (options) => {
    if (!options) return new Map();
    if (Array.isArray(options)) {
      // [{value,text}, ...]
      return new Map(options.map(o => [String(o.value), o.text]));
    }
    // object map {val: label, ...}
    return new Map(Object.entries(options).map(([k, v]) => [String(k), v]));
  };

  const map = makeMap(opts);

  const key = rawValue === null || rawValue === undefined ? '' : String(rawValue);
  const label = map.has(key) ? map.get(key) : (rawValue && rawValue.text) ? rawValue.text : key;
  const index = map.has(key) ? Array.from(map.keys()).indexOf(key) : -1;


  cell.innerHTML = `<span class="status${index}" data-i18n>${label}</span>`;
}

function formatStarStatus(cell, rawValue, rowData, attributes) {
  if (rawValue === 'active' || parseInt(rawValue) === 1) {
    cell.innerHTML = '<span class="icon-star2 color-primary"></span>';
  } else {
    cell.innerHTML = '<span class="icon-star0 color-silver"></span>';
  }
}

function formatActiveStatus(cell, rawValue, rowData, attributes) {
  if (rawValue === 'active' || parseInt(rawValue) === 1) {
    cell.innerHTML = '<span class="icon-valid color-red" title="' + Now.translate('Active') + '"></span>';
  } else {
    cell.innerHTML = '<span class="icon-invalid color-silver" title="' + Now.translate('Inactive') + '"></span>';
  }
}

function formatLink(cell, rawValue, rowData, attributes) {
  if (!rawValue) {
    cell.innerHTML = '-';
    return;
  }

  const value = String(rawValue).trim();

  // Simple recognizers
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRegex = /^\+?[0-9()\s\-./]{6,}$/;
  const urlProtocolRegex = /^https?:\/\//i;

  const makeLink = (href, text, iconClass) => {
    const a = document.createElement('a');
    a.href = href;
    // Open http(s) links in new tab, others (mailto/tel) in same
    if (/^https?:\/\//i.test(href)) {
      a.target = '_blank';
      a.rel = 'noopener';
    }
    if (iconClass) a.className = iconClass;
    a.textContent = text;
    cell.innerHTML = '';
    cell.appendChild(a);
  };

  if (/^mailto:/i.test(value)) {
    makeLink(value, value.replace(/^mailto:/i, ''), 'icon-mail');
    return;
  }

  if (/^tel:/i.test(value)) {
    makeLink(value, value.replace(/^tel:/i, ''), 'icon-phone');
    return;
  }

  if (emailRegex.test(value)) {
    makeLink('mailto:' + value, value, 'icon-mail');
    return;
  }

  if (phoneRegex.test(value)) {
    // Normalize phone for href (keep leading + if present)
    const telHref = 'tel:' + value.replace(/[^\d+]/g, '');
    makeLink(telHref, value, 'icon-phone');
    return;
  }

  // Fallback: treat as URL
  let href = value;
  if (!urlProtocolRegex.test(href)) href = 'http://' + href;
  const displayUrl = href.replace(/^https?:\/\//, '').replace(/\/$/, '');
  makeLink(href, displayUrl, 'icon-world');
}

function formatImage(cell, rawValue, rowData, attributes) {
  cell.innerHTML = '';
  if (!rawValue) return;

  // Build the thumbnail via DOM + style API instead of string concatenation.
  // Reject dangerous schemes and any character that could break out of the
  // CSS url() context (quotes, parens, angle brackets, whitespace, backslash).
  const url = String(rawValue).trim();
  const isDangerousScheme = /^(?:javascript|data|vbscript|file|about):/i.test(url);
  const hasUnsafeChars = /["'()\\\s<>]/.test(url);
  if (isDangerousScheme || hasUnsafeChars) return;

  const thumb = document.createElement('div');
  thumb.className = 'thumbnail';
  thumb.style.backgroundImage = `url("${url}")`;
  cell.appendChild(thumb);
}

/**
 * Attach a "copy to language" handler to the #copy_menu button.
 * @param {HTMLElement} element - Form root element
 * @param {string} endpoint - API endpoint to POST to
 * @param {string} saveFirstMsg - Translation key shown when id is missing
 * @returns {Function} Cleanup function that removes the listener
 */
function makeCopyButton(element, endpoint, saveFirstMsg) {
  const copyBtn = element.querySelector('#copy_menu');
  const languageSelect = element.querySelector('#language');
  if (!copyBtn || !languageSelect) return () => {};

  const handler = async () => {
    const lang = languageSelect.value;
    if (!lang) {
      NotificationManager.warning(Now.translate('Please select a language to copy to'));
      return;
    }
    const id = element.querySelector('[name="id"]')?.value;
    if (!id) {
      NotificationManager.warning(Now.translate(saveFirstMsg));
      return;
    }
    try {
      const response = await ApiService.post(endpoint, {id, language: lang});
      if (response.success) {
        NotificationManager.success(response.data.message || Now.translate('Copied successfully'));
      } else {
        NotificationManager.error(response.data.message || Now.translate('Copy failed'));
      }
    } catch {
      NotificationManager.error(Now.translate('Copy failed'));
    }
  };

  copyBtn.addEventListener('click', handler);
  return () => copyBtn.removeEventListener('click', handler);
}

/**
 * Attach a language-change handler that reloads the form with the selected language,
 * and a copy-to-language handler. Used by forms where content is per-language file
 * (intro, maintenance) rather than per-record database entries.
 *
 * @param {HTMLElement} element - Form root element
 *
 * @returns {Function} Cleanup function
 */
function makeLanguageReloadForm(element) {
  const languageSelect = element.querySelector('#language');
  if (!languageSelect) return () => {};

  // Reload form data for the chosen language
  const onLanguageChange = async () => {
    const lang = languageSelect.value;
    if (!lang) return;

    const url = new URL(window.location.href);
    url.searchParams.set('language', lang);
    window.location.href = url.toString();
  };

  languageSelect.addEventListener('change', onLanguageChange);

  return () => {
    languageSelect.removeEventListener('change', onLanguageChange);
  };
}

function initMaintenanceForm(element, data) {
  return makeLanguageReloadForm(element);
}

function initIntroForm(element, data) {
  return makeLanguageReloadForm(element);
}

function initModuleForm(element, data) {
  return makeCopyButton(element, '../api/index/adminmodule/copy', 'Please save the module first');
}

function initPageForm(element, data) {
  return makeCopyButton(element, '../api/index/adminpage/copy', 'Please save the page first');
}

function initMailTemplateForm(element, data) {
  return makeCopyButton(element, '../api/index/mailtemplate/copy', 'Please save the template first');
}

/**
 * Menu form: show/hide action-dependent fields and handle copy button
 */
function initMenuForm(element, data) {
  const actionSelect = element.querySelector('#menu_action');
  const fieldIndexId = element.querySelector('#field_index_id');
  const fieldMenuUrl = element.querySelector('#field_menu_url');
  const fieldMenuTarget = element.querySelector('#field_menu_target');
  const menuParent = element.querySelector('#parent');
  const menuType = element.querySelector('#type');
  const menuOrder = element.querySelector('#menu_order');
  const menuId = parseInt(element.querySelector('[name="id"]')?.value || '0');

  let _updateMenuParentTimer = null;
  const updateMenuParent = () => {
    clearTimeout(_updateMenuParentTimer);
    _updateMenuParentTimer = setTimeout(async () => {
      if (parseInt(menuType.value) === 0) {
        SelectElementFactory.clearOptions(menuOrder, false);
        return;
      }

      try {
        const response = await ApiService.post('../api/index/adminmenu/menus', {
          parent: menuParent.value,
        });

        if (!response.success) {
          NotificationManager.error(response.message || Now.translate('Failed to load menu'));
          return;
        }

        // Convert plain object {id: text} → [{value, text}] preserving API insertion order
        // (cannot pass plain object directly - SelectElementFactory sorts numeric keys)
        const data = response.data.data || {};

        // Find the item just before menuId in the ordered list
        let prevId = null; // null = menuId is first → "First position"
        let foundMenuId = false;
        for (const [value] of Object.entries(data)) {
          const id = value.replace(/^O_/, '');
          if (String(id) === String(menuId)) {
            foundMenuId = true;
            break;
          }
          prevId = id;
        }

        // Build options, skip the menuId item itself
        const options = [];
        Object.entries(data).forEach(([value, text]) => {
          const id = value.replace(/^O_/, '');
          if (String(id) !== String(menuId)) {
            options.push({value: id, text});
          } else {
            options.push({value: id, text, disabled: true}); // Keep the current item in the list but disabled
          }
        });

        // Populate select (updateOptions auto-restores old value, so set correct value after)
        SelectElementFactory.updateOptions(menuOrder, options, false);

        // Select the item that was before menuId in the list
        if (foundMenuId) {
          menuOrder.value = prevId !== null ? prevId : '0';
        }

        // updateOptions saves current value before clearing and auto-restores if still exists
        SelectElementFactory.updateOptions(menuOrder, options, false);

      } catch (error) {
        NotificationManager.error(Now.translate('Failed to load menu'));
      }
    }, 150);
  };

  const updateActionFields = () => {
    const val = parseInt(actionSelect?.value ?? '-1', 10);
    if (fieldIndexId) fieldIndexId.classList.toggle('hidden', val !== 1);
    if (fieldMenuUrl) fieldMenuUrl.classList.toggle('hidden', val !== 2);
    if (fieldMenuTarget) fieldMenuTarget.classList.toggle('hidden', val !== 1 && val !== 2);
  };


  actionSelect.addEventListener('change', updateActionFields);
  updateActionFields();


  menuParent.addEventListener('change', updateMenuParent);
  menuType.addEventListener('change', updateMenuParent);
  updateMenuParent();

  const cleanupCopy = makeCopyButton(element, '../api/index/adminmenu/copy', 'Please save the menu item first');

  return () => {
    if (actionSelect) actionSelect.removeEventListener('change', updateActionFields);
    if (menuParent) menuParent.removeEventListener('change', updateMenuParent);
    if (menuType) menuType.removeEventListener('change', updateMenuParent);
    cleanupCopy();
    clearTimeout(_updateMenuParentTimer);
  };
}

/**
 * Theme Gallery - data-on-load callback
 * Renders color dots and wires up activate buttons.
 *
 * @param {HTMLElement} element  The API component container
 * @param {Object}      context  Template context (context.state = API data)
 */
function initThemeGallery(element, context) {
  const themes = context?.state?.themes || [];

  // Build a lookup of theme colors keyed by name
  const colorsMap = {};
  themes.forEach(t => {
    colorsMap[t.name] = t.colors || {};
  });

  // Render color dots inside each .theme-card__color-dots placeholder
  element.querySelectorAll('.theme-card__color-dots').forEach(dotsEl => {
    const themeName = dotsEl.getAttribute('data-colors');
    const colors = colorsMap[themeName];
    if (!colors) return;

    const primary = colors.primary;
    const accent = colors.accent;
    dotsEl.innerHTML = '';

    if (primary) {
      const dot = document.createElement('span');
      dot.className = 'theme-card__dot';
      dot.style.background = primary;
      dot.title = 'Primary';
      dotsEl.appendChild(dot);
    }
    if (accent && accent !== primary) {
      const dot = document.createElement('span');
      dot.className = 'theme-card__dot';
      dot.style.background = accent;
      dot.title = 'Accent';
      dotsEl.appendChild(dot);
    }
  });

  // Style color-swatch placeholders (for themes without screenshots)
  element.querySelectorAll('.theme-card').forEach(card => {
    const themeName = card.getAttribute('data-theme');
    const colors = colorsMap[themeName];
    if (!colors) return;

    const placeholder = card.querySelector('.theme-card__placeholder');
    if (placeholder) {
      placeholder.style.background = colors.background || '#f8fafc';
      const header = placeholder.querySelector('.theme-card__placeholder-header');
      if (header) header.style.background = colors.primary || '#6366f1';

      const bars = placeholder.querySelectorAll('.theme-card__placeholder-bar');
      if (bars[0]) {bars[0].style.background = colors.primary || '#6366f1'; bars[0].style.opacity = '0.5'; bars[0].style.width = '60%';}
      if (bars[1]) {bars[1].style.background = '#e2e8f0'; bars[1].style.width = '80%';}

      const dot = placeholder.querySelector('.theme-card__placeholder-dot');
      if (dot) dot.style.background = colors.accent || '#06b6d4';
    }
  });

  // Wire up activate buttons
  element.querySelectorAll('.theme-card__actions button[data-theme]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const themeName = btn.dataset.theme;
      if (!themeName) return;

      btn.disabled = true;
      const originalText = btn.textContent;
      btn.textContent = Now.translate('{LNG_Activating}...');

      try {
        await httpAction.post('../api/index/themes/select', {theme: themeName});
      } catch (error) {
        NotificationManager.show({
          type: 'error',
          title: Now.translate('Error'),
          message: error.message || Now.translate('Could not activate theme')
        });
        btn.disabled = false;
        btn.textContent = originalText;
      }
    });
  });
}

function formatMenuArrow(cell, rawValue, rowData, attributes) {
  cell.innerHTML = '';
  if (attributes.field === 'move_left' && rawValue) {
    const button = document.createElement('a');
    button.className = 'icon-move_left';
    button.dataset.action = 'move_left';
    button.dataset.value = rawValue;
    button.title = Now.translate('Move submenu to the top');
    cell.appendChild(button);
  } else if (attributes.field === 'move_right' && rawValue) {
    const button = document.createElement('a');
    button.className = 'icon-move_right';
    button.dataset.action = 'move_right';
    button.dataset.value = rawValue;
    button.title = Now.translate('Move menu to submenu of the top');
    cell.appendChild(button);
  }
}

function initAiTheme(element) {
  let _generated = null;

  const btnGenerate = element.querySelector('#btn-generate');
  const btnSave = element.querySelector('#btn-save-theme');
  const genInfoEl = element.querySelector('#aitheme-gen-info');
  const previewSection = element.querySelector('#aitheme-preview-section');
  const palette = element.querySelector('#aitheme-palette');
  const cssPreview = element.querySelector('#aitheme-css-preview');
  const layoutPreview = element.querySelector('#aitheme-layout-preview');
  const layoutSummary = element.querySelector('#aitheme-layout-summary');
  const layoutBlocks = element.querySelector('#aitheme-layout-blocks');
  const previewName = element.querySelector('#preview-name');
  const previewSlug = element.querySelector('#preview-slug');
  const previewDesc = element.querySelector('#preview-description');
  const promptInput = element.querySelector('#ai_theme_prompt');

  const normalizeGeneratedThemePayload = (input) => {
    const candidates = [
      input,
      input?.data,
      input?.raw?.data,
      input?.raw
    ];

    for (const candidate of candidates) {
      if (!candidate || typeof candidate !== 'object' || Array.isArray(candidate)) {
        continue;
      }

      const themeJson = candidate.theme_json;
      if (!themeJson || typeof themeJson !== 'object' || Array.isArray(themeJson)) {
        continue;
      }

      return {
        ...candidate,
        theme_json: themeJson,
        slug: typeof candidate.slug === 'string' ? candidate.slug : '',
        base_theme: typeof candidate.base_theme === 'string' ? candidate.base_theme : '',
        base_theme_label: typeof candidate.base_theme_label === 'string' ? candidate.base_theme_label : '',
        css: typeof candidate.css === 'string' ? candidate.css : '',
        home_html: typeof candidate.home_html === 'string' ? candidate.home_html : '',
        css_mode: typeof candidate.css_mode === 'string' ? candidate.css_mode : '',
        model: typeof candidate.model === 'string' ? candidate.model : '',
        tokens: Number.isFinite(candidate.tokens) ? candidate.tokens : (parseInt(candidate.tokens, 10) || 0)
      };
    }

    return null;
  };

  // ── Palette renderer ────────────────────────────────────────────────────
  const renderPalette = (colors) => {
    palette.innerHTML = '';
    if (!colors || typeof colors !== 'object' || Array.isArray(colors)) {
      return;
    }
    const labels = {
      primary: 'Primary', primary_hover: 'Primary Hover',
      secondary: 'Secondary', accent: 'Accent',
      background: 'Background', surface: 'Surface',
      text: 'Text', text_secondary: 'Text 2nd', border: 'Border'
    };
    Object.keys(colors).forEach((key) => {
      const hex = colors[key];
      const swatch = document.createElement('div');
      swatch.className = 'aitheme-swatch';
      swatch.innerHTML =
        `<div class="aitheme-swatch__color" style="background:${hex}"></div>` +
        `<span class="aitheme-swatch__label">${labels[key] || key}</span>` +
        `<span class="aitheme-swatch__hex">${hex}</span>`;
      palette.appendChild(swatch);
    });
  };

  // ── Layout preview: turn generated home.html into a readable block outline ──
  const renderLayoutPreview = (homeHtml) => {
    if (!layoutPreview) return;
    if (!homeHtml || typeof homeHtml !== 'string' || !homeHtml.trim()) {
      layoutPreview.classList.add('hidden');
      return;
    }

    try {
      const doc = new DOMParser().parseFromString(homeHtml, 'text/html');

      // Detect column layout from the wrapper class used inside .homepage
      const layoutLabels = {
        'three-columns': Now.translate('3 columns (left sidebar + content + right sidebar)'),
        'sidebar-left': Now.translate('2 columns (sidebar left + content)'),
        'sidebar-right': Now.translate('2 columns (content + sidebar right)')
      };
      let layoutText = Now.translate('Single column (no sidebar)');
      for (const cls of Object.keys(layoutLabels)) {
        if (doc.querySelector('.' + cls)) { layoutText = layoutLabels[cls]; break; }
      }

      // Friendly names for the standard containers
      const containerLabels = {
        content: Now.translate('Main content'),
        sidebar: Now.translate('Sidebar'),
        'sidebar-left': Now.translate('Left sidebar'),
        'sidebar-right': Now.translate('Right sidebar')
      };

      const containers = Array.from(doc.querySelectorAll('[data-editor-container]'));
      let totalBlocks = 0;
      layoutBlocks.innerHTML = '';

      containers.forEach((container) => {
        const name = container.getAttribute('data-editor-container') || '';
        // Top-level blocks only (skip blocks nested inside another block)
        const blocks = Array.from(container.querySelectorAll('[data-block-type]'))
          .filter((b) => {
            const p = b.parentElement;
            return !p || !p.closest('[data-block-type]');
          });
        totalBlocks += blocks.length;

        const col = document.createElement('div');
        col.className = 'aitheme-layout-col';
        const head = document.createElement('div');
        head.className = 'aitheme-layout-col__head';
        head.textContent = containerLabels[name] || name;
        col.appendChild(head);

        const ul = document.createElement('ul');
        blocks.forEach((b) => {
          const li = document.createElement('li');
          const label = b.getAttribute('data-editor-label') || '';
          const type = b.getAttribute('data-block-type') || '';
          li.textContent = label ? `${label}  ·  ${type}` : type;
          ul.appendChild(li);
        });
        col.appendChild(ul);
        layoutBlocks.appendChild(col);
      });

      layoutSummary.textContent = `${layoutText}  ·  ${totalBlocks} ${Now.translate('blocks')}`;
      layoutPreview.classList.remove('hidden');
    } catch (e) {
      layoutPreview.classList.add('hidden');
    }
  };

  // ── Show preview section after generation ───────────────────────────────
  const showPreview = (data) => {
    const generated = normalizeGeneratedThemePayload(data);
    if (!generated) {
      throw new Error(Now.translate('Theme generator response is missing theme_json.'));
    }

    _generated = generated;
    const tj = generated.theme_json;
    previewName.value = tj.name || '';
    previewDesc.value = tj.description || '';
    previewSlug.value = generated.slug || '';
    renderPalette(tj.colors || {});
    cssPreview.textContent = generated.css || '';
    renderLayoutPreview(generated.home_html || '');

    if (genInfoEl) {
      const infoParts = [];
      if (generated.base_theme_label || generated.base_theme) {
        infoParts.push(`Based on ${generated.base_theme_label || generated.base_theme}`);
      }
      if (generated.css_mode) {
        infoParts.push(generated.css_mode === 'token-only' ? 'Token-safe :root append overrides' : generated.css_mode);
      }
      if (generated.home_html) {
        infoParts.push(Now.translate('Homepage layout generated'));
      }
      if (generated.model) {
        infoParts.push(`${generated.model}  ·  ${generated.tokens || 0} tokens`);
      }
      genInfoEl.textContent = infoParts.join('  ·  ');
      genInfoEl.classList.toggle('hidden', infoParts.length === 0);
    }

    previewSection.classList.remove('hidden');
    previewSection.scrollIntoView({behavior: 'smooth', block: 'start'});
  };

  // ── Generate ────────────────────────────────────────────────────────────
  const handleGenerate = async () => {
    const prompt = (element.querySelector('#ai_theme_prompt').value || '').trim();
    if (!prompt) {
      NotificationManager.error(Now.translate('Please enter a theme description.'));
      return;
    }

    setBusyButton(btnGenerate, Now.translate('{LNG_Generating}…'), true);
    previewSection.classList.add('hidden');

    try {
      const includeHomeEl = element.querySelector('#ai_include_home_html');
      const response = await ApiService.post('../api/index/aitheme/generate', {
        prompt,
        color_scheme: element.querySelector('#ai_color_scheme').value,
        name: (element.querySelector('#ai_theme_name').value || '').trim(),
        include_home_html: !!(includeHomeEl && includeHomeEl.checked)
      });
      const result = unwrapApiResponse(response);

      if (!result.success) {
        NotificationManager.error(result.message || Now.translate('Generation failed.'));
        return;
      }

      if (!result.data || typeof result.data !== 'object') {
        NotificationManager.error(Now.translate('Theme generator response is missing data.'));
        return;
      }

      showPreview(result.data);
      if (_generated) {
        _generated.prompt = prompt;
      }
      NotificationManager.success(result.message || Now.translate('Theme generated! Review and save below.'));
    } catch (err) {
      NotificationManager.error(extractApiErrorMessage(err, Now.translate('Generation failed.')));
    } finally {
      setBusyButton(btnGenerate, '', false);
    }
  };

  // ── Save ────────────────────────────────────────────────────────────────
  const handleSave = async () => {
    if (!_generated) return;
    const slug = previewSlug.value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '-');
    if (!slug) {
      NotificationManager.error(Now.translate('Slug cannot be empty.'));
      return;
    }

    setBusyButton(btnSave, Now.translate('Saving…'), true);

    const isThemeExistsConflict = (message) => /Theme already exists/i.test(String(message || ''));
    const askOverwrite = () => confirm(Now.translate('Theme already exists. Overwrite AI token overrides in this theme?'));
    const saveTheme = async (overwrite = false) => {
      const response = await ApiService.post('../api/index/aitheme/save', {
        slug,
        base_theme: _generated.base_theme,
        theme_json: _generated.theme_json,
        css: _generated.css,
        home_html: _generated.home_html || '',
        prompt: _generated.prompt || '',
        overwrite
      });

      return unwrapApiResponse(response);
    };

    try {
      let result = await saveTheme(false);

      if (!result.success && isThemeExistsConflict(result.message) && askOverwrite()) {
        result = await saveTheme(true);
      }

      if (result.success) {
        const overwriteLabel = result.data?.overwritten ? Now.translate('Theme updated') : Now.translate('Theme saved');
        NotificationManager.show({
          type: 'success',
          title: overwriteLabel,
          message: `"${slug}" ${Now.translate('is ready.')} <a href="#/themes">${Now.translate('Go to Themes →')}</a>`
        });
      } else {
        NotificationManager.error(result.message || Now.translate('Save failed.'));
      }
    } catch (err) {
      const message = extractApiErrorMessage(err, Now.translate('Save failed.'));

      if (isThemeExistsConflict(message) && askOverwrite()) {
        try {
          const overwriteResult = await saveTheme(true);
          if (overwriteResult.success) {
            NotificationManager.show({
              type: 'success',
              title: Now.translate('Theme updated'),
              message: `"${slug}" ${Now.translate('is ready.')} <a href="#/themes">${Now.translate('Go to Themes →')}</a>`
            });
          } else {
            NotificationManager.error(overwriteResult.message || Now.translate('Save failed.'));
          }
        } catch (overwriteError) {
          NotificationManager.error(extractApiErrorMessage(overwriteError, Now.translate('Save failed.')));
        }
      } else {
        NotificationManager.error(message);
      }
    } finally {
      setBusyButton(btnSave, '', false);
    }
  };

  const handlePromptKeydown = (e) => {
    if (e.ctrlKey && e.key === 'Enter') handleGenerate();
  };

  btnGenerate.addEventListener('click', handleGenerate);
  btnSave.addEventListener('click', handleSave);
  promptInput.addEventListener('keydown', handlePromptKeydown);

  return () => {
    btnGenerate.removeEventListener('click', handleGenerate);
    btnSave.removeEventListener('click', handleSave);
    promptInput.removeEventListener('keydown', handlePromptKeydown);
  };
}
