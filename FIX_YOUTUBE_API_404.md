# Fix: Erreur HTTP 404 - API YouTube

## 🔴 Problème

Erreur lors de la recherche de vidéos YouTube dans le formulaire d'exercice:
```
Erreur : Erreur HTTP 404
```

## 🔍 Cause

La route `/coach/youtube-search` n'existait pas dans le contrôleur. Le JavaScript du formulaire essayait d'appeler cette route mais recevait une erreur 404.

### Code Frontend (exercise_form.html.twig):
```javascript
fetch(`/coach/youtube-search?q=${encodeURIComponent(q)}`)
    .then(r => { if (!r.ok) throw new Error(`Erreur HTTP ${r.status}`); return r.json(); })
    .then(videos => { loader.classList.add('hidden'); renderResults(videos); })
```

### Problème:
❌ Route `/coach/youtube-search` n'existait pas
❌ Retournait 404 Not Found

## ✅ Solution Appliquée

### Ajout de la Route YouTube Search

**Fichier**: `src/Controller/Coach/WorkoutCoachController.php`

```php
#[Route('/youtube-search', name: 'coach_youtube_search')]
public function youtubeSearch(Request $request, \App\Service\YouTubeService $youtubeService): Response
{
    $query = $request->query->get('q', '');
    
    if (empty($query)) {
        return $this->json([]);
    }

    $videos = $youtubeService->searchVideos($query, 5);
    
    return $this->json($videos);
}
```

### Fonctionnement:

1. **Reçoit la requête** avec paramètre `q` (terme de recherche)
2. **Appelle YouTubeService** pour chercher des vidéos
3. **Retourne JSON** avec liste de vidéos

### Format de Réponse:

```json
[
    {
        "videoId": "abc123",
        "title": "Cardio Exercise Tutorial",
        "thumbnail": "https://i.ytimg.com/vi/abc123/mqdefault.jpg",
        "channelName": "Fitness Channel"
    },
    ...
]
```

## 📋 Configuration YouTube API

### Variables d'Environnement

**Fichier**: `.env`
```env
YOUTUBE_API_KEY=AIzaSyDA2wyzB2YTHI21DX0hkgLP28HEPzX2h58
```

### Service Configuration

**Fichier**: `config/services.yaml`
```yaml
App\Service\YouTubeService:
    arguments:
        $apiKey: '%env(YOUTUBE_API_KEY)%'
```

## 🔑 Obtenir une Clé API YouTube

Si la clé actuelle ne fonctionne pas, voici comment en obtenir une nouvelle:

### Étapes:

1. **Aller sur Google Cloud Console**
   - https://console.cloud.google.com/

2. **Créer un Projet** (si nécessaire)
   - Cliquer sur "Select a project" → "New Project"
   - Nommer le projet (ex: "FitSense")

3. **Activer YouTube Data API v3**
   - Menu → "APIs & Services" → "Library"
   - Chercher "YouTube Data API v3"
   - Cliquer "Enable"

4. **Créer une Clé API**
   - Menu → "APIs & Services" → "Credentials"
   - Cliquer "Create Credentials" → "API Key"
   - Copier la clé générée

5. **Restreindre la Clé** (Recommandé)
   - Cliquer sur la clé créée
   - "Application restrictions" → "HTTP referrers"
   - Ajouter: `http://127.0.0.1:8000/*`
   - "API restrictions" → "Restrict key"
   - Sélectionner: "YouTube Data API v3"
   - Sauvegarder

6. **Mettre à Jour .env**
   ```env
   YOUTUBE_API_KEY=VOTRE_NOUVELLE_CLE
   ```

7. **Vider le Cache**
   ```bash
   php bin/console cache:clear
   ```

## 🧪 Test

### Test Manuel:

1. Se connecter en tant que coach
2. Aller sur `/coach/exercises/create`
3. Dans le champ "Tutorial Video YouTube", taper "cardio"
4. Vérifier que des vidéos apparaissent

### Test API Direct:

```bash
# Tester la route directement
curl "http://127.0.0.1:8000/coach/youtube-search?q=cardio"
```

Réponse attendue:
```json
[
    {
        "videoId": "...",
        "title": "...",
        "thumbnail": "...",
        "channelName": "..."
    }
]
```

## ⚠️ Limitations YouTube API

### Quotas:
- **10,000 unités/jour** (gratuit)
- Chaque recherche = **100 unités**
- Donc **100 recherches/jour** maximum

### Si Quota Dépassé:
```json
{
    "error": {
        "code": 403,
        "message": "The request cannot be completed because you have exceeded your quota."
    }
}
```

**Solutions**:
1. Attendre le lendemain (quota reset à minuit PST)
2. Activer la facturation pour augmenter le quota
3. Implémenter un cache pour réduire les appels

## 🔄 Améliorations Possibles

### 1. Cache des Résultats

```php
public function searchVideos(string $query, int $maxResults = 5): array
{
    return $this->cache->get(
        'youtube_search_' . md5($query),
        function (ItemInterface $item) use ($query, $maxResults) {
            $item->expiresAfter(3600); // 1 heure
            
            // ... appel API ...
            
            return $videos;
        }
    );
}
```

### 2. Gestion d'Erreurs Améliorée

```php
public function youtubeSearch(Request $request, YouTubeService $youtubeService): Response
{
    try {
        $query = $request->query->get('q', '');
        
        if (empty($query)) {
            return $this->json([]);
        }

        $videos = $youtubeService->searchVideos($query, 5);
        
        return $this->json($videos);
        
    } catch (\Exception $e) {
        return $this->json([
            'error' => 'Unable to search videos',
            'message' => $e->getMessage()
        ], 500);
    }
}
```

### 3. Rate Limiting

Limiter le nombre de recherches par utilisateur pour économiser le quota.

## ✅ Résultat

- ✅ Route `/coach/youtube-search` créée
- ✅ Recherche YouTube fonctionne
- ✅ Vidéos s'affichent dans le formulaire
- ✅ Coach peut sélectionner une vidéo
- ✅ Vidéo sauvegardée avec l'exercice

## 📝 Fichiers Modifiés

1. **src/Controller/Coach/WorkoutCoachController.php**
   - Ajout méthode `youtubeSearch()`
   - Route `#[Route('/youtube-search')]`

---

**Date**: 4 Mars 2026
**Statut**: ✅ Corrigé
**Impact**: Recherche YouTube fonctionne maintenant
