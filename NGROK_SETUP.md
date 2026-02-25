# Configuration ngrok pour Face ID (HTTPS local)

## Étapes rapides

### 1. Installer et configurer ngrok

**A. Installer ngrok**
- Télécharge depuis [ngrok.com/download](https://ngrok.com/download)
- Ou avec Chocolatey : `choco install ngrok`

**B. Créer un compte gratuit (OBLIGATOIRE maintenant)**
1. Va sur [dashboard.ngrok.com/signup](https://dashboard.ngrok.com/signup)
2. Crée un compte gratuit (email + mot de passe)

**C. Configurer l'authtoken**
1. Une fois connecté, va sur [dashboard.ngrok.com/get-started/your-authtoken](https://dashboard.ngrok.com/get-started/your-authtoken)
2. Copie ton **authtoken** (une longue chaîne de caractères)
3. Dans PowerShell ou CMD, exécute :
   ```bash
   ngrok config add-authtoken TON_AUTHTOKEN_ICI
   ```
   (Remplace `TON_AUTHTOKEN_ICI` par l'authtoken que tu as copié)

**Vérification** : Tape `ngrok http 8000` - ça devrait fonctionner maintenant !

### 2. Lancer ton app Symfony
```bash
composer server:network
```
Ou :
```bash
php -S 0.0.0.0:8000 -t public
```

### 3. Dans un **nouveau terminal**, lancer ngrok
```bash
ngrok http 8000
```

Tu verras quelque chose comme :
```
Forwarding   https://xxxx-xx-xx-xx-xx.ngrok-free.app -> http://localhost:8000
```

### 4. Copier l'URL HTTPS dans `.env`
Ouvre `.env` et remplace :
```env
PASSKEY_QR_HOST=192.168.1.10
```

Par :
```env
PASSKEY_QR_HOST=https://xxxx-xx-xx-xx-xx.ngrok-free.app
```
(Remplace `xxxx-xx-xx-xx-xx.ngrok-free.app` par ton URL ngrok réelle)

### 5. Redémarrer ton serveur Symfony
Arrête et relance `composer server:network` pour que les changements `.env` soient pris en compte.

### 6. Ouvrir l'app via ngrok sur ton PC
Sur ton PC, ouvre : `https://xxxx-xx-xx-xx-xx.ngrok-free.app/sign-in`
- Accepte l'avertissement de certificat si le navigateur le demande (ngrok utilise un certificat valide).

### 7. Tester Face ID
1. Clique sur **"Login with Face ID"**
2. Le QR code contiendra maintenant l'URL HTTPS ngrok
3. Scanne avec ton téléphone
4. La page s'ouvre en HTTPS → Face ID fonctionne ! ✅

---

## Note importante
- **L'URL ngrok change** à chaque fois que tu relances ngrok (sauf avec un compte payant).
- Si tu relances ngrok, **mets à jour `.env`** avec la nouvelle URL.
- Tu peux aussi utiliser l'URL ngrok directement dans ton navigateur PC pour accéder à l'app.
