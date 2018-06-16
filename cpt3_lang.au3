Global $Rus = '00000419'; Раскладка русского языка
Global $Eng = '00000409'; Раскладка английского языка

Global $dll = DllOpen("user32.dll")


HotKeySet("{TAB}", '_lang')

While 1
   if get_lang() == $Rus Then
	  file_rus_lang(1)
   Else
	  file_rus_lang(0)
   EndIf

   Sleep(500)
WEnd

Func _lang()
   if get_lang() == $Rus Then
	  set_lang($Eng)
	  file_rus_lang(0)
   Else
	  set_lang($Rus)
	  file_rus_lang(1)
   EndIf
EndFunc

Func file_rus_lang($value)
   $file = FileOpen(@ScriptDir & '/files/rus_lang.txt', 2);
   FileWrite($file, $value);
   FileClose($file)
EndFunc

Func get_lang()
   $hWnd = WinGetHandle('[ACTIVE]')
	Local $Ret = DllCall($dll, 'long', 'GetWindowThreadProcessId', 'hwnd', $hWnd, 'ptr', 0)
	If (@error) Or ($Ret[0] = 0) Then
		Return SetError(1, 0, 0)
	EndIf
	$Ret = DllCall($dll, 'long', 'GetKeyboardLayout', 'long', $Ret[0])
	If (@error) Or ($Ret[0] = 0) Then
		Return SetError(1, 0, 0)
	EndIf
	Return '0000' & Hex($Ret[0], 4)
EndFunc   ;==>_WinAPI_GetKeyboardLayout

Func set_lang($sLayout)
   $hWnd = WinGetHandle('[ACTIVE]')
	If Not WinExists($hWnd) Then
		Return SetError(1, 0, 0)
	EndIf
	Local $Ret = DllCall($dll, 'long', 'LoadKeyboardLayout', 'str', StringFormat('%08s', StringStripWS($sLayout, 8)), 'int', 0)
	If (@error) Or ($Ret[0] = 0) Then
		Return SetError(1, 0, 0)
	EndIf
	DllCall($dll, 'ptr', 'SendMessage', 'hwnd', $hWnd, 'int', 0x0050, 'int', 1, 'int', $Ret[0])
	Return SetError(0, 0, 1)
 EndFunc   ;==>_WinAPI_SetKeyboardLayout