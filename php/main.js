const {app, BrowserWindow} = require('electron') 
const url = require('url') 
const path = require('path')  

let win

function createWindow() { 
   win = new BrowserWindow({width: 1000, height: 980}) 
	win.loadURL('http://127.0.0.1:8000/index.php')
	win.removeMenu()
}  

app.on('ready', createWindow) 

app.on('activate', function () {
  if (win === null) {
    createWindow()
  }
})
