// Register Routes for Document Module via EventManager
EventManager.on('router:initialized', () => {
  if (window.RouterManager) {
    RouterManager.register('/documents', {
      template: WEB_URL + 'modules/document/template/setup.html',
      title: '{LNG_Articles}',
      requireAuth: true
    });
    RouterManager.register('/document', {
      template: WEB_URL + 'modules/document/template/write.html',
      title: '{LNG_Article}',
      menuPath: '/documents',
      requireAuth: true
    });
    RouterManager.register('/document-categories', {
      template: WEB_URL + 'modules/document/template/categories.html',
      title: '{LNG_Categories}',
      requireAuth: true
    });
    RouterManager.register('/document-category', {
      template: WEB_URL + 'modules/document/template/category.html',
      title: '{LNG_Category}',
      menuPath: '/document-categories',
      requireAuth: true
    });
    RouterManager.register('/document-settings', {
      template: WEB_URL + 'modules/document/template/settings.html',
      title: '{LNG_Settings}',
      requireAuth: true
    });
  }
});

const DOCUMENT_AI_ALLOWED_CLASSES = [
  'left', 'center', 'right', 'justify',
  'top', 'bottom', 'middle', 'baseline',
  'float-left', 'float-right', 'float-center',
  'block', 'inline', 'inline-block'
];

function translateAiText(text) {
  if (window.Now && typeof window.Now.translate === 'function') {
    return window.Now.translate(text);
  }

  return text;
}

function initDocumentWriteAi(form) {
  if (!form || form.dataset.aiInitialized === 'true') {
    return () => {};
  }

  const controls = {
    mode: form.querySelector('#ai_mode'),
    sourceLng: form.querySelector('#ai_source_lng'),
    targetLng: form.querySelector('#ai_target_lng'),
    tone: form.querySelector('#ai_tone'),
    mood: form.querySelector('#ai_mood'),
    audience: form.querySelector('#ai_audience'),
    instruction: form.querySelector('#ai_instruction'),
    seoFocus: form.querySelector('#ai_seo_focus'),
    applyTitle: form.querySelector('#ai_apply_title'),
    applyKeywords: form.querySelector('#ai_apply_keywords'),
    applyDescription: form.querySelector('#ai_apply_description'),
    applySlug: form.querySelector('#ai_apply_slug'),
    generateImage: form.querySelector('#ai_generate_image'),
    imagePrompt: form.querySelector('#ai_image_prompt'),
    imageSize: form.querySelector('#ai_image_size'),
    runWrite: form.querySelector('#ai_apply_write'),
    runMeta: form.querySelector('#ai_apply_meta'),
    status: form.querySelector('#ai_document_status')
  };

  if (!controls.mode || !controls.sourceLng || !controls.targetLng || !controls.runWrite || !controls.runMeta) {
    return () => {};
  }

  form.dataset.aiInitialized = 'true';

  const languages = collectDocumentLanguages(form);
  populateLanguageSelect(controls.sourceLng, languages);
  populateLanguageSelect(controls.targetLng, languages);

  const currentLang = getCurrentLanguageTab(form, languages);
  if (currentLang) {
    controls.sourceLng.value = currentLang;
    controls.targetLng.value = currentLang;
  }

  const updateModeState = () => {
    const isTranslate = controls.mode.value === 'translate';
    controls.targetLng.disabled = !isTranslate;
    if (!isTranslate) {
      controls.targetLng.value = controls.sourceLng.value;
    } else if (controls.targetLng.value === controls.sourceLng.value) {
      const alternative = languages.find((lng) => lng !== controls.sourceLng.value);
      if (alternative) {
        controls.targetLng.value = alternative;
      }
    }
  };

  const onSourceLanguageChange = () => {
    if (controls.mode.value !== 'translate') {
      controls.targetLng.value = controls.sourceLng.value;
      return;
    }

    if (controls.targetLng.value === controls.sourceLng.value) {
      const alternative = languages.find((lng) => lng !== controls.sourceLng.value);
      if (alternative) {
        controls.targetLng.value = alternative;
      }
    }
  };

  const onRunWriteClick = async () => {
    await runAiDocumentFlow(form, controls, true, controls.runWrite);
  };

  const onRunMetaClick = async () => {
    await runAiDocumentFlow(form, controls, false, controls.runMeta);
  };

  controls.mode.addEventListener('change', updateModeState);
  controls.sourceLng.addEventListener('change', onSourceLanguageChange);
  controls.runWrite.addEventListener('click', onRunWriteClick);
  controls.runMeta.addEventListener('click', onRunMetaClick);

  updateModeState();
  setAiDocumentStatus(controls, translateAiText('Ready. Select mode and options, then click a button to start.'), 'info');

  return () => {
    controls.mode.removeEventListener('change', updateModeState);
    controls.sourceLng.removeEventListener('change', onSourceLanguageChange);
    controls.runWrite.removeEventListener('click', onRunWriteClick);
    controls.runMeta.removeEventListener('click', onRunMetaClick);
    delete form.dataset.aiInitialized;
  };
}

async function runAiDocumentFlow(form, controls, includeRewrite, triggerButton) {
  const sourceLng = String(controls.sourceLng.value || '').trim();
  const targetLng = controls.mode.value === 'translate'
    ? String(controls.targetLng.value || '').trim()
    : sourceLng;
  const isTranslate = controls.mode.value === 'translate';

  if (!sourceLng || !targetLng) {
    const message = translateAiText('Please select source and target languages.');
    setAiDocumentStatus(controls, message, 'error');
    showAiNotification('warning', message);
    return;
  }

  const sourceHtml = getDetailHtml(form, sourceLng);
  let targetHtml = getDetailHtml(form, targetLng);

  if (includeRewrite && isTranslate && sourceHtml.trim() === '') {
    const message = translateAiText('Source content is empty. Please add content before using AI.');
    setAiDocumentStatus(controls, message, 'error');
    showAiNotification('warning', message);
    return;
  }

  const hasMetadataTask = Boolean(
    controls.applyTitle.checked
    || controls.applyKeywords.checked
    || controls.applyDescription.checked
    || controls.applySlug.checked
    || controls.generateImage.checked
  );

  if (!includeRewrite && !hasMetadataTask) {
    const message = translateAiText('No SEO task selected. Please select at least one option.');
    setAiDocumentStatus(controls, message, 'error');
    showAiNotification('warning', message);
    return;
  }

  const actionLabel = includeRewrite
    ? translateAiText('Rewrite/Translate + SEO')
    : translateAiText('Generate SEO');
  setAiBusyState(controls, true, triggerButton, actionLabel);
  setAiDocumentStatus(
    controls,
    includeRewrite
      ? translateAiText('Starting: Rewrite/Translate + SEO')
      : translateAiText('Starting: Generate SEO'),
    'loading'
  );
  showAiNotification(
    'info',
    includeRewrite
      ? translateAiText('Processing Rewrite/Translate + SEO ...')
      : translateAiText('Processing Generate SEO ...')
  );

  try {
    if (includeRewrite) {
      setAiDocumentStatus(controls, translateAiText('Rewriting/translating content...'), 'loading');

      const rewriteInstruction = buildRewriteInstruction(form, controls, {
        sourceLng,
        targetLng,
        isTranslate
      });

      const rewriteAction = !isTranslate && sourceHtml.trim() === '' ? 'generate' : 'rewrite';
      const rewritePayload = {
        prompt: rewriteInstruction,
        allowed_classes: DOCUMENT_AI_ALLOWED_CLASSES
      };

      if (rewriteAction === 'rewrite') {
        rewritePayload.content_html = sourceHtml;
      } else if (targetHtml.trim() !== '') {
        rewritePayload.context_html = targetHtml;
      }

      const rewriteData = await requestAiWriter(rewriteAction, rewritePayload);

      targetHtml = String(rewriteData.html || '').trim();
      if (targetHtml === '') {
        throw new Error(translateAiText('AI returned empty content. Please try again.'));
      }

      setDetailHtml(form, targetLng, targetHtml);
      setAiDocumentStatus(controls, translateAiText('Rewrite/translate completed. Generating SEO metadata...'), 'loading');
    }

    let metadata = null;
    if (hasMetadataTask) {
      metadata = await requestAiWriter('metadata', {
        mode: controls.mode.value,
        target_language: targetLng,
        topic: getInputValue(form, `topic_${targetLng}`),
        keywords: getInputValue(form, `keywords_${targetLng}`),
        description: getInputValue(form, `description_${targetLng}`),
        detail_html: targetHtml,
        tone: controls.tone.value,
        mood: controls.mood.value,
        audience: controls.audience.value,
        seo_focus: controls.seoFocus.checked,
        include_image_prompt: controls.generateImage.checked
      });

      if (controls.applyTitle.checked && metadata.topic) {
        setInputValue(form, `topic_${targetLng}`, metadata.topic);
      }
      if (controls.applyKeywords.checked && metadata.keywords) {
        setInputValue(form, `keywords_${targetLng}`, metadata.keywords);
      }
      if (controls.applyDescription.checked && metadata.description) {
        setInputValue(form, `description_${targetLng}`, metadata.description);
      }
      if (controls.applySlug.checked && metadata.slug) {
        setInputValue(form, 'alias', metadata.slug);
      }

      if (metadata.image_prompt && controls.imagePrompt.value.trim() === '') {
        controls.imagePrompt.value = metadata.image_prompt;
      }
    }

    if (controls.generateImage.checked) {
      const imagePrompt = String(controls.imagePrompt.value || '').trim()
        || String(metadata?.image_prompt || '').trim();

      if (imagePrompt === '') {
        throw new Error(translateAiText('Image generation selected but no image prompt was provided.'));
      }

      setAiDocumentStatus(controls, translateAiText('Generating article image...'), 'loading');
      const imageData = await requestAiWriter('image', {
        prompt: imagePrompt,
        size: controls.imageSize.value || '1024x1024'
      });

      const imagePayload = imageData?.images?.[0] || null;
      if (!imagePayload || !imagePayload.b64_json) {
        throw new Error(translateAiText('AI did not return a usable image payload.'));
      }

      await attachGeneratedImageToPictureInput(form, imagePayload);
    }

    if (includeRewrite) {
      setAiDocumentStatus(controls, translateAiText('Completed: Rewrite/translate and SEO generation finished.'), 'success');
      showAiNotification('success', translateAiText('Rewrite/translate and SEO generation completed.'));
    } else {
      setAiDocumentStatus(controls, translateAiText('Completed: SEO metadata generation finished.'), 'success');
      showAiNotification('success', translateAiText('SEO metadata generation completed.'));
    }
  } catch (error) {
    const message = error?.message || translateAiText('An error occurred while calling AI. Please try again.');
    setAiDocumentStatus(controls, message, 'error');
    showAiNotification('error', message);
  } finally {
    setAiBusyState(controls, false, triggerButton, actionLabel);
  }
}

function collectDocumentLanguages(form) {
  const langs = new Set();

  form.querySelectorAll('textarea[id^="detail_"]').forEach((textarea) => {
    const lng = textarea.id.replace(/^detail_/, '').trim();
    if (lng) {
      langs.add(lng);
    }
  });

  if (langs.size === 0) {
    form.querySelectorAll('input[id^="topic_"]').forEach((input) => {
      const lng = input.id.replace(/^topic_/, '').trim();
      if (lng) {
        langs.add(lng);
      }
    });
  }

  return Array.from(langs);
}

function getCurrentLanguageTab(form, languages) {
  const checked = form.querySelector('input[name="tab"]:checked');
  if (checked && checked.id && checked.id.indexOf('tab_') === 0) {
    const lng = checked.id.replace(/^tab_/, '');
    if (languages.includes(lng)) {
      return lng;
    }
  }

  return languages.length > 0 ? languages[0] : '';
}

function populateLanguageSelect(select, languages) {
  if (!select) {
    return;
  }

  select.innerHTML = '';
  languages.forEach((lng) => {
    const option = document.createElement('option');
    option.value = lng;
    option.textContent = `${getLanguageLabel(lng)} [${lng}]`;
    select.appendChild(option);
  });
}

function getLanguageLabel(code) {
  const key = String(code || '').trim().toLowerCase();
  const map = {
    en: 'English',
    th: 'Thai',
    jp: 'Japanese',
    ja: 'Japanese',
    zh: 'Chinese',
    ko: 'Korean',
    vi: 'Vietnamese',
    id: 'Indonesian',
    ms: 'Malay',
    fr: 'French',
    de: 'German',
    es: 'Spanish',
    it: 'Italian',
    ru: 'Russian',
    ar: 'Arabic',
    hi: 'Hindi'
  };

  return translateAiText(map[key] || key.toUpperCase());
}

function getDetailHtml(form, lng) {
  const field = form.querySelector(`#detail_${lng}`);
  if (!field) {
    return '';
  }

  const elementManager = window.Now?.getManager?.('element');
  const instance = elementManager?.getInstanceByElement?.(field);
  if (instance && typeof instance.getValue === 'function') {
    return String(instance.getValue() || '');
  }

  return String(field.value || '');
}

function setDetailHtml(form, lng, html) {
  const field = form.querySelector(`#detail_${lng}`);
  if (!field) {
    return;
  }

  const value = String(html || '');
  const elementManager = window.Now?.getManager?.('element');
  const instance = elementManager?.getInstanceByElement?.(field);

  if (instance && typeof instance.setValue === 'function') {
    instance.setValue(value);
  } else {
    field.value = value;
  }

  field.dispatchEvent(new Event('input', {bubbles: true}));
  field.dispatchEvent(new Event('change', {bubbles: true}));
}

function getInputValue(form, id) {
  const input = form.querySelector(`#${id}`);
  return input ? String(input.value || '') : '';
}

function setInputValue(form, id, value) {
  const input = form.querySelector(`#${id}`);
  if (!input) {
    return;
  }

  input.value = String(value || '');
  input.dispatchEvent(new Event('input', {bubbles: true}));
  input.dispatchEvent(new Event('change', {bubbles: true}));
}

function buildRewriteInstruction(form, controls, options) {
  const instructionParts = [];
  if (options.isTranslate) {
    instructionParts.push(`Translate this content to language code ${options.targetLng}.`);
  } else {
    instructionParts.push('Write a complete article in clean HTML.');
    const topic = getInputValue(form, `topic_${options.targetLng}`);
    const keywords = getInputValue(form, `keywords_${options.targetLng}`);
    const description = getInputValue(form, `description_${options.targetLng}`);

    if (topic !== '') {
      instructionParts.push(`Topic: ${topic}.`);
    }
    if (keywords !== '') {
      instructionParts.push(`Keywords: ${keywords}.`);
    }
    if (description !== '') {
      instructionParts.push(`Article summary: ${description}.`);
    }

    if (getDetailHtml(form, options.sourceLng).trim() !== '') {
      instructionParts.push('Rewrite this content in new wording while preserving all important facts.');
    } else {
      instructionParts.push(`Write the article in language code ${options.targetLng}.`);
    }
  }

  const tone = String(controls.tone.value || '').trim();
  const mood = String(controls.mood.value || '').trim();
  const audience = String(controls.audience.value || '').trim();
  const additional = String(controls.instruction.value || '').trim();

  if (tone !== '') {
    instructionParts.push(`Tone: ${tone}.`);
  }
  if (mood !== '') {
    instructionParts.push(`Mood: ${mood}.`);
  }
  if (audience !== '') {
    instructionParts.push(`Target audience: ${audience}.`);
  }
  if (controls.seoFocus.checked) {
    instructionParts.push('Optimize wording for SEO and readability.');
  }
  if (additional !== '') {
    instructionParts.push(additional);
  }

  return instructionParts.join(' ');
}

function setAiBusyState(controls, busy, triggerButton, actionLabel) {
  controls.runWrite.disabled = busy;
  controls.runMeta.disabled = busy;

  if (!triggerButton) {
    return;
  }

  if (!triggerButton.dataset.originalHtml) {
    triggerButton.dataset.originalHtml = triggerButton.innerHTML;
  }

  if (busy) {
    triggerButton.innerHTML = `<span class="spinner"></span> ${translateAiText('Running')}...`;
    return;
  }

  triggerButton.innerHTML = triggerButton.dataset.originalHtml;
}

function setAiDocumentStatus(controls, message, type) {
  if (!controls.status) {
    return;
  }

  controls.status.textContent = String(message || '');
  controls.status.dataset.status = type || 'info';
}

function showAiNotification(type, message) {
  if (!window.NotificationManager || !message) {
    return;
  }

  if (type === 'success' && typeof window.NotificationManager.success === 'function') {
    window.NotificationManager.success(message);
    return;
  }
  if (type === 'warning' && typeof window.NotificationManager.warning === 'function') {
    window.NotificationManager.warning(message);
    return;
  }
  if (type === 'error' && typeof window.NotificationManager.error === 'function') {
    window.NotificationManager.error(message);
    return;
  }

  if (typeof window.NotificationManager.show === 'function') {
    window.NotificationManager.show({
      type: type || 'info',
      message
    });
  }
}

async function requestAiWriter(action, payload) {
  const base = getAiWriterBaseUrl();
  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json'
  };

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    headers['X-CSRF-Token'] = csrfToken;
  }

  const response = await fetch(`${base}/${action}`, {
    method: 'POST',
    headers,
    credentials: 'same-origin',
    body: JSON.stringify(payload || {})
  });

  const raw = await response.json().catch(() => ({}));
  const normalized = unwrapApiResponse(raw);

  if (!response.ok || !normalized.success) {
    throw new Error(normalized.message || `AI request failed (${response.status})`);
  }

  return normalized.data || {};
}

function unwrapApiResponse(payload) {
  if (payload && typeof payload === 'object') {
    if (typeof payload.success === 'boolean') {
      return payload;
    }
    if (payload.data && typeof payload.data === 'object' && typeof payload.data.success === 'boolean') {
      return payload.data;
    }
  }

  return {
    success: false,
    message: 'Unexpected API response format.',
    data: null
  };
}

function getAiWriterBaseUrl() {
  const base = typeof window.WEB_URL === 'string' && window.WEB_URL !== '' ? window.WEB_URL : '/';
  const normalized = base.endsWith('/') ? base : `${base}/`;

  return `${normalized}api/index/aiwriter`;
}

async function attachGeneratedImageToPictureInput(form, imagePayload) {
  const pictureInput = form.querySelector('#picture');
  if (!pictureInput) {
    throw new Error('Thumbnail input was not found.');
  }

  const {blob, name} = resolveGeneratedImageUploadData(imagePayload);
  const payloadName = typeof name === 'string' ? name.trim() : '';
  const extension = detectImageExtension(blob.type, payloadName);
  const file = new File([blob], payloadName || `ai-${Date.now()}.${extension}`, {
    type: blob.type || 'image/png'
  });

  const transfer = new DataTransfer();
  transfer.items.add(file);
  pictureInput.files = transfer.files;
  pictureInput.dispatchEvent(new Event('change', {bubbles: true}));
}

function resolveGeneratedImageUploadData(imagePayload) {
  const payload = imagePayload || {};
  if (typeof payload.b64_json !== 'string' || payload.b64_json.trim() === '') {
    throw new Error('AI did not return a usable image payload.');
  }

  const mimeType = typeof payload.mime_type === 'string' && payload.mime_type.trim() !== ''
    ? payload.mime_type.trim()
    : 'image/png';

  return {
    blob: base64ToBlob(payload.b64_json, mimeType),
    name: payload.name || ''
  };
}

function base64ToBlob(base64, mimeType) {
  const normalized = String(base64 || '').replace(/^data:[^,]+,/, '').trim();
  const binary = window.atob(normalized);
  const bytes = new Uint8Array(binary.length);

  for (let index = 0; index < binary.length; index += 1) {
    bytes[index] = binary.charCodeAt(index);
  }

  return new Blob([bytes], {type: mimeType || 'image/png'});
}

function detectImageExtension(mimeType, imageUrl) {
  const map = {
    'image/jpeg': 'jpg',
    'image/png': 'png',
    'image/gif': 'gif',
    'image/webp': 'webp'
  };

  if (mimeType && map[mimeType]) {
    return map[mimeType];
  }

  const match = String(imageUrl || '').match(/\.(jpg|jpeg|png|gif|webp)(?:\?|#|$)/i);
  if (match) {
    const ext = match[1].toLowerCase();
    return ext === 'jpeg' ? 'jpg' : ext;
  }

  return 'png';
}