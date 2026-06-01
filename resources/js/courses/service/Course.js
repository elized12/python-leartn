export default class Course {
    constructor() {
        this.mainInfo = {
            title: '',
            description: '',
            difficulty: '',
            url: '',
            time: '',
            coverImage: null,
            categoryIds: []
        };

        this.lessons = [];
    }

    setMainInfo(params) {
        this.mainInfo.title = params.title;
        this.mainInfo.description = params.description;
        this.mainInfo.difficulty = params.difficulty;
        this.mainInfo.url = params.url;
        this.mainInfo.time = params.time;
        this.mainInfo.coverImage = params.coverImage;
        this.mainInfo.categoryIds = params.categoryIds || [];
    }

    addLesson(lesson) {
        this.lessons.push(lesson);

        lesson.setOrder(this.lessons.length);
    }

    getLesson(lessonId) {
        return this.lessons.find(lesson => Number(lesson.id) === Number(lessonId));
    }

    getLessons() {
        return this.lessons;
    }

    updateLessonsOrder() {
        this.lessons.sort((a, b) => a.getOrder() - b.getOrder())
            .forEach((lesson, index) => {
                lesson.setOrder(index + 1);
            });
    }

    removeLesson(lessonId) {
        this.lessons = this.lessons.filter(lesson => Number(lesson.id) !== Number(lessonId));
    }

    toJson() {
        return {
            mainInfo: this.mainInfo,
            lessons: this.#toJsonLessons()
        }
    }

    #toJsonLessons() {
        return this.lessons.map(lesson => lesson.toJson());
    }

    loadFromJson(jsonData) {
        this.mainInfo = jsonData.mainInfo;
        this.lessons = jsonData.lessons;
    }
}
