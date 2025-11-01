<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle("My Common Sense");
?>

    <div class="insights">
        <form class="insights__composer" id="insights-form" autocomplete="off">
            <input class="insights__input" type="text" name="title" placeholder="Заголовок" />
            <textarea class="insights__textarea" name="text" placeholder="Инсайт…" required></textarea>
            <input class="insights__input" type="text" name="tags" placeholder="Теги (через запятую)" />
            <button class="insights__btn insights__btn--primary" type="submit">Добавить</button>
        </form>

        <div class="insights__toolbar">
            <input class="insights__input" id="insights-search" type="search" placeholder="Поиск…" />
            <button class="insights__btn" id="insights-search-btn" type="button">Найти</button>
        </div>

        <div id="insights-error" class="insights__error" hidden></div>

        <div id="insights-list" class="insights__list" aria-live="polite"></div>

        <div class="insights__pager">
            <button class="insights__btn" id="insights-more" type="button" hidden>Показать ещё</button>
        </div>
    </div>


    <script>
        (function () {
            // Ждём DOM (и BX не нужен)
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else { init(); }

            function init() {
                // DOM
                const form = document.getElementById('insights-form');
                const listEl = document.getElementById('insights-list');
                const moreBtn = document.getElementById('insights-more');
                const errEl = document.getElementById('insights-error');
                const searchInput = document.getElementById('insights-search');
                const searchBtn = document.getElementById('insights-search-btn');

                // State
                let page = 1;
                const pageSize = 20;
                let total = 0;
                let q = '';
                let loading = false;

                // Helpers
                const esc = (s) => String(s ?? '')
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');

                const api = (action) => `/local/api/insights.php?a=${action}`;

                function showError(msg) {
                    if (msg) {
                        errEl.hidden = false; errEl.textContent = msg;
                    } else {
                        errEl.hidden = true; errEl.textContent = '';
                    }
                }

                function setMoreVisibility() {
                    const shown = listEl.querySelectorAll('.insights-card').length;
                    moreBtn.hidden = shown >= total;
                }

                function renderCard(it) {
                    return `
        <article class="insights-card" data-id="${it.ID}">
          <header class="insights-card__head">
            <div class="insights-card__title">
              <input type="text" name="UF_TITLE" value="${esc(it.UF_TITLE || '')}" placeholder="Без названия" />
            </div>
            <div class="insights-card__actions">
              <button class="insights__btn js-pin" type="button">
                ${Number(it.UF_IS_PINNED) === 1 ? '📌 Открепить' : '📍 Закрепить'}
              </button>
              <button class="insights__btn" data-role="delete" type="button">Удалить</button>
            </div>
          </header>
          <div class="insights-card__body">
            <textarea name="UF_TEXT" placeholder="Текст инсайта…">${esc(it.UF_TEXT || '')}</textarea>
          </div>
          <footer class="insights-card__foot">
            <input class="insights__input" style="flex:1" type="text" name="UF_TAGS" value="${esc(it.UF_TAGS || '')}" placeholder="теги…" />
            <span class="insights__meta" title="ID: ${it.ID}">ID: ${it.ID}</span>
          </footer>
        </article>
      `;
                }

                async function asJsonSafe(res) {
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch {
                        throw new Error('Сервер вернул не-JSON:\n' + text.slice(0, 600));
                    }
                }

                // API calls
                async function apiList(opts = {}) {
                    const params = new URLSearchParams({
                        page: String(opts.page ?? page),
                        pageSize: String(pageSize),
                        q: opts.q ?? q
                    });
                    const res = await fetch(api('list') + '&' + params.toString(), { credentials: 'include' });
                    return asJsonSafe(res);
                }

                async function apiCreate(payload) {
                    const fd = new FormData();
                    fd.append('text', payload.text || '');
                    fd.append('title', payload.title || '');
                    fd.append('tags', payload.tags || '');
                    const res = await fetch(api('create'), { method:'POST', body: fd, credentials:'include' });
                    return asJsonSafe(res);
                }

                async function apiUpdate(id, fields) {
                    const fd = new FormData();
                    fd.append('id', id);
                    Object.entries(fields).forEach(([k,v]) => fd.append(`fields[${k}]`, v ?? ''));
                    const res = await fetch(api('update'), { method:'POST', body: fd, credentials:'include' });
                    return asJsonSafe(res);
                }

                async function apiDelete(id) {
                    const fd = new FormData();
                    fd.append('id', id);
                    const res = await fetch(api('delete'), { method:'POST', body: fd, credentials:'include' });
                    return asJsonSafe(res);
                }

                async function apiTogglePin(id) {
                    const fd = new FormData();
                    fd.append('id', id);
                    const res = await fetch(api('togglePin'), { method:'POST', body: fd, credentials:'include' });
                    return asJsonSafe(res);
                }

                // Data flow
                async function fetchList({ reset = false } = {}) {
                    if (loading) return;
                    loading = true; showError('');
                    try {
                        const json = await apiList({ page, q });
                        if (!json.ok) throw new Error(json.error || 'Ошибка');

                        total = json.total ?? 0;
                        if (reset) listEl.innerHTML = '';
                        const html = (json.items || []).map(renderCard).join('');
                        listEl.insertAdjacentHTML('beforeend', html);
                        setMoreVisibility();
                    } catch (e) {
                        showError(e.message);
                    } finally {
                        loading = false;
                    }
                }

                // Events
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(form);
                    const text = (fd.get('text') || '').toString().trim();
                    if (!text) return;

                    const btn = form.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    try {
                        const resp = await apiCreate({
                            title: (fd.get('title') || '').toString(),
                            text,
                            tags: (fd.get('tags') || '').toString()
                        });
                        if (!resp.ok) throw new Error(resp.error || 'Ошибка');
                        form.reset();
                        page = 1;
                        await fetchList({ reset: true });
                    } catch (err) {
                        showError(err.message);
                    } finally {
                        btn.disabled = false;
                    }
                });

                const doSearch = async () => {
                    q = searchInput.value.trim();
                    page = 1;
                    await fetchList({ reset: true });
                };
                searchBtn.addEventListener('click', doSearch);
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
                });

                moreBtn.addEventListener('click', async () => {
                    page += 1;
                    await fetchList({ reset: false });
                });

                // Делегирование: удалить/закрепить/обновление по blur
                listEl.addEventListener('click', async (e) => {
                    const card = e.target.closest('.insights-card');
                    if (!card) return;
                    const id = card.getAttribute('data-id');

                    // Удалить
                    if (e.target.matches('[data-role="delete"]')) {
                        if (!confirm('Удалить инсайт?')) return;
                        try {
                            const r = await apiDelete(id);
                            if (!r.ok) throw new Error(r.error || 'Ошибка');
                            card.remove();
                            total = Math.max(0, total - 1);
                            setMoreVisibility();
                        } catch (err) {
                            showError(err.message);
                        }
                    }

                    // Закрепить
                    if (e.target.classList.contains('js-pin')) {
                        e.target.disabled = true;
                        try {
                            const r = await apiTogglePin(id);
                            if (!r.ok) throw new Error(r.error || 'Ошибка');
                            // Перезапрашиваем — изменится сортировка
                            page = 1;
                            await fetchList({ reset: true });
                        } catch (err) {
                            showError(err.message);
                        } finally {
                            e.target.disabled = false;
                        }
                    }
                });

                listEl.addEventListener('focusout', async (e) => {
                    const el = e.target;
                    if (!el.matches('input[name="UF_TITLE"], textarea[name="UF_TEXT"], input[name="UF_TAGS"]')) return;

                    const card = el.closest('.insights-card');
                    if (!card) return;
                    const id = card.getAttribute('data-id');

                    const title = card.querySelector('input[name="UF_TITLE"]').value;
                    const text  = card.querySelector('textarea[name="UF_TEXT"]').value;
                    const tags  = card.querySelector('input[name="UF_TAGS"]').value;

                    try {
                        const r = await apiUpdate(id, { UF_TITLE: title, UF_TEXT: text, UF_TAGS: tags });
                        if (!r.ok) throw new Error(r.error || 'Ошибка');
                    } catch (err) {
                        showError(err.message);
                    }
                });

                // First load
                fetchList({ reset: true });
            }
        })();
    </script>


<?
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
?>