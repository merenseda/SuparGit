import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _githubTokenController = TextEditingController();

  bool _isLoading = true;
  bool _isSaving = false;
  bool _notificationsEnabled = true;
  Map<String, dynamic>? _user;
  String? _statusMessage;
  bool _isError = false;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _githubTokenController.dispose();
    super.dispose();
  }

  Future<void> _loadProfile() async {
    setState(() => _isLoading = true);
    final user = await ApiService.getProfile();
    if (user != null && mounted) {
      setState(() {
        _user = user;
        _nameController.text = user['name'] ?? '';
        _emailController.text = user['email'] ?? '';
        _notificationsEnabled = user['notifications_enabled'] ?? true;
        _isLoading = false;
      });
    } else {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _saveProfile() async {
    final name = _nameController.text.trim();
    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();
    final githubToken = _githubTokenController.text.trim();

    if (name.isEmpty || email.isEmpty) {
      setState(() {
        _isError = true;
        _statusMessage = 'Lütfen ad soyad ve e-posta alanlarını doldurun.';
      });
      return;
    }

    setState(() {
      _isSaving = true;
      _statusMessage = null;
    });

    try {
      final res = await ApiService.updateProfile(
        name: name,
        email: email,
        password: password.isNotEmpty ? password : null,
        notificationsEnabled: _notificationsEnabled,
        githubToken: githubToken.isNotEmpty ? githubToken : null,
      );

      if (mounted) {
        if (res['success'] == true) {
          setState(() {
            _isError = false;
            _statusMessage = 'Profil bilgileri başarıyla güncellendi!';
            _passwordController.clear();
            _githubTokenController.clear();
            if (res['user'] != null) {
              _user = res['user'];
            }
          });
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('✅ Profil başarıyla güncellendi'),
              backgroundColor: Color(0xFF10B981),
            ),
          );
        } else {
          setState(() {
            _isError = true;
            _statusMessage = res['message'] ?? 'Güncelleme yapılamadı.';
          });
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isError = true;
          _statusMessage = 'Bağlantı hatası: Sunucuya ulaşılamadı.';
        });
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF6366F1)),
      );
    }

    final githubConnected = _user?['github_connected'] == true;
    final githubUsername = _user?['github_username'];
    final githubAvatar = _user?['github_avatar'];
    final role = _user?['role'] ?? 'developer';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Profil Üst Başlık Kartı
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFF334155)),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 32,
                  backgroundColor: const Color(0xFF6366F1),
                  backgroundImage: (githubAvatar != null && githubAvatar.isNotEmpty)
                      ? NetworkImage(githubAvatar)
                      : null,
                  child: (githubAvatar == null || githubAvatar.isEmpty)
                      ? Text(
                          (_user?['name'] != null && _user!['name'].isNotEmpty)
                              ? _user!['name'][0].toUpperCase()
                              : '👤',
                          style: const TextStyle(fontSize: 28, color: Colors.white, fontWeight: FontWeight.bold),
                        )
                      : null,
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _user?['name'] ?? 'Kullanıcı',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        _user?['email'] ?? '',
                        style: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                      ),
                      const SizedBox(height: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFF334155),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          'Rol: ${role.toUpperCase()}',
                          style: const TextStyle(color: Color(0xFF818CF8), fontSize: 11, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // GitHub Durum Kartı
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: githubConnected ? const Color(0xFF059669) : const Color(0xFFD97706),
              ),
            ),
            child: Row(
              children: [
                Icon(
                  githubConnected ? Icons.check_circle : Icons.warning_amber_rounded,
                  color: githubConnected ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                  size: 26,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        githubConnected ? 'GitHub Hesabı Bağlı' : 'GitHub Bağlı Değil',
                        style: TextStyle(
                          color: githubConnected ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        githubConnected
                            ? '@$githubUsername olarak bağlı'
                            : 'Aşağıdan Token girerek veya Webten bağlayabilirsiniz.',
                        style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Durum Mesajı
          if (_statusMessage != null) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: _isError ? const Color(0xFF450A0A) : const Color(0xFF064E3B),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: _isError ? const Color(0xFFDC2626) : const Color(0xFF059669),
                ),
              ),
              child: Text(
                _statusMessage!,
                style: TextStyle(
                  color: _isError ? const Color(0xFFFCA5A5) : const Color(0xFFA7F3D0),
                  fontSize: 13,
                ),
              ),
            ),
            const SizedBox(height: 16),
          ],

          // Form Alanları
          _buildTextField(
            controller: _nameController,
            label: 'Ad Soyad',
            icon: Icons.person_outline,
          ),
          const SizedBox(height: 14),

          _buildTextField(
            controller: _emailController,
            label: 'E-posta Adresi',
            icon: Icons.email_outlined,
            keyboardType: TextInputType.emailAddress,
          ),
          const SizedBox(height: 14),

          _buildTextField(
            controller: _passwordController,
            label: 'Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)',
            icon: Icons.lock_outline,
            obscureText: true,
          ),
          const SizedBox(height: 14),

          _buildTextField(
            controller: _githubTokenController,
            label: 'GitHub Personal Access Token (PAT)',
            icon: Icons.vpn_key_outlined,
            obscureText: true,
            helperText: 'GitHub repo ve commit entegrasyonu için token girin.',
          ),
          const SizedBox(height: 16),

          // Bildirim Tercihi Switch
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: const Color(0xFF1E293B),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.notifications_active_outlined, color: Color(0xFF818CF8), size: 22),
                    SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Push Bildirimleri', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 14)),
                        Text('Yeni push yapıldığında bildirim al', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                      ],
                    ),
                  ],
                ),
                Switch(
                  value: _notificationsEnabled,
                  activeColor: const Color(0xFF6366F1),
                  onChanged: (val) {
                    setState(() => _notificationsEnabled = val);
                  },
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Kaydet Butonu
          ElevatedButton(
            onPressed: _isSaving ? null : _saveProfile,
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF6366F1),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              elevation: 2,
            ),
            child: _isSaving
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Text('Değişiklikleri Kaydet', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
          ),
          const SizedBox(height: 16),

          // Çıkış Yap Butonu
          OutlinedButton.icon(
            onPressed: () async {
              await ApiService.clearToken();
              if (mounted) {
                Navigator.pushReplacementNamed(context, '/login');
              }
            },
            icon: const Icon(Icons.logout, size: 18, color: Colors.redAccent),
            label: const Text('Hesaptan Çıkış Yap', style: TextStyle(color: Colors.redAccent, fontSize: 14)),
            style: OutlinedButton.styleFrom(
              side: const BorderSide(color: Color(0xFF7F1D1D)),
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 24),

          // Sürüm ve Güncelleme Kartı
          _buildAppVersionCard(),
          const SizedBox(height: 30),
        ],
      ),
    );
  }

  Widget _buildAppVersionCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFF6366F1).withOpacity(0.15),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.info_outline_rounded, color: Color(0xFF818CF8), size: 20),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Uygulama Sürümü', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                    SizedBox(height: 2),
                    Text(
                      'GitSüpar Mobil v${ApiService.currentVersionName} (Build ${ApiService.currentVersionCode})',
                      style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: _checkManualUpdate,
              icon: const Icon(Icons.sync_rounded, size: 16, color: Color(0xFF818CF8)),
              label: const Text('Güncellemeleri Denetle', style: TextStyle(color: Color(0xFF818CF8), fontSize: 13, fontWeight: FontWeight.w600)),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: Color(0xFF6366F1)),
                padding: const EdgeInsets.symmetric(vertical: 11),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _checkManualUpdate() async {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('🔍 Güncellemeler denetleniyor...'),
        duration: Duration(seconds: 1),
      ),
    );

    final updateData = await ApiService.checkUpdate();
    if (!mounted) return;

    if (updateData == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ Güncelleme sunucusuna erişilemedi.'),
          backgroundColor: Colors.amber,
        ),
      );
      return;
    }

    final remoteCode = updateData['version_code'] as int? ?? 1;
    if (remoteCode > ApiService.currentVersionCode) {
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          backgroundColor: const Color(0xFF1E293B),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: [
              const Icon(Icons.system_update_rounded, color: Color(0xFF818CF8), size: 28),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Yeni Güncelleme (${updateData['version'] ?? ''})',
                  style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'GitSüpar için yeni bir sürüm yayınlandı. Yeniliklerden faydalanmak ve kesintisiz kullanmak için lütfen güncelleyin.',
                style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 13, height: 1.4),
              ),
              if (updateData['release_notes'] != null) ...[
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFF0F172A),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFF334155)),
                  ),
                  child: Text(
                    updateData['release_notes'].toString(),
                    style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                  ),
                ),
              ],
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Daha Sonra', style: TextStyle(color: Color(0xFF94A3B8))),
            ),
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6366F1),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              icon: const Icon(Icons.download_rounded, size: 18),
              label: const Text('Şimdi Güncelle'),
              onPressed: () async {
                Navigator.pop(ctx);
                final url = updateData['download_url']?.toString();
                if (url != null) {
                  final uri = Uri.parse(url);
                  if (await canLaunchUrl(uri)) {
                    await launchUrl(uri, mode: LaunchMode.externalApplication);
                  }
                }
              },
            ),
          ],
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ GitSüpar en güncel sürümünde! (v${ApiService.currentVersionName})'),
          backgroundColor: Color(0xFF10B981),
        ),
      );
    }
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    bool obscureText = false,
    TextInputType keyboardType = TextInputType.text,
    String? helperText,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(color: Color(0xFFCBD5E1), fontSize: 12, fontWeight: FontWeight.w500),
        ),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          obscureText: obscureText,
          keyboardType: keyboardType,
          style: const TextStyle(color: Colors.white, fontSize: 14),
          decoration: InputDecoration(
            prefixIcon: Icon(icon, color: const Color(0xFF64748B), size: 20),
            filled: true,
            fillColor: const Color(0xFF1E293B),
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFF334155)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFF334155)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFF6366F1), width: 1.5),
            ),
          ),
        ),
        if (helperText != null) ...[
          const SizedBox(height: 4),
          Text(helperText, style: const TextStyle(color: Color(0xFF64748B), fontSize: 11)),
        ],
      ],
    );
  }
}
