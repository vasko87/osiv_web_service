document.addEventListener("DOMContentLoaded", () => {
    document.querySelector('form').addEventListener("submit", beforeSubmit);

    const trigger = document.querySelector(`ul.nav a[href="${window.location.hash}"]`);
    if (trigger) {
        const tab = new bootstrap.Tab(trigger);
        tab.show();
    }

    let dbSelect = document.querySelector('#db_name');
    let dbSort = document.querySelector('#sortFields');
    let dbDebug = document.querySelector('#db_debug');
    let dbQ = document.querySelector('#ta_q');
    dbSelect.addEventListener('click', function (e) {
        setCookie('st_db', e.target.value);
        window.dbSctructControl.update();
    });
    dbSort.addEventListener('click', function (e) {
        setCookie('st_sort', e.target.checked);
    });
    dbDebug.addEventListener('click', function (e) {
        setCookie('st_debug', e.target.checked);
    });

    let stDb = getCookie('st_db');
    let stSort = getCookie('st_sort');
    let stDebug = getCookie('st_debug');
    let stQ = JSON.parse(getCookie('st_q'));

    if (stDb !== undefined) {
        dbSelect.value = stDb;
    }
    if (stSort !== undefined) {
        dbSort.checked = stSort === 'true';
    }
    if (stDebug !== undefined) {
        dbDebug.checked = stDebug === 'true';
    }
    if (stQ !== undefined) {
        dbQ.value = stQ;
    }

    window.dbSctructControl = new DbStructControl();
    let collapseDbStruct = document.querySelector('#collapseDbStruct')
    collapseDbStruct.addEventListener('show.bs.collapse', function () {
        window.dbSctructControl.update();
    })
});

document.addEventListener('shown.bs.tab', function (e) {
    const url = new URL(e.target.href);
    window.location.hash = url.hash;
});

function beforeSubmit(e) {
    const q = document.getElementById('ta_q').value;

    if (e.submitter.name === 'action[sql]' && q === '') {
        alert('empty query');
        e.preventDefault();
    } else {
        setCookie('st_q', JSON.stringify(q));
    }
}

function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(cname, cvalue, exdays) {
    exdays = exdays || 356;
    let d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/;SameSite=Lax";
}

function updateDbStruct() {
    let collapseDbStruct = document.querySelector('#collapseDbStruct');
    if (!collapseDbStruct.classList.contains('show')) {
        return;
    }
}

class DbStructControl
{
    constructor() {
        this.dbName = '';
        this.debug = '';
        this.inProgress = false;

        this.getTbody().addEventListener('click', e => {
            if (e.target.classList.contains('fa')) {
                const idx = e.target.parentNode.dataset.table;
                const rows = document.querySelectorAll(`.table-${idx}`);
                rows.forEach(r => r.classList.toggle('d-none'));

                if (e.target.classList.contains('fa-plus')) {
                    e.target.classList.remove('fa-plus');
                    e.target.classList.add('fa-minus');
                } else {
                    e.target.classList.remove('fa-minus');
                    e.target.classList.add('fa-plus');
                }
            }
            if (e.target.classList.contains('fa-copy')) {
                e.preventDefault();
                const td = e.target.parentNode.parentNode;
                const text = td.innerText.trim();

                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');  // копируем текст
                    e.target.parentNode.style.opacity = '1';
                    setTimeout(() => {
                        e.target.parentNode.style.opacity = '0.5';
                    }, 500);
                } catch (err) {
                    console.error('Copy failed', err);
                }
                document.body.removeChild(textarea);
            }
        });
    }

    update() {
        if (this.inProgress) {
            return;
        }

        let dbSelect = document.querySelector('#db_name');
        let dbName = dbSelect.value;
        if (dbName === '') {
            return;
        }

        let dbDebug = document.querySelector('#db_debug').value;

        // update dbName if it has changed
        if (this.dbName === dbName && this.debug === dbDebug) {
            return;
        }

        this.inProgress = true;
        this.spinner(true);
        this.getTbody().innerHTML = '';

        const isDebug = document.querySelector('#db_debug').checked ? '1' : '';
        let url = `get_db_struct?db=${dbName}&debug=${isDebug}`;
        fetch(url, {method: 'POST'})
            .then(response => response.json())
            .then(data => {
                this.render(data, dbName, dbDebug);
            })
            .catch(error => {
                this.spinner(false);
                this.err('Error fetching database structure:' + error);
                console.error('Error fetching database structure:', error);
            });
    }

    spinner(flag) {
        let spinner = document.querySelector('#db_struct_spinner');
        let tbl = document.querySelector('#db_struct_table');
        if (flag) {
            tbl.classList.add('d-none');
            spinner.classList.remove('d-none');
        } else {
            tbl.classList.remove('d-none');
            spinner.classList.add('d-none');
        }
    }

    err(val) {
        let errEl = document.querySelector('#db_struct_error');
        if (!val) {
            errEl.classList.add('d-none');
            errEl.innerHTML = '';
        } else {
            errEl.classList.remove('d-none');
            errEl.innerHTML = val;
        }
    }

    getTbody() {
        const el = document.querySelector('#db_struct_table');
        return el.getElementsByTagName('tbody')[0];
    }

    render(data, dbName, dbDebug) {
        this.err();
        this.spinner(false);

        const el = document.querySelector('#db_struct_table');
        const tbody = el.getElementsByTagName('tbody')[0];

        const copyButton = `
            <a href="#" title="Copy" style="font-size: 70%; opacity: 0.5; margin-left: 10px;">
              <i class="fas fa-copy"></i>
          </a>
        `;

        Object.entries(data.struct).forEach(([tableName, tableInfo], tableIndex) => {
            const row = document.createElement('tr');
            row.innerHTML = `
        <td colspan="2">
          <span class="toggle-btn" data-table="${tableIndex}"><i class="fa fa-plus"></i></span>
          ${tableName}
          ${copyButton}
        </td>
      `;
            tbody.appendChild(row);

            Object.entries(tableInfo.columns).forEach(([colName, colType]) => {
                const colRow = document.createElement('tr');
                colRow.classList.add('sub-row', `table-${tableIndex}`, 'd-none');
                colRow.innerHTML = `
          <td>${colName}${copyButton}</td>
          <td>${colType}</td>
        `;
                tbody.appendChild(colRow);
            });
        });

        this.inProgress = false;
        this.dbName = dbName;
        this.debug = dbDebug;
    }
}
