# Railway infrastructure

The production environment is defined in `railway.ts` with Railway's
project-level Infrastructure as Code.

```bash
npm install --prefix .railway
railway login
railway link
railway config plan
railway config apply
```

Review every plan before applying it. Railway requires explicit
confirmation for destructive changes.
