import os
import sys
import json
import threading
import tkinter as tk
from tkinter import filedialog, messagebox
import customtkinter as ctk
import requests

ctk.set_appearance_mode("Dark")
ctk.set_default_color_theme("blue")

CONFIG_FILE = os.path.join(os.path.expanduser("~"), ".gitpush_config.json")
DEFAULT_API_URL = "https://git.susluparantez.com.tr/api"

class GitPushDesktopApp(ctk.CTk):
    def __init__(self):
        super().__init__()

        self.title("GitSüpar Desktop — Ekip Kolay Git Yükleme")
        self.geometry("780x620")
        self.minsize(700, 560)

        self.api_url = DEFAULT_API_URL
        self.auth_token = None
        self.user_data = None
        self.repos = []
        self.branches = []
        self.team_members = []
        self.selected_file_path = None
        self.selected_recipients = set()

        self.load_local_config()
        self.create_widgets()

        if self.auth_token:
            self.fetch_initial_data()
        else:
            self.show_login_frame()

    def load_local_config(self):
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, "r", encoding="utf-8") as f:
                    data = json.load(f)
                    self.auth_token = data.get("token")
                    self.api_url = data.get("api_url", DEFAULT_API_URL)
            except Exception:
                pass

    def save_local_config(self):
        try:
            with open(CONFIG_FILE, "w", encoding="utf-8") as f:
                json.dump({
                    "token": self.auth_token,
                    "api_url": self.api_url
                }, f)
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

    def handle_login(self):
        email = self.entry_email.get().strip()
        password = self.entry_password.get().strip()

        if not email or not password:
            self.lbl_login_status.configure(text="Lütfen e-posta ve şifrenizi girin.")
            return

        self.lbl_login_status.configure(text="Giriş yapılıyor...", text_color="#3b82f6")

        def _req():
            headers = {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "User-Agent": "GitPushDesktop/1.0"
            }
            try:
                print(f"İstek gönderiliyor: {self.api_url}/login")
                res = requests.post(
                    f"{self.api_url}/login",
                    json={"email": email, "password": password},
                    headers=headers,
                    timeout=15
                )
                print(f"Yanıt kodu: {res.status_code}")
                try:
                    data = res.json()
                except Exception:
                    data = {"message": res.text or f"HTTP {res.status_code}"}

                if res.status_code == 200 and data.get("success"):
                    self.auth_token = data.get("token")
                    self.user_data = data.get("user")
                    self.save_local_config()
                    self.after(0, self.fetch_initial_data)
                else:
                    msg = data.get("message", "Giriş başarısız.")
                    self.after(0, lambda m=msg: self.lbl_login_status.configure(text=m, text_color="#ef4444"))
            except Exception as e:
                import traceback
                traceback.print_exc()
                err_text = f"Hata: {str(e)[:45]}"
                self.after(0, lambda t=err_text: self.lbl_login_status.configure(text=t, text_color="#ef4444"))

        threading.Thread(target=_req, daemon=True).start()

    # ─── VERİ YÜKLEME ────────────────────────────────────────────────────────
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
                r_res = requests.get(f"{self.api_url}/repos", headers=headers, timeout=12)
                if r_res.status_code == 200:
                    self.repos = r_res.json().get("repos", [])

                # Ekip üyeleri
                t_res = requests.get(f"{self.api_url}/team-members", headers=headers, timeout=10)
                if t_res.status_code == 200:
                    self.team_members = t_res.json().get("members", [])

                self.after(0, self.show_main_dashboard)
            except Exception as e:
                err_msg = str(e)
                self.after(0, lambda m=err_msg: messagebox.showerror("Hata", f"Veriler alınırken hata oluştu: {m}"))

        threading.Thread(target=_fetch, daemon=True).start()

    # ─── ANA DASHBOARD & PUSH EKRANI ─────────────────────────────────────────
    def show_main_dashboard(self):
        self.clear_container()

        # Üst Header
        header = ctk.CTkFrame(self.main_container, height=60, corner_radius=0, fg_color="#18181b")
        header.pack(fill="x")

        u_name = self.user_data.get("name", "Kullanıcı") if self.user_data else ""
        u_gh = self.user_data.get("github_username", "") if self.user_data else ""
        gh_badge = f"(@{u_gh})" if u_gh else "(GitHub Bağlı Değil)"

        lbl_user = ctk.CTkLabel(
            header,
            text=f"🚀 GitSüpar — {u_name} {gh_badge}",
            font=ctk.CTkFont(size=15, weight="bold")
        )
        lbl_user.pack(side="left", padx=20, pady=15)

        btn_logout = ctk.CTkButton(
            header, text="Çıkış", width=70, height=32,
            fg_color="#dc2626", hover_color="#b91c1c",
            command=self.logout
        )
        btn_logout.pack(side="right", padx=20)

        # Ana Gövde (Sol Form + Sağ Bildirim Listesi)
        body = ctk.CTkFrame(self.main_container, fg_color="transparent")
        body.pack(fill="both", expand=True, padx=20, pady=16)

        # ─── Sol Panel: Push Formu ──────────────────
        left_panel = ctk.CTkFrame(body, corner_radius=12)
        left_panel.pack(side="left", fill="both", expand=True, padx=(0, 10))

        # 1. Repo Seçimi
        lbl_repo = ctk.CTkLabel(left_panel, text="1. Hedef Repo Seçin:", font=ctk.CTkFont(size=13, weight="bold"))
        lbl_repo.pack(anchor="w", padx=16, pady=(16, 4))

        repo_names = [r["full_name"] for r in self.repos] if self.repos else ["Bağlı repo bulunamadı"]
        self.combo_repo = ctk.CTkComboBox(
            left_panel, values=repo_names, height=36,
            command=self.on_repo_selected
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

        # ─── Sağ Panel: Ekip Bildirim Alıcıları ─────
        right_panel = ctk.CTkFrame(body, width=240, corner_radius=12)
        right_panel.pack(side="right", fill="y", padx=(10, 0))
        right_panel.pack_propagate(False)

        lbl_team = ctk.CTkLabel(right_panel, text="🔔 Bildirim Alıcıları", font=ctk.CTkFont(size=13, weight="bold"))
        lbl_team.pack(anchor="w", padx=14, pady=(16, 4))

        lbl_team_sub = ctk.CTkLabel(
            right_panel, text="İşlem sonrası e-posta ve push alacak ekip üyeleri:",
            font=ctk.CTkFont(size=11), text_color="gray", wraplength=210
        )
        lbl_team_sub.pack(anchor="w", padx=14, pady=(0, 10))

        scroll_team = ctk.CTkScrollableFrame(right_panel, fg_color="transparent")
        scroll_team.pack(fill="both", expand=True, padx=6, pady=4)

        if self.team_members:
            for member in self.team_members:
                var = tk.BooleanVar(value=True)
                self.selected_recipients.add(member["id"])

                def _toggle(uid=member["id"], v=var):
                    if v.get():
                        self.selected_recipients.add(uid)
                    else:
                        self.selected_recipients.discard(uid)

                cb = ctk.CTkCheckBox(
                    scroll_team,
                    text=f"{member['name']}\n{member.get('role', 'dev')}",
                    variable=var,
                    command=_toggle,
                    font=ctk.CTkFont(size=12)
                )
                cb.pack(anchor="w", pady=6, padx=8)
        else:
            ctk.CTkLabel(scroll_team, text="Kayıtlı başka üye yok.", text_color="gray").pack(pady=20)

        # İlk reponun branch'lerini otomatik yükle
        if self.repos:
            first_repo = self.repos[0]["full_name"]
            self.combo_repo.set(first_repo)
            self.on_repo_selected(first_repo)

    def on_repo_selected(self, repo_full_name):
        if not repo_full_name or "/" not in repo_full_name:
            return

        self.combo_branch.configure(values=["Yükleniyor..."])
        self.combo_branch.set("Yükleniyor...")
        owner, repo = repo_full_name.split("/")

        def _fetch_b():
            try:
                res = requests.get(
                    f"{self.api_url}/branches",
                    headers=self.get_auth_headers(),
                    params={"owner": owner, "repo": repo},
                    timeout=10
                )
                if res.status_code == 200:
                    branches = res.json().get("branches", [])
                    if branches:
                        self.after(0, lambda: self.update_branches_ui(branches))
                    else:
                        self.after(0, lambda: self.combo_branch.configure(values=["main"]))
            except Exception:
                self.after(0, lambda: self.combo_branch.configure(values=["main"]))

        threading.Thread(target=_fetch_b, daemon=True).start()

    def update_branches_ui(self, branches):
        self.combo_branch.configure(values=branches)
        if "main" in branches:
            self.combo_branch.set("main")
        elif "master" in branches:
            self.combo_branch.set("master")
        elif branches:
            self.combo_branch.set(branches[0])

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
        if not branch or branch in ["Yükleniyor...", "Önce repo seçin"]:
            messagebox.showwarning("Eksik Bilgi", "Lütfen bir branch seçin.")
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
                    self.after(0, lambda: self.push_fail(err))
            except Exception as e:
                self.after(0, lambda: self.push_fail(str(e)))

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
