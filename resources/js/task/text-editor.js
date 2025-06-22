import start, { E_EDITOR_THEME } from 'monaco-python';

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

function handleAttemptNotification(notification) {
    const outputContainer = document.querySelector('.output-container');
    const outputMessageDiv = document.getElementById('output');

    clearOutputBlock();

    switch (notification.type) {
        case 'Success':
            outputContainer.classList.add('output-success');
            break;
        case 'Error':
            outputContainer.classList.add('output-error');
            break;
        case 'Warning':
            outputContainer.classList.add('output-warning');
            break;
        default:
            outputContainer.classList.add('output-default');
            break;
    }

    outputMessageDiv.innerText = notification.message;
}

function showAnimationWaitRequest() {
    clearOutputBlock();

    const buttonRun = document.querySelector('.run-button');
    buttonRun.disabled = true;

    const animationSpinner = document.getElementById('spinner-output');
    animationSpinner.style.display = 'block';
}

function hideAnimationWaitRequest() {
    const buttonRun = document.querySelector('.run-button');
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

    try {
        const wrapper = await start(textEditor, {
            theme: E_EDITOR_THEME.LIGHT_VS
        });

        const editor = wrapper.getEditor();
        editor.onDidChangeModelContent(() => {
            textarea.value = editor.getValue();
        });

        loader.style.display = 'none';
    } catch (error) {
        console.error('Editor initialization failed:', error);
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    const form = document.querySelector('form');
    const submitButton = form.querySelector('.run-button');

    initEditor();

    submitButton.addEventListener('click', async function (e) {
        e.preventDefault();
        showAnimationWaitRequest();

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
    window.Echo.private(channel).listen('.attempt.notification', (event) => {
        handleAttemptNotification(event);
        hideAnimationWaitRequest();
    });
});
