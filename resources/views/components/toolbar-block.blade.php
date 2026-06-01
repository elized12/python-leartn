<div class="blocks-toolbar" id="blocksToolbar">
    <div class="toolbar-header">
        <div class="toolbar-title">
            <i class="fas fa-plus-circle"></i>
            <h4>Добавить блок</h4>
        </div>
        <button class="toolbar-close" id="closeToolbarBtn">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="blocks-grid" id="blocksToolbarGrid">
    </div>

    <div class="add-block-fab" id="addBlockFAB">
        <i class="fas fa-plus"></i>
        <span>Добавить блок</span>
    </div>
</div>

@pushOnce('js')
    @vite(['resources/js/components/courses/ToolbarBlock.js'])
@endPushOnce

@pushOnce('css')
    @vite(['resources/css/components/courses/toolbar-block.css'])
@endPushOnce