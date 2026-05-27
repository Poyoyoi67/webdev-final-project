# Firebase service account (local only)

1. Firebase Console → Project settings → Service accounts
2. Generate new private key → save as `service-account.json` in this folder
3. Do **not** commit this file (it is gitignored)

On Railway, use the `FIREBASE_SERVICE_ACCOUNT_JSON` variable instead.
