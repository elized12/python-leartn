import IBlock from "./IBlock";
import { marked } from 'marked';

export default class QuizBlock extends IBlock {
    constructor(id, params = {}) {
        super(id, 'quiz', {
            title: 'Тестирование',
            description: 'Ответьте на вопросы ниже',
            questions: [
                {
                    id: 1,
                    type: 'single',
                    question: 'Какой язык программирования мы изучаем?',
                    options: [
                        { id: 1, text: 'Python', correct: true },
                        { id: 2, text: 'JavaScript', correct: false },
                        { id: 3, text: 'Java', correct: false }
                    ],
                    explanation: ''
                }
            ],
            ...params
        });

        this.userAnswers = {};
        this.isSubmitted = false;
        this.isEditing = false;
    }

    render() {
        const block = document.createElement('div');
        block.className = `quiz-block ${this.isSubmitted ? 'submitted' : ''}`;
        block.dataset.blockId = this.id;
        block.dataset.blockType = 'quiz';
        block.dataset.order = this.order;

        block.innerHTML = `            
            <div class="quiz-content">
                <div class="quiz-header">
                    <h3 class="quiz-title">${this.params.title}</h3>
                    ${this.params.description ? `<p class="quiz-description">${this.parseMarkdown(this.params.description)}</p>` : ''}
                </div>
                
                <div class="questions-list">
                    ${this.params.questions.map((q, index) => this.renderQuestion(q, index)).join('')}
                </div>
                
                <div class="quiz-footer">
                    ${!this.isSubmitted ? `
                        <button class="btn btn-primary submit-quiz-btn">
                            <i class="fas fa-paper-plane"></i>
                            Проверить ответы
                        </button>
                    ` : this.renderResults()}
                </div>
            </div>
        `;

        this.initEventListeners(block);
        return block;
    }

    renderWithEditor() {
        const block = document.createElement('div');
        block.className = `quiz-block`;
        block.dataset.blockId = this.id;
        block.dataset.blockType = 'quiz';
        block.dataset.order = this.order;

        block.innerHTML = `
            <div class="block-quiz-header">
                <button class="block-drag-handle" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="block-delete-btn" title="Удалить блок">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="quiz-content">
                <div class="quiz-header">
                    <h3 class="quiz-title">${this.params.title}</h3>
                    ${this.params.description ? `<p class="quiz-description">${this.parseMarkdown(this.params.description)}</p>` : ''}
                </div>
                
                <div class="questions-list">
                    ${this.params.questions.map((q, index) => this.renderQuestion(q, index)).join('')}
                </div>
                
                <div class="quiz-footer">
                    ${!this.isSubmitted ? `
                        <button class="btn btn-primary submit-quiz-btn">
                            <i class="fas fa-paper-plane"></i>
                            Проверить ответы
                        </button>
                    ` : this.renderResults()}
                </div>
            </div>
            
            <div class="edit-form">
                <div class="edit-header">
                    <h4><i class="fas fa-cog"></i> Настройки теста</h4>
                    <button class="close-edit-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="edit-body">
                    <div class="quiz-form-group">
                        <label>Заголовок</label>
                        <input type="text" class="edit-title" value="${this.escapeHtml(this.params.title)}">
                    </div>

                    <div class="quiz-form-group">
                        <label>Описание</label>
                        <textarea class="edit-description" rows="2">${this.escapeHtml(this.params.description)}</textarea>
                    </div>
                    
                    <div class="questions-editor">
                        <div class="editor-header">
                            <h5>Вопросы</h5>
                            <button type="button" class="btn btn-outline add-question-btn">
                                <i class="fas fa-plus"></i> Добавить вопрос
                            </button>
                        </div>
                        
                        <div class="questions-list-editor">
                            ${this.params.questions.map((q, index) => this.renderQuestionEditor(q, index)).join('')}
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button class="btn btn-cancel">Отмена</button>
                        <button class="btn btn-primary btn-save">Сохранить</button>
                    </div>
                </div>
            </div>
        `;

        this.initEventListeners(block);
        this.initEditListeners(block);
        return block;
    }

    renderQuestion(question, index) {
        const questionNumber = index + 1;

        return `
            <div class="question-item" data-question-id="${question.id}">
                <div class="question-header">
                    <span class="question-number">Вопрос ${questionNumber}</span>
                </div>
                <div class="question-text">${this.parseMarkdown(question.question)}</div>
                <div class="question-options">
                    ${this.renderOptions(question)}
                </div>
                ${this.isSubmitted && question.explanation ? `
                    <div class="question-explanation">
                        <strong>Объяснение:</strong>${this.parseMarkdown(question.explanation)}
                    </div>
                ` : ''}
            </div>
        `;
    }

    renderOptions(question) {
        if (question.type === 'single') {
            return this.renderSingleOptions(question);
        }
        if (question.type === 'multiple') {
            return this.renderMultipleOptions(question);
        }
        if (question.type === 'text') {
            return this.renderTextOption(question);
        }
        return '';
    }

    renderSingleOptions(question) {
        const userAnswer = this.userAnswers[question.id];

        return `
            <div class="options-single">
                ${question.options.map(option => {
            const isSelected = userAnswer === option.id.toString();
            const isCorrect = option.correct && this.isSubmitted;
            const isWrong = isSelected && !option.correct && this.isSubmitted;

            return `
                        <label class="option ${isCorrect ? 'correct' : ''} ${isWrong ? 'wrong' : ''}">
                            <input type="radio" 
                                   name="question_${question.id}" 
                                   value="${option.id}"
                                   ${isSelected ? 'checked' : ''}
                                   ${this.isSubmitted ? 'disabled' : ''}>
                            <span class="option-text">${option.text}</span>
                            ${isCorrect ? '<i class="fas fa-check"></i>' : ''}
                            ${isWrong ? '<i class="fas fa-times"></i>' : ''}
                        </label>
                    `;
        }).join('')}
            </div>
        `;
    }

    renderMultipleOptions(question) {
        const userAnswer = this.userAnswers[question.id] || [];

        return `
            <div class="options-multiple">
                ${question.options.map(option => {
            const isSelected = userAnswer.includes(option.id.toString());
            const isCorrect = option.correct && this.isSubmitted;
            const isWrong = isSelected && !option.correct && this.isSubmitted;

            return `
                        <label class="option ${isCorrect ? 'correct' : ''} ${isWrong ? 'wrong' : ''}">
                            <input type="checkbox" 
                                   name="question_${question.id}" 
                                   value="${option.id}"
                                   ${isSelected ? 'checked' : ''}
                                   ${this.isSubmitted ? 'disabled' : ''}>
                            <span class="option-text">${option.text}</span>
                            ${isCorrect ? '<i class="fas fa-check"></i>' : ''}
                            ${isWrong ? '<i class="fas fa-times"></i>' : ''}
                        </label>
                    `;
        }).join('')}
            </div>
        `;
    }

    renderTextOption(question) {
        const userAnswer = this.userAnswers[question.id] || '';
        const isCorrect = this.isSubmitted && this.checkAnswer(question, userAnswer);

        return `
            <div class="option-text">
                <textarea class="text-answer" 
                          placeholder="Введите ответ..."
                          ${this.isSubmitted ? 'readonly' : ''}>${userAnswer}</textarea>
                ${isCorrect ? '<i class="fas fa-check correct-icon"></i>' : ''}
            </div>
        `;
    }

    renderResults() {
        const results = this.calculateResults();

        return `
            <div class="quiz-results">
                <h4>Результаты</h4>
                <div class="score">
                    <span class="score-value">${results.correct}/${results.total}</span>
                    <span class="score-label">правильных ответов</span>
                </div>
                ${results.correct === results.total ? `
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        Отлично! Все ответы правильные!
                    </div>
                ` : `
                    <div class="info-message">
                        <i class="fas fa-info-circle"></i>
                        Есть ошибки. Проверьте пояснения под вопросами.
                    </div>
                `}
                <button class="btn btn-outline retry-btn">
                    <i class="fas fa-redo"></i>
                    Пройти заново
                </button>
            </div>
        `;
    }

    renderQuestionEditor(question, index) {
        const questionId = question.id || `q${Date.now()}_${index}`;

        return `
            <div class="question-editor" data-question-id="${questionId}" data-index="${index}">
                <div class="editor-header">
                    <div class="question-editor-title">
                        <span class="question-number">${index + 1}.</span>
                        <textarea type="text" class="edit-question-text" name="question_${questionId}" placeholder="Текст вопроса">${this.escapeHtml(question.question)}</textarea>
                    </div>
                    <div class="question-editor-actions">
                        <select class="edit-question-type">
                            <option value="single" ${question.type === 'single' ? 'selected' : ''}>Один ответ</option>
                            <option value="multiple" ${question.type === 'multiple' ? 'selected' : ''}>Несколько ответов</option>
                            <option value="text" ${question.type === 'text' ? 'selected' : ''}>Текстовый ответ</option>
                        </select>
                        <button type="button" class="btn-icon delete-question-btn" title="Удалить вопрос">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="editor-body">
                    ${question.type === 'text' ? this.renderTextEditor(question) : this.renderOptionsEditor(question)}
                    
                    <div class="explanation-editor">
                        <label>Объяснение (показывается после проверки)</label>
                        <textarea class="edit-question-explanation" 
                                  rows="2" 
                                  placeholder="Объяснение правильного ответа">${this.escapeHtml(question.explanation || '')}</textarea>
                    </div>
                </div>
            </div>
        `;
    }

    renderOptionsEditor(question) {
        return `
            <div class="options-editor">
                <label>Варианты ответов</label>
                <div class="options-list">
                    ${question.options.map((option, idx) => `
                        <div class="option-editor-item" data-option-index="${idx}">
                            <label class="option-checkbox">
                                <input type="${question.type === 'single' ? 'radio' : 'checkbox'}" 
                                       name="correct_${question.id}" 
                                       value="${option.id}"
                                       ${option.correct ? 'checked' : ''}>
                                <span class="checkmark"></span>
                            </label>
                            <input type="text" class="option-text-input" 
                                   value="${this.escapeHtml(option.text)}" 
                                   placeholder="Текст варианта">
                            <button type="button" class="btn-icon delete-option-btn" title="Удалить вариант">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `).join('')}
                </div>
                <button type="button" class="btn-text add-option-btn">
                    <i class="fas fa-plus"></i> Добавить вариант
                </button>
            </div>
        `;
    }

    renderTextEditor(question) {
        const correctAnswer = question.options.find(o => o.correct)?.text ?? '';
        return `
            <div class="text-editor">
                <label>Правильный ответ</label>
                <input type="text" class="correct-answer-input" 
                       value="${this.escapeHtml(correctAnswer)}" 
                       placeholder="Введите правильный ответ">
            </div>
        `;
    }

    initEventListeners(block) {
        const submitBtn = block.querySelector('.submit-quiz-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitQuiz(block));
        }

        block.querySelectorAll('input[type="radio"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const questionId = e.target.name.split('_')[1];
                this.userAnswers[questionId] = e.target.value;
            });
        });

        block.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const questionId = e.target.name.split('_')[1];
                if (!this.userAnswers[questionId]) this.userAnswers[questionId] = [];
                const answers = this.userAnswers[questionId];

                if (e.target.checked) {
                    answers.push(e.target.value);
                } else {
                    const index = answers.indexOf(e.target.value);
                    if (index > -1) answers.splice(index, 1);
                }
            });
        });

        block.querySelectorAll('.text-answer').forEach(textarea => {
            textarea.addEventListener('input', (e) => {
                const questionId = e.target.closest('.question-item').dataset.questionId;
                this.userAnswers[questionId] = e.target.value;
            });
        });

        const retryBtn = block.querySelector('.retry-btn');
        if (retryBtn) {
            retryBtn.addEventListener('click', () => this.resetQuiz(block));
        }
    }

    initEditListeners(block) {
        const deleteBtn = block.querySelector('.block-delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => this.onDelete(block));
        }

        const content = block.querySelector('.quiz-content');
        if (content) {
            content.addEventListener('click', (e) => {

                if (!e.target.closest('.btn, input, textarea, select, label') && !this.isEditing) {
                    this.showEditForm(block);
                }
            });
        }

        const saveBtn = block.querySelector('.btn-save');
        const cancelBtn = block.querySelector('.btn-cancel');
        const closeBtn = block.querySelector('.close-edit-btn');

        if (saveBtn) saveBtn.addEventListener('click', () => this.saveChanges(block));
        if (cancelBtn) cancelBtn.addEventListener('click', () => this.hideEditForm(block));
        if (closeBtn) closeBtn.addEventListener('click', () => this.hideEditForm(block));

        const addQuestionBtn = block.querySelector('.add-question-btn');
        if (addQuestionBtn) {
            addQuestionBtn.addEventListener('click', () => this.addNewQuestion(block));
        }

        this.initQuestionEditors(block);
    }

    initQuestionEditors(block) {
        block.querySelectorAll('.edit-question-type').forEach(select => {
            select.addEventListener('change', (e) => {
                const questionEditor = e.target.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);
                const questionType = e.target.value;

                this.params.questions[questionIndex].type = questionType;

                this.updateQuestionEditor(questionEditor, questionIndex);
            });
        });

        block.querySelectorAll('.delete-question-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const questionEditor = e.target.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);

                if (confirm('Удалить этот вопрос?')) {
                    this.params.questions.splice(questionIndex, 1);
                    questionEditor.remove();

                    this.updateQuestionIndices(block);
                }
            });
        });

        block.querySelectorAll('.edit-question-text').forEach(input => {
            input.addEventListener('input', (e) => {
                const questionEditor = e.target.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);
                this.params.questions[questionIndex].question = e.target.value;
            });
        });

        block.querySelectorAll('.edit-question-explanation').forEach(textarea => {
            textarea.addEventListener('input', (e) => {
                const questionEditor = e.target.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);
                this.params.questions[questionIndex].explanation = e.target.value;
            });
        });

        block.querySelectorAll('.add-option-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const questionEditor = e.target.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);
                const question = this.params.questions[questionIndex];

                const newOption = {
                    id: Date.now(),
                    text: 'Новый вариант',
                    correct: false
                };

                question.options.push(newOption);
                this.updateOptionsEditor(questionEditor, questionIndex);
            });
        });

        block.querySelectorAll('.delete-option-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const optionItem = e.target.closest('.option-editor-item');
                const questionEditor = optionItem.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);
                const optionIndex = parseInt(optionItem.dataset.optionIndex);

                const question = this.params.questions[questionIndex];
                question.options.splice(optionIndex, 1);

                this.updateOptionsEditor(questionEditor, questionIndex);
            });
        });

        block.querySelectorAll('.option-text-input').forEach(input => {
            input.addEventListener('input', (e) => {
                const optionItem = e.target.closest('.option-editor-item');
                const questionEditor = optionItem.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);
                const optionIndex = parseInt(optionItem.dataset.optionIndex);

                const question = this.params.questions[questionIndex];
                question.options[optionIndex].text = e.target.value;
            });
        });

        block.querySelectorAll('input[name^="correct_"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const questionEditor = e.target.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);
                const question = this.params.questions[questionIndex];

                if (question.type === 'single') {
                    question.options.forEach((opt, idx) => {
                        opt.correct = false;
                    });
                    const optionIndex = parseInt(e.target.closest('.option-editor-item').dataset.optionIndex);
                    question.options[optionIndex].correct = true;
                } else if (question.type === 'multiple') {
                    const optionIndex = parseInt(e.target.closest('.option-editor-item').dataset.optionIndex);
                    question.options[optionIndex].correct = e.target.checked;
                }
            });
        });

        block.querySelectorAll('.correct-answer-input').forEach(input => {
            input.addEventListener('input', (e) => {
                const questionEditor = e.target.closest('.question-editor');
                const questionIndex = parseInt(questionEditor.dataset.index);
                const question = this.params.questions[questionIndex];

                if (question.options.length === 0) {
                    question.options.push({
                        id: 1,
                        text: e.target.value,
                        correct: true
                    });
                } else {
                    question.options[0].text = e.target.value;
                    question.options[0].correct = true;
                }
            });
        });
    }

    updateQuestionEditor(questionEditor, questionIndex) {
        const question = this.params.questions[questionIndex];
        const newEditor = document.createElement('div');
        newEditor.innerHTML = this.renderQuestionEditor(question, questionIndex);

        questionEditor.innerHTML = newEditor.querySelector('.question-editor').innerHTML;
        this.initQuestionEditorsForEditor(questionEditor);
    }

    updateOptionsEditor(questionEditor, questionIndex) {
        const question = this.params.questions[questionIndex];
        const optionsEditor = questionEditor.querySelector('.options-editor');
        if (optionsEditor) {
            const newOptionsHTML = this.renderOptionsEditor(question);
            optionsEditor.outerHTML = newOptionsHTML;

            this.initQuestionEditorsForEditor(questionEditor);
        }
    }

    initQuestionEditorsForEditor(questionEditor) {
        const block = questionEditor.closest('.quiz-block');
        this.initQuestionEditors(block);
    }

    updateQuestionIndices(block) {
        const questionEditors = block.querySelectorAll('.question-editor');
        questionEditors.forEach((editor, index) => {
            editor.dataset.index = index;

            const numberSpan = editor.querySelector('.question-number');
            if (numberSpan) {
                numberSpan.textContent = `${index + 1}.`;
            }

            this.params.questions[index] = this.params.questions[index] || this.params.questions[index];
        });
    }

    addNewQuestion(block) {
        const newQuestion = {
            id: Date.now(),
            type: 'single',
            question: 'Новый вопрос?',
            options: [
                { id: 1, text: 'Правильный ответ', correct: true },
                { id: 2, text: 'Неправильный ответ', correct: false }
            ],
            explanation: ''
        };

        this.params.questions.push(newQuestion);

        const questionsListEditor = block.querySelector('.questions-list-editor');
        if (questionsListEditor) {
            const newQuestionHTML = this.renderQuestionEditor(newQuestion, this.params.questions.length - 1);
            questionsListEditor.insertAdjacentHTML('beforeend', newQuestionHTML);

            const newEditor = questionsListEditor.lastElementChild;
            this.initQuestionEditorsForEditor(newEditor);
        }
    }

    submitQuiz(block) {
        if (this.isSubmitted) return;

        this.isSubmitted = true;
        block.classList.add('submitted');

        this.params.questions.forEach((q, index) => {
            const userAnswer = this.userAnswers[q.id];
            q.correct = this.checkAnswer(q, userAnswer);

            if (!q.correct && !q.explanation) {
                q.explanation = 'Попробуйте еще раз!';
            }
        });

        const newContent = this.render();
        block.querySelector('.quiz-content').innerHTML = newContent.querySelector('.quiz-content').innerHTML;
        this.initEventListeners(block);

        this.showNotification('Ответы проверены!', 'success');
    }

    resetQuiz(block) {
        this.isSubmitted = false;
        this.userAnswers = {};
        block.classList.remove('submitted');

        this.params.questions.forEach(q => {
            q.correct = undefined;
        });

        const newContent = this.render();
        block.querySelector('.quiz-content').innerHTML = newContent.querySelector('.quiz-content').innerHTML;
        this.initEventListeners(block);
    }

    checkAnswer(question, userAnswer) {
        if (!userAnswer || (Array.isArray(userAnswer) && userAnswer.length === 0)) {
            return false;
        }

        if (question.type === 'single') {
            const correctOption = question.options.find(o => o.correct);
            return correctOption && userAnswer === correctOption.id.toString();
        }

        if (question.type === 'multiple') {
            const correctIds = question.options.filter(o => o.correct).map(o => o.id.toString());
            if (!Array.isArray(userAnswer)) return false;

            if (userAnswer.length !== correctIds.length) return false;

            const sortedUser = [...userAnswer].sort();
            const sortedCorrect = [...correctIds].sort();

            return sortedUser.every((id, i) => id === sortedCorrect[i]);
        }

        if (question.type === 'text') {
            const correctOption = question.options.find(o => o.correct);
            if (!correctOption) return false;

            const userText = userAnswer.trim().toLowerCase();
            const correctText = correctOption.text.trim().toLowerCase();

            return userText === correctText;
        }

        return false;
    }

    calculateResults() {
        let correct = 0;
        this.params.questions.forEach(q => {
            if (q.correct) correct++;
        });

        return {
            correct,
            total: this.params.questions.length,
            score: Math.round((correct / this.params.questions.length) * 100)
        };
    }

    saveChanges(block) {
        const title = block.querySelector('.edit-title')?.value.trim() || 'Тестирование';
        const description = block.querySelector('.edit-description')?.value.trim() || '';

        const updatedQuestions = [];
        const questionEditors = block.querySelectorAll('.question-editor');

        questionEditors.forEach((editor, index) => {
            const questionText = editor.querySelector('.edit-question-text')?.value.trim();
            const type = editor.querySelector('.edit-question-type')?.value;
            const explanation = editor.querySelector('.edit-question-explanation')?.value.trim();

            if (!questionText) return;

            let options = [];

            if (type === 'text') {
                const correctAnswer = editor.querySelector('.correct-answer-input')?.value.trim() ?? '';
                if (!this.isBlank(correctAnswer)) {
                    options = [{
                        id: 1,
                        text: correctAnswer,
                        correct: true
                    }];
                }
            } else {
                const optionItems = editor.querySelectorAll('.option-editor-item');
                optionItems.forEach((optItem, idx) => {
                    const optionText = optItem.querySelector('.option-text-input')?.value.trim() ?? '';
                    const isCorrect = optItem.querySelector('input[type="radio"], input[type="checkbox"]')?.checked;

                    if (!this.isBlank(optionText)) {
                        options.push({
                            id: idx + 1,
                            text: optionText,
                            correct: isCorrect
                        });
                    }
                });
            }

            const originalQuestion = this.params.questions[index];
            const questionId = originalQuestion?.id || Date.now();

            updatedQuestions.push({
                id: questionId,
                type,
                question: questionText,
                options,
                explanation
            });
        });

        this.updateParams({
            title,
            description,
            questions: updatedQuestions
        });

        this.isSubmitted = false;
        this.userAnswers = {};

        this.hideEditForm(block);
        this.showNotification('Тест обновлен', 'success');

        const newContent = this.renderWithEditor();
        block.outerHTML = newContent.outerHTML;

        const newBlock = document.querySelector(`[data-block-id="${this.id}"]`);
        if (newBlock) {
            this.initEventListeners(newBlock);
            this.initEditListeners(newBlock);
        }
    }

    showEditForm(block) {
        this.isEditing = true;
        block.classList.add('editing');
    }

    hideEditForm(block) {
        this.isEditing = false;
        block.classList.remove('editing');
    }

    onDelete(block) {
        if (confirm('Удалить этот тест?')) {
            const event = new CustomEvent('blockDeleted', {
                detail: { blockId: this.id, type: 'quiz' },
                bubbles: true
            });
            block.dispatchEvent(event);
            block.remove();
        }
    }

    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    isBlank(value) {
        return value === null || value === undefined || String(value).trim() === '';
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : '#3b82f6'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(20px)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    parseMarkdown(markdown) {
        if (!markdown || !markdown.trim()) return '';

        try {
            return marked.parse(markdown);
        } catch (error) {
            console.error('Error parsing markdown:', error);
            return markdown;
        }
    }


    updateParams(newParams) {
        this.params = { ...this.params, ...newParams };
    }
}
