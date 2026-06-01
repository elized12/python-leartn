let pyodidePromise = null;

function loadPython() {
    if (!pyodidePromise) {
        importScripts('https://cdn.jsdelivr.net/pyodide/v0.24.1/full/pyodide.js');
        pyodidePromise = loadPyodide({
            indexURL: 'https://cdn.jsdelivr.net/pyodide/v0.24.1/full/',
        });
    }

    return pyodidePromise;
}

self.onmessage = async (event) => {
    const message = event.data || {};

    if (message.type !== 'run') {
        return;
    }

    try {
        const pyodide = await loadPython();

        self.postMessage({ type: 'running' });

        pyodide.runPython(`
import sys, io
output_buffer = io.StringIO()
error_buffer = io.StringIO()
sys.stdout = output_buffer
sys.stderr = error_buffer
        `);

        pyodide.runPython(message.code || '');

        const output = pyodide.runPython('output_buffer.getvalue()');
        const error = pyodide.runPython('error_buffer.getvalue()');

        pyodide.runPython(`
sys.stdout = sys.__stdout__
sys.stderr = sys.__stderr__
        `);

        self.postMessage({
            type: 'result',
            output: output || '',
            error: error || '',
        });
    } catch (error) {
        let errorMessage = error?.toString?.() || 'Ошибка выполнения кода';

        try {
            const pyodide = await pyodidePromise;
            const stderr = pyodide.runPython('error_buffer.getvalue()');
            if (stderr) {
                errorMessage = stderr;
            }
        } catch (_) {
        }

        self.postMessage({
            type: 'error',
            error: errorMessage,
        });
    }
};
