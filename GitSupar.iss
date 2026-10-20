; GitSüpar Inno Setup Script
#define MyAppName "GitSüpar"
#define MyAppVersion "1.0.3"
#define MyAppPublisher "Süpar"
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
SetupIconFile=app_icon.ico

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
UninstallDisplayIcon={app}\app_icon.ico
UninstallDisplayName={#MyAppName}

[Languages]
Name: "turkish"; MessagesFile: "compiler:Languages\Turkish.isl"
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"

[Files]
Source: "GitSupar.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "app_icon.ico"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; IconFilename: "{app}\app_icon.ico"
Name: "{group}\{cm:UninstallProgram,{#MyAppName}}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon; IconFilename: "{app}\app_icon.ico"

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: nowait postinstall skipifsilent
