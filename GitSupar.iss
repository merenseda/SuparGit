; GitSüpar Inno Setup Script
#define MyAppName "GitSüpar"
#define MyAppVersion "1.0.1"
#define MyAppPublisher "Süslü Parantez"
#define MyAppURL "https://git.susluparantez.com.tr"
#define MyAppExeName "GitSupar.exe"

[Setup]
AppId={{D37D19A4-68BF-4D2B-8A89-2228EBF49F12}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}

; Kullanıcı profiline kurulum (Yönetici izni gerektirmez, otomatik güncelleme sorunsuz çalışır)
DefaultDirName={localappdata}\Programs\GitSupar
DefaultGroupName={#MyAppName}
AllowNoIcons=yes

; Çıkış dosyası
OutputDir=.
OutputBaseFilename=GitSupar-Setup
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern

; Yetki ayarları
PrivilegesRequired=lowest
PrivilegesRequiredOverridesAllowed=dialog

; Program açıkken kurulum/güncelleme yapılırsa otomatik kapatıp yeniden açabilme
CloseApplications=yes
RestartApplications=yes

; Denetim masası / Program ekle kaldır bilgileri
UninstallDisplayIcon={app}\{#MyAppExeName}
UninstallDisplayName={#MyAppName}

[Languages]
Name: "turkish"; MessagesFile: "compiler:Languages\Turkish.isl"
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"

[Files]
Source: "GitSupar.exe"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"
Name: "{group}\{cm:UninstallProgram,{#MyAppName}}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: nowait postinstall skipifsilent
