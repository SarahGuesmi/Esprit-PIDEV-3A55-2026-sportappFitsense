# Guide de Configuration MySQL - Correction des 3 Erreurs

## 🔴 Erreur 1: Timezone Mismatch (CRITIQUE)

### Problème
MySQL timezone est "+01:00" mais PHP timezone est "Europe/Paris". Cela cause:
- DateTime sauvegardés avec mauvais timezone
- Requêtes NOW(), CURDATE() retournent des heures différentes
- Comparaisons de dates échouent
- Timestamps incorrects dans les rapports

### ✅ Solution Appliquée

**Fichier modifié**: `config/packages/doctrine.yaml`

```yaml
doctrine:
    dbal:
        options:
            1002: "SET time_zone = '+01:00'"
```

Cette configuration force MySQL à utiliser le même timezone que PHP à chaque connexion.

### Vérification

```bash
# Vider le cache
php bin/console cache:clear

# Redémarrer le serveur
symfony server:stop
symfony server:start
```

Puis vérifier dans le profiler Doctrine Doctor que l'erreur a disparu.

---

## 🟠 Erreur 2: MySQL Timezone Tables Not Loaded (WARNING)

### Problème
Les tables de timezone MySQL (mysql.time_zone_name) sont vides. Cela empêche:
- Utilisation de CONVERT_TZ() avec noms de timezone
- Conversions de timezone flexibles
- Seuls les offsets (+00:00) fonctionnent

### ✅ Solutions (Choisir selon votre installation)

#### Option A: XAMPP/WAMP (Recommandé)
Les tables sont généralement déjà chargées. Vérifiez:

```sql
-- Exécutez dans phpMyAdmin ou MySQL Workbench
SELECT COUNT(*) FROM mysql.time_zone_name;
```

Si le résultat est > 0, les tables sont déjà chargées ✅

#### Option B: MySQL Standalone sur Windows

1. **Télécharger les données de timezone**
   - Allez sur: https://dev.mysql.com/downloads/timezones.html
   - Téléchargez le fichier SQL pour votre version MySQL
   - Exemple: `timezone_2024a_posix.sql`

2. **Importer dans MySQL**
   ```bash
   # Via ligne de commande
   mysql -u root -p mysql < timezone_2024a_posix.sql
   
   # OU via phpMyAdmin
   # 1. Sélectionnez la base "mysql"
   # 2. Cliquez sur "Importer"
   # 3. Choisissez le fichier SQL téléchargé
   # 4. Cliquez sur "Exécuter"
   ```

3. **Vérifier l'import**
   ```sql
   SELECT COUNT(*) FROM mysql.time_zone_name;
   -- Devrait retourner ~600 lignes
   ```

#### Option C: Docker/Compose

Si vous utilisez Docker plus tard:
```bash
docker-compose exec db mysql_tzinfo_to_sql /usr/share/zoneinfo | docker-compose exec -T db mysql -u root -p mysql
```

### Impact si Non Corrigé
⚠️ Non critique - Vous pouvez continuer à utiliser des offsets (+01:00) au lieu de noms (Europe/Paris)

---

## 🔵 Erreur 3: Binary Logging Enabled in Development (INFO)

### Problème
Le binary logging (binlog) est activé. Les binlogs:
- Sont utilisés pour la réplication et la récupération
- Gaspillent de l'espace disque en développement
- Ne sont pas nécessaires en dev

### ✅ Solution

#### Étape 1: Localiser le fichier de configuration MySQL

**Pour XAMPP:**
```
C:\xampp\mysql\bin\my.ini
```

**Pour WAMP:**
```
C:\wamp64\bin\mysql\mysql[version]\my.ini
```

**Pour MySQL Standalone:**
```
C:\ProgramData\MySQL\MySQL Server [version]\my.ini
```

#### Étape 2: Modifier la configuration

1. Ouvrez le fichier `my.ini` avec un éditeur de texte (en tant qu'administrateur)

2. Trouvez la section `[mysqld]`

3. Ajoutez cette ligne:
   ```ini
   [mysqld]
   skip-log-bin
   ```

4. OU si vous voulez garder binlog mais limiter la taille:
   ```ini
   [mysqld]
   binlog_expire_logs_seconds = 86400  # 1 jour
   max_binlog_size = 100M
   ```

#### Étape 3: Redémarrer MySQL

**Pour XAMPP:**
- Ouvrez le panneau de contrôle XAMPP
- Cliquez sur "Stop" pour MySQL
- Cliquez sur "Start" pour MySQL

**Pour WAMP:**
- Clic gauche sur l'icône WAMP
- MySQL → Service → Restart Service

**Pour MySQL Standalone:**
```bash
# Via Services Windows
services.msc
# Trouvez "MySQL", clic droit → Redémarrer
```

#### Étape 4: Vérifier

```sql
-- Exécutez dans MySQL
SHOW VARIABLES LIKE 'log_bin';
```

Résultat attendu: `OFF` ✅

### Impact si Non Corrigé
ℹ️ Non critique - Juste un gaspillage d'espace disque

---

## 📊 Résumé des Corrections

| Erreur | Niveau | Statut | Action Requise |
|--------|--------|--------|----------------|
| Timezone Mismatch | 🔴 Critical | ✅ Corrigé | Redémarrer serveur |
| Timezone Tables | 🟠 Warning | ⚠️ Manuel | Importer données timezone |
| Binary Logging | 🔵 Info | ⚠️ Manuel | Modifier my.ini |

## 🔄 Commandes de Vérification

```bash
# 1. Vider le cache Symfony
php bin/console cache:clear

# 2. Redémarrer le serveur
symfony server:stop
symfony server:start

# 3. Vérifier dans le navigateur
# Ouvrir l'application → Profiler → Doctrine Doctor
```

## ✅ Checklist Finale

- [x] Timezone synchronisé dans doctrine.yaml
- [ ] Tables de timezone MySQL chargées (optionnel)
- [ ] Binary logging désactivé (optionnel)
- [x] Cache vidé
- [ ] Serveur redémarré
- [ ] Doctrine Doctor vérifié

## 📝 Notes Importantes

1. **Timezone Mismatch** (🔴): DOIT être corrigé - Déjà fait ✅
2. **Timezone Tables** (🟠): Optionnel - Améliore la flexibilité
3. **Binary Logging** (🔵): Optionnel - Économise de l'espace disque

## 🎯 Résultat Attendu

Après avoir appliqué toutes les corrections:
- ✅ 0 erreur critique dans Database Config
- ✅ 0 warning (si timezone tables chargées)
- ✅ 0 info (si binlog désactivé)

---

**Date**: 4 Mars 2026
**Statut**: Configuration MySQL Optimisée
