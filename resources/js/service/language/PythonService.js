export default class PythonService {
    static MAX_EXECUTION_TIMEOUT_MS = 10000;

    static createWorker() {
        const workerSource = `
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
    if (message.type !== 'run') return;

    try {
        const pyodide = await loadPython();
        self.postMessage({ type: 'running' });

        pyodide.runPython(\`
import sys, io
output_buffer = io.StringIO()
error_buffer = io.StringIO()
sys.stdout = output_buffer
sys.stderr = error_buffer
\`);

        pyodide.runPython(message.code || '');

        const output = pyodide.runPython('output_buffer.getvalue()');
        const error = pyodide.runPython('error_buffer.getvalue()');

        pyodide.runPython(\`
sys.stdout = sys.__stdout__
sys.stderr = sys.__stderr__
\`);

        self.postMessage({ type: 'result', output: output || '', error: error || '' });
    } catch (error) {
        let errorMessage = error?.toString?.() || 'Ошибка выполнения кода';

        try {
            const pyodide = await pyodidePromise;
            const stderr = pyodide.runPython('error_buffer.getvalue()');
            if (stderr) errorMessage = stderr;
        } catch (_) {
        }

        self.postMessage({ type: 'error', error: errorMessage });
    }
};
        `;

        const blob = new Blob([workerSource], { type: 'application/javascript' });
        const objectUrl = URL.createObjectURL(blob);
        const worker = new Worker(objectUrl);
        worker.objectUrl = objectUrl;

        return worker;
    }

    static executeCodeWithTimeout(code, timeoutMs = this.MAX_EXECUTION_TIMEOUT_MS) {
        const worker = this.createWorker();
        const executionTimeoutMs = Math.min(Number(timeoutMs) || this.MAX_EXECUTION_TIMEOUT_MS, this.MAX_EXECUTION_TIMEOUT_MS);
        const bootTimeoutMs = 30000;

        return new Promise((resolve) => {
            let executionTimeoutId = setTimeout(() => {
                finish({
                    output: '',
                    error: 'Интерпретатор Python загружался слишком долго. Попробуйте ещё раз.',
                });
            }, bootTimeoutMs);
            let settled = false;

            const finish = (result) => {
                if (settled) {
                    return;
                }

                settled = true;
                clearTimeout(executionTimeoutId);
                worker.terminate();
                URL.revokeObjectURL(worker.objectUrl);
                resolve(result);
            };

            worker.onmessage = (event) => {
                const message = event.data || {};

                if (message.type === 'running') {
                    executionTimeoutId = setTimeout(() => {
                        finish({
                            output: '',
                            error: `Время выполнения превысило ${executionTimeoutMs / 1000} секунд`,
                        });
                    }, executionTimeoutMs);
                    return;
                }

                if (message.type === 'result') {
                    finish({
                        output: message.output || '',
                        error: message.error || '',
                    });
                    return;
                }

                if (message.type === 'error') {
                    finish({
                        output: '',
                        error: message.error || 'Ошибка выполнения кода',
                    });
                }
            };

            worker.onerror = (error) => {
                finish({
                    output: '',
                    error: error.message || 'Ошибка worker при выполнении кода',
                });
            };

            worker.postMessage({ type: 'run', code });
        });
    }
}
