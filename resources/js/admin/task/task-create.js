function sanitizeHtml(input, allowedTags = ['b', 'i', 'em', 'strong', 'code']) {
    const doc = new DOMParser().parseFromString(input, 'text/html');
    const allowed = new Set(allowedTags);

    function clean(node) {
        const children = Array.from(node.childNodes);
        for (const child of children) {
            if (child.nodeType === Node.ELEMENT_NODE) {
                if (!allowed.has(child.tagName.toLowerCase())) {
                    child.replaceWith(...child.childNodes);
                } else {
                    clean(child);
                }
            }
        }
    }

    clean(doc.body);
    return doc.body.innerHTML;
}

function addTest() {
    const testContainer = document.getElementById('test-cases-container');
    const testId = Date.now();

    const test = `
    <div class="test-case" data-id="${testId}">
        <div class="test-header">
            <h4>Пример #${testContainer.children.length + 1}</h4>
            <button type="button" class="remove-test" ${testContainer.children.length === 0 ? 'disabled' : ''}>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="form-group">
            <label for="input-${testId}">Входные данные</label>
            <input type="text" id="input-${testId}" class="test-input"
                   value="" placeholder="Например: [2,7,11,15], 9" name="test-case-input-${testId}" required>
        </div>
        <div class="form-group">
            <label for="output-${testId}">Ожидаемый вывод</label>
            <input type="text" id="output-${testId}" class="test-output"
                   value="" placeholder="Например: [0,1]" name="test-case-output-${testId}" required>
        </div>
    </div>`;

    testContainer.insertAdjacentHTML('beforeend', test);

    if (testContainer.children.length > 1) {
        const removeButtons = testContainer.querySelectorAll('.remove-test');
        removeButtons.forEach(btn => btn.disabled = false);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('task-title');
    const difficultySelect = document.getElementById('task-difficulty');
    const descriptionInput = document.getElementById('task-description');
    const exampleInput = document.getElementById('task-example');
    const addExampleBtn = document.getElementById('add-test-case');

    const previewTitle = document.getElementById('preview-title');
    const previewCategory = document.getElementById('preview-category');
    const previewDifficulty = document.getElementById('preview-difficulty');
    const previewDescription = document.getElementById('preview-description');
    const previewExample = document.getElementById('preview-task-example');

    function updatePreview() {
        previewDescription.innerHTML = sanitizeHtml(descriptionInput.value, ['p', 'code']) || `
        <p>Напишите функцию two_sum, которая принимает список чисел и целевое число. Функция должна вернуть индексы двух чисел, которые в сумме дают целевое значение.</p>
        <p>Можно предположить, что существует ровно одно решение, и нельзя использовать один и тот же элемент дважды.</p>`;
        previewTitle.textContent = sanitizeHtml(titleInput.value) || 'Two Sum';
        previewExample.innerHTML = sanitizeHtml(exampleInput.value, ['h1', 'div', 'strong', 'p', 'ul', 'li', 'b']) || `
        <div>
        <h1>Пример 1:</h1>
        <p><strong>Вход:</strong> nums = [2,7,11,15], target = 9</p>
        <p><strong>Выход:</strong> [0,1]</p>
        <p><strong>Объяснение:</strong> nums[0] + nums[1] == 9 → [0, 1]</p>
        </div>`;

        const difficulty = difficultySelect.value;
        previewDifficulty.className = 'task-difficulty ' + difficulty;
        previewDifficulty.textContent =
            difficulty < 1200 ? 'Легкая' :
                difficulty < 1800 ? 'Средняя' : 'Сложная';

        previewDifficulty.classList.remove('easy', 'medium', 'hard');
        previewDifficulty.classList.add(difficulty < 1200 ? 'easy' :
            difficulty < 1800 ? 'medium' : 'hard');
    }

    titleInput.addEventListener('input', updatePreview);
    difficultySelect.addEventListener('change', updatePreview);
    descriptionInput.addEventListener('input', updatePreview);
    exampleInput.addEventListener('input', updatePreview);
    addExampleBtn.addEventListener('click', addTest);

    document.getElementById('test-cases-container').addEventListener('click', function (e) {
        if (e.target.closest('.remove-test')) {
            const testCase = e.target.closest('.test-case');
            if (document.querySelectorAll('.test-case').length > 1) {
                testCase.remove();
                document.querySelectorAll('.test-case').forEach((caseEl, index) => {
                    caseEl.querySelector('h4').textContent = `Пример #${index + 1}`;
                });
                if (document.querySelectorAll('.test-case').length === 1) {
                    document.querySelector('.remove-test').disabled = true;
                }
            }
        }
    });

    updatePreview();
});
