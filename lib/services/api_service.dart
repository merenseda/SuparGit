import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'https://git.susluparantez.com.tr/api';
  static const String currentVersionName = '1.0.2';
  static const int currentVersionCode = 3;

  // Güncelleme Kontrolü
  static Future<Map<String, dynamic>?> checkUpdate() async {
    try {
      final res = await http.get(
        Uri.parse('$baseUrl/app-version?platform=android'),
        headers: {'Accept': 'application/json'},
      );
      if (res.statusCode == 200) {
        return jsonDecode(res.body);
      }
    } catch (e) {
      // offline or network error
    }
    return null;
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  static Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }

  static Future<Map<String, String>> _headers() async {
    final token = await getToken();
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // Giriş Yap
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    return jsonDecode(res.body);
  }

  // Push Geçmişi (Feed)
  static Future<List<dynamic>> getLogs() async {
    final res = await http.get(Uri.parse('$baseUrl/logs'), headers: await _headers());
    if (res.statusCode == 200) {
      final data = jsonDecode(res.body);
      return data['logs']['data'] ?? [];
    }
    return [];
  }

  // Kullanıcı Profili
  static Future<Map<String, dynamic>?> getProfile() async {
    final res = await http.get(Uri.parse('$baseUrl/profile'), headers: await _headers());
    if (res.statusCode == 200) {
      return jsonDecode(res.body);
    }
    return null;
  }

  // Profil Güncelle
  static Future<Map<String, dynamic>> updateProfile({
    String? name,
    String? email,
    String? password,
    bool? notificationsEnabled,
    String? githubToken,
  }) async {
    final Map<String, dynamic> body = {};
    if (name != null && name.trim().isNotEmpty) body['name'] = name.trim();
    if (email != null && email.trim().isNotEmpty) body['email'] = email.trim();
    if (password != null && password.trim().isNotEmpty) body['password'] = password.trim();
    if (notificationsEnabled != null) body['notifications_enabled'] = notificationsEnabled;
    if (githubToken != null && githubToken.trim().isNotEmpty) body['github_token'] = githubToken.trim();

    final res = await http.post(
      Uri.parse('$baseUrl/profile'),
      headers: await _headers(),
      body: jsonEncode(body),
    );
    try {
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Sunucu yanıtı anlaşılamadı.'};
    }
  }

  // FCM Push Token Kaydı
  static Future<void> registerFcmToken(String fcmToken) async {
    await http.post(
      Uri.parse('$baseUrl/fcm-token'),
      headers: await _headers(),
      body: jsonEncode({'fcm_token': fcmToken}),
    );
  }
}
