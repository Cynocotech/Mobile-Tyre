# Flutter App Integration Guide

This document outlines how to build a native Flutter app on top of the existing web system. The backend APIs and data structures can be reused—Flutter will call the same PHP endpoints.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Existing Web Stack                       │
│  PHP APIs · database/ · Stripe · dynamic.json · jobs.json   │
└─────────────────────────────────────────────────────────────┘
                              ▲
                              │ HTTP/JSON
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      Flutter App                             │
│  Driver login · Jobs list · Location · Photo upload · Cash   │
└─────────────────────────────────────────────────────────────┘
```

## API Endpoints to Reuse

All driver APIs use session cookies. For Flutter you have two options:

1. **Cookie-based** – Use a package like `dio` with `CookieManager` to persist cookies across requests.
2. **Token-based** – Add a simple API token to the driver system (e.g. `api_token` in drivers.json, return it on login, send as `Authorization: Bearer TOKEN`).

### Driver APIs (mirror the web)

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/driver/api/register.php` | POST | Create driver + Stripe Connect (returns onboarding URL) |
| `/driver/login.php` | POST (form) | Login; returns session cookie |
| `/driver/api/jobs.php` | GET | List jobs assigned to driver |
| `/driver/api/jobs.php` | POST | `action=location` \| `proof` \| `cash_paid` |
| `/driver/api/location.php` | POST | Update driver’s general location |

### Example: Login (Flutter)

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<Map<String, dynamic>> login(String email, String password) async {
  final uri = Uri.parse('https://your-domain.com/driver/login.php');
  final resp = await http.post(
    uri,
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'email=${Uri.encodeComponent(email)}&password=${Uri.encodeComponent(password)}',
  );
  // Check Set-Cookie for session; or add token-based auth
  return {'ok': resp.statusCode == 200};
}
```

### Example: Get Jobs

```dart
Future<List<dynamic>> getJobs() async {
  final uri = Uri.parse('https://your-domain.com/driver/api/jobs.php');
  final resp = await http.get(uri); // with cookie/header
  final data = jsonDecode(resp.body);
  return data['jobs'] ?? [];
}
```

### Example: Upload Proof Photo

```dart
Future<bool> uploadProof(String ref, File photo) async {
  final uri = Uri.parse('https://your-domain.com/driver/api/jobs.php');
  var req = http.MultipartRequest('POST', uri);
  req.fields['action'] = 'proof';
  req.fields['reference'] = ref;
  req.files.add(await http.MultipartFile.fromPath('photo', photo.path));
  final resp = await req.send();
  return resp.statusCode == 200;
}
```

## Flutter Project Structure

```
mobile_tyre_driver/
├── lib/
│   ├── main.dart
│   ├── api/
│   │   ├── auth_service.dart    # login, session
│   │   ├── jobs_service.dart    # get jobs, location, proof, cash
│   │   └── config.dart          # base URL
│   ├── screens/
│   │   ├── login_screen.dart
│   │   ├── dashboard_screen.dart
│   │   ├── job_detail_screen.dart
│   │   └── profile_screen.dart
│   ├── widgets/
│   │   ├── job_card.dart
│   │   └── location_picker.dart
│   └── models/
│       ├── job.dart
│       └── driver.dart
├── pubspec.yaml
└── android/ / ios/
```

## Key Dependencies

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  provider: ^6.0.0        # state management
  shared_preferences: ^2.0.0  # store session/token
  image_picker: ^1.0.0    # proof photo
  geolocator: ^10.0.0     # location
  url_launcher: ^6.0.0    # open Maps, tel:
```

## Screens to Implement

1. **Login** – Email/password or PIN; call `login.php`, store session.
2. **Dashboard** – List jobs from `api/jobs.php`; pull-to-refresh.
3. **Job detail** – Customer, vehicle, location, Maps link, “Update location”, “Upload proof”, “Mark cash paid”.
4. **Profile** – Driver info, Stripe status (read-only or link to Stripe Express Dashboard).
5. **Onboarding** – For new drivers: collect details, call `register.php`, open WebView or `url_launcher` for Stripe Connect URL.

## Push Notifications (optional)

- Use `firebase_messaging` or `flutter_local_notifications`.
- Backend: when admin assigns a job, call FCM (or similar) to send push to the assigned driver’s device.
- Store FCM tokens in `database/drivers.json` (e.g. `fcm_token`) or a separate `push_subscriptions.json`.
- Add `/driver/api/push-subscribe.php` to save the token.

## Stripe Connect in Flutter

- Use the existing flow: driver signs up on web (or in-app WebView), completes Stripe Connect onboarding in a browser/WebView.
- For “Connect account” management, open `https://connect.stripe.com/express/...` via `url_launcher` (Stripe provides Express Dashboard links).

## Platform Notes

- **iOS**: Add location, camera permissions in `Info.plist`.
- **Android**: Add permissions in `AndroidManifest.xml`.
- **HTTPS**: All API calls must use HTTPS in production.

## Summary

1. Reuse existing PHP APIs; add token-based auth if you prefer over cookies.
2. Implement the same flows: login, jobs list, job detail, location, proof upload, cash paid.
3. Use `image_picker` and `geolocator` for native UX.
4. Add push via FCM when backend supports it.
5. Stripe Connect stays web-based (onboarding in browser/WebView).
