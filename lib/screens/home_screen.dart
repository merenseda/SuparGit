import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<dynamic> logs = [];
  Map<String, dynamic>? user;
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() => isLoading = true);
    final fetchedUser = await ApiService.getProfile();
    final fetchedLogs = await ApiService.getLogs();
    setState(() {
      user = fetchedUser;
      logs = fetchedLogs;
      isLoading = false;
    });
  }

  Future<void> _openDiff(String? diffUrl) async {
    if (diffUrl == null) return;
    final uri = Uri.parse(diffUrl);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        title: const Text('🚀 GitPush Ekip Akışı', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: const Color(0xFF1E293B),
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchData,
          ),
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.redAccent),
            onPressed: () async {
              await ApiService.clearToken();
              if (mounted) Navigator.pushReplacementNamed(context, '/login');
            },
          ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6366F1)))
          : RefreshIndicator(
              onRefresh: _fetchData,
              color: const Color(0xFF6366F1),
              child: logs.isEmpty
                  ? const Center(
                      child: Text(
                        'Henüz hiç push kaydı yok.',
                        style: TextStyle(color: Colors.grey),
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(12),
                      itemCount: logs.length,
                      itemBuilder: (context, index) {
                        final log = logs[index];
                        final userName = log['user']?['name'] ?? 'Bilinmeyen Üye';
                        final repoName = log['repo_full_name'] ?? '';
                        final branch = log['branch'] ?? 'main';
                        final filename = log['original_filename'] ?? '';
                        final commitMsg = log['commit_message'] ?? '';
                        final diffUrl = log['diff_url'];

                        return Card(
                          color: const Color(0xFF1E293B),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          margin: const EdgeInsets.only(bottom: 12),
                          elevation: 2,
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    const CircleAvatar(
                                      radius: 16,
                                      backgroundColor: Color(0xFF6366F1),
                                      child: Icon(Icons.person, size: 18, color: Colors.white),
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            userName,
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontWeight: FontWeight.bold,
                                              fontSize: 14,
                                            ),
                                          ),
                                          Text(
                                            repoName,
                                            style: TextStyle(color: Colors.indigo.shade200, fontSize: 12),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: Colors.black26,
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        '🌿 $branch',
                                        style: const TextStyle(color: Colors.grey, fontSize: 11),
                                      ),
                                    ),
                                  ],
                                ),
                                const Divider(color: Color(0xFF334155), height: 22),
                                Row(
                                  children: [
                                    const Icon(Icons.insert_drive_file, size: 16, color: Colors.amber),
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: Text(
                                        filename,
                                        style: const TextStyle(color: Colors.white, fontSize: 13, fontFamily: 'monospace'),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.all(10),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF0F172A),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Text(
                                    commitMsg,
                                    style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                                  ),
                                ),
                                if (diffUrl != null) ...[
                                  const SizedBox(height: 10),
                                  Align(
                                    alignment: Alignment.centerRight,
                                    child: TextButton.icon(
                                      onPressed: () => _openDiff(diffUrl),
                                      icon: const Icon(Icons.visibility, size: 14, color: Color(0xFF818CF8)),
                                      label: const Text(
                                        "Değişiklikleri (Diff) Gör",
                                        style: TextStyle(color: Color(0xFF818CF8), fontSize: 12),
                                      ),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}
