import * as monaco from 'monaco-editor';

document.addEventListener('DOMContentLoaded', function () {
    const editor = monaco.editor.create(document.getElementById('text-editor'), {
        language: 'python',
        automaticLayout: true,
        minimap: {
            enabled: false
        },
    });

    const result = runScriptPython();

    console.log(result);
});

async function runScriptPython() {
    let pythonEngine = await loadPyodide();
    return pythonEngine.runPython(`
            def hello():
                return "Hello from Python!"
            hello()
        `);
}
