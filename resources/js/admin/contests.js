function pluralTasks(count) {
    const lastDigit = count % 10;
    const lastTwoDigits = count % 100;

    if (lastDigit === 1 && lastTwoDigits !== 11) {
        return `${count} выбрана`;
    }

    if ([2, 3, 4].includes(lastDigit) && ![12, 13, 14].includes(lastTwoDigits)) {
        return `${count} выбрано`;
    }

    return `${count} выбрано`;
}

function initContestTaskPicker(picker) {
    const searchInput = picker.querySelector('.contest-task-search-input');
    const options = Array.from(picker.querySelectorAll('.contest-task-option'));
    const pickedList = picker.querySelector('.contest-task-picked-list');
    const hiddenInputs = picker.querySelector('.contest-task-hidden-inputs');
    const pickedCount = picker.querySelector('.contest-task-picked-count');
    const selected = new Map();

    function taskFromOption(option) {
        return {
            id: Number(option.dataset.taskId),
            title: option.dataset.taskTitle || `Задача ${option.dataset.taskId}`,
            rating: Number(option.dataset.taskRating || 0),
        };
    }

    function render() {
        hiddenInputs.replaceChildren();
        pickedList.replaceChildren();

        options.forEach((option) => {
            option.classList.toggle('is-selected', selected.has(Number(option.dataset.taskId)));
        });

        pickedCount.textContent = pluralTasks(selected.size);

        if (selected.size === 0) {
            const empty = document.createElement('div');
            empty.className = 'contest-task-picked-empty';
            empty.textContent = 'Выберите задачи слева';
            pickedList.appendChild(empty);
            return;
        }

        Array.from(selected.values()).forEach((task, index) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'task_ids[]';
            input.value = String(task.id);
            hiddenInputs.appendChild(input);

            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'contest-task-picked-item';
            row.dataset.taskId = String(task.id);
            row.innerHTML = `
                <span class="contest-task-picked-number">${index + 1}</span>
                <span>
                    <strong>${task.title}</strong>
                    <small>#${task.id} · рейтинг ${task.rating}</small>
                </span>
                <span class="contest-task-picked-remove">×</span>
            `;
            pickedList.appendChild(row);
        });
    }

    function toggleTask(task) {
        if (selected.has(task.id)) {
            selected.delete(task.id);
        } else {
            selected.set(task.id, task);
        }

        render();
    }

    options.forEach((option) => {
        option.addEventListener('click', () => toggleTask(taskFromOption(option)));
    });

    pickedList.addEventListener('click', (event) => {
        const row = event.target.closest('.contest-task-picked-item');
        if (!row) return;

        selected.delete(Number(row.dataset.taskId));
        render();
    });

    searchInput?.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();

        options.forEach((option) => {
            option.hidden = query !== '' && !option.dataset.search.includes(query);
        });
    });

    const initialIds = JSON.parse(picker.dataset.selectedTaskIds || '[]').map(Number);
    initialIds.forEach((taskId) => {
        const option = options.find((item) => Number(item.dataset.taskId) === taskId);
        if (option) {
            const task = taskFromOption(option);
            selected.set(task.id, task);
        }
    });

    render();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.contest-task-picker').forEach(initContestTaskPicker);
});
