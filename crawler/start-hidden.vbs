' Run crawler in background with auto-start (Windows)
Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "cmd /c cd /d """ & CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName) & """ && node ai-job-checker.js --auto-start > crawler.log 2>&1", 0, False
