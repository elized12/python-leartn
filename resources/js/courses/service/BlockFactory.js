import TextBlock from "../blocks/TextBlock";
import ExecutableCodeBlock from "../blocks/ExecutableCodeBlock";
import DividerBlock from "../blocks/DividerBlock"
import VideoBlock from "../blocks/VideoBlock";
import QuizBlock from "../blocks/QuizBlock";
import InfoBoxBlock from "../blocks/InfoBoxBlock";
import ImageBlock from "../blocks/ImageBlock";
import TaskListBlock from "../blocks/TaskListBlock";

export default class BlockFactory {
    static blockId = 0;

    static createBlock(type, params) {
        this.blockId++;

        switch (type) {
            case 'text':
                return new TextBlock(this.blockId, params);
            case 'video':
                return new VideoBlock(this.blockId, params);
            case 'quiz':
                return new QuizBlock(this.blockId, params);
            case 'infoBox':
                return new InfoBoxBlock(this.blockId, params);
            case 'image':
                return new ImageBlock(this.blockId, params);
            case 'taskList':
                return new TaskListBlock(this.blockId, params);
            case 'divider':
                return new DividerBlock(this.blockId, params);
            case 'executableCode':
                return new ExecutableCodeBlock(this.blockId, params);
            default:
                throw new Error('There is no such block type');
        }
    }
};
