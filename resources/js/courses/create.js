import CourseEditorMainInfo from "./service/CourseMainInfoEditor";
import ToolbarBlock from "../components/courses/ToolbarBlock";
import BlockFactory from "./service/BlockFactory";
import Course from "./service/Course";
import LessonRender from "./service/LessonRender";
import Lesson from "./service/Lesson";
import OrderManager from "./service/OrderManager";
import ErrorModal from "../components/modal/ErrorModal";

let nextLessonId = 1;
let currentLesson = null;
let currentLessonId = 0;
let blocksOrderManager = null;
let lessonsOrderManager = null;

const errorModal = new ErrorModal();
const course = new Course();
const courseEditorMainInfo = new CourseEditorMainInfo();
const toolbarBlock = new ToolbarBlock([
    {
        type: 'text',
        icon: 'fa-font',
        title: 'Текст',
        description: 'Markdown текст с форматированием'
    },
    {
        type: 'executableCode',
        icon: 'fa-code',
        title: 'Код',
        description: 'Код с подсветкой синтаксиса'
    },
    {
        type: 'quiz',
        icon: 'fa-question-circle',
        title: 'Вопрос',
        description: 'Тестовый вопрос с ответами'
    },
    {
        type: 'infoBox',
        icon: 'fa-lightbulb',
        title: 'Подсказка',
        description: 'Важная заметка или совет'
    },
    {
        type: 'taskList',
        icon: 'fa-list-check',
        title: 'Задачи',
        description: 'Список задач для решения'
    },
    {
        type: 'image',
        icon: 'fa-image',
        title: 'Изображение',
        description: 'Картинка с описанием',
    },
    {
        type: 'video',
        icon: 'fa-video',
        title: 'Видео',
        description: 'Видео с YouTube/Vimeo',
    },
    {
        type: 'divider',
        icon: 'fa-minus',
        title: 'Разделитель',
        description: 'Визуальный разделитель',
    },
    {
        type: 'file',
        icon: 'fa-paperclip',
        title: 'Файл',
        description: 'Файл для скачивания',
        disable: true
    }
]);

function initHighlightJS() {
    if (typeof hljs !== 'undefined') {
        hljs.highlightAll();

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) {
                            node.querySelectorAll('pre code').forEach((block) => {
                                if (!block.classList.contains('hljs')) {
                                    hljs.highlightElement(block);
                                }
                            });
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        return true;
    }
    return false;
}

function initLessonsOrderManager() {
    const lessonsList = document.getElementById('lessonsList');
    if (!lessonsList) return;

    if (lessonsOrderManager) {
        lessonsOrderManager.destroy();
    }

    lessonsOrderManager = new OrderManager(lessonsList, {
        handle: '.drag-btn',
        draggable: '.lesson-item[data-lesson-id]',
        dataIdAttribute: 'data-lesson-id',
        dataOrderAttribute: 'data-order',
        orderSelector: '.lesson-order',

        onChange: (order, event) => {
            saveLessonsOrder(order);
        }
    });

    lessonsOrderManager.registerItems(course.getLessons());
}

function resetLessons() {
    const lessonItems = document.querySelectorAll('.lesson-item');
    lessonItems.forEach(item => {
        item.classList.remove('active');
    });
}

function showMainCourseInfo() {
    const courseInfoSection = document.getElementById('courseInfoSection');
    courseInfoSection.style.display = 'block';
}

function hideMainCourseInfo() {
    const courseInfoSection = document.getElementById('courseInfoSection');
    courseInfoSection.style.display = 'none';
}

function saveLessonsOrder(order) {
    order.forEach(item => {
        const lesson = course.getLesson(item.id);
        if (lesson && lesson.setOrder) {
            lesson.setOrder(item.order);
        }
    });
}

function showLessonEditor() {
    const lessonEditorSection = document.getElementById('lessonEditorSection');
    lessonEditorSection.style.display = 'block';
}

function hideLessonEditor() {
    const lessonEditorSection = document.getElementById('lessonEditorSection');
    lessonEditorSection.style.display = 'none';
}

function clearChilds(element) {
    while (element.firstChild) {
        element.removeChild(element.firstChild);
    }
}

function setActiveLesson(lessonId) {
    currentLessonId = lessonId;

    if (lessonId === 0) {
        hideLessonEditor();
        showMainCourseInfo();
        currentLesson = null;

        resetLessons();

        const activeLessonItem = document.querySelector(`[data-lesson-id="${lessonId}"]`);
        if (activeLessonItem) {
            activeLessonItem.classList.add('active');
        }

        return;
    }

    hideMainCourseInfo();
    showLessonEditor();

    currentLesson = course.getLesson(lessonId);

    if (!currentLesson) {
        console.error(`Урок с ID ${lessonId} не найден`);
        return;
    }

    resetLessons();

    const activeLessonItem = document.querySelector(`[data-lesson-id="${lessonId}"]`);
    if (activeLessonItem) {
        activeLessonItem.classList.add('active');
    }
}

function deleteLesson(lessonIdToDelete) {
    const lessonItem = document.querySelector(`[data-lesson-id="${lessonIdToDelete}"]`);
    if (lessonItem) {
        lessonItem.remove();
    }

    course.removeLesson(lessonIdToDelete);

    if (lessonsOrderManager) {
        lessonsOrderManager.registerItems(course.getLessons());
    }

    if (currentLessonId === lessonIdToDelete) {
        setActiveLesson(0);
    }
}

function initBlocksOrderManager(container, lesson) {
    if (blocksOrderManager) {
        blocksOrderManager.destroy();
    }

    blocksOrderManager = new OrderManager(container, {
        handle: '.block-drag-handle',
        draggable: '[data-block-id]',
        dataIdAttribute: 'data-block-id',
        dataOrderAttribute: 'data-order',
        orderSelector: '.block-order',

        onChange: (order, event) => {
            saveBlockOrder(order);
        }
    });

    const blocks = lesson.getBlocks();
    blocksOrderManager.registerItems(blocks);
}

function saveBlockOrder(order) {
    order.forEach(item => {
        if (item.item && item.item.setOrder) {
            item.item.setOrder(item.order);
        }
    });
}

function showLesson(lessonId) {
    if (currentLesson && currentLesson.getId() === lessonId) {
        return;
    }

    setActiveLesson(lessonId);

    if (!currentLesson) {
        console.error('Урок не найден');
        return;
    }

    const blocksContainer = document.getElementById('blocksContainer');
    clearChilds(blocksContainer);

    const lessonBlocks = currentLesson.getBlocks();
    if (lessonBlocks.length === 0) {
        addStandartBlock();
    } else {
        LessonRender.render(blocksContainer, currentLesson, true);
        initBlocksOrderManager(blocksContainer, currentLesson);
    }

    toolbarBlock.show();
}

function addStandartBlock() {
    const blocksContainer = document.getElementById('blocksContainer');
    if (!blocksContainer) return;

    const emptyBlock = document.createElement('div');
    emptyBlock.classList.add('empty-blocks-state');
    emptyBlock.innerHTML = `
        <div class="empty-blocks-content">
            <i class="fas fa-inbox fa-3x"></i>
            <p>В этом уроке пока нет блоков. Добавьте новый блок, используя панель инструментов.</p>
        </div>
    `;

    blocksContainer.appendChild(emptyBlock);
}

function removeStandartLesson() {
    const emptyBlock = document.querySelector('.empty-blocks-state');
    if (emptyBlock) {
        emptyBlock.remove();
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
}

function addLesson(lessonData = null, shouldOpen = true) {
    const newLessonId = lessonData?.id ?? nextLessonId;
    nextLessonId = Math.max(nextLessonId, newLessonId + 1);

    const lesson = new Lesson(newLessonId, lessonData?.title || `Урок ${newLessonId}`);
    course.addLesson(lesson);
    lesson.setOrder(lessonData?.order ?? course.getLessons().length);

    if (lessonData?.blocks?.length) {
        lessonData.blocks.forEach((blockData) => {
            const block = BlockFactory.createBlock(blockData.type, blockData.params || {});
            lesson.addBlock(block, blockData.order ?? null);
        });
    }

    let lessonItem = document.createElement('div');
    lessonItem.classList.add('lesson-item');
    lessonItem.setAttribute('data-lesson-id', newLessonId);
    lessonItem.setAttribute('data-order', lesson.getOrder());

    lessonItem.innerHTML = `
        <div class="lesson-content">
            <div class="lesson-order">${lesson.getOrder()}</div>
            <div class="lesson-title" contenteditable="true" data-placeholder="Введите название урока">
                ${escapeHtml(lesson.title)}
            </div>
            <div class="lesson-actions">
                <button class="action-btn drag-btn" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="action-btn delete-btn" title="Удалить">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    lessonItem.addEventListener('click', function (event) {
        event.stopPropagation();
        showLesson(newLessonId);
    });

    const buttonDeleteLesson = lessonItem.querySelector('.delete-btn');
    buttonDeleteLesson.addEventListener('click', function (event) {
        event.stopPropagation();
        deleteLesson(newLessonId);
    });

    const titleElement = lessonItem.querySelector('.lesson-title');
    titleElement.addEventListener('input', function () {
        lesson.title = titleElement.textContent.trim() || `Урок ${newLessonId}`;
    });

    document.getElementById('lessonsList').appendChild(lessonItem);

    if (lessonsOrderManager) {
        lessonsOrderManager.registerItems(course.getLessons());
    } else {
        initLessonsOrderManager();
    }

    if (shouldOpen) {
        showLesson(newLessonId);
    }
}

async function saveCourse(status = 'published') {
    const buttonPublish = document.getElementById('publishBtn');
    const buttonDraft = document.getElementById('saveDraftBtn');
    const originalText = buttonPublish.innerHTML;
    const originalDraftText = buttonDraft?.innerHTML;
    const activeButton = status === 'draft' ? buttonDraft : buttonPublish;

    try {
        if (activeButton) {
            activeButton.disabled = true;
        }

        course.setMainInfo(courseEditorMainInfo.getParams());
        const jsonData = course.toJson();
        jsonData.status = status;

        const mode = window.courseEditorMode || {};
        const response = await fetch(mode.isEdit && mode.updateUrl ? mode.updateUrl : '/courses/create', {
            method: mode.isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(jsonData)
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(JSON.stringify(result));
        }

        if (result.status && result.data && result.data.url) {
            showSuccessMessage(status === 'draft' ? 'Черновик сохранён' : (mode.isEdit ? 'Курс успешно обновлён! Перенаправление...' : 'Курс успешно создан! Перенаправление...'));

            if (status === 'published') {
                setTimeout(() => {
                    window.location.href = `/courses`;
                }, 2000);
            } else if (!mode.isEdit && result.data.course_id) {
                window.courseEditorMode = {
                    isEdit: true,
                    updateUrl: `/courses/${result.data.course_id}`,
                };
            }
        } else {
            throw new Error('Неизвестный ответ от сервера');
        }

    } catch (error) {
        console.error('Ошибка при публикации:', error);

        try {
            const errorData = JSON.parse(error.message);
            if (errorData.errors) {
                errorModal.show(errorData.errors);
            } else if (errorData.message) {
                errorModal.show([errorData.message]);
            } else {
                errorModal.show(['Неизвестная ошибка сервера']);
            }
        } catch (e) {
            errorModal.show([error.message || 'Произошла ошибка при публикации курса']);
        }
    } finally {
        buttonPublish.innerHTML = originalText;
        if (buttonDraft && originalDraftText) {
            buttonDraft.innerHTML = originalDraftText;
        }
        buttonPublish.disabled = false;
        if (buttonDraft) {
            buttonDraft.disabled = false;
        }
    }
}

function showSuccessMessage(message) {
    const successMessage = document.createElement('div');
    successMessage.className = 'success-message';
    successMessage.innerHTML = `
        <div class="success-content">
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .success-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .success-message i {
            font-size: 24px;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(successMessage);

    setTimeout(() => {
        successMessage.style.opacity = '0';
        successMessage.style.transform = 'translateX(100%)';
        setTimeout(() => successMessage.remove(), 300);
    }, 3000);
}

function readInitialPayload() {
    const payloadElement = document.getElementById('course-editor-payload');
    if (!payloadElement || !payloadElement.textContent.trim() || payloadElement.textContent.trim() === 'null') {
        return null;
    }

    try {
        return JSON.parse(payloadElement.textContent);
    } catch (error) {
        console.error('Не удалось прочитать данные курса:', error);
        return null;
    }
}

function loadInitialCourse(payload) {
    courseEditorMainInfo.loadParams(payload.mainInfo || {});

    clearChilds(document.getElementById('lessonsList'));
    course.getLessons().splice(0, course.getLessons().length);

    (payload.lessons || []).forEach((lessonData) => {
        addLesson(lessonData, false);
    });

    const firstLesson = course.getLessons()[0];
    if (firstLesson) {
        showLesson(firstLesson.getId());
    } else {
        setActiveLesson(0);
    }
}

function difficultyLabel(value) {
    if (value === 'intermediate') {
        return 'Средний уровень';
    }

    if (value === 'advanced') {
        return 'Продвинутый';
    }

    return 'Для начинающих';
}

function openPreview() {
    course.setMainInfo(courseEditorMainInfo.getParams());

    const modal = document.getElementById('coursePreviewModal');
    const title = document.getElementById('coursePreviewTitle');
    const description = document.getElementById('previewDescription');
    const difficulty = document.getElementById('previewDifficulty');
    const time = document.getElementById('previewTime');
    const lessons = document.getElementById('previewLessons');
    const lessonTitle = document.getElementById('previewLessonTitle');
    const blocks = document.getElementById('previewBlocks');
    const cover = document.getElementById('previewCover');

    title.textContent = course.mainInfo.title || 'Без названия';
    description.textContent = course.mainInfo.description || 'Описание курса пока не заполнено.';
    difficulty.textContent = difficultyLabel(course.mainInfo.difficulty);
    time.textContent = `${course.mainInfo.time || 1} ч.`;
    const previewCoverImage = courseEditorMainInfo.getPreviewCoverImage();
    cover.style.backgroundImage = previewCoverImage ? `url("${previewCoverImage}")` : '';
    cover.classList.toggle('has-image', Boolean(previewCoverImage));

    clearChilds(lessons);
    clearChilds(blocks);

    const courseLessons = course.getLessons().slice().sort((a, b) => a.getOrder() - b.getOrder());
    const firstLesson = courseLessons[0] || null;

    courseLessons.forEach((lesson, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `course-preview-lesson ${lesson === firstLesson ? 'active' : ''}`;
        button.innerHTML = `<span>${index + 1}</span>${escapeHtml(lesson.title || `Урок ${index + 1}`)}`;
        button.addEventListener('click', () => {
            document.querySelectorAll('.course-preview-lesson').forEach(item => item.classList.remove('active'));
            button.classList.add('active');
            renderPreviewLesson(lesson);
        });
        lessons.appendChild(button);
    });

    renderPreviewLesson(firstLesson);

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}

function renderPreviewLesson(lesson) {
    const lessonTitle = document.getElementById('previewLessonTitle');
    const blocks = document.getElementById('previewBlocks');
    clearChilds(blocks);

    if (!lesson) {
        lessonTitle.textContent = 'Уроков пока нет';
        blocks.innerHTML = '<div class="empty-blocks-state"><p>Добавьте урок, чтобы увидеть превью.</p></div>';
        return;
    }

    lessonTitle.textContent = lesson.title || 'Урок без названия';

    if (lesson.getBlocks().length === 0) {
        blocks.innerHTML = '<div class="empty-blocks-state"><p>В этом уроке пока нет блоков.</p></div>';
        return;
    }

    LessonRender.render(blocks, lesson);
}

function closePreview() {
    const modal = document.getElementById('coursePreviewModal');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
}

document.addEventListener('DOMContentLoaded', function () {
    initHighlightJS();

    const initialPayload = readInitialPayload();
    if (initialPayload) {
        loadInitialCourse(initialPayload);
    }

    initLessonsOrderManager();

    const blockLessonTitle = document.getElementById('lessonMainInfo');
    blockLessonTitle.addEventListener('click', function () {
        if (currentLessonId === 0) {
            return;
        }
        setActiveLesson(0);
    });

    const buttonAddLesson = document.getElementById('addLessonBtn');
    buttonAddLesson.addEventListener('click', function () {
        addLesson();
    });

    toolbarBlock.addEventListener('block-select', (event) => {
        if (!currentLesson) {
            alert('Сначала выберите или создайте урок!');
            return;
        }

        if (currentLesson.getBlocks().length === 0) {
            removeStandartLesson();
        }

        const block = BlockFactory.createBlock(event.detail.type);
        if (!block) {
            console.error(`Не удалось создать блок типа: ${event.detail.type}`);
            return;
        }

        currentLesson.addBlock(block);

        const blockElement = block.renderWithEditor();
        const blocksContainer = document.getElementById('blocksContainer');
        blocksContainer.appendChild(blockElement);

        if (blocksOrderManager) {
            blocksOrderManager.registerItems(currentLesson.getBlocks());
        } else {
            initBlocksOrderManager(blocksContainer, currentLesson);
        }
    });

    const buttonPublishCourse = document.getElementById('publishBtn');
    buttonPublishCourse.addEventListener('click', function (event) {
        event.preventDefault();
        saveCourse('published');
    });

    const buttonSaveDraft = document.getElementById('saveDraftBtn');
    buttonSaveDraft.addEventListener('click', function (event) {
        event.preventDefault();
        saveCourse('draft');
    });

    const buttonPreviewCourse = document.getElementById('previewBtn');
    buttonPreviewCourse.addEventListener('click', function (event) {
        event.preventDefault();
        openPreview();
    });

    document.querySelectorAll('[data-preview-close]').forEach((element) => {
        element.addEventListener('click', closePreview);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePreview();
        }
    });

    document.addEventListener('blockDeleted', (event) => {
        if (currentLesson && event.detail?.blockId) {
            currentLesson.removeBlock(event.detail.blockId);
        }
    });
});
