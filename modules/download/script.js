/**
 * Download module — front end.
 *
 * A download link is <a id="download_{ID}"> (module listing, download widget)
 * or <a id="getdl_{ID}"> (the widget's single file): a click asks
 * api/download/download/action for permission (and an optional confirmation),
 * then for the file, updates the counter #downloads_{ID} and starts the
 * download in the link's target frame. One delegated listener serves every
 * link on the page, including links rendered later.
 */
async function requestDownloadAction(action, id) {
    const url = WEB_URL + 'api/download/download/action';
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

function triggerDownload(url, target) {
    if (!url) {
        return;
    }

    const targetName = typeof target === 'string' ? target.trim() : '';
    if (targetName === '' || targetName === '_self') {
        window.location.href = url;
        return;
    }

    let frame = null;
    const frames = document.getElementsByTagName('iframe');
    for (let i = 0; i < frames.length; i += 1) {
        if (frames[i].name === targetName) {
            frame = frames[i];
            break;
        }
    }

    if (!frame) {
        frame = document.createElement('iframe');
        frame.name = targetName;
        frame.style.display = 'none';
        document.body.appendChild(frame);
    }

    frame.src = url;
}

function downloadError(message) {
    if (window.NotificationManager && typeof NotificationManager.error === 'function') {
        NotificationManager.error(message);
    } else {
        window.alert(message);
    }
}

function downloadConfirm(message) {
    if (window.DialogManager && typeof DialogManager.confirm === 'function') {
        return DialogManager.confirm(message);
    }
    return Promise.resolve(window.confirm(message));
}

async function doDownloadClick(event) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    const id = this && this.id ? this.id : '';
    if (!id) {
        return false;
    }

    try {
        const check = await requestDownloadAction('download', id);
        if (!check.ok) {
            if (check.message) {
                downloadError(check.message);
            }
            return false;
        }

        if (check.data && check.data.confirm && !(await downloadConfirm(check.data.confirm))) {
            return false;
        }

        const download = await requestDownloadAction('downloading', id);
        if (!download.ok) {
            if (download.message) {
                downloadError(download.message);
            }
            return false;
        }

        if (download.data && download.data.downloads && download.data.id) {
            const counter = document.getElementById('downloads_' + download.data.id);
            if (counter) {
                counter.textContent = download.data.downloads;
            }
        }

        if (download.data && download.data.href) {
            triggerDownload(download.data.href, this && this.target ? this.target : '');
        }
    } catch (error) {
        downloadError(error && error.message ? error.message : 'Unable to complete the transaction');
    }

    return false;
}

// once per page, even if a theme includes this file again (a flag — the
// function declarations above are already global when this runs)
if (!window.downloadLinksBound) {
    window.downloadLinksBound = true;
    document.addEventListener('click', function(event) {
        const link = event.target && event.target.closest ? event.target.closest('a[id]') : null;
        if (link && /^(download|getdl)_[0-9]+$/.test(link.id)) {
            doDownloadClick.call(link, event);
        }
    });

    // Category <select id="download-cat"> of older themes' listings: open the chosen URL
    document.addEventListener('change', function(event) {
        const select = event.target;
        if (select && select.id === 'download-cat' && select.value) {
            window.location.href = select.value;
        }
    });
}

/**
 * Kept for themes whose templates still call it — the links are handled by
 * the delegated listener above.
 */
function initDownloadList() {}

window.doDownloadClick = doDownloadClick;
window.initDownloadList = initDownloadList;
