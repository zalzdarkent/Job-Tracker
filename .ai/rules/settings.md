---
paths:
  - 'app/Http/Controllers/Settings/**'
---

# Settings

## Manual Google OAuth flow
Google mailbox connection is implemented with raw HTTP calls to Google OAuth and Gmail profile endpoints because Socialite is not installed in this app. Keep the callback at /settings/google/callback and store tokens on GoogleAccount using encrypted casts.
