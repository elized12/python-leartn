export default class LessonRender {
    static render(container, lesson, withEdit = false) {
        const blocks = lesson.getBlocks();
        blocks.sort((a, b) => {
            if (a.getOrder() > b.getOrder()) return 1;
            if (a.getOrder() < b.getOrder()) return -1;

            return 0;
        });

        Array.from(blocks).forEach(block => {
            if (withEdit) {
                container.appendChild(block.renderWithEditor());
            }
            else {
                container.appendChild(block.render());
            }
        });
    }
};