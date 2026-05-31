/**
 * GCMS Designer – Side Panel
 *
 * เนื้อหาแผงอยู่ใน `<form id="gcms-designer-panel-form" data-form="gcms-designer-panel">`
 * เพื่อผสาน FormManager (Now.js): init หลังแนบเนื้อหา · teardown ก่อนล้าง/ลบแผง
 *
 * FormBuilderManager ไม่ถูกใช้ที่นี่ — เหมาะกับฟอร์ม dynamic schema + canvas;
 * inspector บล็อกเป็น control กำหนดเอง (สี, gradient, FileBrowser) มากเกินกว่าจะ map
 * เป็น field types มาตรฐานอย่างคุ้ม
 *
 * @filesource js/designer/ui/Panel.js
 */

export function registerPanel(ctx) {

  /**
   * หลังแนบเนื้อหาแผง: ใส่ id ฟิลด์ + init FormManager แล้วเรียก onDone
   * (ใช้ให้ inspector ผูก event หลัง ColorElementFactory init เพื่อไม่ให้ change/input ตอน init ทำลายสไตล์)
   * @param {(() => void) | undefined} onDone
   */
  function syncPanelFormWithFormManager(onDone) {
    const runDone = () => {
      try {
        onDone?.();
      } catch (e) {
        console.warn('[GCMS Designer] onFormReady failed', e);
      }
    };

    const form = ctx.state.panelContent;
    if (!form || form.tagName !== 'FORM') {
      queueMicrotask(runDone);
      return;
    }

    ctx.prepareDesignerFormFieldsForFormManager(form, `p${Date.now().toString(36)}`);

    if (!window.FormManager || typeof window.FormManager.initForm !== 'function') {
      queueMicrotask(runDone);
      return;
    }

    /* initForm มี await หลายจุด — ถ้าค้างจะไม่มี listener; จำกัดเวลาแล้วผูก event ต่อ */
    let finished = false;
    const safeDone = () => {
      if (finished) return;
      finished = true;
      runDone();
    };
    const t = window.setTimeout(safeDone, 800);

    void window.FormManager.initForm(form)
      .catch(e => {
        console.warn('[GCMS Designer] FormManager.initForm failed', e);
      })
      .finally(() => {
        window.clearTimeout(t);
        safeDone();
      });
  }

  ctx.buildPanel = function() {
    const panel = document.createElement('aside');
    panel.id = 'gcms-designer-panel';
    panel.innerHTML = `
      <div class="gcms-panel-header">
        <h3 data-i18n>Designer</h3>
        <button type="button" class="gcms-panel-close" aria-label="{LNG_Close}">&times;</button>
      </div>
      <div class="gcms-panel-content">
        <form id="gcms-designer-panel-form" class="gcms-designer-panel-form"
          data-form="gcms-designer-panel"
          data-ajax-submit="false"
          data-auto-validate="false"
          data-validation="false"
          novalidate></form>
      </div>`;
    document.body.appendChild(panel);

    ctx.state.panel = panel;
    ctx.state.panelTitle = panel.querySelector('h3');
    const panelForm = panel.querySelector('#gcms-designer-panel-form');
    ctx.state.panelContent = panelForm;
    /* กัน submit ก่อน FormManager — ไม่ให้ส่ง POST ไปที่หน้า home */
    panelForm.addEventListener('submit', e => {
      e.preventDefault();
      e.stopImmediatePropagation();
    }, true);
    ctx.localizeDom(panel);
    panel.querySelector('.gcms-panel-close').addEventListener('click', () => panel.classList.remove('visible'));

    /* MutationObserver ของ FormManager อาจ init ฟอร์มว่าง — ถอนรอจนมีเนื้อหา */
    ctx.teardownDesignerPanelFormManager(panelForm);
  };

  /**
   * @param {string} title
   * @param {Node} content
   * @param {{ onFormReady?: () => void }=} options เรียกหลัง FormManager.initForm สำเร็จ (หรือไม่มี FormManager)
   */
  ctx.showPanel = function(title, content, options = {}) {
    const opts = options && typeof options === 'object' ? options : {};
    const sessionId = ++ctx.state.panelSessionId;

    if (ctx.state.preview) ctx.togglePreview(false);
    const form = ctx.state.panelContent;
    if (form) {
      ctx.teardownDesignerPanelFormManager(form);
      form.innerHTML = '';
      form.appendChild(content);
      ctx.localizeDom(ctx.state.panel);
      ctx.state.panelTitle.textContent = ctx.translate(title);
      syncPanelFormWithFormManager(() => {
        if (sessionId !== ctx.state.panelSessionId) return;
        try {
          opts.onFormReady?.();
        } catch (e) {
          console.warn('[GCMS Designer] onFormReady failed', e);
        }
      });
    }
    ctx.state.panel?.classList.add('visible');
  };

  ctx.hidePanel = function() {
    document.querySelectorAll('.gcms-counter-wrap-active').forEach(el => {
      el.classList.remove('gcms-counter-wrap-active');
    });
    ctx.state.selectedEditable = null;
    ctx.state.panel?.classList.remove('visible');
  };
}
