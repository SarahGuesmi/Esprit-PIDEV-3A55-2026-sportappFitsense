# Débogage Face ID - Registration Failed

Si tu obtiens "Registration failed", voici comment déboguer :

## 1. Vérifier les logs du serveur

Dans le terminal où tourne ton serveur Symfony (`composer server:network`), tu devrais voir des messages d'erreur détaillés comme :
```
Passkey registration error: invalid origin
RP ID used: unstigmatic-lossy-keith.ngrok-free.dev
Request host: unstigmatic-lossy-keith.ngrok-free.dev
Request scheme: https
Request origin: https://unstigmatic-lossy-keith.ngrok-free.dev
```

## 2. Vérifier la console du navigateur sur le téléphone

Sur ton téléphone, quand tu vois "Registration failed", ouvre la console du navigateur (si possible) ou regarde les logs dans Safari :
- Sur iPhone : Settings → Safari → Advanced → Web Inspector (active-le)
- Connecte ton iPhone à ton Mac et ouvre Safari → Develop → [Ton iPhone] → [La page]

Tu verras l'erreur exacte dans la console.

## 3. Erreurs courantes et solutions

### "invalid origin"
**Cause** : L'origine dans le clientDataJSON ne correspond pas au rpId.
**Solution** : Vérifie que :
- Tu accèdes à l'app via l'URL ngrok HTTPS (pas http://192.168.1.10)
- Le `PASSKEY_QR_HOST` dans `.env` correspond exactement à l'URL ngrok (sans espace)
- Le serveur Symfony utilise bien le bon rpId (vérifie les logs)

### "invalid challenge"
**Cause** : Le challenge utilisé pour créer la credential ne correspond pas à celui stocké en session.
**Solution** : 
- Assure-toi que la session PHP fonctionne correctement
- Vérifie que tu ne fais pas plusieurs tentatives avec des sessions différentes

### "invalid certificate signature" ou "invalid root certificate"
**Cause** : Le format d'attestation n'est pas reconnu ou le certificat n'est pas valide.
**Solution** : 
- Vérifie que les formats supportés incluent 'apple' (pour Face ID iPhone)
- Le code actuel supporte déjà 'apple', donc ça devrait fonctionner

## 4. Test rapide

1. **Ouvre l'app sur ton PC via ngrok** : `https://unstigmatic-lossy-keith.ngrok-free.dev/sign-in`
2. **Ouvre la console du navigateur** (F12 → Console)
3. **Clique sur "Login with Face ID"** et scanne le QR
4. **Sur le téléphone**, essaie de t'inscrire avec Face ID
5. **Regarde les logs** dans :
   - La console du navigateur PC (pour voir les requêtes)
   - Le terminal du serveur Symfony (pour voir les erreurs serveur)
   - La console Safari sur iPhone (si activée)

## 5. Vérifications importantes

- ✅ Le serveur Symfony tourne sur le port 8000
- ✅ ngrok forward bien vers `http://localhost:8000`
- ✅ Tu accèdes à l'app via l'URL ngrok HTTPS (pas HTTP)
- ✅ Le `PASSKEY_QR_HOST` dans `.env` correspond exactement à l'URL ngrok
- ✅ Pas d'espace avant ou après l'URL dans `.env`
- ✅ Le téléphone et le PC sont sur le même réseau (ou ngrok fonctionne)

## 6. Si ça ne fonctionne toujours pas

Envoie-moi :
1. Le message d'erreur exact affiché sur le téléphone
2. Les logs du serveur Symfony (terminal où tourne `composer server:network`)
3. L'URL ngrok que tu utilises
4. Le contenu de ton `.env` (masque les secrets si nécessaire)
