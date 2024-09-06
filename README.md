# What should I be doing right now

How to run:
1. install XAMPP
2. install PHP Composer
3. run `composer require google/apiclient:^2.0`
4. add `client_secret.json`:
```
{
  "web": {
    "client_id": "YOUR_CLIENT_ID",
    "project_id": "YOUR_PROJECT_ID",
    "auth_uri": "https://accounts.google.com/o/oauth2/auth",
    "token_uri": "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
    "client_secret": "YOUR_CLIENT_SECRET",
    "redirect_uris": [
      "https://your-redirect-uri.com/oauth2callback.php"
    ]
  }
}
```
