# Alternative : Cloudflare Tunnel (sans compte requis)

Si tu ne veux pas créer de compte ngrok, tu peux utiliser **Cloudflare Tunnel** (anciennement cloudflared) qui fonctionne **sans compte** pour un usage basique.

## Étapes

### 1. Installer cloudflared
- Télécharge depuis [github.com/cloudflare/cloudflared/releases](https://github.com/cloudflare/cloudflared/releases)
- Choisis `cloudflared-windows-amd64.exe` pour Windows
- Renomme le fichier en `cloudflared.exe`
- Place-le dans un dossier accessible (ex: `C:\cloudflared\`) ou ajoute-le au PATH

### 2. Lancer ton app Symfony
```bash
composer server:network
```

### 3. Dans un **nouveau terminal**, lancer cloudflared
```bash
cloudflared tunnel --url http://localhost:8000
```

Tu verras quelque chose comme :
```
+--------------------------------------------------------------------------------------------+
|  Your quick Tunnel has been created! Visit it at (it may take some time to be reachable): |
|  https://xxxx-xx-xx-xx-xx.trycloudflare.com                                               |
+--------------------------------------------------------------------------------------------+
```

### 4. Copier l'URL HTTPS dans `.env`
Ouvre `.env` et remplace :
```env
PASSKEY_QR_HOST=192.168.1.10
```

Par :
```env
PASSKEY_QR_HOST=https://xxxx-xx-xx-xx-xx.trycloudflare.com
```
(Remplace par ton URL cloudflare réelle)

### 5. Redémarrer ton serveur Symfony
Arrête et relance `composer server:network` pour que les changements `.env` soient pris en compte.

### 6. Ouvrir l'app via Cloudflare sur ton PC
Sur ton PC, ouvre : `https://xxxx-xx-xx-xx-xx.trycloudflare.com/sign-in`

### 7. Tester Face ID
1. Clique sur **"Login with Face ID"**
2. Le QR code contiendra maintenant l'URL HTTPS Cloudflare
3. Scanne avec ton téléphone
4. La page s'ouvre en HTTPS → Face ID fonctionne ! ✅

---

## Avantages vs ngrok
- ✅ **Pas besoin de compte** (pour usage basique)
- ✅ **Gratuit**
- ⚠️ L'URL change à chaque redémarrage (comme ngrok gratuit)

## Note
- Si tu utilises cloudflared, assure-toi que le tunnel reste actif pendant tes tests.
- L'URL change à chaque fois que tu relances cloudflared, donc mets à jour `.env` si nécessaire.
