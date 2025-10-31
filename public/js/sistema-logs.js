/**
 * SISTEMA DE CAPTURA DE LOGS DO NAVEGADOR
 *
 * Este script captura todos os erros JavaScript, console.log, console.error, etc
 * e envia para o servidor para análise detalhada.
 *
 * Autor: Claude Code (Anthropic)
 * Data: 18/10/2025
 * Sistema: Cesta de Preços - DattaPro
 */

(function() {
    'use strict';

    // Configuração
    const basePath = window.APP_BASE_PATH || '';
    const CONFIG = {
        enabled: true, // Ativar/desativar sistema de logs
        endpoint: basePath + '/api/logs/browser',
        batchSize: 10, // Enviar logs em lotes
        batchInterval: 5000, // Enviar a cada 5 segundos
        captureConsole: true, // Capturar console.log, console.warn, etc
        captureErrors: true, // Capturar erros não tratados
        captureUnhandledRejections: true, // Capturar promises rejeitadas
        verbose: true // Mostrar logs no console original também
    };

    // Fila de logs para envio em lote
    let logQueue = [];
    let batchTimer = null;

    /**
     * Enviar logs para o servidor
     */
    function sendLogs(logs) {
        if (!CONFIG.enabled || logs.length === 0) {
            return;
        }

        // Obter token CSRF do meta tag ou cookie
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                         getCookie('XSRF-TOKEN');

        // Enviar cada log individualmente para melhor rastreamento
        logs.forEach(logData => {
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            // Adicionar CSRF token se disponível
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }

            fetch(CONFIG.endpoint, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(logData)
            }).catch(err => {
                // Evitar loop infinito - não logar erros de logging
                if (CONFIG.verbose) {
                    console.warn('[SISTEMA DE LOGS] Erro ao enviar log:', err);
                }
            });
        });
    }

    /**
     * Obter cookie por nome
     */
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }
        return null;
    }

    /**
     * Adicionar log à fila
     */
    function queueLog(logData) {
        logQueue.push(logData);

        // Se atingiu o tamanho do lote, enviar imediatamente
        if (logQueue.length >= CONFIG.batchSize) {
            flushLogs();
        } else if (!batchTimer) {
            // Agendar envio em lote
            batchTimer = setTimeout(flushLogs, CONFIG.batchInterval);
        }
    }

    /**
     * Enviar todos os logs da fila
     */
    function flushLogs() {
        if (logQueue.length > 0) {
            const logsToSend = [...logQueue];
            logQueue = [];
            sendLogs(logsToSend);
        }

        if (batchTimer) {
            clearTimeout(batchTimer);
            batchTimer = null;
        }
    }

    /**
     * Criar objeto de log com contexto completo
     */
    function createLogObject(type, args, errorObj = null) {
        const logData = {
            type: type,
            message: args.map(arg => {
                if (typeof arg === 'object') {
                    try {
                        return JSON.stringify(arg, null, 2);
                    } catch (e) {
                        return String(arg);
                    }
                }
                return String(arg);
            }).join(' '),
            url: window.location.href,
            timestamp: Date.now(),
            userAgent: navigator.userAgent
        };

        // Adicionar informações de erro se disponível
        if (errorObj) {
            logData.line = errorObj.lineno || null;
            logData.column = errorObj.colno || null;
            logData.stack = errorObj.error ? errorObj.error.stack : null;
        }

        return logData;
    }

    /**
     * Sobrescrever métodos do console para captura
     */
    if (CONFIG.captureConsole) {
        const originalConsole = {
            log: console.log,
            info: console.info,
            warn: console.warn,
            error: console.error,
            debug: console.debug
        };

        // console.log
        console.log = function(...args) {
            if (CONFIG.verbose) {
                originalConsole.log.apply(console, args);
            }
            queueLog(createLogObject('log', args));
        };

        // console.info
        console.info = function(...args) {
            if (CONFIG.verbose) {
                originalConsole.info.apply(console, args);
            }
            queueLog(createLogObject('info', args));
        };

        // console.warn
        console.warn = function(...args) {
            if (CONFIG.verbose) {
                originalConsole.warn.apply(console, args);
            }
            queueLog(createLogObject('warn', args));
        };

        // console.error
        console.error = function(...args) {
            if (CONFIG.verbose) {
                originalConsole.error.apply(console, args);
            }
            queueLog(createLogObject('error', args));
        };

        // console.debug
        console.debug = function(...args) {
            if (CONFIG.verbose) {
                originalConsole.debug.apply(console, args);
            }
            queueLog(createLogObject('debug', args));
        };

        // Preservar referência ao console original
        window._originalConsole = originalConsole;
    }

    /**
     * Capturar erros JavaScript não tratados
     */
    if (CONFIG.captureErrors) {
        window.addEventListener('error', function(event) {
            const logData = createLogObject('error', [event.message], {
                lineno: event.lineno,
                colno: event.colno,
                error: event.error
            });

            logData.filename = event.filename;

            queueLog(logData);

            // Não prevenir comportamento padrão - deixar outros handlers executarem
            return false;
        }, true);
    }

    /**
     * Capturar promises rejeitadas não tratadas
     */
    if (CONFIG.captureUnhandledRejections) {
        window.addEventListener('unhandledrejection', function(event) {
            const logData = createLogObject('error', [
                'Unhandled Promise Rejection:',
                event.reason
            ]);

            if (event.reason && event.reason.stack) {
                logData.stack = event.reason.stack;
            }

            queueLog(logData);
        });
    }

    /**
     * Enviar logs restantes ao sair da página
     */
    window.addEventListener('beforeunload', function() {
        flushLogs();
    });

    /**
     * Função pública para desativar captura temporariamente
     */
    window.SistemaLogs = {
        enable: function() {
            CONFIG.enabled = true;
//             console.log('[SISTEMA DE LOGS] Ativado');
        },
        disable: function() {
            CONFIG.enabled = false;
//             console.log('[SISTEMA DE LOGS] Desativado');
        },
        flush: flushLogs,
        getQueue: function() {
            return [...logQueue];
        },
        config: CONFIG
    };

    // Indicar que sistema está ativo
    if (CONFIG.verbose) {
//         console.log('%c[SISTEMA DE LOGS] Sistema de captura ativo', 'color: #10b981; font-weight: bold;');
//         console.log('[SISTEMA DE LOGS] Configuração:', CONFIG);
    }
})();
