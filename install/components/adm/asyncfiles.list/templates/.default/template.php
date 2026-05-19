<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>

<div class="asyncfiles-list" id="asyncListComponent">
    <div class="asyncfiles-list__header">
        <h3 class="asyncfiles-list__title">Загруженные файлы</h3>
        <button type="button" class="asyncfiles-list__refresh" id="asyncRefreshBtn" onclick="window.refreshAsyncFilesList()">
            <svg class="asyncfiles-list__refresh-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4v5h5M20 20v-5h-5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M20.49 9A9 9 0 005.64 5.64L4 9m16 6l-1.64 3.36A9 9 0 014.51 15" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Обновить
        </button>
    </div>

    <table class="asyncfiles-list__table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Файл</th>
                <th>Размер</th>
                <th>Статус</th>
                <th>Дата загрузки</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody id="asyncListBody">
            <tr>
                <td colspan="6" class="asyncfiles-list__empty">Загрузка данных...</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
/**
 * Модуль отображения списка загруженных файлов.
 *
 * Загружает данные через AJAX, отображает в таблице с безопасным
 * созданием DOM-элементов (защита от XSS). Поддерживает автоматический
 * поллинг каждые 5 секунд.
 */
(function() {
    'use strict';

    var ajaxUrl = '<?= CUtil::JSEscape($arResult["AJAX_URL"]) ?>';
    var tbody = document.getElementById('asyncListBody');
    var refreshBtn = document.getElementById('asyncRefreshBtn');
    var pollingInterval = null;
    var isLoading = false;

    /**
     * Безопасно создаёт текстовый TD-элемент (защита от XSS).
     *
     * @param {string} text Текст для отображения
     * @param {string} className Дополнительный CSS-класс
     * @return {HTMLTableCellElement}
     */
    function createTextCell(text, className) {
        var td = document.createElement('td');
        td.textContent = text;
        if (className) {
            td.className = className;
        }
        return td;
    }

    /**
     * Создаёт ячейку со статус-бейджем.
     *
     * @param {Object} item Объект задачи
     * @return {HTMLTableCellElement}
     */
    function createStatusCell(item) {
        var td = document.createElement('td');
        var badge = document.createElement('span');
        badge.className = 'asyncfiles-list__status asyncfiles-list__status--' + item.status;

        var dot = document.createElement('span');
        dot.className = 'asyncfiles-list__status-dot';
        badge.appendChild(dot);

        var label = document.createTextNode(item.status_label || item.status);
        badge.appendChild(label);
        td.appendChild(badge);

        return td;
    }

    /**
     * Создаёт ячейку с кнопкой скачивания.
     *
     * @param {Object} item Объект задачи
     * @return {HTMLTableCellElement}
     */
    function createDownloadCell(item) {
        var td = document.createElement('td');

        if (item.download_url) {
            var link = document.createElement('a');
            link.href = item.download_url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.className = 'asyncfiles-list__download';

            var icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.setAttribute('class', 'asyncfiles-list__download-icon');
            icon.setAttribute('viewBox', '0 0 24 24');
            icon.setAttribute('fill', 'none');
            icon.setAttribute('stroke', 'currentColor');
            icon.setAttribute('stroke-width', '2');

            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', 'M12 4v12m0 0l-4-4m4 4l4-4M4 17v2a1 1 0 001 1h14a1 1 0 001-1v-2');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            icon.appendChild(path);
            link.appendChild(icon);

            var text = document.createTextNode('Скачать');
            link.appendChild(text);

            td.appendChild(link);
        } else {
            td.textContent = '—';
            td.style.color = '#94a3b8';
        }

        return td;
    }

    /**
     * Загружает и отображает список файлов.
     * Все данные отображаются через textContent / createElement — никакого innerHTML.
     */
    window.refreshAsyncFilesList = function() {
        if (isLoading) return;
        isLoading = true;

        refreshBtn.classList.add('asyncfiles-list__refresh--loading');

        fetch(ajaxUrl, { method: 'GET' })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                // Полная очистка tbody безопасным способом
                while (tbody.firstChild) {
                    tbody.removeChild(tbody.firstChild);
                }

                if (data.status === 'success' && data.data && data.data.length > 0) {
                    data.data.forEach(function(item) {
                        var tr = document.createElement('tr');

                        tr.appendChild(createTextCell(String(item.id)));
                        tr.appendChild(createTextCell(item.file_name, 'asyncfiles-list__filename'));
                        tr.appendChild(createTextCell(item.file_size));
                        tr.appendChild(createStatusCell(item));
                        tr.appendChild(createTextCell(item.created_at || '—'));
                        tr.appendChild(createDownloadCell(item));

                        tbody.appendChild(tr);
                    });
                } else {
                    var emptyTr = document.createElement('tr');
                    var emptyTd = document.createElement('td');
                    emptyTd.colSpan = 6;
                    emptyTd.className = 'asyncfiles-list__empty';
                    emptyTd.textContent = 'Нет загруженных файлов';
                    emptyTr.appendChild(emptyTd);
                    tbody.appendChild(emptyTr);
                }
            })
            .catch(function(error) {
                // Тихая обработка ошибок — не показываем пользователю
            })
            .finally(function() {
                isLoading = false;
                refreshBtn.classList.remove('asyncfiles-list__refresh--loading');
            });
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.refreshAsyncFilesList();

        // Автоматический поллинг каждые 5 секунд
        pollingInterval = setInterval(window.refreshAsyncFilesList, 5000);
    });
})();
</script>
