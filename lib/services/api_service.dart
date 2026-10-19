import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'https://git.susluparantez.com.tr/api';

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

  // FCM Push Token Kaydı
  static Future<void> registerFcmToken(String fcmToken) async {
    await http.post(
      Uri.parse('$baseUrl/fcm-token'),
      headers: await _headers(),
      body: jsonEncode({'fcm_token': fcmToken}),
    );
  }
}
