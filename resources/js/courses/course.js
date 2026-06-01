import LessonRender from "./service/LessonRender";
import LessonParser from "./service/LessonParser";

class CoursePage {
    constructor() {
        this.currentLessonId = null;
        this.completedLessonIds = new Set((window.courseProgress?.completedLessonIds || []).map(Number));
        this.lessonsCount = Number(window.courseProgress?.lessonsCount || 0);
        this.init();
    }

    init() {
        this.bindCompleteButton();
        this.refreshProgress();
        this.loadFirstLesson();
    }

    async selectLesson(lessonId) {
        if (this.currentLessonId === lessonId) return;

        document.querySelectorAll('.lesson-item').forEach(item => {
            item.classList.remove('active');
        });

        const selectedLesson = document.querySelector(`[onclick*="selectLesson(${lessonId})"]`);
        if (selectedLesson) {
            selectedLesson.classList.add('active');
        }

        const container = document.querySelector('.lesson-content-main');
        container.innerHTML = '';

        try {
            const response = await fetch(`/course/lesson/${lessonId}`);
            const data = await response.json();

            if (!data.status) {
                throw new Error(data.message || 'Ошибка сервера');
            }

            const lesson = LessonParser.parseLessonFromJson(data.lesson);

            LessonRender.render(container, lesson);

            const titleElement = document.getElementById('lessonTitle');
            if (selectedLesson && titleElement && lesson.title) {
                titleElement.textContent = lesson.title;
            }

            this.currentLessonId = lessonId;
            this.updateCompleteButton();

        } catch (error) {
            console.error('Ошибка загрузки урока:', error);
            container.innerHTML = `
                <div style="padding: 20px; text-align: center; color: red;">
                    Ошибка загрузки урока: ${error.message}
                </div>
            `;
        }
    }

    loadFirstLesson() {
        const firstLessonItem = document.querySelector('.lesson-item');
        if (firstLessonItem) {
            const lessonId = firstLessonItem.getAttribute('onclick')?.match(/selectLesson\((\d+)\)/)?.[1];
            if (lessonId) {
                setTimeout(() => {
                    this.selectLesson(lessonId);
                }, 500);
            }
        }
    }

    bindCompleteButton() {
        const button = document.getElementById('completeLessonBtn');
        if (!button) return;

        button.addEventListener('click', () => this.completeCurrentLesson());
    }

    updateCompleteButton() {
        const button = document.getElementById('completeLessonBtn');
        if (!button || !this.currentLessonId) return;

        const isCompleted = this.completedLessonIds.has(Number(this.currentLessonId));
        button.disabled = isCompleted;
        button.classList.toggle('is-completed', isCompleted);
        button.querySelector('span').textContent = isCompleted ? 'Урок пройден' : 'Отметить как пройденный';
    }

    async completeCurrentLesson() {
        if (!this.currentLessonId || this.completedLessonIds.has(Number(this.currentLessonId))) {
            return;
        }

        const button = document.getElementById('completeLessonBtn');
        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Сохраняем...</span>';

        try {
            const response = await fetch(`/course/lesson/${this.currentLessonId}/complete`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });
            const data = await response.json();

            if (!response.ok || !data.status) {
                throw new Error(data.message || 'Не удалось отметить урок');
            }

            this.completedLessonIds.add(Number(this.currentLessonId));
            this.markLessonCompleted(this.currentLessonId);
            this.refreshProgress(data.progress, data.completed_lessons);
            this.updateCompleteButton();
        } catch (error) {
            button.innerHTML = originalHtml;
            button.disabled = false;
            alert(error.message);
        }
    }

    markLessonCompleted(lessonId) {
        const lessonItem = document.querySelector(`.lesson-item[data-lesson-id="${lessonId}"]`);
        if (!lessonItem) return;

        lessonItem.classList.add('completed');
        const number = lessonItem.querySelector('.lesson-number');
        const status = lessonItem.querySelector('.lesson-status');
        if (number) {
            number.innerHTML = '<i class="fas fa-check"></i>';
        }
        if (status) {
            status.textContent = 'Пройден';
        }
    }

    refreshProgress(progress = null, completedCount = null) {
        const completed = completedCount ?? this.completedLessonIds.size;
        const value = progress ?? Math.round((completed / Math.max(this.lessonsCount, 1)) * 100);
        const fill = document.getElementById('courseProgressFill');
        const text = document.getElementById('courseProgressText');
        const completedElement = document.getElementById('completedLessonsCount');

        if (fill) fill.style.width = `${value}%`;
        if (text) text.textContent = `${value}%`;
        if (completedElement) completedElement.textContent = completed;
    }
}

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

document.addEventListener('DOMContentLoaded', () => {
    initHighlightJS();
    
    const coursePage = new CoursePage();

    window.selectLesson = (lessonId) => {
        coursePage.selectLesson(lessonId);
    };

    window.toggleSidebar = () => {
        const sidebar = document.getElementById('courseSidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    };

    window.closeSidebar = () => {
        const sidebar = document.getElementById('courseSidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    };
});
