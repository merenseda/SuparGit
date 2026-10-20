import os
import sys
import json
import threading
import requests
import customtkinter as ctk
from tkinter import filedialog, messagebox

# ─── AYARLAR & SABİTLER ───────────────────────────────────────────────────────
DEFAULT_API_URL = "https://git.susluparantez.com.tr/api"
CONFIG_FILE = os.path.join(os.path.expanduser("~"), ".gitpush_config.json")
APP_VERSION = "1.0.2"
APP_VERSION_CODE = 3

ctk.set_appearance_mode("dark")
ctk.set_default_color_theme("blue")

class GitPushDesktopApp(ctk.CTk):
    def __init__(self):
        super().__init__()

        self.title("GitSüpar Desktop")
        self.geometry("860x650")
        self.minsize(740, 580)

        self.api_url = DEFAULT_API_URL
        self.auth_token = None
        self.user_data = None
        self.repos = []
        self.branches = []
        self.team_members = []
        self.selected_file_path = None
        self.selected_recipients = set()
        self.repo_error = None

        self.load_local_config()
        self.create_widgets()

        # Otomatik Güncelleme Kontrolü
        self.check_for_updates(silent=True)

        if self.auth_token:
            self.fetch_initial_data()
        else:
            self.show_login_frame()

    def load_local_config(self):
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, "r", encoding="utf-8") as f:
                    data = json.load(f)
                    self.auth_token = data.get("auth_token")
            except Exception:
                self.auth_token = None

    def save_local_config(self, token):
        self.auth_token = token
        try:
            with open(CONFIG_FILE, "w", encoding="utf-8") as f:
                json.dump({"auth_token": token}, f)
        except Exception:
            pass

    def clear_local_config(self):
        self.auth_token = None
        if os.path.exists(CONFIG_FILE):
            try:
                os.remove(CONFIG_FILE)
            except Exception:
                pass

    def get_auth_headers(self):
        return {
            "Authorization": f"Bearer {self.auth_token}",
            "Accept": "application/json"
        }

    def create_widgets(self):
        self.main_container = ctk.CTkFrame(self, corner_radius=0, fg_color="transparent")
        self.main_container.pack(fill="both", expand=True)

    def clear_container(self):
        for widget in self.main_container.winfo_children():
            widget.destroy()

    # ─── OTOMATİK GÜNCELLEME SİSTEMİ ─────────────────────────────────────────
    def check_for_updates(self, silent=True):
        def _check():
            try:
                res = requests.get(f"{self.api_url}/app-version?platform=desktop", timeout=6)
                if res.status_code == 200:
                    data = res.json().get("data", {})
                    server_code = data.get("version_code", 1)
                    if server_code > APP_VERSION_CODE:
                        self.after(0, lambda d=data: self.show_update_dialog(d))
                    elif not silent:
                        self.after(0, lambda: messagebox.showinfo("Güncel", f"Uygulamanız en son sürümde (v{APP_VERSION})."))
            except Exception as e:
                if not silent:
                    self.after(0, lambda err=str(e): messagebox.showerror("Hata", f"Güncelleme kontrolü başarısız:\n{err}"))

        threading.Thread(target=_check, daemon=True).start()

    def show_update_dialog(self, data):
        dialog = ctk.CTkToplevel(self)
        dialog.title("🚀 Yeni Güncelleme Mevcut!")
        dialog.geometry("480x380")
        dialog.resizable(False, False)
        dialog.transient(self)
        dialog.grab_set()

        lbl_title = ctk.CTkLabel(
            dialog,
            text=f"🚀 GitSüpar v{data.get('version')} Yayında!",
            font=ctk.CTkFont(size=18, weight="bold")
        )
        lbl_title.pack(pady=(20, 6), padx=20)

        lbl_curr = ctk.CTkLabel(
            dialog,
            text=f"Mevcut: v{APP_VERSION}  →  Yeni: v{data.get('version')}",
            font=ctk.CTkFont(size=12),
            text_color="#94a3b8"
        )
        lbl_curr.pack(pady=(0, 10))

        notes_box = ctk.CTkTextbox(dialog, height=90, font=ctk.CTkFont(size=11))
        notes_box.pack(fill="x", padx=24, pady=(0, 10))
        notes_box.insert("1.0", "Yenilikler:\n" + data.get("release_notes", "Performans ve hata düzeltmeleri."))
        notes_box.configure(state="disabled")

        lbl_status = ctk.CTkLabel(dialog, text="Program otomatik indirilip kendini güncelleyecektir.", font=ctk.CTkFont(size=11), text_color="#cbd5e1")
        lbl_status.pack(pady=(0, 6), padx=24)

        prog_bar = ctk.CTkProgressBar(dialog, height=10)
        prog_bar.set(0)

        btn_frame = ctk.CTkFrame(dialog, fg_color="transparent")
        btn_frame.pack(fill="x", padx=24, pady=12)

        btn_cancel = ctk.CTkButton(
            btn_frame, text="Daha Sonra", width=100, height=36,
            fg_color="#374151", hover_color="#4b5563",
            command=dialog.destroy
        )
        btn_cancel.pack(side="left")

        def start_download():
            btn_cancel.configure(state="disabled")
            btn_update.configure(state="disabled", text="İndiriliyor...")
            prog_bar.pack(fill="x", padx=24, pady=(0, 10))

            def _download_thread():
                import tempfile, subprocess
                download_url = data.get("download_url")
                temp_dir = tempfile.gettempdir()
                temp_new_exe = os.path.join(temp_dir, "GitSupar_new.exe")
                try:
                    r = requests.get(download_url, stream=True, timeout=90)
                    total_len = int(r.headers.get("content-length", 0))
                    downloaded = 0
                    with open(temp_new_exe, "wb") as f:
                        for chunk in r.iter_content(chunk_size=65536):
                            if chunk:
                                f.write(chunk)
                                downloaded += len(chunk)
                                if total_len > 0:
                                    pct = downloaded / total_len
                                    mb_done = downloaded / (1024 * 1024)
                                    mb_total = total_len / (1024 * 1024)
                                    self.after(0, lambda p=pct, d=mb_done, t=mb_total: (
                                        prog_bar.set(p),
                                        lbl_status.configure(text=f"İndiriliyor: %{int(p*100)} ({d:.1f} / {t:.1f} MB)")
                                    ))

                    self.after(0, lambda: lbl_status.configure(text="İndirme tamamlandı! Uygulama güncelleniyor..."))

                    is_frozen = getattr(sys, 'frozen', False)
                    target_exe = sys.executable if is_frozen else os.path.abspath("GitSupar.exe")

                    bat_path = os.path.join(temp_dir, "gitsupar_updater.bat")
                    bat_content = f"""@echo off
timeout /t 1 /nobreak > NUL
:retry
move /Y "{temp_new_exe}" "{target_exe}" > NUL 2>&1
if errorlevel 1 (
    timeout /t 1 /nobreak > NUL
    goto retry
)
start "" "{target_exe}"
del "%~f0"
"""
                    with open(bat_path, "w", encoding="utf-8") as bf:
                        bf.write(bat_content)

                    # Arka planda penceresiz çalıştır ve hemen çık
                    subprocess.Popen(["cmd.exe", "/c", bat_path], creationflags=0x08000000)
                    os._exit(0)

                except Exception as e:
                    self.after(0, lambda err=str(e): (
                        messagebox.showerror("Güncelleme Hatası", f"İndirme sırasında hata oluştu:\n{err}"),
                        btn_cancel.configure(state="normal"),
                        btn_update.configure(state="normal", text="Tekrar Dene")
                    ))

            threading.Thread(target=_download_thread, daemon=True).start()

        btn_update = ctk.CTkButton(
            btn_frame, text="Şimdi Güncelle 🚀", width=160, height=36,
            fg_color="#4f46e5", hover_color="#4338ca",
            command=start_download
        )
        btn_update.pack(side="right")

    # ─── GİRİŞ EKRANI ────────────────────────────────────────────────────────
    def show_login_frame(self):
        self.clear_container()

        frame = ctk.CTkFrame(self.main_container, width=420, corner_radius=16)
        frame.place(relx=0.5, rely=0.5, anchor="center")

        title = ctk.CTkLabel(frame, text="🚀 GitSüpar Desktop", font=ctk.CTkFont(size=24, weight="bold"))
        title.pack(pady=(28, 4), padx=30)

        subtitle = ctk.CTkLabel(frame, text="Hesabınıza giriş yapın", font=ctk.CTkFont(size=13), text_color="gray")
        subtitle.pack(pady=(0, 20), padx=30)

        self.entry_email = ctk.CTkEntry(frame, placeholder_text="E-posta Adresi", width=340, height=40)
        self.entry_email.pack(pady=8, padx=30)

        self.entry_password = ctk.CTkEntry(frame, placeholder_text="Parola", show="•", width=340, height=40)
        self.entry_password.pack(pady=8, padx=30)

        self.lbl_login_status = ctk.CTkLabel(frame, text="", font=ctk.CTkFont(size=12), text_color="#ef4444")
        self.lbl_login_status.pack(pady=4)

        btn_login = ctk.CTkButton(
            frame, text="Giriş Yap", width=340, height=42,
            font=ctk.CTkFont(size=14, weight="bold"),
            command=self.handle_login
        )
        btn_login.pack(pady=(10, 28), padx=30)

        # Sol alt köşede küçük versiyon bilgisi
        lbl_ver = ctk.CTkLabel(
            self.main_container,
            text=f"v{APP_VERSION}",
            font=ctk.CTkFont(size=11),
            text_color="#71717a"
        )
        lbl_ver.place(x=16, rely=1.0, y=-12, anchor="sw")

    def handle_login(self):
        email = self.entry_email.get().strip()
        password = self.entry_password.get().strip()

        if not email or not password:
            self.lbl_login_status.configure(text="Lütfen e-posta ve şifrenizi girin.")
            return

        self.lbl_login_status.configure(text="Giriş yapılıyor...", text_color="#3b82f6")

        def _login():
            try:
                res = requests.post(
                    f"{self.api_url}/login",
                    json={"email": email, "password": password},
                    headers={"Accept": "application/json"},
                    timeout=10
                )
                data = res.json()
                if res.status_code == 200 and data.get("success"):
                    token = data.get("token")
                    self.save_local_config(token)
                    self.user_data = data.get("user")
                    self.after(0, self.fetch_initial_data)
                else:
                    err_msg = data.get("message", "Giriş başarısız oldu.")
                    self.after(0, lambda m=err_msg: self.lbl_login_status.configure(text=m, text_color="#ef4444"))
            except Exception as e:
                err_msg = "Bağlantı hatası: Sunucuya ulaşılamadı."
                self.after(0, lambda m=err_msg: self.lbl_login_status.configure(text=m, text_color="#ef4444"))

        threading.Thread(target=_login, daemon=True).start()

    # ─── VERİLERİ YÜKLE (REPOLAR, EKİP ÜYELERİ) ──────────────────────────────
    def fetch_initial_data(self):
        self.clear_container()

        loading_lbl = ctk.CTkLabel(self.main_container, text="⏳ Repolar ve ekip üyeleri yükleniyor...", font=ctk.CTkFont(size=16))
        loading_lbl.place(relx=0.5, rely=0.5, anchor="center")

        def _fetch():
            headers = self.get_auth_headers()
            try:
                # Profil
                p_res = requests.get(f"{self.api_url}/profile", headers=headers, timeout=10)
                if p_res.status_code == 401:
                    self.clear_local_config()
                    self.after(0, self.show_login_frame)
                    return
                self.user_data = p_res.json()

                # Repolar
                r_res = requests.get(f"{self.api_url}/repos", headers=headers, timeout=15)
                if r_res.status_code == 200:
                    self.repos = r_res.json().get("repos", [])
                    self.repo_error = None
                else:
                    self.repos = []
                    try:
                        self.repo_error = r_res.json().get("message", "GitHub repoları yüklenemedi.")
                    except Exception:
                        self.repo_error = "GitHub repoları yüklenemedi."

                # Ekip üyeleri
                t_res = requests.get(f"{self.api_url}/team-members", headers=headers, timeout=10)
                if t_res.status_code == 200:
                    self.team_members = t_res.json().get("members", [])

                self.after(0, self.show_main_dashboard)
            except Exception as e:
                err_msg = str(e)
                self.after(0, lambda m=err_msg: messagebox.showerror("Hata", f"Veriler alınırken hata oluştu: {m}"))

        threading.Thread(target=_fetch, daemon=True).start()

    # ─── GITHUB TOKEN DIALOG ─────────────────────────────────────────────────
    def open_github_token_dialog(self):
        dialog = ctk.CTkInputDialog(
            text="GitHub Personal Access Token (PAT) girin:\n(repo iznine sahip ghp_ veya gho_ token)",
            title="GitHub Token Bağla / Güncelle"
        )
        token = dialog.get_input()
        if token and token.strip():
            self.save_github_token(token.strip())

    def save_github_token(self, token):
        headers = self.get_auth_headers()
        try:
            res = requests.post(
                f"{self.api_url}/profile",
                headers=headers,
                json={"github_token": token},
                timeout=15
            )
            data = res.json()
            if res.status_code == 200 and data.get("success"):
                messagebox.showinfo("Başarılı! 🎉", "GitHub hesabınız başarıyla bağlandı!\nRepolar yükleniyor...")
                self.fetch_initial_data()
            else:
                err = data.get("message", "Token kaydedilemedi.")
                messagebox.showerror("Hata", f"GitHub bağlantısı başarısız:\n{err}")
        except Exception as e:
            messagebox.showerror("Hata", f"Bağlantı hatası: {e}")

    # ─── ANA DASHBOARD & PUSH EKRANI ─────────────────────────────────────────
    def show_main_dashboard(self):
        self.clear_container()

        # Üst Header
        header = ctk.CTkFrame(self.main_container, height=60, corner_radius=0, fg_color="#18181b")
        header.pack(fill="x")

        lbl_user = ctk.CTkLabel(
            header,
            text="GitSüpar Desktop",
            font=ctk.CTkFont(size=16, weight="bold")
        )
        lbl_user.pack(side="left", padx=20, pady=15)

        # Sağ Butonlar
        btn_logout = ctk.CTkButton(
            header, text="Çıkış", width=60, height=32,
            fg_color="#dc2626", hover_color="#b91c1c",
            command=self.logout
        )
        btn_logout.pack(side="right", padx=(4, 16))

        btn_update = ctk.CTkButton(
            header, text="✨ Güncelleme", width=95, height=32,
            fg_color="#059669", hover_color="#047857",
            command=lambda: self.check_for_updates(silent=False)
        )
        btn_update.pack(side="right", padx=4)

        btn_refresh = ctk.CTkButton(
            header, text="🔄 Yenile", width=70, height=32,
            fg_color="#374151", hover_color="#4b5563",
            command=self.fetch_initial_data
        )
        btn_refresh.pack(side="right", padx=4)

        btn_gh = ctk.CTkButton(
            header, text="🔑 Token", width=80, height=32,
            fg_color="#4f46e5", hover_color="#4338ca",
            command=self.open_github_token_dialog
        )
        btn_gh.pack(side="right", padx=4)

        # Alt Durum Çubuğu (Versiyon sol alt köşede)
        footer = ctk.CTkFrame(self.main_container, height=26, corner_radius=0, fg_color="#18181b")
        footer.pack(fill="x", side="bottom")

        lbl_version = ctk.CTkLabel(
            footer,
            text=f"v{APP_VERSION}",
            font=ctk.CTkFont(size=11),
            text_color="#71717a"
        )
        lbl_version.pack(side="left", padx=16, pady=3)

        # Ana Gövde (Sol Form + Sağ Bildirim Listesi)
        body = ctk.CTkFrame(self.main_container, fg_color="transparent")
        body.pack(fill="both", expand=True, padx=20, pady=(12, 6))

        # ─── Sol Panel: Push Formu ──────────────────
        left_panel = ctk.CTkFrame(body, corner_radius=12)
        left_panel.pack(side="left", fill="both", expand=True, padx=(0, 10))

        # 1. Repo Seçimi
        lbl_repo = ctk.CTkLabel(left_panel, text="1. Hedef Repo Seçin:", font=ctk.CTkFont(size=13, weight="bold"))
        lbl_repo.pack(anchor="w", padx=16, pady=(16, 4))

        if self.repos:
            repo_names = [r["full_name"] for r in self.repos]
            self.combo_repo = ctk.CTkComboBox(
                left_panel, values=repo_names, height=36,
                command=self.on_repo_selected
            )
            self.combo_repo.pack(fill="x", padx=16, pady=(0, 10))
        else:
            # Repo bulunamadı / Token uyarısı
            err_text = self.repo_error or "Bağlı GitHub reposu bulunamadı."
            warn_frame = ctk.CTkFrame(left_panel, fg_color="#3f1e1e", corner_radius=8)
            warn_frame.pack(fill="x", padx=16, pady=(0, 10))

            lbl_warn = ctk.CTkLabel(
                warn_frame, text=f"⚠️ {err_text}",
                text_color="#fca5a5", font=ctk.CTkFont(size=11),
                wraplength=380, justify="left"
            )
            lbl_warn.pack(padx=12, pady=(8, 4), anchor="w")

            btn_bind = ctk.CTkButton(
                warn_frame, text="🔑 GitHub Token Gir ve Repoları Yükle",
                height=30, fg_color="#4f46e5", hover_color="#4338ca",
                command=self.open_github_token_dialog
            )
            btn_bind.pack(padx=12, pady=(0, 8), anchor="w")

            self.combo_repo = ctk.CTkComboBox(
                left_panel, values=["Repo yok - Önce token bağlayın"], height=36, state="disabled"
            )
            self.combo_repo.pack(fill="x", padx=16, pady=(0, 10))

        # 2. Branch Seçimi
        lbl_branch = ctk.CTkLabel(left_panel, text="2. Hedef Branch:", font=ctk.CTkFont(size=13, weight="bold"))
        lbl_branch.pack(anchor="w", padx=16, pady=(4, 4))

        self.combo_branch = ctk.CTkComboBox(left_panel, values=["Önce repo seçin"], height=36)
        self.combo_branch.pack(fill="x", padx=16, pady=(0, 10))

        # 3. Dosya Seçimi (Kutu & Sürükle / Seç)
        lbl_file = ctk.CTkLabel(left_panel, text="3. Yüklenecek Dosya:", font=ctk.CTkFont(size=13, weight="bold"))
        lbl_file.pack(anchor="w", padx=16, pady=(4, 4))

        file_box = ctk.CTkFrame(left_panel, height=75, fg_color="#27272a", corner_radius=10)
        file_box.pack(fill="x", padx=16, pady=(0, 10))
        file_box.pack_propagate(False)

        self.lbl_selected_file = ctk.CTkLabel(
            file_box, text="Dosya seçmek için tıklayın...",
            font=ctk.CTkFont(size=12), text_color="#a1a1aa"
        )
        self.lbl_selected_file.pack(side="left", padx=16)

        btn_browse = ctk.CTkButton(
            file_box, text="Gözat 📂", width=90, height=34,
            command=self.browse_file
        )
        btn_browse.pack(side="right", padx=12)

        # 4. Repo İçi Konum & Commit Mesajı
        lbl_path = ctk.CTkLabel(left_panel, text="Repo İçi Dosya Yolu:", font=ctk.CTkFont(size=12))
        lbl_path.pack(anchor="w", padx=16, pady=(2, 2))
        self.entry_path = ctk.CTkEntry(left_panel, placeholder_text="örn: src/main.py veya index.html", height=34)
        self.entry_path.pack(fill="x", padx=16, pady=(0, 8))

        lbl_commit = ctk.CTkLabel(left_panel, text="Commit Mesajı (Zorunlu):", font=ctk.CTkFont(size=12, weight="bold"))
        lbl_commit.pack(anchor="w", padx=16, pady=(2, 2))
        self.entry_commit = ctk.CTkEntry(left_panel, placeholder_text="Yapılan değişiklik hakkında kısa açıklama...", height=36)
        self.entry_commit.pack(fill="x", padx=16, pady=(0, 14))

        # Push Butonu
        self.btn_push = ctk.CTkButton(
            left_panel, text="🚀 GitHub'a Gönder ve Ekibi Bilgilendir",
            height=44, font=ctk.CTkFont(size=14, weight="bold"),
            fg_color="#4f46e5", hover_color="#4338ca",
            command=self.handle_push
        )
        self.btn_push.pack(fill="x", padx=16, pady=(0, 16))

        # ─── Sağ Panel: Bildirim Gönderilecekler ──────
        right_panel = ctk.CTkFrame(body, width=280, corner_radius=12)
        right_panel.pack(side="right", fill="both", expand=False)
        right_panel.pack_propagate(False)

        lbl_team = ctk.CTkLabel(right_panel, text="🔔 Bildirim Alıcıları", font=ctk.CTkFont(size=14, weight="bold"))
        lbl_team.pack(anchor="w", padx=16, pady=(16, 2))

        lbl_team_sub = ctk.CTkLabel(
            right_panel, text="Push sonrası kime e-posta ve mobil bildirim gönderilsin?",
            font=ctk.CTkFont(size=11), text_color="gray", wraplength=240, justify="left"
        )
        lbl_team_sub.pack(anchor="w", padx=16, pady=(0, 12))

        scroll_members = ctk.CTkScrollableFrame(right_panel, fg_color="transparent")
        scroll_members.pack(fill="both", expand=True, padx=8, pady=(0, 12))

        self.selected_recipients.clear()
        if self.team_members:
            for member in self.team_members:
                m_id = member["id"]
                m_name = member.get("name", "Üye")
                m_role = member.get("role", "developer")

                self.selected_recipients.add(m_id)
                cb = ctk.CTkCheckBox(
                    scroll_members,
                    text=f"{m_name} ({m_role})",
                    font=ctk.CTkFont(size=12),
                    command=lambda uid=m_id: self.toggle_recipient(uid)
                )
                cb.select()
                cb.pack(anchor="w", padx=8, pady=6)
        else:
            lbl_none = ctk.CTkLabel(scroll_members, text="Ekip üyesi bulunamadı.", font=ctk.CTkFont(size=12), text_color="gray")
            lbl_none.pack(pady=20)

    # ─── REPO & BRANCH SEÇİM METOTLARI ───────────────────────────────────────
    def on_repo_selected(self, chosen_repo):
        if not chosen_repo or "/" not in chosen_repo:
            return
        owner, repo = chosen_repo.split("/")
        self.combo_branch.configure(values=["Branch'ler yükleniyor..."])
        self.combo_branch.set("Branch'ler yükleniyor...")

        def _fetch_branches():
            headers = self.get_auth_headers()
            try:
                res = requests.get(
                    f"{self.api_url}/branches",
                    params={"owner": owner, "repo": repo},
                    headers=headers,
                    timeout=10
                )
                if res.status_code == 200:
                    branches = res.json().get("branches", [])
                    if branches:
                        self.after(0, lambda b=branches: self.update_branch_combo(b, chosen_repo))
                    else:
                        self.after(0, lambda: self.combo_branch.configure(values=["İzinli branch bulunamadı"]))
                        self.after(0, lambda: self.combo_branch.set("İzinli branch bulunamadı"))
                else:
                    self.after(0, lambda: self.combo_branch.configure(values=["Branch'ler alınamadı"]))
                    self.after(0, lambda: self.combo_branch.set("Branch'ler alınamadı"))
            except Exception as e:
                self.after(0, lambda: self.combo_branch.configure(values=["Hata oluştu"]))
                self.after(0, lambda: self.combo_branch.set("Hata oluştu"))

        threading.Thread(target=_fetch_branches, daemon=True).start()

    def update_branch_combo(self, branches, repo_name):
        self.combo_branch.configure(values=branches)
        selected_repo_data = next((r for r in self.repos if r.get("full_name") == repo_name), None)
        def_branch = selected_repo_data.get("default_branch") if selected_repo_data else None

        if def_branch and def_branch in branches:
            self.combo_branch.set(def_branch)
        elif branches:
            self.combo_branch.set(branches[0])

    def toggle_recipient(self, user_id):
        if user_id in self.selected_recipients:
            self.selected_recipients.remove(user_id)
        else:
            self.selected_recipients.add(user_id)

    def browse_file(self):
        f = filedialog.askopenfilename(title="GitHub'a Gönderilecek Dosyayı Seçin")
        if f:
            self.selected_file_path = f
            fname = os.path.basename(f)
            self.lbl_selected_file.configure(text=f"📄 {fname}", text_color="#38bdf8")
            if not self.entry_path.get().strip():
                self.entry_path.insert(0, fname)

    def handle_push(self):
        repo = self.combo_repo.get().strip()
        branch = self.combo_branch.get().strip()
        path = self.entry_path.get().strip()
        commit = self.entry_commit.get().strip()

        if not repo or "/" not in repo:
            messagebox.showwarning("Eksik Bilgi", "Lütfen geçerli bir repo seçin.")
            return
        if not branch or branch in ["Yükleniyor...", "Önce repo seçin", "İzinli branch bulunamadı", "Branch'ler alınamadı"]:
            messagebox.showwarning("Eksik Bilgi", "Lütfen geçerli bir branch seçin.")
            return
        if not self.selected_file_path or not os.path.exists(self.selected_file_path):
            messagebox.showwarning("Eksik Bilgi", "Lütfen yüklenecek bir dosya seçin.")
            return
        if not path:
            messagebox.showwarning("Eksik Bilgi", "Lütfen dosyanın repo içindeki yolunu yazın.")
            return
        if not commit or len(commit) < 3:
            messagebox.showwarning("Eksik Bilgi", "Lütfen en az 3 karakterlik bir commit açıklaması yazın.")
            return

        self.btn_push.configure(state="disabled", text="⏳ Push Gönderiliyor...")

        def _do_push():
            try:
                with open(self.selected_file_path, "rb") as f_obj:
                    files = {"file": (os.path.basename(self.selected_file_path), f_obj)}
                    data = {
                        "repo_full_name": repo,
                        "branch": branch,
                        "file_path": path,
                        "commit_message": commit,
                    }
                    for i, uid in enumerate(self.selected_recipients):
                        data[f"recipient_ids[{i}]"] = uid

                    res = requests.post(
                        f"{self.api_url}/push",
                        headers=self.get_auth_headers(),
                        data=data,
                        files=files,
                        timeout=30
                    )

                resp_data = res.json()
                if res.status_code == 200 and resp_data.get("success"):
                    self.after(0, lambda: self.push_success(resp_data))
                else:
                    err = resp_data.get("message", "Push işlemi başarısız oldu.")
                    self.after(0, lambda m=err: self.push_fail(m))
            except Exception as e:
                self.after(0, lambda m=str(e): self.push_fail(m))

        threading.Thread(target=_do_push, daemon=True).start()

    def push_success(self, data):
        self.btn_push.configure(state="normal", text="🚀 GitHub'a Gönder ve Ekibi Bilgilendir")
        self.entry_commit.delete(0, "end")
        messagebox.showinfo("Başarılı! 🎉", f"Dosya başarıyla GitHub'a push edildi!\nEkip üyelerine bildirim gönderildi.")

    def push_fail(self, error):
        self.btn_push.configure(state="normal", text="🚀 GitHub'a Gönder ve Ekibi Bilgilendir")
        messagebox.showerror("Push Hatası", f"Hata oluştu:\n{error}")

    def logout(self):
        if messagebox.askyesno("Çıkış", "Oturumu kapatmak istediğinize emin misiniz?"):
            self.clear_local_config()
            self.show_login_frame()

if __name__ == "__main__":
    app = GitPushDesktopApp()
    app.mainloop()
