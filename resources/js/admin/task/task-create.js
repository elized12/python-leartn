import { marked } from 'marked';

marked.setOptions({
    breaks: true,
    gfm: true,
    headerIds: true,
    smartypants: true,
});

function sanitizeHtml(input, allowedTags = ['b', 'i', 'em', 'strong', 'code']) {
    const doc = new DOMParser().parseFromString(input || '', 'text/html');
    const allowed = new Set(allowedTags);
    const allowedAttributes = {
        a: ['href', 'title'],
        img: ['src', 'alt', 'title'],
    };

    function clean(node) {
        for (const child of Array.from(node.childNodes)) {
            if (child.nodeType === Node.ELEMENT_NODE) {
                const tagName = child.tagName.toLowerCase();
                if (!allowed.has(tagName)) {
                    child.replaceWith(...child.childNodes);
                } else {
                    for (const attribute of Array.from(child.attributes)) {
                        const attributeName = attribute.name.toLowerCase();
                        const isAllowed = (allowedAttributes[tagName] || []).includes(attributeName);
                        const isUnsafeUrl = ['href', 'src'].includes(attributeName)
                            && /^(javascript|data):/i.test(attribute.value.trim());

                        if (!isAllowed || isUnsafeUrl) {
                            child.removeAttribute(attribute.name);
                        }
                    }

                    clean(child);
                }
            }
        }
    }

    clean(doc.body);
    return doc.body.innerHTML;
}

function renderMarkdown(markdown) {
    if (!markdown || !markdown.trim()) {
        return '';
    }

    try {
        const html = marked.parse(markdown);
        return sanitizeHtml(html, [
            'a', 'blockquote', 'br', 'code', 'em', 'h1', 'h2', 'h3', 'h4', 'hr',
            'li', 'ol', 'p', 'pre', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'ul',
        ]);
    } catch (error) {
        console.error('Error parsing markdown:', error);
        return sanitizeHtml(markdown);
    }
}

function highlightMarkdownPreview(root) {
    if (!root || !window.hljs) {
        return;
    }

    root.querySelectorAll('pre code').forEach((codeBlock) => {
        if (!Array.from(codeBlock.classList).some((className) => className.startsWith('language-'))) {
            codeBlock.classList.add('language-python');
        }

        codeBlock.classList.add('hljs');
        codeBlock.removeAttribute('data-highlighted');
        window.hljs.highlightElement(codeBlock);
    });
}

function bindModeCards(inputName, blockId = null) {
    const inputs = document.querySelectorAll(`input[name="${inputName}"]`);
    const block = blockId ? document.getElementById(blockId) : null;

    function update() {
        inputs.forEach((input) => {
            input.closest('.mode-option')?.classList.toggle('active', input.checked);
        });

        if (block) {
            const selected = document.querySelector(`input[name="${inputName}"]:checked`)?.value;
            block.style.display = ['runner', 'custom'].includes(selected) ? 'block' : 'none';
        }
    }

    inputs.forEach((input) => input.addEventListener('change', update));
    update();
}

document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('task-title');
    const difficultyInput = document.getElementById('task-difficulty');
    const selectedCategories = document.getElementById('selected-categories');
    const descriptionInput = document.getElementById('task-description');
    const exampleInput = document.getElementById('task-example');
    const environmentSelect = document.getElementById('execution-environment');
    const inputModeSelect = document.getElementById('input-mode');
    const timeLimitInput = document.getElementById('time-limit');
    const memoryLimitInput = document.getElementById('memory-limit');
    const testsFileInput = document.getElementById('tests-json-file');
    const testsContentInput = document.getElementById('tests-json-content');
    const taskFilesInput = document.getElementById('task-files');
    const taskFilesVisibility = document.getElementById('task-files-visibility');

    const previewTitle = document.getElementById('preview-title');
    const previewCategory = document.getElementById('preview-category');
    const previewDifficulty = document.getElementById('preview-difficulty');
    const previewDescription = document.getElementById('preview-description');
    const previewExample = document.getElementById('preview-task-example');
    const previewEnvironment = document.getElementById('preview-environment');
    const previewEntrypoint = document.getElementById('preview-entrypoint');
    const previewInputMode = document.getElementById('preview-input-mode');
    const previewChecker = document.getElementById('preview-checker');
    const previewLimits = document.getElementById('preview-limits');
    const previewRunCommand = document.getElementById('preview-run-command');
    const previewCheckerText = document.getElementById('preview-checker-text');

    bindModeCards('runner_mode', 'runner-file-block');
    bindModeCards('checker_type', 'checker-file-block');

    function selectedCategoryNames() {
        return Array.from(selectedCategories?.querySelectorAll('.selected-category') || [])
            .map((category) => category.childNodes[0]?.textContent?.trim())
            .filter(Boolean);
    }

    function updateCategorySuggestionState(categoryId, isSelected) {
        const button = document.querySelector(`.category-suggestion[data-category-id="${categoryId}"]`);
        if (button) {
            button.disabled = isSelected;
        }
    }

    function bindSelectedCategoryRemove(button) {
        button.addEventListener('click', () => {
            updateCategorySuggestionState(button.dataset.categoryId, false);
            button.remove();
            updatePreview();
        });
    }

    function addSelectedCategory(categoryId, categoryName) {
        if (!selectedCategories || selectedCategories.querySelector(`[data-category-id="${categoryId}"]`)) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'selected-category';
        button.dataset.categoryId = categoryId;
        button.innerHTML = `
            ${categoryName}
            <span aria-hidden="true">×</span>
            <input type="hidden" name="category_ids[]" value="${categoryId}">
        `;

        bindSelectedCategoryRemove(button);
        selectedCategories.appendChild(button);
        updateCategorySuggestionState(categoryId, true);
        updatePreview();
    }

    function updatePreview() {
        const rating = Number(difficultyInput.value || 800);
        const runnerMode = document.querySelector('input[name="runner_mode"]:checked')?.value || 'solution';
        const checkerType = document.querySelector('input[name="checker_type"]:checked')?.value || 'standard';
        const entrypoint = runnerMode === 'runner' ? 'runner.py' : 'solution.py';

        previewDescription.innerHTML = renderMarkdown(descriptionInput.value) || '<p>Описание задачи...</p>';
        previewTitle.textContent = titleInput.value || 'Two Sum';
        previewCategory.textContent = selectedCategoryNames().join(', ') || 'Без категории';
        previewExample.innerHTML = renderMarkdown(exampleInput.value) || '<p><strong>Вход:</strong> 2 3</p><p><strong>Выход:</strong> 5</p>';
        highlightMarkdownPreview(previewDescription);
        highlightMarkdownPreview(previewExample);

        previewDifficulty.textContent = rating < 1200 ? 'Легкая' : rating < 1800 ? 'Средняя' : 'Сложная';
        previewDifficulty.classList.remove('easy', 'medium', 'hard');
        previewDifficulty.classList.add(rating < 1200 ? 'easy' : rating < 1800 ? 'medium' : 'hard');

        previewEnvironment.textContent = environmentSelect.selectedOptions[0]?.textContent?.trim() || 'Не выбрано';
        previewEntrypoint.textContent = entrypoint;
        previewInputMode.textContent = inputModeSelect.value;
        previewChecker.textContent = checkerType === 'custom' ? 'custom checker.py' : 'standard tokens';
        previewLimits.textContent = `${timeLimitInput.value || 2} сек. / ${memoryLimitInput.value || 128} МБ`;
        previewRunCommand.textContent = `timeout ${timeLimitInput.value || 2}s python3 ${entrypoint} < input.txt -> output.txt`;
        previewCheckerText.textContent = checkerType === 'custom'
            ? 'Custom checker.py получает input.txt, expected.txt и output.txt.'
            : 'Стандартный checker сравнивает expected и output по токенам.';
    }

    [titleInput, difficultyInput, descriptionInput, exampleInput, environmentSelect, inputModeSelect, timeLimitInput, memoryLimitInput].forEach((element) => {
        element?.addEventListener('input', updatePreview);
        element?.addEventListener('change', updatePreview);
    });

    document.querySelectorAll('.category-suggestion').forEach((button) => {
        button.addEventListener('click', () => {
            addSelectedCategory(button.dataset.categoryId, button.dataset.categoryName);
        });
    });

    document.querySelectorAll('.selected-category').forEach(bindSelectedCategoryRemove);

    document.querySelectorAll('input[name="runner_mode"], input[name="checker_type"]').forEach((input) => {
        input.addEventListener('change', updatePreview);
    });

    testsFileInput?.addEventListener('change', async function () {
        const file = this.files?.[0];
        if (file) {
            testsContentInput.value = await file.text();
        }
    });

    taskFilesInput?.addEventListener('change', function () {
        if (!taskFilesVisibility) {
            return;
        }

        taskFilesVisibility.innerHTML = '';

        Array.from(this.files || []).forEach((file, index) => {
            const row = document.createElement('div');
            row.className = 'file-visibility-item';
            row.innerHTML = `
                <div>
                    <strong></strong>
                    <span>${Math.ceil(file.size / 1024)} КБ</span>
                </div>
                <select name="task_files_visibility[${index}]">
                    <option value="public" selected>Публичный</option>
                    <option value="private">Private</option>
                </select>
            `;
            row.querySelector('strong').textContent = file.name;
            taskFilesVisibility.appendChild(row);
        });
    });

    document.querySelectorAll('.close-alert').forEach((button) => {
        button.addEventListener('click', () => button.closest('.alert')?.remove());
    });

    updatePreview();
});
