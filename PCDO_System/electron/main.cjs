const { app, BrowserWindow } = require('electron')
const { spawn } = require('child_process')
const path = require('path')

let php, win

app.whenReady().then(() => {
    const base = path.resolve(__dirname, '..')

    const phpPath = app.isPackaged
        ? path.join(process.resourcesPath, 'php', 'php.exe')
        : path.join(__dirname, 'resources', 'php', 'php.exe')

    php = spawn(phpPath, ['artisan', 'serve', '--host=127.0.0.1', '--port=8000'], { cwd: base })

    win = new BrowserWindow({ width: 900, height: 650 })
    win.loadURL('http://127.0.0.1:8000')
})

app.on('before-quit', () => {
    if (php && !php.killed) php.kill()
})
