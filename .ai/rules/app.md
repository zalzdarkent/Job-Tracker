---
paths:
  - 'app/**'
---

# App

## Laravel 13: dates are CarbonImmutable
In this Laravel 13 app, `now()` and datetime casts return `Carbon\CarbonImmutable`, NOT `Illuminate\Support\Carbon`. Never check `instanceof Illuminate\Support\Carbon` on cast date attributes (it is false) — check `instanceof Carbon\CarbonInterface` instead. This caused Gmail token refresh to wrongly trigger in GoogleOAuthService.
