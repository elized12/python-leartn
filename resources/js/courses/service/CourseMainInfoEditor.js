export default class CourseMainInfoEditor {
    constructor() {
        this.courseEditor = document.getElementById('courseEditor');
        this.courseTitle = this.courseEditor.querySelector('textarea[name="courseTitle"]');
        this.courseDescription = this.courseEditor.querySelector('textarea[name="courseDescription"]');
        this.courseDifficultySelect = document.querySelector('.difficulty-select');
        this.courseUrl = this.courseEditor.querySelector('#courseUrl');
        this.courseTime = this.courseEditor.querySelector('input[name="courseTime"]');
        this.courseSelectCoverButton = this.courseEditor.querySelector('#selectCoverBtn');
        this.courseRemoveCoverButton = this.courseEditor.querySelector('#removeCoverBtn');
        this.courseCoverImage = this.courseEditor.querySelector('#coverImage');
        this.courseCategoryInputs = this.courseEditor.querySelectorAll('input[name="courseCategoryIds[]"]');
        this.coverImageValue = null;

        this.#enableEvents();
    }

    getParams() {
        const paramsCourse = {
            'title': this.courseTitle.value,
            'description': this.courseDescription.value,
            'difficulty': this.#getDifficulty(),
            'url': this.courseUrl.value,
            'time': this.courseTime.value,
            'coverImage': this.coverImageValue && !this.coverImageValue.startsWith('data:') ? this.coverImageValue : null,
            'categoryIds': this.#getCategoryIds()
        }

        return paramsCourse
    }

    getPreviewCoverImage() {
        return this.coverImageValue;
    }

    loadParams(params = {}) {
        this.courseTitle.value = params.title || '';
        this.courseDescription.value = params.description || '';
        this.courseUrl.value = params.url || '';
        this.courseTime.value = params.time || 10;
        this.coverImageValue = params.coverImage || null;
        const categoryIds = (params.categoryIds || []).map(Number);

        this.courseCategoryInputs.forEach(input => {
            input.checked = categoryIds.includes(Number(input.value));
        });

        const activeDifficulty = params.difficulty || 'beginner';
        this.courseEditor.querySelectorAll('.difficulty-option').forEach(option => {
            option.classList.toggle('active', option.dataset.value === activeDifficulty);
        });

        this.#renderCoverPreview(this.coverImageValue);
        this.#updateCounters();
    }

    #enableEvents() {
        const difficultyOptions = this.courseEditor.querySelectorAll('.difficulty-option');
        difficultyOptions.forEach(option => {
            option.addEventListener('click', function (event) {
                const optionActive = document.querySelector('.difficulty-option.active');
                optionActive.classList.remove('active');

                option.classList.add('active');
            });
        });

        this.courseTitle.addEventListener('input', (event) => {
            this.courseTitle.value = this.courseTitle.value.replace(/(\r\n|\n|\r)/gm, "");
            this.#updateCounters();
        });

        this.courseDescription.addEventListener('input', (event) => {
            this.#updateCounters();
        });

        this.courseUrl.addEventListener('input', (event) => {
            this.courseUrl.value = this.courseUrl.value.replace(/[^-A-Za-zа-яА-Я0-9]/gm, "");
            this.courseUrl.value = this.courseUrl.value
                .toLowerCase()
                .replace(/[ъь]/g, '')
                .replace(/ё/g, 'yo')
                .replace(/й/g, 'y')
                .replace(/ц/g, 'ts')
                .replace(/у/g, 'u')
                .replace(/к/g, 'k')
                .replace(/е/g, 'e')
                .replace(/н/g, 'n')
                .replace(/г/g, 'g')
                .replace(/ш/g, 'sh')
                .replace(/щ/g, 'sch')
                .replace(/з/g, 'z')
                .replace(/х/g, 'h')
                .replace(/ф/g, 'f')
                .replace(/ы/g, 'i')
                .replace(/в/g, 'v')
                .replace(/а/g, 'a')
                .replace(/п/g, 'p')
                .replace(/р/g, 'r')
                .replace(/о/g, 'o')
                .replace(/л/g, 'l')
                .replace(/д/g, 'd')
                .replace(/ж/g, 'zh')
                .replace(/э/g, 'e')
                .replace(/я/g, 'ya')
                .replace(/ч/g, 'ch')
                .replace(/с/g, 's')
                .replace(/м/g, 'm')
                .replace(/и/g, 'i')
                .replace(/т/g, 't')
                .replace(/б/g, 'b')
                .replace(/ю/g, 'yu')

            this.#updateCounters();
        });

        document.querySelector('.duration-btn.plus').addEventListener('click', (event) => {
            let value = Number.parseInt(this.courseTime.value);
            if (500 < value + 1) {
                return;
            }

            this.courseTime.value = value + 1;
        });

        document.querySelector('.duration-btn.minus').addEventListener('click', (event) => {
            let value = Number.parseInt(this.courseTime.value);
            if (value - 1 < 1) {
                return;
            }

            this.courseTime.value = value - 1;
        });

        this.courseTime.addEventListener('input', (event) => {
            const value = Number.parseInt(this.courseTime.value);
            if (isNaN(value)) {
                this.courseTime.value = 1;
                return;
            }

            if (500 < value) {
                this.courseTime.value = 500;
            }

            if (value < 1) {
                this.courseTime.value = 1;
            }
        });

        this.courseSelectCoverButton.addEventListener('click', (event) => {
            event.preventDefault();

            this.courseCoverImage.click();
        });

        this.courseRemoveCoverButton.addEventListener('click', (event) => {
            event.preventDefault();
            this.courseCoverImage.value = '';
            this.coverImageValue = null;

            const coverImagePreview = this.courseEditor.querySelector('.cover-image-preview');
            if (coverImagePreview.firstChild) {
                coverImagePreview.removeChild(coverImagePreview.firstChild);
            }
        });

        this.courseCoverImage.addEventListener('change', (event) => {
            const file = this.courseCoverImage.files[0];
            if (!file) {
                return;
            }

            const fileReader = new FileReader();
            fileReader.readAsDataURL(file);

            fileReader.onload = (event) => {
                this.coverImageValue = fileReader.result;
                this.#renderCoverPreview(this.coverImageValue);
            };
        });
    }

    #getDifficulty() {
        const diffcultyOptionActive = this.courseDifficultySelect.querySelector('.difficulty-option.active');
        return diffcultyOptionActive.dataset.value;
    }

    #getCategoryIds() {
        return Array.from(this.courseCategoryInputs)
            .filter(input => input.checked)
            .map(input => Number(input.value));
    }

    #renderCoverPreview(src) {
        const coverImagePreview = this.courseEditor.querySelector('.cover-image-preview');
        if (!coverImagePreview) {
            return;
        }

        while (coverImagePreview.firstChild) {
            coverImagePreview.removeChild(coverImagePreview.firstChild);
        }

        if (!src) {
            return;
        }

        const imgPreview = document.createElement('img');
        imgPreview.src = src;
        imgPreview.alt = 'Превью обложки курса';
        imgPreview.classList.add('cover-image');

        coverImagePreview.appendChild(imgPreview);
    }

    #updateCounters() {
        const titleCount = this.courseEditor.querySelector('#titleCount');
        const descriptionCount = this.courseEditor.querySelector('#descriptionCount');
        const urlCount = this.courseEditor.querySelector('#urlCount');

        if (titleCount) {
            titleCount.innerText = this.courseTitle.value ? this.courseTitle.value.length : 0;
        }

        if (descriptionCount) {
            descriptionCount.innerText = this.courseDescription.value ? this.courseDescription.value.length : 0;
        }

        if (urlCount) {
            urlCount.innerText = this.courseUrl.value ? this.courseUrl.value.length : 0;
        }
    }
};
