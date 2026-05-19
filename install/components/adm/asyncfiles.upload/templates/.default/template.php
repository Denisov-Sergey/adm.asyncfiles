<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>

<div class="asyncfiles-upload" id="asyncUploadComponent">
    <h3 class="asyncfiles-upload__title">Загрузка файла для обработки</h3>

    <form id="asyncUploadForm" enctype="multipart/form-data">
        <input type="hidden" name="sessid" value="<?= htmlspecialcharsbx($arResult['SESSID']) ?>">

        <!-- Зона Drag & Drop -->
        <div class="asyncfiles-upload__dropzone" id="asyncDropzone">
            <svg class="asyncfiles-upload__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 16V4m0 0l-4 4m4-4l4 4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 17l.621 2.485A2 2 0 004.561 21h14.878a2 2 0 001.94-1.515L22 17" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="asyncfiles-upload__dropzone-text">
                Перетащите файл сюда или <strong>выберите вручную</strong>
            </div>
            <input
                type="file"
                id="asyncFileInput"
                name="file"
                required
                class="asyncfiles-upload__file-input"
            >
        </div>

        <!-- Информация о выбранном файле -->
        <div class="asyncfiles-upload__file-info" id="asyncFileInfo">
            <span class="asyncfiles-upload__file-name" id="asyncFileName"></span>
            <span class="asyncfiles-upload__file-size" id="asyncFileSize"></span>
            <button type="button" class="asyncfiles-upload__file-remove" id="asyncFileRemove" title="Удалить файл">&times;</button>
        </div>

        <!-- Прогресс-бар -->
        <div class="asyncfiles-upload__progress" id="asyncProgress">
            <div class="asyncfiles-upload__progress-bar-bg">
                <div class="asyncfiles-upload__progress-bar" id="asyncProgressBar"></div>
            </div>
            <div class="asyncfiles-upload__progress-text" id="asyncProgressText">0%</div>
        </div>

        <button type="submit" id="asyncSubmitBtn" class="asyncfiles-upload__submit">
            Загрузить и отправить в очередь
        </button>
    </form>

    <div class="asyncfiles-upload__status" id="asyncUploadStatus"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    var form = document.getElementById('asyncUploadForm');
    var fileInput = document.getElementById('asyncFileInput');
    var dropzone = document.getElementById('asyncDropzone');
    var fileInfo = document.getElementById('asyncFileInfo');
    var fileName = document.getElementById('asyncFileName');
    var fileSize = document.getElementById('asyncFileSize');
    var fileRemove = document.getElementById('asyncFileRemove');
    var progressBlock = document.getElementById('asyncProgress');
    var progressBar = document.getElementById('asyncProgressBar');
    var progressText = document.getElementById('asyncProgressText');
    var statusDiv = document.getElementById('asyncUploadStatus');
    var submitBtn = document.getElementById('asyncSubmitBtn');

    /**
     * Форматирует размер файла в человекочитаемый вид
     *
     * @param {number} bytes
     * @return {string}
     */
    function formatSize(bytes) {
        if (bytes === 0) return '0 B';
        var units = ['B', 'KB', 'MB', 'GB'];
        var power = Math.floor(Math.log(bytes) / Math.log(1024));
        power = Math.min(power, units.length - 1);
        return (bytes / Math.pow(1024, power)).toFixed(1) + ' ' + units[power];
    }

    /**
     * Экранирует HTML-сущности для защиты от XSS
     *
     * @param {string} text
     * @return {string}
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /** Обновляет отображение информации о выбранном файле */
    function updateFileInfo() {
        if (fileInput.files && fileInput.files.length > 0) {
            var file = fileInput.files[0];
            fileName.textContent = file.name;
            fileSize.textContent = formatSize(file.size);
            fileInfo.classList.add('asyncfiles-upload__file-info--visible');
            dropzone.classList.add('asyncfiles-upload__dropzone--has-file');
        } else {
            fileInfo.classList.remove('asyncfiles-upload__file-info--visible');
            dropzone.classList.remove('asyncfiles-upload__dropzone--has-file');
        }
    }

    /** Сбрасывает статус-сообщение */
    function hideStatus() {
        statusDiv.classList.remove(
            'asyncfiles-upload__status--visible',
            'asyncfiles-upload__status--success',
            'asyncfiles-upload__status--error'
        );
    }

    /**
     * Показывает статус-сообщение
     *
     * @param {string} message
     * @param {string} type 'success' или 'error'
     */
    function showStatus(message, type) {
        statusDiv.textContent = message;
        statusDiv.classList.add(
            'asyncfiles-upload__status--visible',
            'asyncfiles-upload__status--' + type
        );
    }

    // Drag & Drop обработчики
    ['dragenter', 'dragover'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('asyncfiles-upload__dropzone--active');
        });
    });

    ['dragleave', 'drop'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('asyncfiles-upload__dropzone--active');
        });
    });

    dropzone.addEventListener('drop', function(e) {
        var droppedFiles = e.dataTransfer.files;
        if (droppedFiles.length > 0) {
            fileInput.files = droppedFiles;
            updateFileInfo();
        }
    });

    fileInput.addEventListener('change', updateFileInfo);

    // Удаление выбранного файла
    fileRemove.addEventListener('click', function() {
        fileInput.value = '';
        updateFileInfo();
        hideStatus();
    });

    // Отправка формы через XMLHttpRequest (для прогресс-бара)
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!fileInput.files || !fileInput.files.length) {
            showStatus('Выберите файл для загрузки', 'error');
            return;
        }

        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();

        submitBtn.disabled = true;
        submitBtn.textContent = 'Загрузка...';
        hideStatus();
        progressBlock.classList.add('asyncfiles-upload__progress--visible');
        progressBar.style.width = '0%';
        progressText.textContent = '0%';

        // Отслеживание прогресса загрузки
        xhr.upload.addEventListener('progress', function(event) {
            if (event.lengthComputable) {
                var percent = Math.round((event.loaded / event.total) * 100);
                progressBar.style.width = percent + '%';
                progressText.textContent = percent + '%';
            }
        });

        xhr.addEventListener('load', function() {
            progressBlock.classList.remove('asyncfiles-upload__progress--visible');

            try {
                var data = JSON.parse(xhr.responseText);
                if (data.status === 'success') {
                    showStatus(
                        'Файл успешно загружен! Задача #' + data.data.task_id + ' поставлена в очередь.',
                        'success'
                    );
                    form.reset();
                    updateFileInfo();

                    // Обновляем список файлов, если компонент присутствует на странице
                    if (typeof window.refreshAsyncFilesList === 'function') {
                        window.refreshAsyncFilesList();
                    }
                } else {
                    var errorMsg = data.errors ? data.errors.join(', ') : 'Неизвестная ошибка';
                    showStatus('Ошибка: ' + errorMsg, 'error');
                }
            } catch (parseError) {
                showStatus('Ошибка обработки ответа сервера', 'error');
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Загрузить и отправить в очередь';
        });

        xhr.addEventListener('error', function() {
            progressBlock.classList.remove('asyncfiles-upload__progress--visible');
            showStatus('Ошибка сети при загрузке файла', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Загрузить и отправить в очередь';
        });

        xhr.open('POST', '<?= CUtil::JSEscape($arResult["AJAX_URL"]) ?>');
        xhr.send(formData);
    });
});
</script>
