/**
 * E-document module — front end.
 *
 * A document link is <a id="edocument_{ID}">: a click asks
 * api/edocument/download/action for permission (and an optional
 * confirmation), then for the file, updates the counter
 * #edocument_downloads_{ID} and opens the file. One delegated listener serves
 * every link on the page.
 */
async function requestEdocumentAction(action, id) {
    const url = WEB_URL + 'api/edocument/download/action';
    if (window.http && typeof window.http.post === 'function') {
        const response = await window.http.post(url, {
            action,
            id
        });
        const body = response && typeof response.data === 'object' ? response.data : null;
        if (body && typeof body.success === 'boolean') {
            return {
                ok: body.success,
                message: body.message || '',
                data: body.data || {}
            };
        }
        return {
            ok: !!(response && response.success),
            message: '',
            data: body || {}
        };
    }

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({action, id}).toString()
    });

    const body = await response.json().catch(() => null);
    if (body && typeof body.success === 'boolean') {
        return {
            ok: body.success,
            message: body.message || '',
            data: body.data || {}
        };
    }
    return {
        ok: response.ok,
        message: response.ok ? '' : response.statusText,
        data: body || {}
    };
}

function openEdocumentFile(url, target) {
    if (!url) {
        return;
    }

    if (target === '_blank') {
        // Opened after an await, so a popup blocker may refuse it: fall back to this tab
        if (!window.open(url, '_blank')) {
            window.location.href = url;
        }
        return;
    }

    let frame = document.querySelector('iframe[name="edocument_downloading"]');
    if (!frame) {
        frame = document.createElement('iframe');
        frame.name = 'edocument_downloading';
        frame.style.display = 'none';
        document.body.appendChild(frame);
    }
    frame.src = url;
}

function edocumentError(message) {
    if (window.NotificationManager && typeof NotificationManager.error === 'function') {
        NotificationManager.error(message);
    } else {
        window.alert(message);
    }
}

function edocumentConfirm(message) {
    if (window.DialogManager && typeof DialogManager.confirm === 'function') {
        return DialogManager.confirm(message);
    }
    return Promise.resolve(window.confirm(message));
}

async function doEdocumentClick(event) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    const id = this && this.id ? this.id : '';
    if (!id) {
        return false;
    }

    try {
        const check = await requestEdocumentAction('download', id);
        if (!check.ok) {
            if (check.message) {
                edocumentError(check.message);
            }
            return false;
        }

        if (check.data && check.data.confirm && !(await edocumentConfirm(check.data.confirm))) {
            return false;
        }

        const download = await requestEdocumentAction('downloading', id);
        if (!download.ok) {
            if (download.message) {
                edocumentError(download.message);
            }
            return false;
        }

        if (download.data && download.data.downloads && download.data.id) {
            const counter = document.getElementById('edocument_downloads_' + download.data.id);
            if (counter) {
                counter.textContent = download.data.downloads;
            }
        }

        if (download.data && download.data.href) {
            openEdocumentFile(download.data.href, download.data.target);
        }
    } catch (error) {
        edocumentError(error && error.message ? error.message : 'Unable to complete the transaction');
    }

    return false;
}

// once per page, even if a theme includes this file again (a flag — the
// function declarations above are already global when this runs)
if (!window.edocumentLinksBound) {
    window.edocumentLinksBound = true;
    document.addEventListener('click', function(event) {
        const link = event.target && event.target.closest ? event.target.closest('a[id]') : null;
        if (link && /^edocument_[0-9]+$/.test(link.id)) {
            doEdocumentClick.call(link, event);
        }
    });
}

/**
 * Kept for themes whose templates still call it — the links are handled by
 * the delegated listener above.
 */
function initEdocumentList() {}

window.doEdocumentClick = doEdocumentClick;
window.initEdocumentList = initEdocumentList;
