; ============================================================
; Inno Setup Script — Dashboard Statistik Umur (Kantor Imigrasi)
; ============================================================
; Cara pakai:
;   1. Siapkan folder "build\" di samping file .iss ini (lihat struktur di bawah).
;   2. Buka file ini di Inno Setup Compiler (https://jrsoftware.org/isinfo.php).
;   3. Klik Build > Compile (Ctrl+F9). Hasil: "Output\DashboardUmur-Setup.exe".
;
; Struktur folder "build\" yang diperlukan:
;   build\
;   ├── php\             ← PHP portable Windows x64 Thread Safe (windows.php.net)
;   │   ├── php.exe
;   │   ├── php.ini      ← sudah dikonfigurasi (lihat OFFLINE.md)
;   │   └── ext\
;   ├── app\             ← dari paket Laravel
;   ├── bootstrap\
;   ├── config\
;   ├── database\
;   ├── public\          ← termasuk public\vendor\chartjs\chart.umd.js
;   ├── resources\
;   ├── routes\
;   ├── storage\
;   ├── vendor\          ← hasil "composer install --no-dev --optimize-autoloader"
;   ├── artisan
;   ├── composer.json
;   └── .env             ← salin dari .env.offline, lalu php artisan key:generate
;
; Catatan: folder "build\" TIDAK perlu berisi JALANKAN.bat —
; installer memakai launcher yang dibuat sendiri di bawah.
; ============================================================

#define AppName    "Dashboard Statistik Umur"
#define AppVersion "1.0"
#define AppPublisher "Kantor Imigrasi"
#define AppExeName "BUKA-DASHBOARD.exe"
#define BuildDir   "build"

[Setup]
AppName={#AppName}
AppVersion={#AppVersion}
AppPublisher={#AppPublisher}
AppId={{A1B2C3D4-E5F6-7890-ABCD-EF1234567890}
DefaultDirName={autopf}\DashboardUmur
DefaultGroupName={#AppName}
OutputDir=Output
OutputBaseFilename=DashboardUmur-Setup
SetupIconFile=
Compression=lzma2/ultra64
SolidCompression=yes
; Tidak butuh admin — user biasa pun bisa install
PrivilegesRequired=lowest
; Tampilan installer
WizardStyle=modern
WizardSizePercent=120
; Tidak perlu restart
RestartIfNeededByRun=no
; Matikan server saat uninstall
UninstallDisplayName={#AppName}
; Informasi legal (opsional, bisa dikosongkan)
LicenseFile=
InfoBeforeFile=
InfoAfterFile=

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "Buat ikon di Desktop"; GroupDescription: "Pintasan:"; Flags: unchecked
Name: "startmenu";  Description: "Buat pintasan di Start Menu"; GroupDescription: "Pintasan:"; Flags: checkedonce

[Files]
; ── Seluruh isi folder build\ ──────────────────────────────────────────────
; PHP portable
Source: "{#BuildDir}\php\*"; DestDir: "{app}\php"; Flags: recursesubdirs createallsubdirs; Excludes: "*.pdb"

; Laravel core
Source: "{#BuildDir}\app\*";       DestDir: "{app}\app";       Flags: recursesubdirs createallsubdirs
Source: "{#BuildDir}\bootstrap\*"; DestDir: "{app}\bootstrap"; Flags: recursesubdirs createallsubdirs
Source: "{#BuildDir}\config\*";    DestDir: "{app}\config";    Flags: recursesubdirs createallsubdirs
Source: "{#BuildDir}\database\*";  DestDir: "{app}\database";  Flags: recursesubdirs createallsubdirs
Source: "{#BuildDir}\public\*";    DestDir: "{app}\public";    Flags: recursesubdirs createallsubdirs
Source: "{#BuildDir}\resources\*"; DestDir: "{app}\resources"; Flags: recursesubdirs createallsubdirs
Source: "{#BuildDir}\routes\*";    DestDir: "{app}\routes";    Flags: recursesubdirs createallsubdirs
Source: "{#BuildDir}\storage\*";   DestDir: "{app}\storage";   Flags: recursesubdirs createallsubdirs
Source: "{#BuildDir}\vendor\*";    DestDir: "{app}\vendor";    Flags: recursesubdirs createallsubdirs

; File tunggal di root proyek
Source: "{#BuildDir}\artisan";        DestDir: "{app}"; Flags: ignoreversion
Source: "{#BuildDir}\composer.json";  DestDir: "{app}"; Flags: ignoreversion
Source: "{#BuildDir}\.env";           DestDir: "{app}"; Flags: ignoreversion

; ── Launcher kecil (dibuat sebagai skrip, dikompilasi saat install) ─────────
; Kita tulis launcher .bat ke folder install; ikon .exe dibuat via Shell link.
Source: "{tmp}\launcher.bat"; DestDir: "{app}"; DestName: "_launcher.bat"; Flags: external ignoreversion

[Dirs]
; Pastikan storage bisa ditulis oleh user
Name: "{app}\storage\app\public"; Permissions: users-full
Name: "{app}\storage\framework\cache"; Permissions: users-full
Name: "{app}\storage\framework\sessions"; Permissions: users-full
Name: "{app}\storage\framework\views"; Permissions: users-full
Name: "{app}\storage\logs"; Permissions: users-full
Name: "{app}\database"; Permissions: users-full

[Icons]
; Start Menu
Name: "{group}\{#AppName}";       Filename: "{app}\php\php.exe"; Parameters: "-f ""{app}\artisan"" serve --host=127.0.0.1 --port=8000"; WorkingDir: "{app}"; IconFilename: "{app}\public\favicon.ico"; Flags: createonlyiffileexists; Tasks: startmenu
Name: "{group}\Buka Dashboard";   Filename: "{app}\_launcher.bat"; WorkingDir: "{app}"; Tasks: startmenu
Name: "{group}\Uninstall {#AppName}"; Filename: "{uninstallexe}"; Tasks: startmenu

; Desktop
Name: "{autodesktop}\{#AppName}"; Filename: "{app}\_launcher.bat"; WorkingDir: "{app}"; Tasks: desktopicon

[Run]
; Jalankan migrasi database saat selesai install
Filename: "{app}\php\php.exe"; Parameters: "artisan migrate --force"; WorkingDir: "{app}"; StatusMsg: "Menyiapkan database..."; Flags: runhidden waituntilterminated

; Tawari buka app setelah install selesai
Filename: "{app}\_launcher.bat"; Description: "Buka Dashboard sekarang"; WorkingDir: "{app}"; Flags: postinstall nowait skipifsilent shellexec

[UninstallRun]
; Hentikan server PHP sebelum uninstall
Filename: "taskkill"; Parameters: "/f /im php.exe"; Flags: runhidden waituntilterminated; RunOnceId: "StopPhp"

[Code]
// ── Buat launcher .bat di folder temp sebelum install dimulai ──────────────
procedure CreateLauncher;
var
  LauncherPath: string;
  Lines: TArrayOfString;
  I: Integer;
begin
  LauncherPath := ExpandConstant('{tmp}\launcher.bat');
  SetArrayLength(Lines, 30);
  I := 0;
  Lines[I] := '@echo off'; Inc(I);
  Lines[I] := 'title Dashboard Statistik Umur'; Inc(I);
  Lines[I] := 'cd /d "%~dp0"'; Inc(I);
  Lines[I] := ''; Inc(I);
  Lines[I] := 'rem Cek apakah server sudah jalan di port 8000'; Inc(I);
  Lines[I] := 'netstat -an | find "127.0.0.1:8000" | find "LISTENING" >nul 2>&1'; Inc(I);
  Lines[I] := 'if not errorlevel 1 ('; Inc(I);
  Lines[I] := '    start http://127.0.0.1:8000/dashboard'; Inc(I);
  Lines[I] := '    exit /b 0'; Inc(I);
  Lines[I] := ')'; Inc(I);
  Lines[I] := ''; Inc(I);
  Lines[I] := 'rem Buat database.sqlite kalau belum ada'; Inc(I);
  Lines[I] := 'if not exist "database\database.sqlite" ('; Inc(I);
  Lines[I] := '    type nul > "database\database.sqlite"'; Inc(I);
  Lines[I] := ')'; Inc(I);
  Lines[I] := ''; Inc(I);
  Lines[I] := 'rem Buka browser setelah 2 detik'; Inc(I);
  Lines[I] := 'start "" cmd /c "timeout /t 2 /nobreak >nul & start http://127.0.0.1:8000/dashboard"'; Inc(I);
  Lines[I] := ''; Inc(I);
  Lines[I] := 'rem Jalankan server (jendela ini tetap terbuka; tutup untuk berhenti)'; Inc(I);
  Lines[I] := 'echo Dashboard berjalan di http://127.0.0.1:8000'; Inc(I);
  Lines[I] := 'echo Jangan tutup jendela ini selama memakai aplikasi.'; Inc(I);
  Lines[I] := 'echo.'; Inc(I);
  Lines[I] := 'php\php.exe artisan serve --host=127.0.0.1 --port=8000'; Inc(I);
  Lines[I] := 'pause'; Inc(I);

  SetArrayLength(Lines, I);
  SaveStringsToFile(LauncherPath, Lines, False);
end;

procedure InitializeWizard;
begin
  CreateLauncher;
end;
