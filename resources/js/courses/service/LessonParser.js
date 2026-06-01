import BlockFactory from './BlockFactory.js';
import Lesson from "./Lesson.js"

export default class LessonParser {
    static parseLessonFromJson(json) {
        const data = JSON.parse(json);
        if (!data) {
            return null;
        }

        const lesson = new Lesson(data.id, data.title);
        lesson.setOrder(data.order || 0);
        const lessonBlocks = data.blocks;
        Array.from(lessonBlocks).forEach(block => {
            const params = typeof block.params === 'string' ? JSON.parse(block.params) : block.params;
            lesson.addBlock(BlockFactory.createBlock(block.type, params), block.order || null);
        });

        return lesson;
    }
}
