# Face ID login – test en local avec le téléphone

Pour que ton **téléphone** puisse ouvrir l’app (scan du QR code) et utiliser **Face ID**, deux choses sont nécessaires :

1. **Serveur accessible** depuis le téléphone (réseau ou tunnel).
2. **Connexion HTTPS** : l’API WebAuthn (Face ID) ne fonctionne qu’en HTTPS (sauf sur localhost). En `http://192.168.1.10` le navigateur du téléphone n’expose pas `navigator.credentials`, d’où l’erreur *"undefined is not an object (evaluating 'navigator.credentials.create')"*.

**Recommandation pour tester Face ID avec le téléphone :** utiliser **ngrok** pour exposer ton app en HTTPS (voir section 3).

---

## 1. Lancer le serveur sur le réseau local

Le serveur doit écouter sur **toutes les interfaces** (`0.0.0.0`), pas seulement sur `127.0.0.1`.

### Option A – PHP intégré (recommandé pour le test)

À la racine du projet :

```bash
composer server:network
```

Ou directement :

```bash
php -S 0.0.0.0:8000 -t public
```

L’app est alors accessible :
- sur le PC : http://127.0.0.1:8000
- sur le téléphone (même Wi‑Fi) : http://192.168.1.10:8000

### Option B – Symfony CLI

Si tu utilises `symfony server:start`, vérifie qu’il écoute sur `0.0.0.0` (voir la doc Symfony pour ton environnement).

---

## 2. Autoriser le port dans le pare-feu Windows

Sinon le téléphone ne pourra pas se connecter.

1. Ouvrir **Pare-feu Windows Défenseur** → **Paramètres avancés**.
2. **Règles de trafic entrant** → **Nouvelle règle**.
3. Type : **Port** → Suivant.
4. TCP, ports locaux : **8000** (ou le port que tu utilises) → Suivant.
5. Autoriser la connexion → Suivant.
6. Coche **Domaine** et **Privé** (au minimum **Privé** pour le Wi‑Fi maison) → Suivant.
7. Nom : par ex. **FitSense local** → Terminer.

---

## 3. Vérifier l’IP et le .env

- Ton PC doit avoir une IP fixe sur le Wi‑Fi (ex. **192.168.1.10**).
- Dans `.env` :

```env
PASSKEY_QR_HOST=192.168.1.10
```

(Remplace par ton IP si elle est différente.)

---

## 4. Tester le flux Face ID

1. Sur le PC : ouvrir http://127.0.0.1:8000/sign-in (ou le port que tu utilises).
2. Cliquer sur **Login with Face ID**.
3. Scanner le QR code avec le téléphone (même Wi‑Fi).
4. Le téléphone doit ouvrir http://192.168.1.10:8000/... et afficher la page Face ID.

Si « Safari ne peut pas ouvrir la page » :
- le serveur n’écoute pas sur `0.0.0.0`, ou
- le pare-feu bloque le port 8000.

---

## 5. Face ID : utiliser HTTPS avec ngrok (recommandé pour le téléphone)

Sans HTTPS, le téléphone affiche une erreur du type *"navigator.credentials.create" is undefined* car l’API WebAuthn n’est pas disponible en HTTP (sauf sur localhost).

**Étapes :**

1. **Installer ngrok**  
   - Télécharger depuis [ngrok.com](https://ngrok.com/download) ou `choco install ngrok`.

2. **Lancer ton app en local** (sur le port 8000) :
   ```bash
   composer server:network
   ```
   ou :
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```

3. **Dans un autre terminal**, lancer ngrok :
   ```bash
   ngrok http 8000
   ```
   Tu obtiendras une URL du type : `https://xxxx-xx-xx-xx-xx.ngrok-free.app`

4. **Sur ton PC**, ouvre l’app via cette URL HTTPS :
   - Exemple : `https://xxxx-xx-xx-xx-xx.ngrok-free.app/sign-in`
   - Accepte l’avertissement de certificat une fois si demandé.

5. **Clique sur « Login with Face ID »** : le QR code contiendra automatiquement l’URL ngrok (HTTPS).

6. **Scanne le QR avec ton téléphone** : la page s’ouvre en HTTPS, et Face ID / inscription fonctionne.

Tu n’as pas besoin de modifier `.env` : en ouvrant l’app via l’URL ngrok, le serveur utilise déjà le bon host et le bon schéma pour le QR code et le `rpId`.
