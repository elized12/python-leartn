import start, { E_EDITOR_THEME } from 'monaco-python';

function editorConfig() {
    return window.taskEditorConfig || {};
}

function normalizeLibraryName(library) {
    return String(library || '').trim().toLowerCase();
}

function buildLibrarySnippets(libraries) {
    const snippets = {};
    const librarySet = new Set((libraries || []).map(normalizeLibraryName).filter(Boolean));

    if (librarySet.has('pandas')) {
        snippets['pandas'] = {
            body: 'import pandas as pd',
            description: 'Import pandas as pd'
        };
        snippets['pandas-read-csv'] = {
            body: [
                'import pandas as pd',
                '',
                'df = pd.read_csv("${1:files/data.csv}")',
                'print(df.head())'
            ],
            description: 'Read CSV with pandas'
        };
        snippets['pandas-read-excel'] = {
            body: [
                'import pandas as pd',
                '',
                'df = pd.read_excel("${1:files/data.xlsx}")',
                'print(df.head())'
            ],
            description: 'Read Excel with pandas'
        };
    }

    if (librarySet.has('numpy')) {
        snippets['numpy'] = {
            body: 'import numpy as np',
            description: 'Import numpy as np'
        };
    }

    if (librarySet.has('openpyxl')) {
        snippets['openpyxl'] = {
            body: 'import openpyxl',
            description: 'Import openpyxl'
        };
    }

    if (librarySet.has('pyarrow')) {
        snippets['pyarrow'] = {
            body: 'import pyarrow as pa',
            description: 'Import pyarrow'
        };
    }

    return snippets;
}

function buildEditorTypesheds(libraries) {
    const librarySet = new Set((libraries || []).map(normalizeLibraryName).filter(Boolean));
    const typesheds = {};

    if (librarySet.has('pandas')) {
        typesheds['stubs/pandas/__init__.pyi'] = [
            'from typing import Any, Iterable, Mapping, Sequence',
            '',
            'class Series:',
            '    @property',
            '    def values(self) -> Any: ...',
            '    def head(self, n: int = 5) -> Series: ...',
            '    def tail(self, n: int = 5) -> Series: ...',
            '    def mean(self, *args: Any, **kwargs: Any) -> Any: ...',
            '    def sum(self, *args: Any, **kwargs: Any) -> Any: ...',
            '    def min(self, *args: Any, **kwargs: Any) -> Any: ...',
            '    def max(self, *args: Any, **kwargs: Any) -> Any: ...',
            '    def unique(self) -> Any: ...',
            '    def value_counts(self, *args: Any, **kwargs: Any) -> Series: ...',
            '    def fillna(self, value: Any = ..., *args: Any, **kwargs: Any) -> Series: ...',
            '    def dropna(self, *args: Any, **kwargs: Any) -> Series: ...',
            '    def astype(self, dtype: Any, *args: Any, **kwargs: Any) -> Series: ...',
            '',
            'class DataFrame:',
            '    @property',
            '    def columns(self) -> Any: ...',
            '    @property',
            '    def shape(self) -> tuple[int, int]: ...',
            '    @property',
            '    def dtypes(self) -> Series: ...',
            '    def head(self, n: int = 5) -> DataFrame: ...',
            '    def tail(self, n: int = 5) -> DataFrame: ...',
            '    def info(self, *args: Any, **kwargs: Any) -> None: ...',
            '    def describe(self, *args: Any, **kwargs: Any) -> DataFrame: ...',
            '    def sort_values(self, by: Any, *args: Any, **kwargs: Any) -> DataFrame: ...',
            '    def groupby(self, by: Any = ..., *args: Any, **kwargs: Any) -> Any: ...',
            '    def merge(self, right: DataFrame, *args: Any, **kwargs: Any) -> DataFrame: ...',
            '    def join(self, other: DataFrame, *args: Any, **kwargs: Any) -> DataFrame: ...',
            '    def drop(self, labels: Any = ..., *args: Any, **kwargs: Any) -> DataFrame: ...',
            '    def dropna(self, *args: Any, **kwargs: Any) -> DataFrame: ...',
            '    def fillna(self, value: Any = ..., *args: Any, **kwargs: Any) -> DataFrame: ...',
            '    def mean(self, *args: Any, **kwargs: Any) -> Series: ...',
            '    def sum(self, *args: Any, **kwargs: Any) -> Series: ...',
            '    def to_csv(self, path_or_buf: Any = ..., *args: Any, **kwargs: Any) -> Any: ...',
            '    def to_excel(self, excel_writer: Any, *args: Any, **kwargs: Any) -> Any: ...',
            '    def __getitem__(self, key: Any) -> Any: ...',
            '    def __setitem__(self, key: Any, value: Any) -> None: ...',
            '',
            'def read_csv(filepath_or_buffer: Any, *args: Any, **kwargs: Any) -> DataFrame: ...',
            'def read_excel(io: Any, *args: Any, **kwargs: Any) -> DataFrame: ...',
            'def read_json(path_or_buf: Any, *args: Any, **kwargs: Any) -> DataFrame: ...',
            'def concat(objs: Iterable[Any], *args: Any, **kwargs: Any) -> DataFrame: ...',
            'def merge(left: DataFrame, right: DataFrame, *args: Any, **kwargs: Any) -> DataFrame: ...',
            'def isna(obj: Any) -> Any: ...',
            'def notna(obj: Any) -> Any: ...',
            'def to_datetime(arg: Any, *args: Any, **kwargs: Any) -> Any: ...',
            '',
        ].join('\n');
    }

    if (librarySet.has('numpy')) {
        typesheds['stubs/numpy/__init__.pyi'] = [
            'from typing import Any, Iterable, Sequence',
            '',
            'class ndarray:',
            '    @property',
            '    def shape(self) -> tuple[int, ...]: ...',
            '    @property',
            '    def dtype(self) -> Any: ...',
            '    def reshape(self, *shape: int) -> ndarray: ...',
            '    def mean(self, *args: Any, **kwargs: Any) -> Any: ...',
            '    def sum(self, *args: Any, **kwargs: Any) -> Any: ...',
            '    def min(self, *args: Any, **kwargs: Any) -> Any: ...',
            '    def max(self, *args: Any, **kwargs: Any) -> Any: ...',
            '    def astype(self, dtype: Any, *args: Any, **kwargs: Any) -> ndarray: ...',
            '',
            'def array(object: Any, dtype: Any = ..., *args: Any, **kwargs: Any) -> ndarray: ...',
            'def zeros(shape: int | Sequence[int], dtype: Any = ..., *args: Any, **kwargs: Any) -> ndarray: ...',
            'def ones(shape: int | Sequence[int], dtype: Any = ..., *args: Any, **kwargs: Any) -> ndarray: ...',
            'def arange(*args: Any, **kwargs: Any) -> ndarray: ...',
            'def linspace(start: Any, stop: Any, num: int = ..., *args: Any, **kwargs: Any) -> ndarray: ...',
            'def mean(a: Any, *args: Any, **kwargs: Any) -> Any: ...',
            'def sum(a: Any, *args: Any, **kwargs: Any) -> Any: ...',
            'def min(a: Any, *args: Any, **kwargs: Any) -> Any: ...',
            'def max(a: Any, *args: Any, **kwargs: Any) -> Any: ...',
            'def sqrt(x: Any, *args: Any, **kwargs: Any) -> Any: ...',
            'def round(a: Any, decimals: int = ..., *args: Any, **kwargs: Any) -> Any: ...',
            '',
        ].join('\n');
    }

    if (librarySet.has('openpyxl')) {
        typesheds['stubs/openpyxl/__init__.pyi'] = [
            'from typing import Any',
            '',
            'class Workbook:',
            '    active: Any',
            '    def save(self, filename: str) -> None: ...',
            '    def create_sheet(self, title: str | None = ...) -> Any: ...',
            '',
            'def load_workbook(filename: str, *args: Any, **kwargs: Any) -> Workbook: ...',
            '',
        ].join('\n');
    }

    if (librarySet.has('pyarrow')) {
        typesheds['stubs/pyarrow/__init__.pyi'] = [
            'from typing import Any',
            '',
            'class Table:',
            '    @staticmethod',
            '    def from_pandas(df: Any, *args: Any, **kwargs: Any) -> Table: ...',
            '    def to_pandas(self, *args: Any, **kwargs: Any) -> Any: ...',
            '',
            'def table(data: Any, *args: Any, **kwargs: Any) -> Table: ...',
            'def array(obj: Any, *args: Any, **kwargs: Any) -> Any: ...',
            '',
        ].join('\n');
    }

    return typesheds;
}

function buildFileSnippets(files) {
    return (files || []).reduce((snippets, file) => {
        if (!file.path) {
            return snippets;
        }

        const key = `open-file-${file.name || file.path}`.replace(/[^a-z0-9_-]/gi, '-').toLowerCase();
        snippets[key] = {
            body: [
                `with open("${file.path}", "r", encoding="utf-8") as file:`,
                '    data = file.read()',
                'print(data[:200])'
            ],
            description: `Open task file ${file.path}`
        };

        if (/\.csv$/i.test(file.path)) {
            snippets[`${key}-pandas`] = {
                body: `pd.read_csv("${file.path}")`,
                description: `Read ${file.path} with pandas`
            };
        }

        return snippets;
    }, {});
}

function buildEditorSnippets() {
    const config = editorConfig();

    return {
        ...buildLibrarySnippets(config.libraries),
        ...buildFileSnippets(config.files),
    };
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

const aiHintCache = new Map();
const aiHintPendingAttempts = new Map();
let currentAiHintRequest = null;

function hideNotification(notificationId) {
    if (!notificationId) {
        return;
    }

    fetch(`/notification/${notificationId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json'
        },
        body: JSON.stringify({})
    }).catch((error) => {
        console.error('Notification hiding failed:', error);
    });
}

function clearOutputBlock() {
    const outputContainer = document.querySelector('.output-container');
    const outputMessageDiv = document.getElementById('output');

    outputContainer.classList.remove(
        'output-success',
        'output-error',
        'output-warning',
        'output-default'
    );

    outputMessageDiv.innerText = '';
}

function createSuccessOutput(description, time, memory) {
    return createResultOutput({
        type: 'success',
        title: 'Accepted',
        subtitle: 'Задача решена',
        description: description || 'Все тесты успешно пройдены.',
        time,
        memory,
    });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function highlightCode(code, language = 'python') {
    if (!window.hljs) {
        return escapeHtml(code);
    }

    try {
        if (language && window.hljs.getLanguage(language)) {
            return window.hljs.highlight(code, { language }).value;
        }

        return window.hljs.highlightAuto(code).value;
    } catch (error) {
        return escapeHtml(code);
    }
}

function renderInlineMarkdown(value) {
    return escapeHtml(value)
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/\*([^*]+)\*/g, '<em>$1</em>')
        .replace(/\[([^\]]+)]\((https?:\/\/[^)\s]+|\/[^)\s]+|#[^)\s]+)\)/g, '<a href="$2">$1</a>');
}

function flushParagraph(lines, html) {
    if (lines.length === 0) {
        return;
    }

    html.push(`<p>${renderInlineMarkdown(lines.join(' '))}</p>`);
    lines.length = 0;
}

function renderCommentMarkdown(markdown) {
    const codeBlocks = [];
    const withoutCode = markdown.replace(/```([a-zA-Z0-9_-]+)?\n([\s\S]*?)```/g, (_, language, code) => {
        const index = codeBlocks.length;
        const normalizedLanguage = (language || 'python').toLowerCase();
        codeBlocks.push(
            `<pre><code class="hljs language-${normalizedLanguage}">${highlightCode(code.trimEnd(), normalizedLanguage)}</code></pre>`
        );

        return `\n@@CODE_BLOCK_${index}@@\n`;
    });

    const html = [];
    const paragraph = [];
    const lines = withoutCode.split(/\r?\n/);

    for (let index = 0; index < lines.length; index++) {
        const line = lines[index];
        const trimmed = line.trim();

        if (!trimmed) {
            flushParagraph(paragraph, html);
            continue;
        }

        const codeMatch = trimmed.match(/^@@CODE_BLOCK_(\d+)@@$/);
        if (codeMatch) {
            flushParagraph(paragraph, html);
            html.push(codeBlocks[Number(codeMatch[1])] || '');
            continue;
        }

        const headingMatch = trimmed.match(/^(#{1,4})\s+(.+)$/);
        if (headingMatch) {
            flushParagraph(paragraph, html);
            html.push(`<h${headingMatch[1].length}>${renderInlineMarkdown(headingMatch[2])}</h${headingMatch[1].length}>`);
            continue;
        }

        if (trimmed.startsWith('> ')) {
            flushParagraph(paragraph, html);
            const quoteLines = [];
            while (index < lines.length && lines[index].trim().startsWith('> ')) {
                quoteLines.push(lines[index].trim().slice(2));
                index++;
            }
            index--;
            html.push(`<blockquote>${renderInlineMarkdown(quoteLines.join(' '))}</blockquote>`);
            continue;
        }

        const unorderedMatch = trimmed.match(/^[-*]\s+(.+)$/);
        if (unorderedMatch) {
            flushParagraph(paragraph, html);
            const items = [];
            while (index < lines.length) {
                const itemMatch = lines[index].trim().match(/^[-*]\s+(.+)$/);
                if (!itemMatch) {
                    break;
                }
                items.push(`<li>${renderInlineMarkdown(itemMatch[1])}</li>`);
                index++;
            }
            index--;
            html.push(`<ul>${items.join('')}</ul>`);
            continue;
        }

        const orderedMatch = trimmed.match(/^\d+\.\s+(.+)$/);
        if (orderedMatch) {
            flushParagraph(paragraph, html);
            const items = [];
            while (index < lines.length) {
                const itemMatch = lines[index].trim().match(/^\d+\.\s+(.+)$/);
                if (!itemMatch) {
                    break;
                }
                items.push(`<li>${renderInlineMarkdown(itemMatch[1])}</li>`);
                index++;
            }
            index--;
            html.push(`<ol>${items.join('')}</ol>`);
            continue;
        }

        paragraph.push(trimmed);
    }

    flushParagraph(paragraph, html);

    return html.join('');
}

function initCommentPreview() {
    const textarea = document.getElementById('comment-content');
    const preview = document.getElementById('comment-preview');
    const previewContent = document.getElementById('comment-preview-content');

    if (!textarea || !preview || !previewContent) {
        return;
    }

    const updatePreview = () => {
        const value = textarea.value.trim();
        preview.hidden = value === '';
        previewContent.innerHTML = value ? renderCommentMarkdown(value) : '';
        highlightMarkdownBlocks(previewContent);
    };

    textarea.addEventListener('input', updatePreview);
    updatePreview();
}

function openAiHintDrawer() {
    const drawer = document.getElementById('aiHintDrawer');
    const backdrop = document.getElementById('aiHintBackdrop');

    if (!drawer || !backdrop) {
        return;
    }

    backdrop.hidden = false;
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
}

function closeAiHintDrawer() {
    const drawer = document.getElementById('aiHintDrawer');
    const backdrop = document.getElementById('aiHintBackdrop');

    if (!drawer || !backdrop) {
        return;
    }

    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    backdrop.hidden = true;
}

function setAiHintContent(content, type = 'default') {
    const container = document.getElementById('aiHintContent');

    if (!container) {
        return;
    }

    container.classList.toggle('is-loading', type === 'loading');
    container.classList.toggle('is-error', type === 'error');
    container.classList.remove('is-streaming');
    container.dataset.rawMarkdown = '';
    container.innerHTML = renderCommentMarkdown(content);
    highlightMarkdownBlocks(container);
}

function setAiHintLoading() {
    const container = document.getElementById('aiHintContent');

    if (!container) {
        return;
    }

    container.dataset.rawMarkdown = '';
    container.classList.add('is-loading');
    container.classList.remove('is-error', 'is-streaming');
    container.innerHTML = `
        <div class="ai-hint-thinking">
            <span class="ai-hint-pulse" aria-hidden="true"></span>
            <div>
                <strong>ИИ читает вашу последнюю попытку</strong>
                <p>Сейчас появится разбор. Ответ будет дорисовываться по мере генерации.</p>
            </div>
        </div>
    `;
}

function setAiHintQueued(message = 'Подсказка поставлена в очередь. Ожидаем свободную нейросеть...') {
    const container = document.getElementById('aiHintContent');

    if (!container) {
        return;
    }

    container.dataset.rawMarkdown = '';
    container.classList.add('is-loading');
    container.classList.remove('is-error', 'is-streaming');
    container.innerHTML = `
        <div class="ai-hint-thinking">
            <span class="ai-hint-pulse" aria-hidden="true"></span>
            <div>
                <strong>Вы в очереди</strong>
                <p>${escapeHtml(message)}</p>
            </div>
        </div>
    `;
}

function appendAiHintContent(content) {
    const container = document.getElementById('aiHintContent');

    if (!container) {
        return;
    }

    container.dataset.rawMarkdown = `${container.dataset.rawMarkdown || ''}${content}`;
    container.classList.remove('is-loading', 'is-error');
    container.classList.add('is-streaming');
    container.innerHTML = renderCommentMarkdown(container.dataset.rawMarkdown);
    highlightMarkdownBlocks(container);
    container.scrollTop = container.scrollHeight;
}

function finishAiHintStream() {
    const container = document.getElementById('aiHintContent');

    if (!container) {
        return;
    }

    container.classList.remove('is-loading', 'is-streaming');
}

function setAiHintAvailable(attemptId = null) {
    document.querySelectorAll('.ai-hint-button').forEach((button) => {
        button.disabled = false;
        if (attemptId) {
            button.dataset.aiAttemptId = attemptId;
        }
    });
}

function setAiHintUnavailable() {
    document.querySelectorAll('.ai-hint-button').forEach((button) => {
        button.disabled = true;
        delete button.dataset.aiAttemptId;
    });
}

async function requestAiHint(attemptId = null) {
    const drawer = document.getElementById('aiHintDrawer');

    if (!drawer?.dataset.aiHintUrl) {
        return;
    }

    if (!attemptId) {
        openAiHintDrawer();
        setAiHintContent('Подсказка появится после новой неудачной отправки решения на этой странице.', 'error');
        return;
    }

    openAiHintDrawer();
    const cacheKey = String(attemptId);
    if (aiHintCache.has(cacheKey)) {
        setAiHintContent(aiHintCache.get(cacheKey));
        return;
    }

    if (aiHintPendingAttempts.has(cacheKey)) {
        setAiHintQueued('Подсказка уже ожидает обработки. Как только worker освободится, ответ начнет появляться здесь.');
        return;
    }

    setAiHintQueued();

    try {
        const response = await fetch(drawer.dataset.aiHintUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(attemptId ? { attempt_id: Number(attemptId) } : {}),
        });

        const data = await response.json();

        if (!response.ok || data.status === false) {
            setAiHintContent(data.message || 'Не удалось получить подсказку.', 'error');
            return;
        }

        currentAiHintRequest = {
            requestId: data.request_id,
            attemptId: Number(data.attempt_id || attemptId),
            fullHint: '',
            receivedContent: false,
        };
        aiHintPendingAttempts.set(cacheKey, data.request_id);
        setAiHintQueued(data.message);
    } catch (error) {
        console.error('AI hint request failed:', error);
        setAiHintContent('Не получилось поставить подсказку в очередь. Проверьте соединение и попробуйте еще раз.', 'error');
    }
}

function handleAiHintEvent(event) {
    if (Number(event.task_id) !== Number(window.taskId)) {
        return;
    }

    if (!currentAiHintRequest || event.request_id !== currentAiHintRequest.requestId) {
        return;
    }

    const cacheKey = String(currentAiHintRequest.attemptId);

    if (event.state === 'started') {
        setAiHintLoading();
        return;
    }

    if (event.state === 'chunk' && event.content) {
        if (!currentAiHintRequest.receivedContent) {
            const container = document.getElementById('aiHintContent');
            if (container) {
                container.innerHTML = '';
            }
            currentAiHintRequest.receivedContent = true;
        }

        currentAiHintRequest.fullHint += event.content;
        appendAiHintContent(event.content);
        return;
    }

    if (event.state === 'done') {
        aiHintPendingAttempts.delete(cacheKey);
        aiHintCache.set(cacheKey, currentAiHintRequest.fullHint);
        finishAiHintStream();
        currentAiHintRequest = null;
        return;
    }

    if (event.state === 'error') {
        aiHintPendingAttempts.delete(cacheKey);
        setAiHintContent(event.message || 'Не удалось получить подсказку.', 'error');
        currentAiHintRequest = null;
    }
}

function highlightMarkdownBlocks(root = document) {
    if (!window.hljs) {
        return;
    }

    root.querySelectorAll('.markdown-content pre code').forEach((codeBlock) => {
        if (!codeBlock.className.includes('language-')) {
            codeBlock.classList.add('language-python');
        }

        codeBlock.classList.add('hljs');
        codeBlock.removeAttribute('data-highlighted');
        window.hljs.highlightElement(codeBlock);
    });
}

function activateTab(tabName) {
    document.querySelectorAll('.task-tab').forEach((tab) => {
        tab.classList.toggle('active', tab.dataset.tab === tabName);
    });

    document.querySelectorAll('.tab-panel').forEach((panel) => {
        panel.classList.toggle('active', panel.dataset.tabPanel === tabName);
    });
}

function markTaskSolved() {
    document.body.classList.add('task-solved', 'just-solved');
    document.querySelector('.task-summary')?.classList.add('is-solved');
    document.querySelector('.author-solution-card')?.classList.remove('is-locked');
    document.querySelector('.author-solution-card')?.classList.add('is-open');

    document.querySelectorAll('.task-tab[data-tab="author-solution"]').forEach((tab) => {
        tab.classList.remove('is-locked');
        tab.querySelector('small')?.remove();
    });

    document.querySelectorAll('.task-tab[data-tab="best-solutions"]').forEach((tab) => {
        tab.classList.remove('is-locked');
        tab.querySelector('small')?.remove();
    });

    const summaryStatus = document.querySelector('.summary-row .locked-badge');
    if (summaryStatus) {
        summaryStatus.className = 'solved-badge';
        summaryStatus.textContent = 'Решено';
    }

    if (!document.querySelector('.mini-solved')) {
        const badge = document.createElement('span');
        badge.className = 'mini-solved';
        badge.textContent = 'решено';
        document.querySelector('.editor-header')?.appendChild(badge);
    }

    setTimeout(() => document.body.classList.remove('just-solved'), 2600);
}

function addAttemptToList(attempt) {
    const attemptsList = document.querySelector('.attempts-list');
    if (!attemptsList) {
        return;
    }

    attemptsList.querySelector('.empty-comments')?.remove();

    const item = document.createElement('article');
    const isSuccess = attempt.status === 'Completed';
    item.className = `attempt-item ${isSuccess ? 'attempt-success' : 'attempt-failed'}`;

    const statusText = isSuccess ? 'Accepted' : attempt.status;
    const time = attempt.execution_time_s ?? '—';
    const memory = attempt.peak_memory_usage_mb ?? '—';

    item.innerHTML = `
        <div>
            <strong>${escapeHtml(statusText)}</strong>
            <span>только что</span>
        </div>
        <p>${escapeHtml(attempt.description || '')}</p>
        <div class="attempt-metrics">
            <span>${escapeHtml(time)} сек.</span>
            <span>${escapeHtml(memory)} МБ</span>
        </div>
    `;

    attemptsList.prepend(item);

    const counter = document.querySelector('.task-tab[data-tab="attempts"] small');
    if (counter) {
        counter.textContent = String(Number(counter.textContent || 0) + 1);
    }
}

function statusTitle(status) {
    switch (status) {
        case 'Incorrect result':
            return 'Wrong Answer';
        case 'Memory limit':
            return 'Memory Limit';
        case 'Time limit':
            return 'Time Limit';
        case 'Error':
            return 'Runtime Error';
        default:
            return 'Not Accepted';
    }
}

function statusSubtitle(status) {
    switch (status) {
        case 'Incorrect result':
            return 'Ответ не совпал с ожидаемым';
        case 'Memory limit':
            return 'Превышен лимит памяти';
        case 'Time limit':
            return 'Превышен лимит времени';
        case 'Error':
            return 'Ошибка выполнения';
        default:
            return 'Задача пока не решена';
    }
}

function createResultOutput({ type, title, subtitle, description, time, memory }) {
    const isSuccess = type === 'success';
    const icon = isSuccess ? '✓' : '!';

    return `
        <div class="result-card result-${type}">
            <div class="result-main">
                <span class="result-icon">${icon}</span>
                <div>
                    <strong>${escapeHtml(title)}</strong>
                    <p>${escapeHtml(subtitle)}</p>
                </div>
            </div>
            <div class="result-metrics">
                <span><b>Время</b>${escapeHtml(time ?? '—')} сек.</span>
                <span><b>Память</b>${escapeHtml(memory ?? '—')} МБ</span>
            </div>
            ${description ? `<div class="result-message">${escapeHtml(description)}</div>` : ''}
        </div>
    `;
}

function createErrorOutput(status, description, time, memory) {
    return createResultOutput({
        type: status === 'Time limit' || status === 'Memory limit' ? 'warning' : 'error',
        title: statusTitle(status),
        subtitle: statusSubtitle(status),
        description,
        time,
        memory,
    });
}

function handleAttempt(attempt) {
    if (Number(attempt.task_id) !== Number(window.taskId)) {
        return;
    }

    const outputContainer = document.querySelector('.output-container');
    const outputMessageDiv = document.getElementById('output');

    clearOutputBlock();
    addAttemptToList(attempt);
    hideNotification(attempt.notification_id);

    switch (attempt.status) {
        case 'Completed':
            outputContainer.classList.add('output-success');
            outputMessageDiv.innerHTML = createSuccessOutput(attempt.description, attempt.execution_time_s, attempt.peak_memory_usage_mb);
            setAiHintUnavailable();
            markTaskSolved();
            loadAuthorSolution();
            activateTab('author-solution');
            break;

        case 'Error':
        case 'Incorrect result':
        case 'Memory limit':
        case 'Time limit':
            outputContainer.classList.add('output-error');
            outputMessageDiv.innerHTML = createErrorOutput(attempt.status, attempt.description, attempt.execution_time_s, attempt.peak_memory_usage_mb);
            setAiHintAvailable(attempt.id);
            break;
    }
}

async function loadAuthorSolution() {
    const card = document.querySelector('.author-solution-card');
    const solutionBlock = card?.querySelector('.solution-code-block');
    const solutionCode = solutionBlock?.querySelector('code');
    const lockedMessage = card?.querySelector('.locked-solution-message');

    if (!card || !solutionBlock || !solutionCode || !card.dataset.authorSolutionUrl) {
        return;
    }

    try {
        const response = await fetch(card.dataset.authorSolutionUrl, {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        solutionCode.textContent = data.solution || 'Авторское решение пока не заполнено.';
        solutionBlock.style.display = 'grid';
        if (window.hljs) {
            solutionCode.removeAttribute('data-highlighted');
            window.hljs.highlightElement(solutionCode);
        }
        lockedMessage?.remove();
        card.querySelector('.locked-solution')?.remove();
    } catch (error) {
        console.error('Author solution loading failed:', error);
    }
}

function showAnimationWaitRequest() {
    clearOutputBlock();

    const buttonRun = document.querySelector('.solution-form .run-button');
    buttonRun.disabled = true;

    const animationSpinner = document.getElementById('spinner-output');
    animationSpinner.style.display = 'block';
}

function hideAnimationWaitRequest() {
    const buttonRun = document.querySelector('.solution-form .run-button');
    buttonRun.disabled = false;

    const animationSpinner = document.getElementById('spinner-output');
    animationSpinner.style.display = 'none';
}

function showValidationError(error) {
    const outputContainer = document.querySelector('.output-container');
    const outputMessageDiv = document.getElementById('output');

    clearOutputBlock();
    outputContainer.classList.add('output-error');

    let errorMessage = error.message || 'Ошибка валидации';

    if (error.errors && error.errors.code) {
        errorMessage += '\n' + error.errors.code.join('\n');
    }

    outputMessageDiv.innerText = errorMessage;
}

async function initEditor() {
    const loader = document.getElementById('spinner');
    const textEditor = document.getElementById('text-editor');
    const textarea = document.querySelector('textarea[name="code"]');
    const initialCode = editorConfig().starterCode || '';

    try {
        const wrapper = await start(textEditor, {
            theme: E_EDITOR_THEME.LIGHT_VS,
            value: initialCode,
            typesheds: buildEditorTypesheds(editorConfig().libraries),
            snippets: buildEditorSnippets()
        });

        const editor = wrapper.getEditor();
        if (initialCode && !editor.getValue()) {
            editor.setValue(initialCode);
        }
        textarea.value = editor.getValue();

        editor.onDidChangeModelContent(() => {
            textarea.value = editor.getValue();
        });

        loader.style.display = 'none';
    } catch (error) {
        console.error('Editor initialization failed:', error);
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    const form = document.querySelector('.solution-form');
    const submitButton = form.querySelector('.run-button');

    if (window.hljs) {
        window.hljs.highlightAll();
        highlightMarkdownBlocks();
    }

    initEditor();
    initCommentPreview();

    document.querySelectorAll('.ai-hint-button').forEach((button) => {
        button.addEventListener('click', () => requestAiHint(button.dataset.aiAttemptId || null));
    });

    document.getElementById('aiHintClose')?.addEventListener('click', closeAiHintDrawer);
    document.getElementById('aiHintBackdrop')?.addEventListener('click', closeAiHintDrawer);

    document.querySelectorAll('.task-tab').forEach((tab) => {
        tab.addEventListener('click', () => activateTab(tab.dataset.tab));
    });

    submitButton.addEventListener('click', async function (e) {
        e.preventDefault();
        showAnimationWaitRequest();
        setAiHintUnavailable();

        const code = form.querySelector('textarea[name="code"]').value;
        const csrf = form.querySelector('input[name="_token"]').value;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code })
            });

            if (!response.ok) {
                const errorData = await response.json();
                showValidationError(errorData);
                hideAnimationWaitRequest();
                return;
            }

            const result = await response.json();

        } catch (error) {
            console.error('Request failed:', error);
            showValidationError({
                message: 'Произошла ошибка при отправке запроса',
                errors: {}
            });
            hideAnimationWaitRequest();
        }
    });

    const channel = `user.task.${window.userId}`;
    window.Echo.private(channel).listen('.user.task', (event) => {
        console.log(event);
        handleAttempt(event);
        hideAnimationWaitRequest();
    }).listen('.user.ai-hint', (event) => {
        handleAiHintEvent(event);
    });
});
